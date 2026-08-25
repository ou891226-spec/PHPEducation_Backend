# PHPEducation Backend

系統的登入、課程、教材與權限管理功能

## 使用技術

- Laravel
- mysql（開發環境）

---

## 架構說明

```text
基本資料
admins     → 管理員
teachers   → 教師
students   → 學生（以學校信箱 email 作為登入帳號，student_no 為學號，含 class_name 班級）
teacher_applications → 教師帳號申請（核准後寫入 teachers）
student_applications → 學生帳號申請（tid → teachers, course_id → courses）
student_application_items → 學生申請明細（一筆申請多位學生，核准後寫入 students）

courses    → 課程（teacher_id → teachers）
enrollments → 選課（students ↔ courses）

教材部分
material_drafts → 匯入草稿（一門課可有多份；`name` 為教材名稱）
topics          → 主題（course_id → courses）
chapters        → 章節（topic_id → topics）
units           → 單元（chapter_id → chapters）
knowledge_cards → 知識卡（unit_id → units）
questions       → 題目（course_id → courses，teacher_id → teachers）
question_knowledge_cards → 題目 ↔ 知識卡
```

三者各自獨立儲存帳號與密碼，由 `AuthService` 依固定順序查詢並判斷 `role`。

課程由教師建立並擁有（`courses.teacher_id`）；學生透過 `enrollments` 與課程形成多對多關聯。

教材層級：教師 → 課程 → 主題 → 章節 → 單元 → 知識卡。
主題名稱由匯入表單一次填寫；Excel 只含章節以下。表單主題 + Excel → Parser → `material_drafts.tree` → 前端畫樹編輯 → 發布 → 正式教材表。

---

## 目錄架構

```text
app/
│
├─ Exceptions/
│  └─ 自訂例外（登入失敗、帳號未開通等）
│
├─ Http/
│  ├─ Controllers/Api/V1/
│  │  ├─ AuthController.php
│  │  ├─ DashboardController.php
│  │  ├─ Student/
│  │  │  └─ MaterialController.php
│  │  └─ Teacher/
│  │     ├─ CourseController.php
│  │     ├─ MaterialTemplateController.php
│  │     ├─ MaterialImportController.php
│  │     ├─ MaterialDraftController.php
│  │     ├─ TopicController.php
│  │     ├─ ChapterController.php
│  │     ├─ UnitController.php
│  │     └─ KnowledgeCardController.php
│  ├─ Middleware/
│  │  └─ EnsureRole.php
│  └─ Requests/
│     ├─ Auth/
│     ├─ Course/
│     └─ Material/
│
├─ Models/
│  └─ Admin、Teacher、Student、Course、Enrollment、
│     MaterialDraft、Topic、Chapter、Unit、KnowledgeCard
│
├─ Providers/
│  └─ 服務提供者
│
└─ Services/
   ├─ AuthService.php
   ├─ CourseService.php
   ├─ MaterialService.php
   ├─ ExcelMaterialParser.php
   ├─ MaterialDraftService.php
   ├─ StudentMaterialService.php
   ├─ DashboardService.php
   └─ UserFormatterService.php

bootstrap/          → 應用程式啟動與 Middleware 註冊
config/             → 設定檔（auth、cors、sanctum、database 等）
public/templates/
└─ material_import_template.xlsx  → 教師教材匯入 Excel 範本
database/
├─ migrations/      → 資料表結構
└─ seeders/         → 測試帳號與示範資料
routes/
├─ api.php          → API 路由（/api/v1/...）
└─ web.php
tests/
└─ Feature/         → API 功能測試
```

### 各層職責

| 層級 | 職責 |
|------|------|
| `Controllers` | 接收 Request、呼叫 Service、回傳 JSON |
| `Requests` | 驗證 Request 欄位（如登入、建立／更新課程） |
| `Services` | 業務邏輯（登入查詢順序、Dashboard、課程 CRUD） |
| `Models` | Eloquent 模型與資料關聯 |
| `Middleware` | 角色權限檢查（如僅教師可存取 `/teacher/*`） |
| `routes/api.php` | 定義 API 路徑與 Middleware 群組 |
| `database/migrations` | 資料表結構定義 |
| `database/seeders` | 測試帳號與示範課程 |

---

## 資料表
### admins 系統管理員

| 欄位 | 型別 | 說明 |
|------|------|------|
| id | bigint | 管理員 ID（PK） |
| account | string | 登入帳號（unique） |
| password | string | 加密後的密碼 |

固定 "name": "系統管理員"

### teachers 教師資料

| 欄位 | 型別 | 說明 |
|------|------|------|
| id | bigint | 教師 ID（PK） |
| account | string | 登入帳號（unique） |
| password | string | 加密後的密碼 |
| name | string | 教師姓名 |
| email | string | Email（unique） |
| created_at | timestamp | 建立時間 |
| updated_at | timestamp | 更新時間 |

### teacher_applications 教師帳號申請

申請人填表後寫入此表；管理員核准後才建立 `teachers` 帳號。

| 欄位 | 型別 | 說明 |
|------|------|------|
| id | bigint | 申請 ID（PK） |
| name | string | 教師姓名 |
| email | string | 教師信箱（unique） |
| account | string | 教師自訂帳號（unique） |
| reason | string | 申請理由 |
| status | string | 申請狀態：`pending`（待審核）、`approved`（已通過），預設 `pending` |
| created_at | timestamp | 建立時間 |
| updated_at | timestamp | 更新時間 |

