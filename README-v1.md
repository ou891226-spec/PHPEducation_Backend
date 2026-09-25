# PHPEducation Backend

系統的登入、課程、教材、題目與權限管理 API。

- Laravel
- MySQL（開發環境）

## 目錄

1. [安裝與執行](#安裝與執行)
2. [架構](#架構)
3. [資料表](#資料表)
4. [共通規則與權限](#共通規則與權限)
5. [登入與帳號](#登入與帳號)
6. [Dashboard 與統計](#dashboard-與統計)
7. [教師帳號申請](#教師帳號申請)
8. [學生帳號與名冊](#學生帳號與名冊)
9. [教師課程管理](#教師課程管理)
10. [教師教材管理](#教師教材管理)
11. [教材匯入](#教材匯入)
12. [學生教材](#學生教材)
13. [題目與作答](#題目與作答)

---

## 安裝與執行

先啟動 MySQL 並建立資料庫 `php_education`。

```bash
composer install
copy .env.example .env
php artisan key:generate
```

編輯 `.env` 的 `DB_USERNAME`、`DB_PASSWORD`，再執行：

```bash
php artisan migrate
php artisan storage:link
php artisan serve
```

API 網址 `http://127.0.0.1:8000`，前端（`http://localhost:9000`）直接對這個網址打。

開發環境預設 `MAIL_MAILER=log`，信件寫進 `storage/logs/laravel.log`，不會真的寄出；正式環境請改 SMTP。

### 測試帳號

| 身分 | 帳號 | 密碼 |
|------|------|------|
| 管理員 | admin@nutc.edu.tw | Password123! |
| 教師 | teacher@school.edu.tw | Password123! |
| 教師 | teacher2@school.edu.tw | Password123! |
| 學生 | 1411131000 | Password123! |

Seeder 為 `teacher2`（陳老師）建立兩門「網際系統設計」（班級資應、資管），王小明選修資應那門。

---

## 架構

```text
帳號
admins / teachers / students         → 三種身分各自存帳密
teacher_applications                 → 教師申請（核准後寫入 teachers）
student_applications / _items        → 學生申請主單與明細（核准後寫入 students）

課程
courses      → 課程（teacher_id → teachers）
enrollments  → 選課（students ↔ courses）

教材：課程 → 章節 → 單元 → 知識卡
chapters / units / knowledge_cards
knowledge_card_unit                  → 同一張卡可掛多個單元

題目
questions                            → 題目（屬於課程）
question_options                     → 選擇／是非選項
question_sub_answers                 → 填空／除錯／解讀正解
question_knowledge_cards             → 題目 ↔ 知識卡
question_records / question_record_subs → 作答
bloom                                → Bloom 編碼
```

### 目錄

```text
app/
├─ Exceptions/                  自訂例外（登入失敗、帳號未開通等）
├─ Http/
│  ├─ Controllers/Api/V1/
│  │  ├─ AuthController、DashboardController、StatsController
│  │  ├─ Student/               MaterialController、QuestionController
│  │  └─ Teacher/               Course、Question、QuestionRecord、MaterialTemplate、
│  │                            StudentRosterTemplate、MaterialImport、MaterialGraph、
│  │                            EditorImage、Chapter、Unit、KnowledgeCard
│  ├─ Middleware/EnsureRole.php
│  └─ Requests/                 Auth、Course、Material、學生申請
├─ Models/
└─ Services/                    業務邏輯（登入、課程、教材、匯入、題目、學生帳號、Dashboard）

public/templates/
├─ course_template.xlsx          教材匯入範本
├─ student_import_template.xlsx  學生名冊範本
└─ student_account_template.xlsx 開通後寄給教師的學生帳密範本

routes/api.php                   API 路由（/api/v1/...）
database/migrations、seeders
tests/Feature                    API 功能測試
```

| 層級 | 職責 |
|------|------|
| Controllers | 接收 Request、呼叫 Service、回傳 JSON |
| Requests | 驗證欄位 |
| Services | 業務邏輯 |
| Middleware | 角色權限（如僅教師可存取 `/teacher/*`） |

---

## 資料表

所有表都有 `id`（PK）；除 `enrollments` 外都有 `created_at`、`updated_at`，以下不再列出。

### admins

| 欄位 | 說明 |
|------|------|
| account | 登入帳號（unique） |
| password | 加密密碼 |

名稱固定回傳「系統管理員」。

### teachers

| 欄位 | 說明 |
|------|------|
| account | 登入帳號（unique） |
| password | 加密密碼 |
| name | 姓名 |
| email | Email（unique） |

### teacher_applications

| 欄位 | 說明 |
|------|------|
| name / email / account | 姓名、信箱、自訂帳號（信箱與帳號 unique） |
| reason | 申請理由 |
| status | `pending`（預設）／`approved` |

### students

| 欄位 | 說明 |
|------|------|
| password | 加密密碼 |
| student_no | 學號（unique） |
| name | 姓名 |
| class_name | 班級（可空；開通時由申請單寫入） |
| email | 學校信箱（unique），**登入帳號** |

### student_applications

教師幫班級學生申請帳號的主單。

| 欄位 | 說明 |
|------|------|
| tid | 教師（FK，刪教師時一併刪除） |
| course_id | 課程（FK，刪課程時一併刪除） |
| class_name | 班級（取自課程） |
| status | `pending`（預設）／`approved` |

### student_application_items

| 欄位 | 說明 |
|------|------|
| application_id | 主單（FK） |
| student_no | 學號（同一主單內 unique） |
| name | 姓名 |
| email | 信箱（可空；未填時開通用 `s{學號}@nutc.edu.tw`） |
| status | `pending`（預設）／`approved` |

### courses

| 欄位 | 說明 |
|------|------|
| name | 課程名稱 |
| description | 課程介紹 |
| semester | 開課學期 |
| class_name | 開課班級（新開課必填） |
| teacher_id | 授課教師（FK） |

### enrollments

複合主鍵 `student_id` + `course_id`，無時間戳。學生 Dashboard 的「已修課程」由此查詢。

### chapters

| 欄位 | 說明 |
|------|------|
| course_id | 所屬課程（FK） |
| name | 章節名稱 |
| sort_order | 排序（同課程內不可重複） |

### units

| 欄位 | 說明 |
|------|------|
| chapter_id | 所屬章節（FK） |
| name | 單元名稱 |
| sort_order | 排序（同章節內不可重複） |
| status | `draft`（僅教師可見）／`published`。手動新增預設 `draft`，Excel 匯入為 `published` |

### knowledge_cards

| 欄位 | 說明 |
|------|------|
| unit_id | 主要單元（可空） |
| course_id | 所屬課程（可空，匯入時用來比對同一張卡） |
| title | 名稱（Excel `card_name`） |
| type | 類型，如 `keyword`、`function`（Excel `card_type`） |
| content | 內容 HTML（Excel `card_content`） |
| example | 程式範例（Excel `code_example`） |
| sort_order | 排序 |

### knowledge_card_unit

`unit_id` + `knowledge_card_id`（不可重複）。同一張卡可掛多個單元；刪單元只拿掉關聯。

### questions

題目屬於課程，透過知識卡對應教材。

| 欄位 | 說明 |
|------|------|
| course_id / teacher_id | 課程、出題教師（FK） |
| title | 標題 |
| type | `choice`、`true_false`、`fill`、`debug`、`interpret`、`coding` |
| question_content | 題幹（填空用 `（1）`、`（2）` 標格子） |
| bloom_id | 出題 Bloom（可空） |
| description | 要考學生什麼（可空） |
| show_example | 是否給學生看知識卡範例（預設否） |
| starter_code | 實作題已知條件（給學生看） |
| expected_output / reference_answer | 實作題期望輸出、參考答案（學生看不到） |

### bloom

出題用 `B11`–`B63`：第一碼 Bloom 層級 1–6，第二碼 SOLO 1–3。下拉只顯示 Bloom 層級。舊碼 `B1`–`B6` 保留給既有題目。

### question_options

| 欄位 | 說明 |
|------|------|
| question_id | 題目（FK） |
| title / description | 選項文字、說明（可空） |
| is_answer | 是否正解 |
| solo | 正解 `2`、其餘 `1`（自動帶入） |

### question_sub_answers

| 欄位 | 說明 |
|------|------|
| question_id | 題目（FK） |
| sub_id | 填空格子編號（對題幹 `（1）`）或除錯行號 |
| answer / description | 正解、說明（可空） |
| solo | 配分，預設 2 |

### question_knowledge_cards

`question_id` + `knowledge_card_id`（不可重複）。刪題目或刪知識卡時關聯一併刪除，題目本體保留。

### question_records / question_record_subs

- 選擇／是非只寫總表；填空／除錯／解讀另寫每格子表。
- `solo`：選擇／是非錯 `1`、對 `2`；其他題型全錯 `1`、部分對 `2`、全對 `3`（`result` 記 `correct`／`total`）。逐格答對抄正解的 `solo`，答錯為 `1`。實作題由老師給 Bloom 後判定。
- 比對前會把全形英數與標點轉半形並去掉前後空白；資料庫仍存學生原字。

### personal_access_tokens

Laravel Sanctum Token 表，關聯 Admin、Teacher 或 Student。

---

## 共通規則與權限

除登入、忘記密碼、送出教師申請外，所有 API 都要帶：

```text
Authorization: Bearer {token}
```

| 狀態碼 | 說明 |
|--------|------|
| 200 / 201 | 成功 |
| 401 | 未登入或 Token 無效 |
| 403 | 角色不符（例如非教師打 `/teacher/*`） |
| 404 | 資料不存在，或不屬於目前使用者（別人的課、未選的課） |
| 422 | 欄位驗證失敗 |

| 使用者 | Dashboard | 教師申請（送出） | 教師申請（列表／核准） | 學生申請（送出） | 學生開通 | 課程／教材／題目管理 |
|--------|-----------|------------------|------------------------|------------------|----------|----------------------|
| 未登入 | ✗ | ✓ | ✗ | ✗ | ✗ | ✗ |
| 管理員 | ✓ | — | ✓ | ✗ | ✓ | ✗ |
| 教師 | ✓ | — | ✗ | ✓ | ✗ | ✓（只限自己的課） |
| 學生 | ✓ | — | ✗ | ✗ | ✗ | ✗ |

學生只能看自己有選課的課程教材與題目。

---

## 登入與帳號

### POST `/api/v1/auth/login`

不需要傳 `role`，後端依序查 `admins.account` → `teachers.account` → `students.email`，先查到的就決定身分。

```json
{ "account": "teacher@school.edu.tw", "password": "Password123!" }
```

學生 `account` 填學號即可（如 `1411131000`，加不加 `s` 都行），後端組成 `s1411131000@nutc.edu.tw` 比對。成功時 `user.account` 回完整信箱。

成功 **200**：

```json
{
  "token": "...",
  "token_type": "Bearer",
  "user": { "id": 1, "account": "teacher@school.edu.tw", "name": "許老師", "role": "teacher" }
}
```

帳號不存在或密碼錯誤 **401**（`{ "statusCode": 401, "message": "帳號或密碼錯誤" }`）；帳號未開通 **403**。

### POST `/api/v1/auth/logout`

撤銷目前 Token。成功 **200**：`{ "message": "登出成功" }`。

### GET `/api/v1/auth/me`

回目前使用者。教師／管理員含 `id`、`account`、`name`、`role`；學生另含 `student_no`、`class_name`。

```json
{
  "user": {
    "id": 1,
    "account": "s1411131000@nutc.edu.tw",
    "student_no": "1411131000",
    "name": "王小明",
    "class_name": "資應二甲",
    "role": "student"
  }
}
```

### 忘記密碼

不需登入。後端產生 12 碼新密碼、寫入帳號，再寄到信箱。管理員沒有這支 API。

| API | Request | 成功訊息 |
|-----|---------|----------|
| POST `/api/v1/auth/student/forgot-password` | `{ "student_no": "1411131000" }`（可加 `s`） | 已寄送新密碼至學生校園信箱 |
| POST `/api/v1/auth/teacher/forgot-password` | `{ "teacher_account": "teacher@school.edu.tw" }` | 已寄送新密碼至教師校園信箱 |

沒帶欄位或帳號不存在 **422**。

---

## Dashboard 與統計

### GET `/api/v1/dashboard`

| 角色 | 回傳 |
|------|------|
| 教師 | `user` + `courses`（自己開的課，含 `class_name`） |
| 學生 | `user` + `courses`（有選的課，含 `class_name`） |
| 管理員 | `user` + `pending_count`（目前固定 0） |

### GET `/api/v1/stats`

管理員取得數量。`semester_course_count` 以最新的 `semester` 計算。

```json
{ "teacher_count": 2, "student_count": 1, "course_count": 2, "semester_course_count": 2, "semester": "115-1" }
```

---

## 教師帳號申請

### POST `/api/v1/teacher-applications`

公開送出申請，不需登入。四個欄位皆必填；`email`、`account` 不可和已申請或已註冊教師重複。

```json
{ "name": "陳老師", "email": "chen@example.com", "account": "teacher_chen", "reason": "申請教師帳號" }
```

成功 **201**，回 `{ "message": "...", "data": { ...申請資料, "status": "pending" } }`。

### GET `/api/v1/teacher-applications`

管理員取得申請列表，回 `{ "applications": [...] }`。可加 `?status=pending`。

### POST `/api/v1/teacher-applications/{id}/approve`

管理員核准：建立 `teachers` 帳號（帳號為申請時的 `account`），產生 12 碼隨機密碼並寄開通信。**明文密碼只出現在這次回應與信件。**

```json
{
  "message": "Teacher application approved.",
  "data": { "tid": 3, "name": "陳老師", "email": "chen@example.com", "account": "teacher_chen", "password": "xY8zR9wP2qTs" }
}
```

申請不存在 **404**；已處理過 **422**。

---

## 學生帳號與名冊

流程：

1. 教師用 Excel 名冊或手動新增學生到課程（學號必填，姓名、信箱選填，班級取自課程）。
2. 已有帳號的學生直接選課，標為已開通；沒有帳號的進入待開通。
3. 管理員開通時建立 `students`（姓名空白先用學號；信箱未填用 `s{學號}@nutc.edu.tw`）並寫入選課，可整班開通。
4. 有新建帳號時，寄信給申請的教師，附件 `學生帳號名單.xlsx` 列出本次新建學生的姓名／帳號／初始密碼（信件本文不含密碼；工作表保護密碼為教師的 `teachers.account`）。沒有新建帳號就不寄。

### 教師端

| Method | URL | 說明 |
|--------|-----|------|
| GET | `/api/v1/teacher/student-applications/template` | 下載名冊範本 |
| GET | `/api/v1/teacher/students/lookup` | 查詢學生是否已有帳號 |
| POST | `/api/v1/teacher/student-applications` | 上傳 Excel 名冊 |
| GET | `/api/v1/teacher/courses/{courseId}/student-applications` | 該課名冊 |
| POST | `/api/v1/teacher/courses/{courseId}/student-applications` | 新增學生（可多筆） |
| PUT | `/api/v1/teacher/courses/{courseId}/student-applications/{itemId}` | 修改一位學生 |
| DELETE | `/api/v1/teacher/courses/{courseId}/student-applications/{itemId}` | 從名冊移除 |

**名冊範本**：第 1 列說明、第 2 列欄位（`學號`、`姓名`）、第 3 列示範不讀、第 4 列起是名冊。多出的欄位忽略。

**lookup**：`?student_no=1411131000`（可加 `s`）或 `?name=王小明`（精確比對，同名多人時 `student_no` 為 `null`）。

```json
{ "has_account": true, "student_no": "1411131000", "name": "王小明", "matches": [{ "student_no": "1411131000", "name": "王小明" }] }
```

**上傳名冊**（`multipart/form-data`）：`tid`、`course_id`（必須是自己的課）、`file`（xlsx）。成功 **201**：

```json
{ "message": "...", "data": { "id": 1, "tid": 2, "course_id": 1, "class_name": "資應二甲", "status": "pending" } }
```

課程沒填班級、只有示範列、學號重複、該課已有相同學號、超過 100 人 **422**。

**名冊列表**：預設回待開通＋已開通，依學號排序。可加 `?status=pending` 或 `?status=approved`。

**新增學生**：一次最多 100 人。已有帳號的以帳號姓名與信箱為準。成功 **201**，格式同上傳名冊。

```json
{
  "students": [
    { "student_no": "1411131001" },
    { "student_no": "1411131002", "name": "陳小華" },
    { "student_no": "1411131003", "name": "林小美", "email": "lin@example.com" }
  ]
}
```

**修改學生**：`student_no`、`name` 必填，`email` 選填。

- 待開通：更新申請列，開通時沿用。
- 已開通：可改學號與信箱；姓名以正式帳號為準。
- 沒帶 `email` 但改了學號：信箱同步為 `s{新學號}@nutc.edu.tw`。
- 學號或信箱和其他學生衝突 **422**。

成功 **200**：`{ "message": "學生資料已更新", "item": { "id", "student_no", "name", "email", "status", "has_account" } }`。

**移除學生**：待開通則刪除申請列；已開通則取消這門課的選課，帳號保留。之後可再加回。成功 **200**：`{ "message": "已從課程移除" }`。

### 管理員端

| Method | URL | 說明 |
|--------|-----|------|
| GET | `/api/v1/courses` | 開通頁的課程清單（含 `teacher_name`） |
| GET | `/api/v1/student-applications` | 待開通明細，可加 `?course_id=`、`?status=pending`、`?q=`（學號或姓名） |
| POST | `/api/v1/student-applications/approve` | 開通並寫入一門或多門課 |
| POST | `/api/v1/teacher/student-applications/{id}/approve` | 整張申請單全數開通 |

明細每筆含 `id`、`student_no`、`name`、`email`、`application_id`、`class_name`、`status`、`course_id`、`provider_teacher_name`、`has_account`。

**開通**：

```json
{ "source_course_id": 1, "course_ids": [1, 2] }
```

| 欄位 | 說明 |
|------|------|
| source_course_id | 來源課程，自動抓該課全部待開通學生（和 `item_ids` 二擇一） |
| course_ids | 要選課的課程，至少 1 門 |
| item_ids | 舊欄位：手動指定明細 ID |

成功 **200**：`{ "message": "已開通課程。", "activated_count": 2, "created_count": 2, "enrolled_count": 4 }`。分別是處理的學生數、新建帳號數、新增的選課筆數（已選過的不重算）。

---

## 教師課程管理

| Method | URL | 說明 |
|--------|-----|------|
| GET | `/api/v1/teacher/courses` | 自己的課程，依學期由新到舊 |
| POST | `/api/v1/teacher/courses` | 建立課程 |
| GET | `/api/v1/teacher/courses/{id}` | 單一課程 |
| PUT | `/api/v1/teacher/courses/{id}` | 修改課程 |
| DELETE | `/api/v1/teacher/courses/{id}` | 刪除課程，回 `{ "message": "課程已刪除" }` |

建立／修改：`name`、`description`（最多 2000 字）、`semester`、`class_name` 皆必填。`teacher_id` 由 Token 判斷。

建立時可從**自己的**既有課程複製（深拷貝，全部新 ID，不共用）：

```json
{
  "name": "PHP 程式設計",
  "description": "從基礎語法到實作練習",
  "semester": "115-2",
  "class_name": "資應二乙",
  "source_course_id": 12,
  "copy_materials": true,
  "copy_questions": true
}
```

- `copy_materials`：複製章、單元、知識卡。
- `copy_questions`：複製題目、選項、正解，知識卡關聯對到新卡；必須同時 `copy_materials=true`，否則 **422**。
- 不複製學生、作答紀錄與審核資料。在同一個交易內完成，來源課不動。

---

## 教師教材管理

只能操作自己課程的教材。刪除章節／單元／知識卡都是**硬刪**；被刪的卡會一併拿掉題目關聯（題目保留），還掛在其他單元的卡只拿掉這個單元的關聯。

### 章節

| Method | URL | 說明 |
|--------|-----|------|
| GET | `/api/v1/teacher/courses/{courseId}/chapters` | 列表 |
| POST | `/api/v1/teacher/courses/{courseId}/chapters` | 新增 |
| PUT | `/api/v1/teacher/chapters/{chapterId}` | 修改 |
| DELETE | `/api/v1/teacher/chapters/{chapterId}` | 刪除（底下單元與知識卡一起刪） |

Request：`{ "name": "第一章 PHP 簡介", "sort_order": 1 }`。`sort_order` 選填，不傳就接在最後；同課程內重複 **422**。回傳含 `item_count`（單元數）。

### 單元

| Method | URL | 說明 |
|--------|-----|------|
| GET | `/api/v1/teacher/chapters/{chapterId}/units` | 列表 |
| POST | `/api/v1/teacher/chapters/{chapterId}/units` | 新增 |
| PUT | `/api/v1/teacher/units/{unitId}` | 修改 |
| DELETE | `/api/v1/teacher/units/{unitId}` | 刪除（底下知識卡一起刪） |

Request：`name`（必填）、`sort_order`、`status`（`draft`／`published`）。新增時沒帶 `status` 預設 `draft`，學生看不到。回傳含 `status`、`item_count`（知識卡數）。題目不受單元草稿影響。

### 知識卡

| Method | URL | 說明 |
|--------|-----|------|
| GET | `/api/v1/teacher/units/{unitId}/knowledge-cards` | 列表 |
| POST | `/api/v1/teacher/units/{unitId}/knowledge-cards` | 新增（**201**） |
| PUT | `/api/v1/teacher/knowledge-cards/{cardId}` | 修改，立刻給學生看 |
| DELETE | `/api/v1/teacher/knowledge-cards/{cardId}` | 刪除，回 `{ "message": "知識卡已刪除" }` |

```json
{
  "title": "變數",
  "type": "keyword",
  "content": "變數是用來儲存資料的容器，PHP 使用 $ 符號宣告變數。",
  "example": "$name = \"PHP\";",
  "sort_order": 1
}
```

`title`、`content` 必填；`type` 預設 `keyword`。也接受別名 `name`、`code_example`。

### 圖譜與圖片

| Method | URL | 說明 |
|--------|-----|------|
| GET | `/api/v1/teacher/courses/{courseId}/tree` | 整棵教材樹，回 `{ course }` |
| POST | `/api/v1/teacher/upload-image` | 編輯器圖片（欄位 `image`，最大 5MB），回 `{ url }`（**201**） |

樹的知識卡欄位：`title`（別名 `name`）、`type`、`content`、`example`（別名 `code_example`）、`sort_order`；章與單元另有 `title` 別名給 vis-network 用。圖片存在 `storage/app/public/editor_images/`（需 `php artisan storage:link`），網址寫進知識卡 `content` 的 HTML。

---

## 教材匯入

| Method | URL | 說明 |
|--------|-----|------|
| GET | `/api/v1/teacher/materials/template` | 下載範本 `教材匯入範本.xlsx`（任何教師） |
| POST | `/api/v1/teacher/courses/{courseId}/materials/import/preview` | 預覽，不寫資料庫（**200**） |
| POST | `/api/v1/teacher/courses/{courseId}/materials/import` | 匯入，回 `{ course, summary }`（**201**） |

流程：老師選匯入方式並上傳 → 打 preview 顯示結果 → 確認後打 import，帶同一份檔、同樣參數與 preview 回傳的 `fingerprint`。

| mode | 做什麼 |
|------|--------|
| append | 新章節接在最後面，不動既有資料 |
| replace | 只重建選定的一章，其他章不動 |
| overwrite | 整門課換成這份 Excel，沒出現的卡會刪除 |

新單元一律 `published`。替換時，有題目或其他單元在用的卡只移除關聯、不刪。

`multipart/form-data`：

| 欄位 | 必填 | 說明 |
|------|------|------|
| file | 是 | `.xlsx` |
| mode | 課程已有教材時必填 | `append`／`replace`／`overwrite`；沒有教材時不帶等同 overwrite |
| chapter_id | `mode=replace` 時 | 要替換的章節 |
| fingerprint | 否（建議帶） | 只有 import 用 |
| overwrite | 否 | 舊版參數，`true` 等同 `mode=overwrite` |

**Excel 格式**：第 1 列欄位 `chapter_title`、`chapter_order`、`unit_title`、`unit_order`、`card_name`、`card_type`、`card_content`、`code_example`；第 2 列示範不讀，第 3 列起是內容。以 `ex：` 或 `ex:` 開頭的列不讀；章節／單元空白沿用上一列；同名＋同 type 的卡只建一張，可掛多個單元。

**限制**：最多 5000 列；名稱 255 字、`card_type` 50 字、`card_content`／`code_example` 各 65535 bytes。

| 情況 | 狀態 |
|------|------|
| 只有範本示範列 | 422 |
| 已有教材但沒帶 `mode` | 422（`mode`） |
| replace 沒帶 `chapter_id` 或章節不屬於此課 | 422（`chapter_id`） |
| replace 但 Excel 不是剛好一個章節 | 422（`file`） |
| 超過列數、欄位過長 | 422（`file`，訊息含列號） |
| 預覽後教材被改過（`fingerprint` 不符） | 409 |

**preview 回應**：`course`（匯入後的樹，新節點 id 為負數）、`highlights`（`new`／`replace`／`reuse`／`warn`）、`summary`（數量統計）、`removed`（會移除的單元與卡）、`affected_questions`（overwrite 受影響題目）、`duplicate_chapter_names`（append 同名章節）、`fingerprint`。

---

## 學生教材

只回 `published` 單元；整章都是草稿時該章不出現。

| Method | URL | 說明 |
|--------|-----|------|
| GET | `/api/v1/student/courses/{courseId}/graph` | 整棵樹，回 `{ graph }` |
| GET | `/api/v1/student/courses/{courseId}/chapters` | 章節列表 |
| GET | `/api/v1/student/chapters/{chapterId}/units` | 單元列表 |
| GET | `/api/v1/student/units/{unitId}/knowledge-cards` | 知識卡列表（草稿單元 **404**） |

---

## 題目與作答

### 教師出題

| Method | URL | 說明 |
|--------|-----|------|
| GET | `/api/v1/teacher/blooms` | Bloom 對照 |
| GET | `/api/v1/teacher/courses/{courseId}/knowledge-cards` | 出題用知識點（依章節分組、去重） |
| GET | `/api/v1/teacher/courses/{courseId}/questions` | 題目列表（含正解），回 `questions` |
| POST | `/api/v1/teacher/courses/{courseId}/questions` | 新增 |
| GET | `/api/v1/teacher/questions/{questionId}` | 單題，回 `question`（含 `options`、`sub_answers`、`knowledge_cards`） |
| PUT | `/api/v1/teacher/questions/{questionId}` | 整題覆寫 |
| DELETE | `/api/v1/teacher/questions/{questionId}` | 刪題（作答紀錄一併刪除） |

知識卡必須屬於此課，否則 **422**。

共通 Request：

```json
{
  "title": "php註解",
  "type": "choice",
  "question_content": "PHP網頁的多行註解是用哪一個符號？",
  "bloom_id": "B11",
  "description": "可空",
  "show_example": false,
  "knowledge_card_ids": [4, 11]
}
```

`bloom_id` 大小寫皆可。各題型另帶：

**選擇／是非**：`options`，剛好一個 `is_answer: true`（是非剛好 2 個）。不用傳 `solo`。

```json
{ "options": [{ "title": "/* */", "is_answer": true }, { "title": "//", "is_answer": false }] }
```

**填空**：`sub_answers`，`sub_id` 對題幹 `（1）（2）`。

```json
{ "sub_answers": [{ "sub_id": 1, "answer": "define" }, { "sub_id": 2, "answer": "PI" }] }
```

**除錯**：題幹放完整的錯誤程式，`sub_answers` **只填有錯的行**（`sub_id` 行號、`answer` 修正後那行、`description` 錯誤原因）。

```json
{
  "type": "debug",
  "question_content": "請找出錯誤並修正。\n<?php\n$name = \"Tom\"\necho $name;",
  "sub_answers": [{ "sub_id": 2, "answer": "$name = \"Tom\";", "description": "PHP 敘述結尾缺少分號 ;" }]
}
```

**解讀**：提問與程式碼用 `<!--code-stem-->` 分開，`sub_answers` 為預期輸出（`description` 交卷後才給學生看），可多個小題。

```json
{
  "type": "interpret",
  "question_content": "請解讀以下 PHP 程式，說明最後會輸出什麼。\n<!--code-stem-->\n$a = 5;\n$b = 10;\nif ($a < $b) {\n    echo \"A\";\n}",
  "sub_answers": [{ "sub_id": 1, "answer": "A", "description": "$a 為 5、$b 為 10，條件成立所以輸出 A。" }]
}
```

**實作**：不自動比對，也沒有 AI 批改，由老師覆核時給 Bloom。

```json
{
  "type": "coding",
  "question_content": "請使用 PHP 撰寫程式，將兩個數字相加後輸出結果。",
  "starter_code": "$a = 10;\n$b = 20;",
  "expected_output": "30",
  "reference_answer": "$a = 10;\n$b = 20;\n$result = $a + $b;\necho $result;"
}
```

### 學生作答

| Method | URL | 說明 |
|--------|-----|------|
| GET | `/api/v1/student/courses/{courseId}/questions` | 題目列表（可加 `knowledge_card_id`） |
| GET | `/api/v1/student/questions/{questionId}` | 單題 |
| POST | `/api/v1/student/questions/{questionId}/submit` | 交卷 |
| GET | `/api/v1/student/courses/{courseId}/question-records` | 自己的作答紀錄（可加 `question_id`） |
| GET | `/api/v1/student/question-records/{recordId}` | 單筆作答紀錄 |

學生看不到：正解、選項 `description`（交卷後以 `explanation` 回傳）、`solo`、`expected_output`、`reference_answer`。知識卡範例只有 `show_example: true` 時以 `examples` 回傳。

交卷格式：

| 題型 | Request |
|------|---------|
| 選擇／是非 | `{ "option_id": 1 }` |
| 填空／解讀 | `{ "answers": { "1": "define", "2": "PI" } }` |
| 除錯 | `{ "code_line": 2, "answer": "$name = \"Tom\";" }`（多個錯誤用 `answers`；取題只回 `debug_error_count`） |
| 實作 | `{ "code": "..." }`，`system_status` 為 `pending`，等老師給 Bloom |

作答紀錄回 `{ "records": [...] }` 或 `{ "record": {...} }`，含題目標題／類型、`result`、`solo`、`bloom_id`、`system_status`、`teacher_status`、`subs`、`created_at`。

### 教師覆核

| Method | URL | 說明 |
|--------|-----|------|
| GET | `/api/v1/teacher/courses/{courseId}/question-records` | 該課學生作答（含 `subs`） |
| PUT | `/api/v1/teacher/question-records/{recordId}` | 實作題帶 `bloom_id`；其他題型帶 `solo`（1 錯／2 對） |

實作題比對 Bloom 第一碼：老師給的 ≥ 出題 Bloom 為對（`solo=2`），否則錯（`solo=1`）。
