# fix/sdd-code-fixes 測試紀錄

## 分支目的

本分支修正 SDD 內容檢查時發現的兩個程式缺口：

| 功能 | 原本問題 | 本次修正 |
| --- | --- | --- |
| 學生申請草稿暫存 | 只有瀏覽器 localStorage，沒有網站資料庫暫存；電腦關機或換裝置後無法恢復 | 新增 `application_drafts`、學生草稿 API，`autosave.js` 每 30 秒同步網站暫存，遠端失敗會顯示錯誤 |
| 管理端獎學金匯入 / 匯出 | `scholarship-admin.js` 和 `002_chen_import_export.sql` 幾乎是空殼，沒有匯入預覽、確認或匯出紀錄 | 新增 CSV 範本、CSV 預覽、錯誤列提示、人工確認匯入、首頁公告選項、CSV 匯出與匯入/匯出紀錄 |

## 主要改動

| 類型 | 檔案 |
| --- | --- |
| Migration | `SE_finalProject/migrations/002_chen_import_export.sql` |
| Migration | `SE_finalProject/migrations/004_xie_application_draft.sql` |
| App migration copy | `SE_finalProject/獎助學金申請系統2/migrations/002_chen_import_export.sql` |
| App migration copy | `SE_finalProject/獎助學金申請系統2/migrations/004_xie_application_draft.sql` |
| Admin frontend | `SE_finalProject/獎助學金申請系統2/admin/system-settings.html` |
| Admin frontend | `SE_finalProject/獎助學金申請系統2/admin/js/scholarship-admin.js` |
| Admin API | `SE_finalProject/獎助學金申請系統2/api/admin/_scholarship_import_common.php` |
| Admin API | `SE_finalProject/獎助學金申請系統2/api/admin/scholarship_import_preview.php` |
| Admin API | `SE_finalProject/獎助學金申請系統2/api/admin/confirm_scholarship_import.php` |
| Admin API | `SE_finalProject/獎助學金申請系統2/api/admin/export_scholarships_csv.php` |
| Admin API | `SE_finalProject/獎助學金申請系統2/api/admin/get_scholarship_import_batches.php` |
| Student frontend | `SE_finalProject/獎助學金申請系統2/autosave.js` |
| Student frontend | `SE_finalProject/獎助學金申請系統2/student/application-form.js` |
| Student API | `SE_finalProject/獎助學金申請系統2/api/student/_draft_common.php` |
| Student API | `SE_finalProject/獎助學金申請系統2/api/student/save_application_draft.php` |
| Student API | `SE_finalProject/獎助學金申請系統2/api/student/get_application_draft.php` |
| Student API | `SE_finalProject/獎助學金申請系統2/api/student/delete_application_draft.php` |

## 測試結果

| 測試項目 | 指令 / 方法 | 結果 | 備註 |
| --- | --- | --- | --- |
| PHP syntax check | `php -l` 檢查 9 支新增/修改 API | 通過 | admin CSV API、student draft API 均無語法錯誤 |
| JavaScript syntax check | `node --check` 檢查 `scholarship-admin.js`、`autosave.js`、`application-form.js` | 通過 | 前端 JS 可被 Node parser 正常解析 |
| 錯誤訊息檢查 | `rg "Permission denied|Input invalid|網站暫存失敗"` | 通過 | 權限錯誤、輸入錯誤、網站暫存失敗會回傳/顯示明確訊息 |
| 本機頁面 smoke test | `php -S 127.0.0.1:8080 -t SE_finalProject/獎助學金申請系統2` 後 `curl -I` | 通過 | `admin/system-settings.html`、`student/application-form.html` 皆回 200 |
| 本機 MySQL/API 實測 | `/Applications/XAMPP/xamppfiles/bin/mysql.server start` | 阻擋 | XAMPP MariaDB 無法寫入 log：`Permission denied`，不是程式碼錯誤 |
| 線上 Zeabur service 狀態 | Zeabur MCP `list_projects` / `list_services` / `get_deployments` | 通過 | `scholarship` project 的 `mysql`、`web` service 均為 RUNNING；最新部署 `6a34baccdc8a677a9ed75637` 為 RUNNING |
| Zeabur build log | Zeabur MCP `get_build_logs` | 通過 | Build log 顯示 `DONE build completed`，舊 deployment 已移除 |
| 線上 HTTP smoke test | `curl -I https://scholarship.zeabur.app/admin/system-settings.html`、`student/application-form.html` | 通過 | 兩頁皆回 200，`last-modified` 更新為 2026-06-19 03:39 UTC |
| 線上新功能 DOM 檢查 | `curl -s .../admin/system-settings.html \| rg "獎學金資料匯入|scholarship-import-preview-btn|下載範本|匯出 CSV"` | 通過 | 線上頁面已出現匯入/匯出區塊 |
| 線上 API 權限錯誤 | 未登入呼叫 admin import/export history、student draft API | 通過 | admin API 回 `Permission denied: 請先登入系統管理員帳號`；student API 回 `Permission denied: 請先登入學生帳號` |
| 線上 DB table read-only check | Zeabur MCP `execute_command` 執行 `SHOW TABLES LIKE ...` | 通過 | `application_drafts`、`scholarship_import_batches`、`scholarship_export_logs` 三張表存在 |
| 線上登入 E2E | 暫時建立 `codex_admin_e2e`、`codex_student_e2e` 後用 curl session 測 CSV preview、confirm、export、draft save/get/delete | 通過 | 匯入測試批次 `2`、測試獎學金 `CODEX_E2E_IMPORT_20260619035206`、草稿皆成功；測後已刪除所有 `codex_*` 測試資料與 log，DB 檢查剩餘筆數皆為 0 |
| 手機 viewport smoke test | Browser plugin，390x844，直接開 `student/application-form.html`、`admin/system-settings.html` | 通過 | 未登入會導向 `login.html`；首屏不是空白、無水平溢出、無 framework error overlay。console 只有 Tailwind CDN production warning |

## 未完成或限制

| 項目 | 狀態 |
| --- | --- |
| XAMPP 本機 DB | 目前被本機權限阻擋，需用 XAMPP GUI 或修正 `/Applications/XAMPP/xamppfiles/var/mysql` 寫入權限後才能做本機 DB API 實測 |
| 檔案草稿 | 草稿只記錄檔名 metadata；真正檔案仍在正式送出申請時上傳，避免未送出檔案殘留在 server |
| 可選欄位報表 | 本次只補獎學金資料 CSV 匯出，不等於完整任意欄位/期間報表產生器 |
| Tailwind CDN warning | 線上頁面 console 會出現 `cdn.tailwindcss.com should not be used in production`，屬於既有前端載入方式；本次未改 build pipeline |