```text
教師申請（pending）──核准──▶ teachers
```

### students 學生資料

| 欄位 | 型別 | 說明 |
|------|------|------|
| id | bigint | 學生 ID（PK） |
| password | string | 加密後的密碼 |
| student_no | string | 學號（unique） |
| name | string | 學生姓名 |
| class_name | string | 現屬班級（可為空；開通帳號時由申請單的 `class_name` 寫入） |
| email | string | 學校信箱（unique），**作為登入帳號** |
| created_at | timestamp | 建立時間 |
| updated_at | timestamp | 更新時間 |

> 學生登入時，前端 Request 的 `account` 填學號（可含前綴 `s`），例如 `s1411131000`。後端組成並比對 `students.email`（`s1411131000@nutc.edu.tw`）。學號存在 `student_no`。若開通時只填學號，系統組成 `s{學號}@nutc.edu.tw`。

### student_applications 學生帳號申請

由**已核准的教師**幫班級學生申請帳號。
老師交一份班級名單，上面可以有很多學生。

| 欄位 | 型別 | 說明 |
|------|------|------|
| id | bigint | 申請 ID（PK） |
| tid | bigint | 教師 ID（FK → teachers.id，刪除教師時一併刪除） |
| course_id | bigint | 課程 ID（規格的 Cid；FK → courses.id，刪除課程時一併刪除） |
| class_name | string | 申請班級 |
| status | string | 申請狀態：`pending`（待審核）、`approved`（已通過），預設 `pending` |
| created_at | timestamp | 建立時間 |
| updated_at | timestamp | 更新時間 |

### student_application_items 學生申請明細

一筆申請底下可有多位學生。

| 欄位 | 型別 | 說明 |
|------|------|------|
| id | bigint | 明細 ID（PK） |
| application_id | bigint | 申請 ID（FK → student_applications.id） |
| student_no | string | 學生學號 |
| name | string | 學生姓名 |
| status | string | 審核狀態：`pending`（待審核）、`approved`（已開通），預設 `pending` |
| created_at | timestamp | 建立時間 |
| updated_at | timestamp | 更新時間 |

明細**沒有 email**。開通時後端用學號組成 `s{學號}@nutc.edu.tw` 寫入 `students.email`。

同一申請內 `student_no` 不可重複（`application_id` + `student_no` unique）。

```text
teachers.id / courses.id
    │
    └─ student_applications.id（主單：班級與課程）
            │
            └─ student_application_items（多位學生：學號與姓名）
                    │
                    └─ 核准後寫入 students（由學號自動產生校園信箱）
```

### courses 課程資料

| 欄位 | 型別 | 說明 |
|------|------|------|
| id | bigint | 課程 ID（PK） |
| name | string | 課程名稱 |
| description | text | 課程介紹 |
| semester | string | 開課學期 |
| teacher_id | bigint | 授課教師 ID（FK → teachers.id） |
| created_at | timestamp | 建立時間 |
| updated_at | timestamp | 更新時間 |

### enrollments 學生選課資料
複合主鍵，無 `timestamps`

| 欄位 | 型別 | 說明 |
|------|------|------|
| student_id | bigint | 學生 ID（FK → students.id） |
| course_id | bigint | 課程 ID（FK → courses.id） |

資料關聯：

```text
students ── enrollments ── courses
```

學生 Dashboard 的「已修課程」透過 `enrollments` 關聯查詢（`Student` ↔ `Course` many-to-many）。
Seeder 已讓王小明選修「網際系統設計 (資應)」。

### material_drafts 教材匯入草稿
一門課可有多份草稿（用 `id` 區分）。同一門課同時只能有一份 `published` Draft 列（舊的改 `archived`），但**正式教材可以同時有多個主題**。發布只同步這份 Draft 裡的主題，不會砍掉其他已發布主題。學生看正式表（`topics` 等），不是看 Draft 列。

| 欄位 | 型別 | 說明 |
|------|------|------|
| id | bigint | 草稿 ID（PK） |
| course_id | bigint | 所屬課程 ID（FK → courses.id） |
| teacher_id | bigint | 匯入教師 ID（FK → teachers.id） |
| name | string | 教材名稱。Excel 匯入時不可與該課**未發布**草稿同名；已發布／已封存／已刪空的名稱可重用 |
| status | string | `draft`、`published` 或 `archived` |
| tree | json | 主題／章節／單元／知識卡樹 |
| created_at | timestamp | 建立時間 |
| updated_at | timestamp | 更新時間 |

### topics 主題資料
一門課程底下可有多個主題

| 欄位 | 型別 | 說明 |
|------|------|------|
| id | bigint | 主題 ID（PK） |
| course_id | bigint | 所屬課程 ID（FK → courses.id） |
| name | string | 主題名稱 |
| sort_order | integer | 排序順序 |
| created_at | timestamp | 建立時間 |
| updated_at | timestamp | 更新時間 |

例如：PHP 程式設計 → PHP 基礎、PHP 語法、PHP 資料庫。

### chapters 章節資料
一個主題底下可有多個章節

| 欄位 | 型別 | 說明 |
|------|------|------|
| id | bigint | 章節 ID（PK） |
| topic_id | bigint | 所屬主題 ID（FK → topics.id） |
| name | string | 章節名稱 |
| sort_order | integer | 排序順序 |
| created_at | timestamp | 建立時間 |
| updated_at | timestamp | 更新時間 |

