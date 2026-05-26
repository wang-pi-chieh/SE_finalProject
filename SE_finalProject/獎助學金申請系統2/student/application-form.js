document.addEventListener('DOMContentLoaded', async () => {
    const user = JSON.parse(localStorage.getItem('user'));
    // DB stores role as '學生', keeping 'student' for backward compat just in case
    if (!user || (user.role !== 'student' && user.role !== '學生')) {
        alert('請先登入');
        window.location.href = '../login.html';
        return;
    }

    // Elements
    const form = document.querySelector('form');
    const scholarshipSelect = document.getElementById('scholarship_id');

    // Edit / View flags
    let isEditMode = false;
    let isViewOnly = false;
    let editingApplicationId = null;
    let hasExistingBioFile = false;

    // 1. Initial Data Fetch
    try {
        // A. Get Student Profile
        const profileRes = await fetch(`../api/get_student_profile.php?username=${user.username}`);
        const profileData = await profileRes.json();

        if (profileData.success) {
            const d = profileData.data;
            document.getElementById('real_name').value = d.real_name || '';
            document.getElementById('username').value = d.username || '';
            document.getElementById('department').value = d.department || '';
            document.getElementById('email').value = d.email || '';
            document.getElementById('phone').value = d.phone || '';

            // Auto-fill professor dept if same
            document.getElementById('professor_department').value = d.department || '';
        }

        // B. Get Grades
        const gradesRes = await fetch(`../api/get_student_grades.php?student_username=${user.username}`);
        const gradesData = await gradesRes.json();
        const gradesTbody = document.getElementById('grades-tbody');

        if (gradesData.success && gradesData.data.length > 0) {
            gradesTbody.innerHTML = '';
            let totalGpa = 0;
            let count = 0;

            gradesData.data.forEach(g => {
                const tr = document.createElement('tr');
                tr.className = 'hover:bg-background-light dark:hover:bg-background-dark transition-colors';
                tr.innerHTML = `
                    <td class="px-6 py-4 text-sm text-text-primary-light dark:text-text-primary-dark whitespace-nowrap">${g.academic_year}</td>
                    <td class="px-6 py-4 text-sm text-text-primary-light dark:text-text-primary-dark whitespace-nowrap">${g.semester}</td>
                    <td class="px-6 py-4 text-sm font-medium text-right text-text-primary-light dark:text-text-primary-dark">${g.avg_score}</td>
                    <td class="px-6 py-4 text-sm font-medium text-right text-text-primary-light dark:text-text-primary-dark">${g.gpa}</td>
                    <td class="px-6 py-4 text-sm text-right text-text-secondary-light dark:text-text-secondary-dark">${g.class_size}</td>
                    <td class="px-6 py-4 text-sm font-bold text-right text-primary">${g.class_rank}</td>
                `;
                gradesTbody.appendChild(tr);
                totalGpa += parseFloat(g.gpa);
                count++;
            });

            // Update Footer GPA
            const avgGpa = count > 0 ? (totalGpa / count).toFixed(2) : "0.00";
            document.querySelector('tfoot td:nth-child(2)').textContent = avgGpa;
        } else {
            gradesTbody.innerHTML = '<tr><td colspan="6" class="px-6 py-4 text-center">尚無成績資料</td></tr>';
        }

        // C. Get Scholarships
        const scholarRes = await fetch('../api/get_scholarships.php');
        const scholarData = await scholarRes.json();
        if (scholarData.success) {
            scholarshipSelect.innerHTML = '<option disabled selected value="">請選擇一個項目...</option>';
            scholarData.data.forEach(s => {
                const option = document.createElement('option');
                option.value = s.id;
                option.textContent = s.name;
                scholarshipSelect.appendChild(option);
            });

            const urlParams = new URLSearchParams(window.location.search);
            const preSelectedId = urlParams.get('scholarship_id');
            const preSelectedName = urlParams.get('scholarship_name');
            const applicationId = urlParams.get('application_id');
            const mode = urlParams.get('mode'); // 'view' 表示僅檢視

            if (preSelectedId) {
                scholarshipSelect.value = preSelectedId;
            } else if (preSelectedName) {
                const options = Array.from(scholarshipSelect.options);
                const matchedOption = options.find(opt => opt.textContent.trim() === preSelectedName.trim());
                if (matchedOption) {
                    scholarshipSelect.value = matchedOption.value;
                }
            }

            // 若從通知或「前往補件 / 詳細狀態」進來，帶有 application_id
            if (applicationId) {
                editingApplicationId = applicationId;
                isViewOnly = (mode === 'view');
                isEditMode = !isViewOnly; // view 模式不允許送出

                // 更新標題
                const titleEl = document.getElementById('form-title');
                if (titleEl) {
                    titleEl.textContent = isViewOnly ? '獎學金申請詳情' : '獎學金申請補件';
                }

                await loadExistingApplication(applicationId, user, isViewOnly);
            }
        }

    } catch (err) {
        console.error('Error fetching initial data:', err);
    }

    // 載入既有申請（補件 / 編輯 / 僅檢視用）
    async function loadExistingApplication(applicationId, user, viewOnly = false) {
        try {
            const res = await fetch(`../api/get_application_for_edit.php?id=${encodeURIComponent(applicationId)}&student_username=${encodeURIComponent(user.username)}`);
            const data = await res.json();

            if (!data.success || !data.application) {
                console.warn('載入原申請失敗：', data.message);
                return;
            }

            const app = data.application;

            // 獎學金
            if (app.scholarship_id && scholarshipSelect) {
                scholarshipSelect.value = String(app.scholarship_id);
                // 補件模式下不可更改獎學金項目
                if (!viewOnly) {
                    scholarshipSelect.setAttribute('disabled', 'disabled');
                    // Add a hint
                    const hint = document.createElement('p');
                    hint.className = "text-xs text-slate-500 mt-1";
                    hint.textContent = "補件模式下不可更改申請項目";
                    scholarshipSelect.parentNode.appendChild(hint);
                }
            }

            // 退件原因
            if (app.review_comment) {
                const commentContainer = document.getElementById('return-comment-container');
                const commentText = document.getElementById('return-comment-text');
                if (commentContainer && commentText) {
                    commentText.textContent = app.review_comment;
                    commentContainer.classList.remove('hidden');
                }
            }

            // 家庭與經濟狀況
            const setValue = (id, value) => {
                const el = document.getElementById(id);
                if (el && value !== null && value !== undefined) {
                    el.value = value;
                }
            };

            setValue('family_housing_status', app.family_housing_status);
            setValue('personal_housing_status', app.personal_housing_status);
            setValue('tuition_waiver', app.tuition_waiver);
            setValue('previous_scholarship_name', app.previous_scholarship_name);
            setValue('family_situation_desc', app.family_situation_desc);
            setValue('family_members_desc', app.family_members_desc);

            // 聯絡與匯款
            setValue('phone', app.phone);
            setValue('email', app.email);
            setValue('bank_account', app.bank_account);

            // 就學貸款單選
            if (app.has_student_loan !== undefined && app.has_student_loan !== null) {
                const loanRadios = document.querySelectorAll('input[name="has_student_loan"]');
                loanRadios.forEach(r => {
                    if (r.value === String(app.has_student_loan)) {
                        r.checked = true;
                    }
                });
            }

            // 推薦信是否需要
            if (app.recommendation_required !== undefined && app.recommendation_required !== null) {
                const recRadios = document.querySelectorAll('input[name="recommendation_required"]');
                recRadios.forEach(r => {
                    if (r.value === String(app.recommendation_required)) {
                        r.checked = true;
                    }
                });

                // 觸發顯示/隱藏推薦欄位
                const event = new Event('change');
                recRadios.forEach(r => r.dispatchEvent(event));
            }

            // 推薦人資訊
            setValue('recommend_professor', app.referrer_name);
            setValue('referrer_relationship', app.referrer_relationship);
            const refUsernameInput = document.getElementById('referrer_username');
            if (refUsernameInput && app.referrer_username) {
                refUsernameInput.value = app.referrer_username;
            }

            // 判斷是否已有自傳檔案，影響必填檢查
            if (app.biography && app.biography.length > 0) {
                hasExistingBioFile = true;
                // 顯示已上傳的自傳檔案名稱 (簡單處理：取路徑最後部分)
                const bioArea = document.getElementById('biography_upload_area');
                if (bioArea) {
                    const fileName = app.biography.split('/').pop();
                    const info = document.createElement('div');
                    info.className = "mt-2 p-3 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800/50 rounded-lg flex items-center gap-2";
                    info.innerHTML = `
                        <span class="material-symbols-outlined text-blue-600 dark:text-blue-400 text-sm">description</span>
                        <span class="text-xs text-blue-800 dark:text-blue-300 font-medium whitespace-nowrap overflow-hidden text-ellipsis">已上傳：${fileName}</span>
                        <a href="../${app.biography}" target="_blank" class="ml-auto text-[10px] text-blue-600 hover:underline font-bold uppercase">查看</a>
                    `;
                    bioArea.parentNode.appendChild(info);
                }
            }

            // 顯示其他附件資訊
            if (app.application_documents && app.application_documents.length > 0) {
                const otherArea = document.getElementById('other_docs_upload_area');
                if (otherArea) {
                    const info = document.createElement('div');
                    info.className = "mt-2 p-3 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800/50 rounded-lg flex items-center gap-2";
                    info.innerHTML = `
                        <span class="material-symbols-outlined text-blue-600 dark:text-blue-400 text-sm">folder</span>
                        <span class="text-xs text-blue-800 dark:text-blue-300 font-medium">已有補充附件資料</span>
                        <p class="text-[10px] text-slate-500 ml-2">若重新上傳將會覆蓋舊檔</p>
                    `;
                    otherArea.parentNode.appendChild(info);
                }
            }

            // 若為僅檢視模式，鎖住所有欄位並隱藏送出按鈕
            if (viewOnly && form) {
                const allInputs = form.querySelectorAll('input, select, textarea');
                allInputs.forEach(el => {
                    el.setAttribute('disabled', 'disabled');
                });

                const submitArea = document.getElementById('submit_application_btn');
                if (submitArea) {
                    submitArea.classList.add('hidden');
                }

                const declarationRow = document.getElementById('declaration')?.closest('div');
                if (declarationRow) {
                    declarationRow.classList.add('opacity-60');
                }
            }

            // 編輯模式下，重新計算送出按鈕狀態
            if (typeof updateSubmitButtonState === 'function') {
                updateSubmitButtonState();
            }
        } catch (err) {
            console.error('載入原申請時發生錯誤：', err);
        }
    }

    // 2. File Upload Handling
    const bioInput = document.getElementById('biography_file');
    const bioArea = document.getElementById('biography_upload_area');
    const bioPreview = document.getElementById('biography_preview');

    const otherInput = document.getElementById('other_docs_file');
    const otherArea = document.getElementById('other_docs_upload_area');
    const otherPreview = document.getElementById('other_docs_preview');

    let bioDocsFiles = [];
    let otherDocsFiles = [];

    // Helper: Create SQUARE Card HTML
    function createCardHTML(file, onRemoveCall) {
        const isPdf = file.type === 'application/pdf';
        const icon = isPdf ? 'picture_as_pdf' : 'image';
        const iconColor = isPdf ? 'text-red-500' : 'text-blue-500';
        const fileUrl = URL.createObjectURL(file);

        return `
            <div class="relative group flex flex-col items-center justify-center p-4 bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 shadow-sm hover:shadow-md transition-all w-full text-center aspect-square" title="${file.name}">
                <div class="cursor-pointer mb-2 transform group-hover:scale-110 transition-transform duration-300" onclick="window.open('${fileUrl}'); event.stopPropagation();">
                    <span class="material-symbols-outlined ${iconColor} text-5xl">${icon}</span>
                </div>
                <div class="w-full cursor-pointer px-1 overflow-hidden" onclick="window.open('${fileUrl}'); event.stopPropagation();">
                    <p class="text-xs font-semibold text-slate-900 dark:text-white truncate w-full">${file.name}</p>
                    <p class="text-[10px] text-slate-500 dark:text-slate-400 mt-1 uppercase tracking-wide">${(file.size / 1024 / 1024).toFixed(2)} MB</p>
                </div>
                <!-- Delete Button -->
                <button type="button" class="absolute top-2 right-2 text-slate-400 hover:text-red-500 bg-white dark:bg-slate-800 rounded-full p-1 opacity-100 md:opacity-0 group-hover:opacity-100 transition-opacity shadow-sm border border-slate-100 dark:border-slate-600 z-10" onclick="${onRemoveCall}; event.stopPropagation();">
                    <span class="material-symbols-outlined text-sm">close</span>
                </button>
            </div>
        `;
    }

    function setupDragDrop(area, input, type) {
        if (!area || !input) return;

        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            area.addEventListener(eventName, preventDefaults, false);
        });

        function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
        }

        ['dragenter', 'dragover'].forEach(eventName => {
            area.addEventListener(eventName, () => area.classList.add('bg-blue-50', 'dark:bg-slate-700'), false);
        });

        ['dragleave', 'drop'].forEach(eventName => {
            area.addEventListener(eventName, () => area.classList.remove('bg-blue-50', 'dark:bg-slate-700'), false);
        });

        area.addEventListener('drop', (e) => {
            const dt = e.dataTransfer;
            const files = dt.files;
            handleFiles(input, files, type);
        }, false);
    }

    // Bind change events
    ['change'].forEach(evt => {
        if (bioInput) bioInput.addEventListener(evt, function () { handleFiles(this, this.files, 'bio'); });
        if (otherInput) otherInput.addEventListener(evt, function () { handleFiles(this, this.files, 'other'); });
    });

    setupDragDrop(bioArea, bioInput, 'bio');
    setupDragDrop(otherArea, otherInput, 'other');

    function handleFiles(input, files, type) {
        Array.from(files).forEach(file => {
            if (file.size > 10 * 1024 * 1024) {
                alert(`檔案 ${file.name} 過大 (限制 10MB)`);
                return;
            }

            if (type === 'bio') {
                if (!bioDocsFiles.some(f => f.name === file.name && f.size === file.size)) {
                    bioDocsFiles.push(file);
                }
            } else if (type === 'other') {
                if (!otherDocsFiles.some(f => f.name === file.name && f.size === file.size)) {
                    otherDocsFiles.push(file);
                }
            }
        });

        if (type === 'bio') renderBioPreview();
        if (type === 'other') renderOtherDocsPreview();

        input.value = '';
    }

    function renderBioPreview() {
        if (bioDocsFiles.length === 0) {
            bioPreview.classList.add('hidden');
            bioPreview.classList.remove('grid');
            if (typeof updateSubmitButtonState === 'function') {
                updateSubmitButtonState();
            }
            return;
        }
        bioPreview.classList.remove('hidden');
        bioPreview.classList.add('grid');
        bioPreview.innerHTML = '';

        bioDocsFiles.forEach((file, index) => {
            bioPreview.insertAdjacentHTML('beforeend', createCardHTML(file, `removeBioFile(${index})`));
        });

        if (typeof updateSubmitButtonState === 'function') {
            updateSubmitButtonState();
        }
    }

    function renderOtherDocsPreview() {
        if (otherDocsFiles.length === 0) {
            otherPreview.classList.add('hidden');
            otherPreview.classList.remove('grid');
            return;
        }
        otherPreview.classList.remove('hidden');
        otherPreview.classList.add('grid');
        otherPreview.innerHTML = '';

        otherDocsFiles.forEach((file, index) => {
            otherPreview.insertAdjacentHTML('beforeend', createCardHTML(file, `removeOtherFile(${index})`));
        });
    }

    // Removers
    window.removeBioFile = function (index) {
        bioDocsFiles.splice(index, 1);
        renderBioPreview();
        if (typeof updateSubmitButtonState === 'function') {
            updateSubmitButtonState();
        }
    };

    window.removeOtherFile = function (index) {
        otherDocsFiles.splice(index, 1);
        renderOtherDocsPreview();
    };


    // Recommendation Logic (已在上面處理，這裡保留初始狀態檢查)
    const initialChecked = document.querySelector('input[name="recommendation_required"]:checked');
    if (initialChecked && initialChecked.value === '1') {
        const recContainer = document.getElementById('recommendation_fields_container');
        if (recContainer) {
            recContainer.classList.remove('hidden');
        }
    }


    // 3. Form Submission
    const declarationCheckbox = document.getElementById('declaration');
    const submitBtn = document.getElementById('submit_application_btn');
    submitBtn.disabled = true;

    // 檢查表單是否完整（不包括聲明勾選）
    function isFormComplete() {
        // 1. 檢查獎學金選擇
        if (!scholarshipSelect.value) return false;

        // 2. 檢查基本資料
        const email = document.getElementById('email');
        if (!email || !email.value.trim()) return false;

        const phone = document.getElementById('phone');
        if (!phone || !phone.value.trim()) return false;

        const bankAccount = document.getElementById('bank_account');
        if (!bankAccount || !bankAccount.value.trim()) return false;

        // 3. 檢查家庭與經濟狀況
        const familyHousing = document.getElementById('family_housing_status');
        if (!familyHousing || !familyHousing.value) return false;

        const personalHousing = document.getElementById('personal_housing_status');
        if (!personalHousing || !personalHousing.value) return false;

        const tuitionWaiver = document.getElementById('tuition_waiver');
        if (!tuitionWaiver || !tuitionWaiver.value) return false;

        const hasLoan = document.querySelector('input[name="has_student_loan"]:checked');
        if (!hasLoan) return false;

        // 4. 檢查家庭狀況說明
        const familySituation = document.getElementById('family_situation_desc');
        if (!familySituation || !familySituation.value.trim()) return false;

        const familyMembers = document.getElementById('family_members_desc');
        if (!familyMembers || !familyMembers.value.trim()) return false;

        // 5. 檢查推薦信
        const recRequired = document.querySelector('input[name="recommendation_required"]:checked');
        if (!recRequired) return false;

        if (recRequired.value === '1') {
            const refProfessor = document.getElementById('recommend_professor');
            const refRelationship = document.getElementById('referrer_relationship');
            if (!refProfessor || !refProfessor.value.trim()) return false;
            if (!refRelationship || !refRelationship.value.trim()) return false;
        }

        // 6. 檢查上傳文件
        // 新申請：一定要上傳一份自傳檔案；編輯模式：已存在檔案或新上傳其一即可
        if (!hasExistingBioFile && bioDocsFiles.length === 0) return false;

        return true;
    }

    // 更新按鈕狀態
    function updateSubmitButtonState() {
        const formComplete = isFormComplete();
        const declarationChecked = declarationCheckbox.checked;
        submitBtn.disabled = !(formComplete && declarationChecked);
    }

    // 監聽聲明勾選
    declarationCheckbox.addEventListener('change', () => {
        updateSubmitButtonState();
    });

    // 監聽所有必填表單欄位變更（所有標記 * 的欄位）
    const requiredFormInputs = [
        { id: 'scholarship_id', type: 'select' },
        { id: 'email', type: 'input' },
        { id: 'phone', type: 'input' },
        { id: 'bank_account', type: 'input' },
        { id: 'family_housing_status', type: 'select' },
        { id: 'personal_housing_status', type: 'select' },
        { id: 'tuition_waiver', type: 'select' },
        { id: 'family_situation_desc', type: 'textarea' },
        { id: 'family_members_desc', type: 'textarea' },
        { id: 'recommend_professor', type: 'input' },
        { id: 'referrer_relationship', type: 'input' }
    ];

    requiredFormInputs.forEach(({ id, type }) => {
        const element = document.getElementById(id);
        if (element) {
            // 對於 input 和 textarea，監聽 input 和 change 事件
            // 對於 select，監聽 change 事件
            if (type === 'select') {
                element.addEventListener('change', updateSubmitButtonState);
            } else {
                // input 和 textarea
                element.addEventListener('input', updateSubmitButtonState);
                element.addEventListener('change', updateSubmitButtonState);
                // textarea 也需要監聽 paste 事件
                if (type === 'textarea') {
                    element.addEventListener('paste', () => {
                        setTimeout(updateSubmitButtonState, 10);
                    });
                }
            }
        }
    });

    // 監聽單選按鈕
    document.querySelectorAll('input[name="has_student_loan"]').forEach(radio => {
        radio.addEventListener('change', updateSubmitButtonState);
    });

    document.querySelectorAll('input[name="recommendation_required"]').forEach(radio => {
        radio.addEventListener('change', () => {
            // 觸發推薦信欄位顯示/隱藏
            const recContainer = document.getElementById('recommendation_fields_container');
            if (recContainer) {
                if (radio.value === '1') {
                    recContainer.classList.remove('hidden');
                    // 當推薦人欄位顯示時，確保它們有監聽器（如果還沒有）
                    const refProfessor = document.getElementById('recommend_professor');
                    const refRelationship = document.getElementById('referrer_relationship');
                    if (refProfessor && !refProfessor.hasAttribute('data-listener-added')) {
                        refProfessor.addEventListener('input', updateSubmitButtonState);
                        refProfessor.addEventListener('change', updateSubmitButtonState);
                        refProfessor.setAttribute('data-listener-added', 'true');
                    }
                    if (refRelationship && !refRelationship.hasAttribute('data-listener-added')) {
                        refRelationship.addEventListener('input', updateSubmitButtonState);
                        refRelationship.addEventListener('change', updateSubmitButtonState);
                        refRelationship.setAttribute('data-listener-added', 'true');
                    }
                } else {
                    recContainer.classList.add('hidden');
                }
            }
            // 更新按鈕狀態
            updateSubmitButtonState();
        });
    });

    // 初始檢查按鈕狀態
    updateSubmitButtonState();


    // 表單驗證函數（用於顯示錯誤訊息）
    function validateForm() {
        const missingFields = [];

        const check = (id, name) => {
            const el = document.getElementById(id);
            if (!el || (el.tagName === 'INPUT' && (el.type === 'radio' || el.type === 'checkbox') ? false : !el.value.trim())) {
                missingFields.push({ id, name });
            }
        };

        // 1. 檢查獎學金選擇
        if (!scholarshipSelect.value) {
            missingFields.push({ id: 'scholarship_id', name: '選擇申請項目' });
        }

        // 2. 檢查基本資料
        check('email', '電子郵件');
        check('phone', '手機號碼');
        check('bank_account', '匯款帳戶');

        // 3. 檢查家庭與經濟狀況
        if (!document.getElementById('family_housing_status').value) missingFields.push({ id: 'family_housing_status', name: '家庭居住狀況' });
        if (!document.getElementById('personal_housing_status').value) missingFields.push({ id: 'personal_housing_status', name: '個人居住狀況' });
        if (!document.getElementById('tuition_waiver').value) missingFields.push({ id: 'tuition_waiver', name: '學雜費減免身分' });

        const hasLoan = document.querySelector('input[name="has_student_loan"]:checked');
        if (!hasLoan) {
            missingFields.push({ id: 'loan_radio_group', name: '是否申請就學貸款' });
        }

        // 4. 檢查家庭狀況說明
        check('family_situation_desc', '家庭狀況說明');
        check('family_members_desc', '家庭成員狀況');

        // 5. 檢查推薦信
        const recRequired = document.querySelector('input[name="recommendation_required"]:checked');
        if (!recRequired) {
            missingFields.push({ id: 'rec_radio_group', name: '是否需要推薦信' });
        } else if (recRequired.value === '1') {
            check('recommend_professor', '推薦人（教授姓名）');
            check('referrer_relationship', '與推薦人關係');
        }

        // 6. 檢查上傳文件
        if (!hasExistingBioFile && bioDocsFiles.length === 0) {
            missingFields.push({ id: 'biography_upload_area', name: '自傳/讀書計畫（需上傳檔案）' });
        }

        // 7. 檢查聲明勾選
        if (!declarationCheckbox.checked) {
            missingFields.push({ id: 'declaration_container', name: '保證聲明（需勾選）' });
        }

        return missingFields;
    }

    submitBtn.addEventListener('click', async (e) => {
        e.preventDefault();

        // 執行完整表單驗證
        const missingFields = validateForm();
        if (missingFields.length > 0) {
            const message = '請完成以下必填欄位：\n\n' + missingFields.map((field, index) => `${index + 1}. ${field.name}`).join('\n');
            alert(message);

            // 滾動到第一個錯誤欄位並加上邊框提示
            const firstError = document.getElementById(missingFields[0].id);
            if (firstError) {
                firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                firstError.focus();

                // 暫時加上紅框效果
                const originalBorder = firstError.style.borderColor;
                firstError.style.borderColor = '#ef4444';
                firstError.style.borderWidth = '2px';
                setTimeout(() => {
                    firstError.style.borderColor = originalBorder;
                    firstError.style.borderWidth = '';
                }, 3000);
            }
            return;
        }

        const scholarshipId = scholarshipSelect.value;
        const recRequired = document.querySelector('input[name="recommendation_required"]:checked');
        const isRecRequired = recRequired.value === '1';

        const formData = new FormData();
        formData.append('student_username', user.username);
        formData.append('scholarship_id', scholarshipId);
        formData.append('academic_year', '113');
        formData.append('semester', '1');
        formData.append('recommendation_required', recRequired.value);

        if (isEditMode && editingApplicationId) {
            formData.append('application_id', editingApplicationId);
        }

        const appendIf = (id, key) => {
            const el = document.getElementById(id);
            if (el) formData.append(key, el.value);
        };

        appendIf('email', 'email');
        appendIf('phone', 'phone');
        appendIf('bank_account', 'bank_account');
        appendIf('family_housing_status', 'family_housing_status');
        appendIf('personal_housing_status', 'personal_housing_status');
        appendIf('tuition_waiver', 'tuition_waiver');

        const loan = document.querySelector('input[name="has_student_loan"]:checked');
        formData.append('has_student_loan', loan ? loan.value : '0');

        appendIf('previous_scholarship_name', 'previous_scholarship_name');
        appendIf('family_situation_desc', 'family_situation_desc');
        appendIf('family_members_desc', 'family_members_desc');

        if (isRecRequired) {
            const rel = document.getElementById('referrer_relationship');
            const refName = document.getElementById('recommend_professor');

            if (!rel.value.trim() || !refName.value.trim()) {
                alert('請填寫完整推薦人資訊');
                recContainer.scrollIntoView({ behavior: 'smooth' });
                return;
            }

            formData.append('referrer_relationship', rel.value);
            formData.append('referrer_name', refName.value);
            const referrerUsername = document.getElementById('referrer_username');
            if (referrerUsername) formData.append('referrer_username', referrerUsername.value);
        }

        if (bioDocsFiles.length > 0) {
            bioDocsFiles.forEach(file => {
                formData.append('biography_file[]', file);
            });
        }

        if (otherDocsFiles.length > 0) {
            otherDocsFiles.forEach(file => {
                formData.append('other_docs_file[]', file);
            });
        }

        try {
            submitBtn.disabled = true;
            submitBtn.textContent = isEditMode ? '更新中...' : '傳送中...';

            const endpoint = isEditMode ? '../api/update_application.php' : '../api/submit_application.php';

            const res = await fetch(endpoint, {
                method: 'POST',
                body: formData
            });
            const result = await res.json();

            if (result.success) {
                alert(isEditMode ? '已更新並重新送出申請！' : '申請成功！');
                window.location.href = 'student-dashboard.html';
            } else {
                alert('申請失敗: ' + result.message);
            }
        } catch (err) {
            console.error(err);
            alert('發生錯誤，請稍後再試');
        } finally {
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<span class="material-symbols-outlined text-[18px]">send</span> 送出申請';
        }
    });

    // 4. Professor Search
    setupProfessorSearch();

    function setupProfessorSearch() {
        const input = document.getElementById('recommend_professor');
        const results = document.getElementById('professor_results');
        const deptInput = document.getElementById('professor_department');

        if (!input || !results) return;

        let debounceTimer;

        async function fetchProfessors(query) {
            try {
                const res = await fetch(`../api/get_professors.php?q=${encodeURIComponent(query)}`);
                if (!res.ok) throw new Error('API Error');
                const data = await res.json();
                renderResults(data);
            } catch (err) {
                console.error('Fetch error:', err);
                results.classList.add('hidden');
            }
        }

        function renderResults(matches) {
            results.innerHTML = '';
            if (!matches || matches.length === 0) {
                results.classList.add('hidden');
                return;
            }

            matches.forEach(prof => {
                const li = document.createElement('li');
                li.className = "px-4 py-2 hover:bg-slate-100 dark:hover:bg-slate-700 cursor-pointer text-sm text-slate-700 dark:text-slate-200 flex justify-between items-center";
                li.innerHTML = `
                    <span class="font-medium">${prof.name}</span>
                    <span class="text-xs text-slate-500 dark:text-slate-400">${prof.department || prof.dept || ''}</span>
                `;
                li.addEventListener('mousedown', (e) => {
                    e.preventDefault();
                    input.value = prof.name;
                    deptInput.value = prof.department || prof.dept || '';
                    document.getElementById('referrer_username').value = prof.id;
                    results.classList.add('hidden');
                });
                results.appendChild(li);
            });
            results.classList.remove('hidden');
        }

        input.addEventListener('input', (e) => {
            const val = e.target.value.trim();
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => {
                fetchProfessors(val);
            }, 300);
        });

        input.addEventListener('focus', () => {
            fetchProfessors(input.value.trim());
        });

        input.addEventListener('blur', () => {
            setTimeout(() => {
                results.classList.add('hidden');
            }, 200);
        });
    }
});
