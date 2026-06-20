document.addEventListener('DOMContentLoaded', () => {
    // 1. Auth Check
    const urlParams = new URLSearchParams(window.location.search);
    const isPreviewMode = urlParams.get('preview') === 'reviewer';
    const previewUser = {
        username: urlParams.get('preview_user') || 'reviewer-preview',
        role: '獎助單位',
        real_name: '審查單位端預覽',
        email: 'reviewer-preview@example.edu'
    };
    const user = isPreviewMode ? previewUser : JSON.parse(localStorage.getItem('user'));
    if (!user) {
        window.location.href = '../login.html';
        return;
    }
    let reviewAutosaveHandle = null;

    // 2. Get Application ID
    const appId = urlParams.get('id');

    if (!appId) {
        showMissingApplicationId();
        return;
    }

    // 3. Load Data
    loadApplicationDetails(appId);

    // 4. Bind Events (Submit / Draft)
    document.getElementById('submit-review-btn').addEventListener('click', () => submitReview(appId, false));
    document.getElementById('save-draft-btn').addEventListener('click', () => submitReview(appId, true));

    function buildReviewerUrl(path) {
        if (!isPreviewMode) return path;

        const params = new URLSearchParams();
        params.set('preview', 'reviewer');
        params.set('preview_user', user.username || previewUser.username);
        return `${path}?${params.toString()}`;
    }

    function showMissingApplicationId() {
        document.getElementById('loading-state')?.classList.add('hidden');
        document.getElementById('main-content')?.classList.add('hidden');

        const pageContainer = document.querySelector('main > .flex.flex-col') || document.querySelector('main');
        if (!pageContainer || document.getElementById('missing-application-state')) return;

        pageContainer.insertAdjacentHTML('beforeend', `
            <section id="missing-application-state" class="rounded-xl border border-slate-200 bg-white p-8 text-center shadow-sm dark:border-slate-700 dark:bg-slate-800">
                <div class="mx-auto mb-4 flex size-12 items-center justify-center rounded-full bg-blue-50 text-lg font-black text-primary dark:bg-blue-900/30">
                    i
                </div>
                <h2 class="text-lg font-bold text-slate-900 dark:text-white">請先選擇要審查的申請案件</h2>
                <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">審查詳情頁需要申請 ID，請從申請審核清單進入。</p>
                <div class="mt-6 flex flex-col items-center justify-center gap-3 sm:flex-row">
                    <a href="${buildReviewerUrl('applications.html')}" class="inline-flex items-center rounded-lg bg-primary px-4 py-2 text-sm font-bold text-white hover:bg-blue-700">
                        前往申請審核
                    </a>
                    <a href="${buildReviewerUrl('reviewer-dashboard.html')}" class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-bold text-slate-700 hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-700">
                        返回儀表板
                    </a>
                </div>
            </section>
        `);
    }

    async function loadApplicationDetails(id) {
        try {
            document.getElementById('loading-state').classList.remove('hidden');
            document.getElementById('main-content').classList.add('hidden');

            const res = await fetch(`../api/get_application_details.php?id=${encodeURIComponent(id)}&reviewer_username=${encodeURIComponent(user.username)}`);
            const result = await res.json();

            document.getElementById('loading-state').classList.add('hidden');

            if (result.success) {
                document.getElementById('main-content').classList.remove('hidden');
                renderData(result.data);
                initReviewAutosave(id);
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
                bindFileCards(bioFilesContainer);
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
                bindFileCards(docsContainer);
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

        if (app.review_score !== undefined && app.review_score !== null) {
            const scoreInput = document.getElementById('review-score');
            if (scoreInput) scoreInput.value = app.review_score;
        }
    }

    function initReviewAutosave(applicationId) {
        if (!window.ServerDraftAutosave) return;
        if (reviewAutosaveHandle) {
            reviewAutosaveHandle.stop();
        }

        const statusInputs = Array.from(document.querySelectorAll('input[name="status"]'));
        const scoreInput = document.getElementById('review-score');
        const stageInput = document.getElementById('review-stage');
        const commentInput = document.getElementById('review-comment');
        const fields = [...statusInputs, scoreInput, stageInput, commentInput].filter(Boolean);

        reviewAutosaveHandle = window.ServerDraftAutosave.register({
            actorUsername: user.username,
            draftType: 'reviewer_review',
            draftKey: `application:${applicationId}`,
            context: { application_id: applicationId },
            fields,
            collect() {
                const selectedStatus = document.querySelector('input[name="status"]:checked');
                return {
                    status: selectedStatus ? selectedStatus.value : '',
                    score: scoreInput ? scoreInput.value.trim() : '',
                    stage: stageInput ? stageInput.value : 'initial',
                    comment: commentInput ? commentInput.value : ''
                };
            },
            apply(data) {
                if (data.status !== undefined) {
                    statusInputs.forEach((input) => {
                        input.checked = String(input.value) === String(data.status);
                    });
                }
                if (scoreInput && data.score !== undefined) scoreInput.value = data.score || '';
                if (stageInput && data.stage !== undefined) stageInput.value = data.stage || 'initial';
                if (commentInput && data.comment !== undefined) commentInput.value = data.comment || '';
            },
            shouldSave() {
                return true;
            }
        });
    }

    function createFileCardHTML(file) {
        if (!file) return '';

        // Support both legacy string paths and structured API file objects.
        let path, filename;
        let exists = true;
        let missingReason = '';
        if (typeof file === 'string') {
            path = file;
            filename = path.split('/').pop();
        } else {
            path = file.url;
            filename = file.original_name || file.name || 'unknown_file';
            exists = file.exists !== false;
            missingReason = file.missing_reason || '線上檔案不存在';
        }

        const isPdf = filename.toLowerCase().endsWith('.pdf');
        const icon = exists ? (isPdf ? 'picture_as_pdf' : 'image') : 'error_outline';
        const iconColor = exists ? (isPdf ? 'text-red-500' : 'text-blue-500') : 'text-amber-500';
        const fileTypeLabel = exists ? (isPdf ? 'PDF' : 'IMAGE') : '檔案不存在';
        const safeFilename = escapeHTML(filename);
        const safePath = escapeHTML(path || '');
        const safeReason = escapeHTML(missingReason);

        if (!exists || !path) {
            return `
                <div class="relative flex aspect-square flex-col items-center justify-center rounded-xl border border-amber-200 bg-amber-50 p-3 text-center opacity-90 dark:border-amber-900/60 dark:bg-amber-950/20" title="${safeReason}">
                    <div class="mb-1">
                        <span class="material-symbols-outlined ${iconColor} text-4xl">${icon}</span>
                    </div>
                    <div class="w-full px-1 overflow-hidden">
                        <p class="text-[10px] font-semibold text-slate-900 dark:text-white truncate w-full leading-tight">${safeFilename}</p>
                        <p class="text-[9px] text-amber-700 dark:text-amber-300 mt-0.5">${fileTypeLabel}</p>
                    </div>
                </div>
            `;
        }

        return `
            <button type="button" class="relative group flex flex-col items-center justify-center p-3 bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 shadow-sm hover:shadow-md transition-all text-center aspect-square cursor-pointer" data-file-url="${safePath}" title="${safeFilename}">
                <div class="mb-1 transform group-hover:scale-110 transition-transform duration-300">
                    <span class="material-symbols-outlined ${iconColor} text-4xl">${icon}</span>
                </div>
                <div class="w-full px-1 overflow-hidden">
                    <p class="text-[10px] font-semibold text-slate-900 dark:text-white truncate w-full leading-tight">${safeFilename}</p>
                    <p class="text-[9px] text-slate-500 dark:text-slate-400 mt-0.5 uppercase tracking-wide">${fileTypeLabel}</p>
                </div>
                <!-- Open Icon -->
                <div class="absolute top-1.5 right-1.5 opacity-0 group-hover:opacity-100 transition-opacity">
                     <span class="material-symbols-outlined text-slate-400 text-[18px]">open_in_new</span>
                </div>
            </button>
        `;
    }

    function bindFileCards(container) {
        container.querySelectorAll('[data-file-url]').forEach(card => {
            card.addEventListener('click', () => {
                window.open(card.dataset.fileUrl, '_blank', 'noopener');
            });
        });
    }

    function escapeHTML(value) {
        return String(value ?? '').replace(/[&<>"']/g, char => ({
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#39;'
        }[char]));
    }

    function setText(id, val) {
        const el = document.getElementById(id);
        if (el) el.innerText = val || '-';
    }

    async function submitReview(appId, isDraft) {
        const statusEl = document.querySelector('input[name="status"]:checked');
        const comment = document.getElementById('review-comment').value;
        const scoreInput = document.getElementById('review-score');
        const stageInput = document.getElementById('review-stage');
        const score = scoreInput ? scoreInput.value.trim() : '';
        const stage = stageInput ? stageInput.value : 'initial';

        if (!isDraft) {
            if (!statusEl) {
                alert('請選擇審查決議 (通過/需補件/駁回)');
                return;
            }
            if (score === '') {
                alert('請輸入審查評分');
                return;
            }
            const scoreNumber = Number(score);
            if (!Number.isFinite(scoreNumber) || scoreNumber < 0 || scoreNumber > 100) {
                alert('審查評分必須介於 0 到 100');
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
                score: score,
                stage: stage,
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
                if (reviewAutosaveHandle) {
                    reviewAutosaveHandle.clear();
                }
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