例如：主題「PHP 基礎」→ 第一章 PHP 簡介、第二章 PHP 環境、第三章 PHP 基本語法。

### units 單元資料
一個章節底下可有多個單元

| 欄位 | 型別 | 說明 |
|------|------|------|
| id | bigint | 單元 ID（PK） |
| chapter_id | bigint | 所屬章節 ID（FK → chapters.id） |
| name | string | 單元名稱 |
| sort_order | integer | 排序順序 |
| created_at | timestamp | 建立時間 |
| updated_at | timestamp | 更新時間 |

例如：第一章 PHP 簡介 → 1-1 PHP 是什麼、1-2 PHP 的特色、1-3 PHP 的應用。

### knowledge_cards 知識卡資料
一個單元底下可有多張知識卡

| 欄位 | 型別 | 說明 |
|------|------|------|
| id | bigint | 知識卡 ID（PK） |
| unit_id | bigint（可空） | 所屬單元 ID（FK → units.id）。有題目使用而從教材樹刪除時改為空，知識卡列會保留 |
| title | string | 知識卡標題 |
| content | text | 知識卡內容 |
| example | text | 知識卡範例（可空） |
| sort_order | integer | 排序順序 |
| created_at | timestamp | 建立時間 |
| updated_at | timestamp | 更新時間 |

例如：單元「1-1 PHP 是什麼」→ PHP 的定義、PHP 的用途、PHP 的特色。

教材資料關聯：

```text
courses → topics → chapters → units → knowledge_cards
courses → questions
questions ↔ knowledge_cards（question_knowledge_cards）
```

畫面採一層一層點進去（鑽層）：課程 → 主題 → 章節 → 單元 → 知識卡。  
列表的 `item_count` 代表下一層有幾筆（對應畫面上的「N 項」）。知識卡沒有下一層，回傳 `title`、`content`、`example`。刪除主題／章節／單元時，沒有題目使用的知識卡會一併刪除；**已掛在 `question_knowledge_cards` 的知識卡會保留**（`unit_id` 改為空，脫離教材樹），題目關聯不斷。直接刪單張知識卡若已有題目使用會 **422**。

### questions 題目資料

題目屬於課程，不掛在單元上。與教材的對應透過知識卡。

| 欄位 | 型別 | 說明 |
|------|------|------|
| id | bigint | 題目編號（PK） |
| course_id | bigint | 所屬課程 ID（FK → courses.id） |
| teacher_id | bigint | 建立教師 ID（FK → teachers.id） |
| title | string | 題目標題 |
| type | string | `choice`、`debug` 或 `coding` |
| question_content | text | 題目內容 |
| created_at | timestamp | 建立時間 |
| updated_at | timestamp | 更新時間 |

### question_knowledge_cards 題目與知識卡

一題可對多張知識卡；一張知識卡也可被多題使用。關聯的知識卡應屬於同一門課。

| 欄位 | 型別 | 說明 |
|------|------|------|
| id | bigint | 流水號（PK） |
| question_id | bigint | 題目編號（FK → questions.id） |
| knowledge_card_id | bigint | 知識卡編號（FK → knowledge_cards.id） |
| created_at | timestamp | 建立時間 |
| updated_at | timestamp | 更新時間 |

同一題對同一張知識卡不可重複（`question_id` + `knowledge_card_id` unique）。刪題目時，對應關聯會一併刪除。刪知識卡時若仍有題目使用則拒絕；從教材樹刪除主題／章節／單元時，有題目使用的知識卡會保留（關聯不斷）。

### personal_access_tokens

Laravel Sanctum 預設 Token 資料表（表名固定為 `personal_access_tokens`）。

| 欄位 | 說明 |
|------|------|
| id | PK |
| tokenable_type / tokenable_id | 關聯至 Admin、Teacher 或 Student |
| name | Token 名稱 |
| token | Token 雜湊值 |
| abilities | 權限（nullable） |
| last_used_at | 最後使用時間 |
| expires_at | 過期時間 |
| created_at / updated_at | 時間戳 |

---

## 登入

### POST `/api/v1/auth/login`

使用者只需要提供帳號與密碼，後端會自動判斷使用者身分。

**查詢順序：**

1. `admins.account`
2. `teachers.account`
3. `students.email`

因此前端**不需要傳送 role**。

**ps：** 若同一字串同時存在於多張表（例如 `admins` 與 `teachers` 有相同 account），後端依上述順序**先查到的表**判定身分；找到即停止，不再往下查。

#### Request

```json
{
  "account": "teacher@school.edu.tw",
  "password": "Password123!"
}
```

學生登入時 `account` 填學號即可，不必填完整信箱：

```json
{
  "account": "s1411131000",
  "password": "Password123!"
}
```

後端會組成 `s1411131000@nutc.edu.tw` 去對 `students.email`。成功時 `user.account` 仍回傳完整信箱。

| 狀態碼 | 說明 |
|--------|------|
| 200 | 登入成功 |
| 401 | 帳號不存在或密碼錯誤 |
| 403 | 帳號尚未開通 |

#### 成功回應 200

```json
{
  "token": "...",
  "token_type": "Bearer",
  "user": {
    "id": 1,
    "account": "teacher@school.edu.tw",
    "name": "許老師",
    "role": "teacher"
  }
}
```

