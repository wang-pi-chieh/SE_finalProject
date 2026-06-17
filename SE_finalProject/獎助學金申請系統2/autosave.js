(function () {
    const DEFAULT_INTERVAL = 30000;
    const handles = new Map();

    function getUserKey() {
        try {
            const user = JSON.parse(localStorage.getItem('user') || '{}');
            return user.username || user.real_name || 'guest';
        } catch {
            return 'guest';
        }
    }

    function storageKey(key) {
        return `autosave:${getUserKey()}:${key}`;
    }

    function collectFormData(form) {
        const data = {};
        const files = {};

        form.querySelectorAll('input, textarea, select').forEach((field) => {
            if (!field.name && !field.id) return;
            if (field.type === 'password' || field.type === 'hidden') return;
            if (field.dataset.autosaveIgnore === 'true') return;

            const key = field.name || field.id;

            if (field.type === 'file') {
                if (field.files && field.files.length > 0) {
                    files[key] = Array.from(field.files).map(file => file.name);
                }
                return;
            }

            if (field.type === 'checkbox') {
                data[key] = field.checked;
                return;
            }

            if (field.type === 'radio') {
                if (field.checked) {
                    data[key] = field.value;
                }
                return;
            }

            data[key] = field.value;
        });

        return {
            data,
            files,
            savedAt: new Date().toISOString(),
            url: location.pathname + location.search
        };
    }

    function restoreFormData(form, draft) {
        if (!draft || !draft.data) return;

        form.querySelectorAll('input, textarea, select').forEach((field) => {
            if (!field.name && !field.id) return;
            if (field.type === 'password' || field.type === 'hidden' || field.type === 'file') return;
            if (field.dataset.autosaveIgnore === 'true') return;

            const key = field.name || field.id;
            if (!Object.prototype.hasOwnProperty.call(draft.data, key)) return;

            if (field.type === 'checkbox') {
                field.checked = Boolean(draft.data[key]);
            } else if (field.type === 'radio') {
                field.checked = field.value === draft.data[key];
            } else {
                field.value = draft.data[key] ?? '';
            }

            field.dispatchEvent(new Event('input', { bubbles: true }));
            field.dispatchEvent(new Event('change', { bubbles: true }));
        });
    }

    function formatSavedAt(value) {
        if (!value) return '';
        const date = new Date(value);
        if (Number.isNaN(date.getTime())) return '';
        return date.toLocaleString('zh-TW', {
            month: '2-digit',
            day: '2-digit',
            hour: '2-digit',
            minute: '2-digit'
        });
    }

    function showStatus(text, type) {
        let el = document.getElementById('autosave-status-toast');
        if (!el) {
            el = document.createElement('div');
            el.id = 'autosave-status-toast';
            el.className = 'fixed left-4 bottom-4 z-[10001] rounded-full px-4 py-2 text-xs font-bold shadow-lg transition-opacity bg-slate-900 text-white';
            document.body.appendChild(el);
        }

        el.textContent = text;
        el.classList.toggle('bg-green-700', type === 'success');
        el.classList.toggle('bg-slate-900', type !== 'success');
        el.classList.remove('opacity-0');

        clearTimeout(el.__autosaveTimer);
        el.__autosaveTimer = setTimeout(() => {
            el.classList.add('opacity-0');
        }, 2500);
    }

    function register(formOrSelector, options) {
        const form = typeof formOrSelector === 'string'
            ? document.querySelector(formOrSelector)
            : formOrSelector;
        if (!form || !options || !options.key) return null;

        const key = storageKey(options.key);
        const intervalMs = options.interval || DEFAULT_INTERVAL;

        if (handles.has(key)) {
            handles.get(key).stop();
        }

        let dirty = false;
        let stopped = false;

        function save(silent) {
            if (stopped) return;
            const payload = collectFormData(form);
            localStorage.setItem(key, JSON.stringify(payload));
            dirty = false;
            if (!silent) {
                showStatus('已自動暫存', 'success');
            }
        }

        function clear() {
            localStorage.removeItem(key);
            dirty = false;
        }

        function stop() {
            stopped = true;
            clearInterval(timer);
            form.removeEventListener('input', markDirty, true);
            form.removeEventListener('change', markDirty, true);
        }

        function markDirty(event) {
            if (event.target && event.target.dataset.autosaveIgnore === 'true') return;
            dirty = true;
        }

        const existing = localStorage.getItem(key);
        if (existing) {
            try {
                const draft = JSON.parse(existing);
                const when = formatSavedAt(draft.savedAt);
                const shouldRestore = window.confirm(`偵測到上次未送出的暫存內容${when ? `（${when}）` : ''}，是否恢復？`);
                if (shouldRestore) {
                    restoreFormData(form, draft);
                    if (typeof options.onRestore === 'function') {
                        options.onRestore(draft);
                    }
                    showStatus('已恢復暫存內容', 'success');
                }
            } catch {
                localStorage.removeItem(key);
            }
        }

        form.addEventListener('input', markDirty, true);
        form.addEventListener('change', markDirty, true);

        const timer = setInterval(() => {
            if (dirty) {
                save(false);
            }
        }, intervalMs);

        window.addEventListener('beforeunload', () => {
            if (dirty) {
                save(true);
            }
        });

        const handle = { save, clear, stop, key };
        handles.set(key, handle);
        return handle;
    }

    window.FormAutosave = { register };
})();
