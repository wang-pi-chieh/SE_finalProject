(function () {
    const DEFAULT_INTERVAL = 30000;
    const DEFAULT_ENDPOINTS = {
        saveUrl: '../api/drafts/save_work_draft.php',
        loadUrl: '../api/drafts/get_work_draft.php',
        deleteUrl: '../api/drafts/delete_work_draft.php'
    };

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
        let el = document.getElementById('server-draft-status-toast');
        if (!el) {
            el = document.createElement('div');
            el.id = 'server-draft-status-toast';
            el.className = 'fixed left-4 bottom-4 z-[10001] rounded-full px-4 py-2 text-xs font-bold shadow-lg transition-opacity bg-slate-900 text-white';
            document.body.appendChild(el);
        }

        el.textContent = text;
        el.classList.toggle('bg-green-700', type === 'success');
        el.classList.toggle('bg-red-700', type === 'error');
        el.classList.toggle('bg-slate-900', type !== 'success' && type !== 'error');
        el.classList.remove('opacity-0');

        clearTimeout(el.__serverDraftTimer);
        el.__serverDraftTimer = setTimeout(() => {
            el.classList.add('opacity-0');
        }, 2500);
    }

    async function readJson(res) {
        const text = await res.text();
        try {
            return JSON.parse(text || '{}');
        } catch {
            throw new Error('伺服器回應格式錯誤');
        }
    }

    function makeBody(options, draft) {
        return {
            actor_username: options.actorUsername,
            draft_type: options.draftType,
            draft_key: options.draftKey,
            context: options.context || {},
            draft
        };
    }

    function makeQuery(options) {
        const params = new URLSearchParams({
            actor_username: options.actorUsername,
            draft_type: options.draftType,
            draft_key: options.draftKey
        });
        Object.entries(options.context || {}).forEach(([key, value]) => {
            if (value !== undefined && value !== null) {
                params.set(key, value);
            }
        });
        return params.toString();
    }

    function register(rawOptions) {
        const options = {
            ...DEFAULT_ENDPOINTS,
            interval: DEFAULT_INTERVAL,
            fields: [],
            ...rawOptions
        };

        if (!options.actorUsername || !options.draftType || !options.draftKey || typeof options.collect !== 'function') {
            return null;
        }

        let dirty = false;
        let stopped = false;
        let loading = false;
        let saving = false;
        let timer = null;

        function markDirty() {
            if (!loading && !stopped) {
                dirty = true;
            }
        }

        async function save(silent = false) {
            if (stopped || saving || !dirty) return;

            const data = options.collect();
            if (typeof options.shouldSave === 'function' && !options.shouldSave(data)) {
                dirty = false;
                return;
            }

            saving = true;
            const draft = {
                data,
                savedAt: new Date().toISOString()
            };

            try {
                const res = await fetch(options.saveUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(makeBody(options, draft)),
                    keepalive: silent
                });
                const result = await readJson(res);
                if (!res.ok || !result.success) {
                    throw new Error(result.message || '網站暫存失敗');
                }
                dirty = false;
                if (!silent) showStatus('已同步網站暫存', 'success');
                if (typeof options.onSave === 'function') options.onSave(result);
            } catch (error) {
                if (!silent) showStatus(`網站暫存失敗：${error.message}`, 'error');
            } finally {
                saving = false;
            }
        }

        async function load() {
            if (stopped) return;
            loading = true;
            try {
                const res = await fetch(`${options.loadUrl}?${makeQuery(options)}`);
                const result = await readJson(res);
                if (!res.ok || !result.success) {
                    throw new Error(result.message || '讀取網站暫存失敗');
                }
                if (!result.draft || !result.draft.data) return;

                const when = formatSavedAt(result.draft.savedAt);
                const shouldRestore = window.confirm(`偵測到網站暫存內容${when ? `（${when}）` : ''}，是否恢復？`);
                if (shouldRestore && typeof options.apply === 'function') {
                    options.apply(result.draft.data);
                    showStatus('已恢復網站暫存內容', 'success');
                }
            } catch (error) {
                showStatus(`讀取網站暫存失敗：${error.message}`, 'error');
            } finally {
                loading = false;
            }
        }

        async function clear() {
            dirty = false;
            try {
                await fetch(options.deleteUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(makeBody(options, { data: {} })),
                    keepalive: true
                });
            } catch {
                // Clearing a draft is best-effort after a manual save or final submit.
            }
        }

        function stop() {
            stopped = true;
            if (timer) clearInterval(timer);
            options.fields.forEach((field) => {
                field.removeEventListener('input', markDirty, true);
                field.removeEventListener('change', markDirty, true);
            });
        }

        options.fields.forEach((field) => {
            if (!field) return;
            field.addEventListener('input', markDirty, true);
            field.addEventListener('change', markDirty, true);
        });

        timer = setInterval(() => save(false), options.interval);
        window.addEventListener('beforeunload', () => save(true));
        load();

        return {
            save,
            clear,
            stop,
            markDirty
        };
    }

    window.ServerDraftAutosave = { register };
})();