#### 登入失敗 401

```json
{
  "statusCode": 401,
  "message": "帳號或密碼錯誤"
}
```

HTTP Status 與 body 中的 `statusCode` 一致。

---

## 登出

### POST `/api/v1/auth/logout`

功能：登出目前登入的使用者，撤銷目前的 Sanctum Token。

需要：

```text
Authorization: Bearer {token}
```

| 狀態碼 | 說明 |
|--------|------|
| 200 | 登出成功 |
| 401 | 未帶 Token 或 Token 無效 |

#### 成功回應 200

```json
{
  "message": "登出成功"
}
```

---

## 教師帳號申請

資料表 `teacher_applications` 已建立。

```text
POST /teacher-applications
GET  /teacher-applications
POST /teacher-applications/{id}/approve
```

### POST `/api/v1/teacher-applications`

功能：公開提交教師帳號申請。**不需要登入**。

Request：

```json
{
  "name": "陳老師",
  "email": "chen@example.com",
  "account": "teacher_chen",
  "reason": "申請教師帳號"
}
```

| 欄位 | 必填 | 說明 |
|------|------|------|
| name | ✓ | 教師姓名 |
| email | ✓ | 信箱（不可與已申請或已註冊教師重複） |
| account | ✓ | 教師自訂帳號（不可與已申請或已註冊教師重複） |
| reason | ✓ | 申請理由 |

成功回應 **201**：

```json
{
  "message": "Teacher application submitted successfully.",
  "data": {
    "id": 1,
    "name": "陳老師",
    "email": "chen@example.com",
    "account": "teacher_chen",
    "reason": "申請教師帳號",
    "status": "pending"
  }
}
```

| 狀態碼 | 說明 |
|--------|------|
| 201 | 申請成功，狀態為 `pending` |
| 422 | 欄位錯誤，或帳號／信箱已被申請／已是教師 |

### GET `/api/v1/teacher-applications`

功能：管理員取得申請列表（使用者管理頁「教師申請核准」）。

需要：

```text
Authorization: Bearer {token}
```

僅限管理員。可加 `?status=pending` 只看待審核。
成功回應 **200**：

```json
{
  "applications": [
    {
      "id": 1,
      "name": "陳老師",
      "email": "chen@example.com",
      "account": "teacher_chen",
      "reason": "申請教師帳號",
      "status": "pending"
    }
  ]
}
```

### POST `/api/v1/teacher-applications/{id}/approve`

功能：管理員核准申請。核准後建立 `teachers` 帳號，申請 `status` 改為 `approved`，並寄發開通通知信（含帳號與初始密碼）。

需要管理員 Token。

**帳號：** 使用教師申請時填寫之自訂帳號 `account`。

**密碼：** 系統隨機生成 12 碼，以 `password => hashed` cast 存進 `teachers.password`，**明文只在此次回應與信件發送一次**。

成功回應 **200**：

```json
{
  "message": "Teacher application approved.",
  "data": {
    "tid": 3,
    "name": "陳老師",
    "email": "chen@example.com",
    "account": "teacher_chen",
    "password": "xY8zR9wP2qTs"
  }
}
```

`tid` 為新建 `teachers.id`。

| 狀態碼 | 說明 |
|--------|------|
| 200 | 核准成功 |
| 401 | 未登入 |
| 403 | 非管理員 |
| 404 | 申請不存在 |
| 422 | 已處理完畢，或無法建立帳號 |

前端使用者管理有「拒絕」，目前資料表沒有 `rejected` 狀態，拒絕 API 尚未定義。

---

## 學生帳號申請

資料表 `student_applications`、`student_application_items` 已建立。

```text
POST /teacher/student-applications
GET  /courses
GET  /teacher/courses/{courseId}/student-applications
GET  /student-applications
POST /student-applications/approve
POST /teacher/student-applications/{id}/approve
```

流程：教師送出班級與多名學生（body 帶 `tid`、`course_id`，學生名冊僅需學號與姓名）→ 管理員審核開通。沒帳號就建 `students`（以學號自動產生校園信箱）並寫 `enrollments`；已有帳號只寫選課。可勾選指定學生開通或整單一鍵開通。

### POST `/api/v1/teacher/student-applications`

功能：教師送出班級學生帳號申請名冊。**不需要提供學生信箱**。

Request：

```json
{
  "tid": 2,
  "course_id": 1,
  "class_name": "資應二甲",
  "students": [
    {
      "student_no": "1411131001",
      "name": "李小華"
    },
    {
      "student_no": "1411131002",
      "name": "張小明"
    }
  ]
}
```

| 欄位 | 必填 | 說明 |
|------|------|------|
| tid | ✓ | 教師 ID（FK → teachers.id） |
| course_id | ✓ | 課程 ID（FK → courses.id，必須為該教師所開課程） |
| class_name | ✓ | 班級名稱 |
| students | ✓ | 學生名冊陣列（1～50 筆） |
| students.*.student_no | ✓ | 學生學號 |
| students.*.name | ✓ | 學生姓名 |

成功回應 **201**：

```json
{
  "message": "Student account application submitted successfully.",
  "data": {
    "id": 1,
    "tid": 2,
    "course_id": 1,
    "class_name": "資應二甲",
    "status": "pending"
  }
}
```

### GET `/api/v1/courses`

