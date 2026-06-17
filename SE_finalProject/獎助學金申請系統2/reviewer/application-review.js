document.addEventListener('DOMContentLoaded', () => {
    // 1. Auth Check
    const user = JSON.parse(localStorage.getItem('user'));
    if (!user) {
        window.location.href = '../login.html';
        return;
    }

    // 2. Get Application ID
    const urlParams = new URLSearchParams(window.location.search);
    const appId = urlParams.get('id');

    if (!appId) {
        alert('無效的申請 ID');
        window.location.href = 'reviewer-dashboard.html';
        return;
    }

    // 3. Load Data
    loadApplicationDetails(appId);

    // 4. Bind Events (Submit / Draft)
    document.getElementById('submit-review-btn').addEventListener('click', () => submitReview(appId, false));
    document.getElementById('save-draft-btn').addEventListener('click', () => submitReview(appId, true));

    async function loadApplicationDetails(id) {
        try {
            document.getElementById('loading-state').classList.remove('hidden');
            document.getElementById('main-content').classList.add('hidden');

            const res = await fetch(`../api/get_application_details.php?id=${id}&reviewer_username=${user.username}`);
            const result = await res.json();

            document.getElementById('loading-state').classList.add('hidden');

            if (result.success) {
                document.getElementById('main-content').classList.remove('hidden');
                renderData(result.data);
            } else {
                alert('載入失敗: ' + result.message);
                window.location.href = 'reviewer-dashboard.html';
            }
        } catch (err) {
            console.error(err);
            alert('系統錯誤');
        }
    }

    function renderData(data) {
        const app = data.application;
        const student = data.student;

        // --- 1. Header & ID ---
        const pageTitle = document.querySelector('h1.text-2xl');
        if (pageTitle) pageTitle.textContent = `${app.scholarship_name || '獎學金申請'} - 申請詳情`;

        // --- 2. Student Profile ---
        setText('student-name', student.name);
        setText('student-id', `學號: ${student.id}`);
        setText('student-dept', student.dept);
        setText('student-grade', `年級: ${student.grade}`);
        setText('student-gpa', `平均成績: ${student.gpa} / 4.0`);
        setText('student-email', student.email);
        setText('student-phone', app.phone);
        setText('student-bank', `匯款帳戶: ${app.bank_account || '未提供'}`);

        // Update Avatar if needed (optional, letter avatar used in table)
        // const avatar = document.getElementById('student-avatar');
        // if (avatar && student.avatar_letter) { ... }

        // --- 3. Application Info ---
        setText('info-semester', app.year_semester); // Pre-formatted from PHP
        setText('info-scholarship', app.scholarship_name);
        setText('info-date', app.application_date);
        setText('info-prev-award', app.prev_award);
        setText('info-recommender', app.referrer); // Pre-formatted

        // --- 4. Family & Financial ---
        setText('family-housing', app.family_housing);
        setText('personal-housing', app.personal_housing);
        setText('student-loan', app.student_loan);
        setText('tuition-waiver', app.tuition_waiver);

        const famDesc = document.getElementById('family-desc');
        if (famDesc) famDesc.innerHTML = app.family_desc; // PHP returns nl2br HTML

        const famMem = document.getElementById('family-members');
        if (famMem) famMem.innerHTML = app.family_members; // PHP returns nl2br HTML

        // --- 5. Biography Text ---
        const bioContent = document.getElementById('biography-content');
        if (bioContent) {
            bioContent.innerHTML = app.biography; // PHP returns nl2br HTML
        }

        // --- 6. Biography FILES ---
        const bioFilesContainer = document.getElementById('biography-files-list');
        if (bioFilesContainer) {
            bioFilesContainer.innerHTML = '';
            if (app.biography_files && app.biography_files.length > 0) {
                bioFilesContainer.classList.remove('hidden');
                bioFilesContainer.innerHTML = app.biography_files.map(file => createFileCardHTML(file)).join('');
            } else {
                bioFilesContainer.classList.add('hidden');
            }
        }

        // --- 7. Supporting Documents FILES ---
        const docsContainer = document.getElementById('documents-list');
        if (docsContainer) {
            docsContainer.innerHTML = '';
            if (data.documents && data.documents.length > 0) {
                docsContainer.classList.remove('hidden');
                docsContainer.innerHTML = data.documents.map(doc => createFileCardHTML(doc)).join('');
            } else {
                docsContainer.innerHTML = '<p class="text-slate-500 text-sm">無上傳檔案</p>';
                docsContainer.classList.remove('hidden');
            }
        }

        // --- 8. Grades Table ---
        if (data.grades && Array.isArray(data.grades)) {
            const tbody = document.getElementById('grades-table-body');
            tbody.innerHTML = '';
            if (data.grades.length === 0) {
                tbody.innerHTML = '<tr><td colspan="5" class="p-4 text-center text-slate-500">無成績資料</td></tr>';
            } else {
                data.grades.forEach(g => {
                    const semesterText = g.semester === '上' ? '1' : (g.semester === '下' ? '2' : g.semester);
                    tbody.innerHTML += `
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/50">
                            <td class="px-4 py-3 font-medium text-slate-900 dark:text-white">${g.academic_year || '-'}</td>
                            <td class="px-4 py-3 text-slate-600 dark:text-slate-300">第${semesterText || '-'}學期</td>
                            <td class="px-4 py-3 text-center text-slate-600 dark:text-slate-300">${g.avg_score || '-'}</td>
                            <td class="px-4 py-3 text-center font-bold text-primary">${g.gpa || '-'}</td>
                            <td class="px-4 py-3 text-center text-slate-600 dark:text-slate-300">${g.class_rank || '-'}/${g.class_size || '-'}</td>
                        </tr>
                    `;
                });
            }
        }

        // --- 9. Recommendation Letter ---
        const recSection = document.getElementById('recommendation-section');
        const recData = app.recommendation;

        if (recData && recData.required == 1) {
            recSection.classList.remove('hidden');
            const recContent = document.getElementById('recommendation-content');

            if (recData.content || recData.file) {
                if (recData.content) {
                    recContent.innerHTML = recData.content.replace(/\n/g, '<br>');
                } else {
                    recContent.innerHTML = '<span class="text-slate-400 italic">無文字內容</span>';
                }

                if (recData.file) {
                    const recFileContainer = document.getElementById('recommendation-file-container');
                    recFileContainer.innerHTML = `
                        <a href="${recData.file}" target="_blank" class="inline-flex items-center gap-2 text-primary hover:underline">
                            <span class="material-symbols-outlined">attachment</span> 下載推薦信檔案
                        </a>
                     `;
                }
            } else {
                recContent.innerHTML = '<div class="p-4 bg-orange-50 text-orange-600 rounded-lg">等待教授填寫中</div>';
            }
        }

        // --- 10. Existing Review Status ---
        if (app.status !== undefined) {
            const radios = document.getElementsByName('status');
            radios.forEach(r => {
                if (r.value == app.status) r.checked = true;
            });
        }

        if (app.review_comment) {
            document.getElementById('review-comment').value = app.review_comment;
        }
    }

    function createFileCardHTML(file) {
        if (!file) return '';

        // Support both string (legacy/fallback) and object (new API)
        let path, filename;
        if (typeof file === 'string') {
            path = file;
            filename = path.split('/').pop();
        } else {
            path = file.url;
            filename = file.original_name || file.name || 'unknown_file';
        }

        const isPdf = filename.toLowerCase().endsWith('.pdf');
        const icon = isPdf ? 'picture_as_pdf' : 'image';
        const iconColor = isPdf ? 'text-red-500' : 'text-blue-500';
        const fileTypeLabel = isPdf ? 'PDF' : 'IMAGE';

        return `
            <div class="relative group flex flex-col items-center justify-center p-3 bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 shadow-sm hover:shadow-md transition-all text-center aspect-square cursor-pointer" onclick="window.open('${path}', '_blank');" title="${filename}">
                <div class="mb-1 transform group-hover:scale-110 transition-transform duration-300">
                    <span class="material-symbols-outlined ${iconColor} text-4xl">${icon}</span>
                </div>
                <div class="w-full px-1 overflow-hidden">
                    <p class="text-[10px] font-semibold text-slate-900 dark:text-white truncate w-full leading-tight">${filename}</p>
                    <p class="text-[9px] text-slate-500 dark:text-slate-400 mt-0.5 uppercase tracking-wide">${fileTypeLabel}</p>
                </div>
                <!-- Open Icon -->
                <div class="absolute top-1.5 right-1.5 opacity-0 group-hover:opacity-100 transition-opacity">
                     <span class="material-symbols-outlined text-slate-400 text-[18px]">open_in_new</span>
                </div>
            </div>
        `;
    }

    function renderFiles(pathData, container, type) {
        if (!container) return;
        container.innerHTML = '';
        container.classList.add('hidden');

        if (!pathData) {
            if (type === 'biography') {
                // Do nothing, text might be there
            } else {
                container.classList.remove('hidden');
                container.innerHTML = '<p class="text-sm text-slate-400 col-span-full">無上傳檔案</p>';
            }
            return;
        }

        let files = [];
        try {
            // Prepare inputs that might be messy
            if (Array.isArray(pathData)) {
                files = pathData;
            } else {
                // Try JSON parse
                try {
                    files = JSON.parse(pathData);
                } catch (e) {
                    // Assume single string path
                    if (typeof pathData === 'string' && pathData.length > 1) {
                        files = [pathData];
                    }
                }
            }

            if (!Array.isArray(files)) files = [files]; // fallback
        } catch (e) {
            console.error('File parse error', e);
            files = [];
        }

        if (files.length === 0) return;

        container.classList.remove('hidden');
        // Ensure grid classes are there (in HTML or add here)
        // container.className = "grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 mt-6 border-t border-slate-100 dark:border-slate-700 pt-4"; // Already in HTML for bio, need ensuring for docs

        files.forEach(path => {
            if (!path) return;

            // Extract filename
            const filename = path.split('/').pop() || 'unknown_file';
            const isPdf = filename.toLowerCase().endsWith('.pdf');
            const icon = isPdf ? 'picture_as_pdf' : 'image';
            const iconColor = isPdf ? 'text-red-500' : 'text-blue-500';
            const fileTypeLabel = isPdf ? 'PDF' : 'IMAGE';

            const card = `
            <div class="relative group flex flex-col items-center justify-center p-4 bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 shadow-sm hover:shadow-md transition-all text-center aspect-square cursor-pointer" onclick="window.open('${path}', '_blank');" title="${filename}">
                <div class="mb-2 transform group-hover:scale-110 transition-transform duration-300">
                    <span class="material-symbols-outlined ${iconColor} text-5xl">${icon}</span>
                </div>
                <div class="w-full px-1 overflow-hidden">
                    <p class="text-xs font-semibold text-slate-900 dark:text-white truncate w-full">${filename}</p>
                    <p class="text-[10px] text-slate-500 dark:text-slate-400 mt-1 uppercase tracking-wide">${fileTypeLabel}</p>
                </div>
                <!-- Open Icon -->
                <div class="absolute top-2 right-2 opacity-0 group-hover:opacity-100 transition-opacity">
                     <span class="material-symbols-outlined text-slate-400 text-sm">open_in_new</span>
                </div>
            </div>
            `;
            container.insertAdjacentHTML('beforeend', card);
        });
    }

    function setText(id, val) {
        const el = document.getElementById(id);
        if (el) el.innerText = val || '-';
    }

    async function submitReview(appId, isDraft) {
        const statusEl = document.querySelector('input[name="status"]:checked');
        const comment = document.getElementById('review-comment').value;

        if (!isDraft) {
            if (!statusEl) {
                alert('請選擇審查決議 (通過/需補件/駁回)');
                return;
            }
        }

        const status = statusEl ? statusEl.value : null;

        const btn = isDraft ? document.getElementById('save-draft-btn') : document.getElementById('submit-review-btn');
        const originalText = btn.innerText;
        btn.disabled = true;
        btn.innerText = '處理中...';

        try {
            const payload = {
                application_id: appId,
                reviewer_username: user.username,
                status: status,
                comment: comment,
                is_draft: isDraft ? '1' : '0'
            };

            const res = await fetch('../api/submit_review.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(payload)
            });
            const result = await res.json();

            if (result.success) {
                alert(isDraft ? '草稿已儲存' : '審查已送出');
                if (!isDraft) window.location.href = 'reviewer-dashboard.html';
            } else {
                alert('失敗: ' + result.message);
            }
        } catch (err) {
            console.error(err);
            alert('發生錯誤');
        } finally {
            btn.disabled = false;
            btn.innerText = originalText;
        }
    }
});
