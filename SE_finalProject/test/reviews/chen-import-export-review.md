# 獎學金管理與儀表板功能簡述及測試情況

## 功能範圍

本次調整涵蓋兩個主要頁面：

- `admin/system-settings.html`
- `admin/admin-dashboard.html`

主要功能包含獎學金資料檢視、編輯、刪除、CSV 匯入、審核完成標記，以及儀表板預算資料篩選與依篩選條件匯出 PDF 報表。

## 獎學金項目管理

### 檢視與編輯

在「系統設定」的「獎學金項目列表」中，操作欄位由原本單一刪除功能調整為：

- 檢視
- 編輯

檢視介面可查看該筆獎學金項目的完整資料，包含名稱、金額、名額、獎助單位、申請起訖日期、啟用狀態、審核完成狀態、建立時間與詳細說明。

編輯介面可修改：

- 獎學金名稱
- 金額
- 名額
- 申請開始日期
- 申請截止日期
- 負責單位
- 詳細說明 / 申請條件
- 是否啟用
- 是否審核完成

刪除按鈕已整合進檢視與編輯介面，不再直接顯示於列表操作欄。

### 審核完成欄位

新增 `review_completed` 欄位，用於記錄獎學金項目是否已完成審核。

相關處理：

- 編輯介面提供「審核完成」勾選欄位。
- 列表中若該項目已審核完成，會顯示「審核完成」標籤。
- `api/update_scholarship.php` 會在欄位不存在時自動補上 `review_completed`。
- `api/import_scholarships_csv.php` 也會在匯入前確認此欄位存在。

### CSV 匯入

在「新增獎學金」視窗中新增「匯入 CSV」區塊，可批次匯入獎學金項目。

CSV 第一列需為欄位名稱，支援欄位如下：

- `name` / `獎學金名稱`
- `amount` / `金額`
- `quota` / `名額`
- `deadline` / `application_end_date` / `截止日期`
- `provider_username` / `負責單位` / `獎助單位`
- `description` / `詳細說明` / `申請條件`
- `application_start_date`
- `is_active`
- `review_completed`

匯入行為：

- 必填欄位為名稱、金額、截止日期。
- 截止日期需為 `YYYY-MM-DD`。
- 若未提供申請開始日期，預設為匯入當日。
- 若未提供負責單位，預設使用 `admin`。
- 若部分列資料錯誤，系統會匯入正確列並回報錯誤列號。

## 儀表板預算資料篩選與匯出

### 篩選功能

在「系統管理」儀表板的「學系獎學金預算與分配概況」區塊新增依系所篩選功能。

目前支援：

- 依系所篩選
- 清除篩選
- 篩選摘要顯示目前條件與筆數

程式中新增 `budgetFilterState` 與 `budgetFilterRegistry`，作為後續擴充篩選條件的接口。未來若新增資料標籤，可在 registry 中新增對應條件，不需重寫表格與匯出流程。

### 依篩選條件匯出 PDF

匯出報表功能已改為根據目前篩選後的資料匯出。

PDF 內容包含：

- 報表標題
- 匯出日期
- 目前篩選條件
- 符合條件的系所預算資料
- 匯出筆數
- 總預算
- 總支出

為避免 PDF 空白，匯出流程已由原本 `html2pdf().from(container)` 改為：

1. 建立白底報表節點。
2. 使用 `html2canvas` 將報表節點轉成圖片。
3. 使用 `jsPDF.addImage()` 寫入 PDF。
4. 內容超過一頁時自動分頁。
5. 匯出完成後移除暫存節點。

## 後端 API

新增或調整的 API：

- `api/update_scholarship.php`
- `api/import_scholarships_csv.php`
- `api/create_scholarship.php`
- `api/delete_scholarship.php`


## 測試情況

### 語法檢查

已執行並通過：

- `node --check admin/js/scholarship-admin.js`
- `php -l api/update_scholarship.php`
- `php -l api/create_scholarship.php`
- `php -l api/delete_scholarship.php`
- `php -l api/import_scholarships_csv.php`
- `php -l api/get_department_budgets.php`

`admin/admin-dashboard.html` 的內嵌 JavaScript 已使用 Node 解析檢查，結果通過。

### 資料庫連線測試

已使用 `test_db_debug.php` 測試資料庫連線，確認可連線至 `scholarshipdata`，並可列出系統所需資料表。

已確認資料表包含：

- `scholarships`
- `scholarship_units`
- `applications`
- `departments`
- `system_logs`
- `users`

### 獎學金編輯測試

曾出現「伺服器回傳格式錯誤」，原因是 PHP 日誌字串輸出 warning，導致 JSON 前方混入 HTML warning。

已修正：

- `api/update_scholarship.php` 日誌字串改為安全字串串接。
- `api/create_scholarship.php` 修正金額日誌字串。
- `api/delete_scholarship.php` 調整為先寫日誌再輸出 JSON。

修正後實際 POST 測試 `api/update_scholarship.php`，回傳為純 JSON：

```json
{"success":true}
```

### CSV 匯入測試

已使用測試 CSV 呼叫 `api/import_scholarships_csv.php`。

測試內容為缺少金額的資料列，API 正確回傳 JSON 錯誤訊息：

```json
{
  "success": false,
  "imported_count": 0,
  "errors": ["第 2 列：缺少獎學金名稱、金額或截止日期"],
  "message": "沒有任何資料被匯入"
}
```

### 儀表板預算 API 測試

已呼叫 `api/get_department_budgets.php`，確認可成功回傳系所預算資料。

回傳資料包含：

- department
- budget
- used
- remaining
- utilization

### PDF 匯出測試情況

已針對 PDF 空白問題進行兩次修正：

1. 將暫存匯出節點掛到 DOM。
2. 改為 `html2canvas + jsPDF` 手動產生 PDF。

目前程式語法檢查通過。實際瀏覽器端 PDF 是否成功顯示，需在瀏覽器中重新整理頁面後再次點擊匯出確認，因為此功能依賴 CDN 載入的 `html2canvas` 與 `jsPDF`。

## 注意事項

- 若瀏覽器無法連線 CDN，PDF 匯出會失敗。
- 匯入 CSV 時，負責單位必須存在於 `scholarship_units.username`。
- 儀表板篩選目前只影響主表格與報表匯出，不影響「參數設定」中的預算設定列表。