管理員開通頁的課程下拉。需要管理員 Token。

### GET `/api/v1/teacher/courses/{courseId}/student-applications`

該課教師取得這門課的待開通名單（每人一列）。預設只回 `pending`，可加 `?status=approved`。別人的課 **404**。

### GET `/api/v1/student-applications`

管理員取得待開通／申請明細（每人一列）。需要管理員 Token。可加 `?course_id=`、`?status=pending`、`?q=`（學號或姓名）。

成功回應 **200**：

```json
{
  "items": [
    {
      "id": 1,
      "student_no": "1411131001",
      "name": "李小華",
      "email": "s1411131001@nutc.edu.tw",
      "application_id": 1,
      "class_name": "資應二甲",
      "status": "pending",
      "course_id": 1,
      "provider_teacher_name": "陳老師",
      "has_account": false
    }
  ]
}
```

### POST `/api/v1/student-applications/approve`

功能：管理員開通勾選的學生。需要管理員 Token。

Request：

```json
{
  "course_id": 1,
  "item_ids": [1, 2]
}
```

| 欄位 | 必填 | 說明 |
|------|------|------|
| course_id | ✓ | 課程 ID |
| item_ids | ✓ | 欲開通之學生明細 ID 陣列（`student_application_items.id`） |

成功回應 **200**：

```json
{
  "message": "已開通學生。",
  "activated_count": 2,
  "created_count": 2,
  "enrolled_count": 2
}
```

非管理員 **403**。未登入 **401**。

### POST `/api/v1/teacher/student-applications/{id}/approve`

功能：管理員將整張申請單一次全數審核開通。需要管理員 Token。

成功回應 **200**：

```json
{
  "message": "Student account application approved.",
  "data": {
    "application_id": 1,
    "activated_count": 2
  }
}
```

非管理員 **403**。未登入 **401**。

### GET `/api/v1/stats`

管理員取得老師、學生、課程數量。需要管理員 Token。

`semester_course_count` 以資料裡最新的 `semester` 計算（例如 `115-1`）。

成功回應 **200**：

```json
{
  "teacher_count": 2,
  "student_count": 1,
  "course_count": 2,
  "semester_course_count": 2,
  "semester": "115-1"
}
```

非管理員 **403**。未登入 **401**。

登入時學生只填學號（例如 `s1411131000`），後端對應學校信箱 `s{學號}@nutc.edu.tw`（`students.email`）。

---

## 目前使用者

### GET `/api/v1/auth/me`

功能：依 Token 查詢目前登入的使用者資料。教師／管理員為 id、account、name、role；學生另含 `student_no`、`class_name`。

需要：

```text
Authorization: Bearer {token}
```

| 狀態碼 | 說明 |
|--------|------|
| 200 | 成功 |
| 401 | 未帶 Token 或 Token 無效 |

#### 成功回應 200

```json
{
  "user": {
    "id": 1,
    "account": "teacher@school.edu.tw",
    "name": "許老師",
    "role": "teacher"
  }
}
```

學生：

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

---

## Dashboard

### GET `/api/v1/dashboard`

功能：取得目前登入使用者的基本資料及相關課程。

需要：

```text
Authorization: Bearer {token}
```

| 狀態碼 | 說明 |
|--------|------|
| 200 | 成功 |
| 401 | 未帶 Token 或 Token 無效 |

依角色回傳不同內容：

| 角色 | 回傳 | 資料來源 |
|------|------|----------|
| 教師 | `user` + `courses` | `courses.teacher_id = 目前教師 id` |
| 學生 | `user` + `courses` | `enrollments` 關聯（student_id ↔ course_id） |
| 管理員 | `user` + `pending_count` | `pending_count` 目前固定為 0 |

---

## 教師課程管理

以下 API 僅限教師使用。

需要：

```text
Authorization: Bearer {token}
```

| 狀態碼 | 說明 |
|--------|------|
| 200 / 201 | 成功 |
| 401 | 未登入 |
| 403 | 非教師角色 |
| 404 | 課程不存在或不屬於目前教師 |
| 422 | Request 欄位驗證失敗 |

### GET `/api/v1/teacher/courses`

功能：取得目前教師自己的課程列表。依開課學期由新到舊排序

### POST `/api/v1/teacher/courses`

功能：建立課程。

Request：

```json
{
  "name": "PHP 程式設計",
  "description": "從基礎語法到實作練習",
  "semester": "115-1"
}
```

`description` 為必填，最多 2000 字。  
`teacher_id` 不需要由前端傳送，後端會從登入 Token 判斷目前教師。

成功回應 **201**。

### GET `/api/v1/teacher/courses/{id}`

功能：取得指定課程資料。只能查看自己的課程。

### PUT `/api/v1/teacher/courses/{id}`

功能：修改指定課程。只能修改自己的課程。

Request：

```json
{
  "name": "PHP 進階",
  "description": "進階主題與專案實作",
  "semester": "115-1"
}
```

`description` 為必填，最多 2000 字。

### DELETE `/api/v1/teacher/courses/{id}`

功能：刪除指定課程。只能刪除自己的課程。

成功回應 200：

```json
{
  "message": "課程已刪除"
}
```

---

## 教師教材管理

以下 API 除「下載 Excel 範本」外，僅限教師操作**自己課程**底下的教材。

需要：

```text
Authorization: Bearer {token}
```

| 狀態碼 | 說明 |
|--------|------|
| 200 / 201 | 成功 |
| 401 | 未登入 |
| 403 | 非教師角色，或不是該課教師 |
| 404 | 資料不存在或不屬於目前教師 |
| 422 | Request 欄位驗證失敗 |

### 教材匯入流程（權限）

範本是系統給所有教師的固定檔，**不綁某一門課**。課程權限放在「匯入／草稿／發布」。

| 功能 | API | 權限 |
|------|-----|------|
| 下載 Excel 範本 | `GET /api/v1/teacher/materials/template` | 教師 |
| 匯入教材 | `POST /api/v1/teacher/courses/{courseId}/materials/import` | 該課教師 |
| 查看草稿 | `GET /api/v1/teacher/courses/{courseId}/material-drafts` | 該課教師 |
| 從已發布教材開新 Draft | `POST /api/v1/teacher/courses/{courseId}/material-drafts` | 該課教師 |
| 編輯草稿 | `/api/v1/teacher/material-drafts/{draftId}/...` | 該課教師 |
| 發布教材 | `POST /api/v1/teacher/material-drafts/{draftId}/publish` | 該課教師 |
| 學生查看正式教材 | `/api/v1/student/courses/{courseId}/topics` 等 | 修課學生 |

第一版流程：

```text
前端填主題名稱 + 教師 Excel
  → Laravel 接收、驗證 topic / file、確認是該課教師
  → ExcelMaterialParser 解析（主題用表單欄位；欄位列的下一列範本整列不讀，「範例」欄寫進知識卡 example）
  → 整份 Excel 掛在同一個 Topic 下，組成 Chapter → Unit → Knowledge Card
  → 教材名稱與該課未發布 Draft 重複則 422
  → 存成 Material Draft
  → API 回傳 Draft JSON
  → 前端 Vue 畫樹（不要在瀏覽器解析 Excel）
  → 教師增刪改，打 Draft API 存回
  → 發布
  → 只同步這份 Draft 的主題到正式教材（其他主題不動）
  → 已選課學生查看正式教材

正式教材之後：
  少量修改 → 對單一主題「加入草稿編輯」（POST 帶 `topic_id`）→ 改完再發布
  不帶 `topic_id` 則複製該課全部已發布主題
  整份 Excel 再上傳 → 另建 Draft（教材名稱只與未發布 Draft 重複才 422）
  發布新 Draft → 舊 published 列改 archived；正式表做主題級合併，不整課刪掉重建
```

一份 Excel 只對應一個主題，主題名稱來自表單 `topic`，不從 Excel 讀。空白的 chapter／unit 會沿用上一列。Excel 列規則：第 1 列可寫 `教材名稱：…`、第 2 列說明、第 3 列欄位標題（`chapters (章節)` / `units (單元)` / `knowledge_card (知識卡)` / `範例`）、第 4 列範本示範整列不讀、第 5 列起才是教師內容。「範例」欄存成知識卡 `example`，可空。知識卡沒有獨立標題欄，標題取內容前約 80 字。有寫 `教材名稱：…` 就當教材名稱；沒寫則用表單的主題名稱。

整份 Excel 再上傳期間，學生仍看已發布的正式教材，直到第 2 份也發布。從已發布教材開新 Draft 修改時，學生也仍看上一版，直到新 Draft 發布。第二份若是**不同主題**，發布後兩個主題會並存；若是**同名主題**，只更新那一棵樹，知識卡 id 會盡量保留（避免 `question_knowledge_cards` 斷掉）。草稿列表、已發布主題列表皆**新到舊**，並回傳 `created_at` / `updated_at`。草稿最後一個主題刪掉時，整份 Draft 會一併刪除，教材名稱可重用。

### 教材匯入範本

檔案位置（專案內固定這一份，下載 API 直接讀這個檔）：

```text
public/templates/material_import_template.xlsx
```

老師下載後檔名會顯示為 `教材匯入範本.xlsx`。範本**沒有主題欄**。

| Method | URL | 說明 |
|--------|-----|------|
| GET | `/api/v1/teacher/materials/template` | 下載 Excel 匯入範本 |

任何已登入教師皆可下載。學生會得到 403。

### 匯入、草稿、發布

`multipart/form-data`：

| 欄位 | 必填 | 說明 |
|------|------|------|
| topic | 是 | 主題名稱。一份檔只掛這一個主題 |
| file | 是 | `.xlsx`，最大 10MB |

沒帶 `topic`（空字串也不行）會 **422**。副檔名必須是 `.xlsx`。

| Method | URL | 說明 |
|--------|-----|------|
| POST | `/api/v1/teacher/courses/{courseId}/materials/import` | 後端解析 Excel，新增一份草稿（前端不要自己解析） |
| GET | `/api/v1/teacher/courses/{courseId}/material-drafts` | 列出該課所有草稿 |
| POST | `/api/v1/teacher/courses/{courseId}/material-drafts` | 從正式教材複製一份新 Draft；可帶 `topic_id` 只複製那一個主題 |
| POST | `/api/v1/teacher/material-drafts/{draftId}/topics` | 草稿新增主題 |
| PUT | `/api/v1/teacher/material-drafts/{draftId}/topics/{nodeId}` | 草稿修改主題 |
| DELETE | `/api/v1/teacher/material-drafts/{draftId}/topics/{nodeId}` | 草稿刪除主題 |
| POST | `/api/v1/teacher/material-drafts/{draftId}/topics/{topicId}/chapters` | 草稿新增章節 |
| PUT | `/api/v1/teacher/material-drafts/{draftId}/chapters/{nodeId}` | 草稿修改章節 |
| DELETE | `/api/v1/teacher/material-drafts/{draftId}/chapters/{nodeId}` | 草稿刪除章節 |
| POST | `/api/v1/teacher/material-drafts/{draftId}/chapters/{chapterId}/units` | 草稿新增單元 |
| PUT | `/api/v1/teacher/material-drafts/{draftId}/units/{nodeId}` | 草稿修改單元 |
| DELETE | `/api/v1/teacher/material-drafts/{draftId}/units/{nodeId}` | 草稿刪除單元 |
| POST | `/api/v1/teacher/material-drafts/{draftId}/units/{unitId}/knowledge-cards` | 草稿新增知識卡 |
| PUT | `/api/v1/teacher/material-drafts/{draftId}/knowledge-cards/{nodeId}` | 草稿修改知識卡 |
| DELETE | `/api/v1/teacher/material-drafts/{draftId}/knowledge-cards/{nodeId}` | 草稿刪除知識卡 |
| POST | `/api/v1/teacher/material-drafts/{draftId}/publish` | 發布為正式教材 |

草稿節點 Request 與正式教材相同：主題／章節／單元用 `name`、`sort_order`；知識卡用 `title`、`content`、`example`、`sort_order`。成功匯入／新增為 **201**。

只上傳範本裡的範例列會 **422**（沒有可匯入的教材列）。沒填 `topic` 會 **422**。找不到教材名稱會 **422**。不是該課教師 **404**。已發布或已封存的 Draft 不能直接改／再發布，會 **422**。同一份 Draft 裡主題名稱重複會 **422**。Excel 教材名稱與該課**未發布** Draft 重複會 **422**。

發布在 transaction 裡：把同課其他 `published` Draft 改成 `archived`，再對這份 `tree` 做**主題級同步**（同名或同正式 id 則 update，否則 insert）。其他主題完全不動。知識卡若已有題目關聯就不會刪。草稿列表依 `updated_at` 新到舊，並含時間欄位。

### 學生教材（已發布、已選課）

學生只能看正式教材，看不到草稿。未選課回 **404**。非學生打這些路由 **403**。

| Method | URL | 說明 |
|--------|-----|------|
| GET | `/api/v1/student/courses/{courseId}/topics` | 列出該課主題 |
| GET | `/api/v1/student/topics/{topicId}/chapters` | 列出該主題章節 |
| GET | `/api/v1/student/chapters/{chapterId}/units` | 列出該章節單元 |
| GET | `/api/v1/student/units/{unitId}/knowledge-cards` | 列出該單元知識卡 |

一層一層往下點，格式與教師列表相同（含 `item_count`、`created_at`、`updated_at`）。主題列表依最後更新時間新到舊。

### 學生作答（一題一答）
① 取得題目 → ② 選擇題單題作答 → ③ 除錯題單題作答 → ④ 實作題送出 → ⑤ AI 批改 → ⑥ 老師覆核 

闖關範圍（單元或章節）尚未定，入口先依**課程**取題。可選 `?knowledge_card_id=` 只看某張知識卡關聯的題。未選課 **404**。非學生 **403**。

取題**不回傳正解**：選擇題沒有 `is_answer`；除錯題不給 `code_line`／`answer`；實作題不給 `ref_answer`／`ref_output`。

| Method | URL | 說明 |
|--------|-----|------|
| GET | `/api/v1/student/courses/{courseId}/questions` | 列出該課題目（可加 `knowledge_card_id`） |
| GET | `/api/v1/student/questions/{questionId}` | 取得單題 |
| POST | `/api/v1/student/questions/{questionId}/submit` | 交卷 |

選擇題 Request：`{ "option_id": 1 }`  
成功時 `system_status` 為 `correct` 或 `wrong`，`explanation` 為正解選項的 `description`。`result` 存選項 ID。

除錯題 Request：至少要有 `code_line` 或 `answer`（可同時傳）。後端比對 `debug_sub_info`，回傳 `system_status` 與 `description`。

實作題 Request：`{ "code": "..." }`。只建紀錄，不批改。

```json
{
  "message": "已提交",
  "system_status": "pending",
  "record": {
    "id": 1,
    "student_id": 1,
    "question_id": 3,
    "result": "<?php echo \"hi\";",
    "question_mapping_id": 1,
    "system_status": "pending",
    "teacher_status": "pending"
  }
}
```

每次交卷都**新增**一筆 `question_records`（可重交）。Bloom/SOLO 使用該題第一筆映射；沒有映射會 **422**。`teacher_status` 交卷時一律 `pending`。

### 教師覆核作答

只能看自己課程的紀錄。非該課教師 **404**。還未做 AI 批改。

| Method | URL | 說明 |
|--------|-----|------|
| GET | `/api/v1/teacher/courses/{courseId}/question-records` | 列出該課學生作答 |
| PUT | `/api/v1/teacher/question-records/{recordId}` | 覆核 `teacher_status`：`correct` 或 `wrong` |

### topics 主題

這段是**正式教材**的鑽層 API（`MaterialService`），給已發布內容或教師手動建正式表用。Excel 匯入與老師改第一版樹請用上面的 **Draft API**。

| Method | URL | 說明 |
|--------|-----|------|
| GET | `/api/v1/teacher/courses/{courseId}/topics` | 列出該課程的主題 |
| POST | `/api/v1/teacher/courses/{courseId}/topics` | 新增主題 |
| PUT | `/api/v1/teacher/topics/{topicId}` | 修改主題 |
| DELETE | `/api/v1/teacher/topics/{topicId}` | 刪除主題（有題目使用的知識卡會保留並脫離教材樹） |

Request（新增／修改）：

```json
{
  "name": "PHP 基礎",
  "sort_order": 1
}
```

`sort_order` 選填；不傳則自動接在最後。

列表／單筆會含 `item_count`（底下章節數量）以及 `created_at`、`updated_at`。主題列表依最後更新時間新到舊。

### chapters 章節

| Method | URL | 說明 |
|--------|-----|------|
| GET | `/api/v1/teacher/topics/{topicId}/chapters` | 列出該主題的章節 |
| POST | `/api/v1/teacher/topics/{topicId}/chapters` | 新增章節 |
| PUT | `/api/v1/teacher/chapters/{chapterId}` | 修改章節 |
| DELETE | `/api/v1/teacher/chapters/{chapterId}` | 刪除章節（有題目使用的知識卡會保留並脫離教材樹） |

Request 同主題，欄位為 `name`、`sort_order`。`item_count` 為底下單元數量。

### units 單元

| Method | URL | 說明 |
|--------|-----|------|
| GET | `/api/v1/teacher/chapters/{chapterId}/units` | 列出該章節的單元 |
| POST | `/api/v1/teacher/chapters/{chapterId}/units` | 新增單元 |
| PUT | `/api/v1/teacher/units/{unitId}` | 修改單元 |
| DELETE | `/api/v1/teacher/units/{unitId}` | 刪除單元（有題目使用的知識卡會保留並脫離教材樹） |

Request 同主題。`item_count` 為底下知識卡數量。

### knowledge_cards 知識卡

| Method | URL | 說明 |
|--------|-----|------|
| GET | `/api/v1/teacher/units/{unitId}/knowledge-cards` | 列出該單元的知識卡 |
| POST | `/api/v1/teacher/units/{unitId}/knowledge-cards` | 新增知識卡 |
| PUT | `/api/v1/teacher/knowledge-cards/{cardId}` | 修改知識卡 |
| DELETE | `/api/v1/teacher/knowledge-cards/{cardId}` | 刪除知識卡（已有題目使用則 422） |

Request（新增／修改）：

```json
{
  "title": "PHP 變數介紹",
  "content": "變數是用來儲存資料的容器，PHP 使用 $ 符號宣告變數。",
  "example": "$name = \"PHP\";",
  "sort_order": 1
}
```

`title`、`content` 必填，`example` 選填。畫面上一層用 `name`，知識卡請用 `title`。

成功建立為 **201**。刪除成功 200：

```json
{
  "message": "知識卡已刪除"
}
```

知識卡已有題目關聯時刪除會 **422**（`knowledge_card`）。請先從題目拿掉該知識卡，或改刪上層主題／章節／單元：有題的卡會保留、沒題的卡會刪，主題本身會從列表消失。

---

## 權限

| 使用者 | 登入 | Dashboard | 教師申請（送出） | 教師申請（列表／核准） | 學生申請（送出） | 學生申請（開通） | 教師課程管理 | 教師教材管理 |
|--------|------|-----------|------------------|------------------------|------------------|------------------|--------------|--------------|
| 未登入 | — | ✗ | ✓ | ✗ | ✗ | ✗ | ✗ | ✗ |
| 管理員 | ✓ | ✓ | — | ✓ | ✗ | ✓ | ✗ | ✗ |
| 教師 | ✓ | ✓ | — | ✗ | ✓ | ✗ | ✓ | ✓ |
| 學生 | ✓ | ✓ | — | ✗ | ✗ | ✗ | ✗ | ✗ |

教師只能管理自己建立的課程，無法查看、修改或刪除其他教師的課程（**404**）。  
非教師存取 `/teacher/*` 回傳 **403**。  
學生只能看自己有選課的課程教材（未選課 **404**）；草稿未發布時學生列表為空。

---

## 測試帳號

| 身分 | 帳號 | 密碼 |
|------|------|------|
| 管理員 | admin@school.edu.tw | Password123! |
| 教師 | teacher@school.edu.tw | Password123! |
| 教師 | teacher2@school.edu.tw | Password123! |
| 學生 | 1411131000 | Password123! |

Seeder 已為 `teacher2`（陳老師）建立「網際系統設計 (資應)」「網際系統設計 (資管)」兩門課，並讓王小明選修「網際系統設計 (資應)」。

---

## 安裝與執行

開發環境使用 MySQL。請先確認 MySQL 已啟動，並建立資料庫 `php_education`。

```text
資料庫在這裡
database/sql/php_education.sql
```

```bash
composer install
copy .env.example .env
php artisan key:generate
```

接著記得編輯 `.env` 的資料庫帳密（`DB_USERNAME`、`DB_PASSWORD`），再執行：

```bash
php artisan serve
```

SQL 裡已經有資料表和測試帳號

API 網址：

```text
http://127.0.0.1:8000
```

前端若開在 `http://localhost:9000`，對這個網址打 API 即可。
