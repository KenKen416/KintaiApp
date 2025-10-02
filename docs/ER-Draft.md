# ER 図ドラフト (FIXED)
このドキュメントは「現在のモデル・マイグレーション・テーブル設計書（CSV）」を唯一のソースとして再作成した ER ドラフトです。過去のドラフト内容ではなく、現状の実装に厳密に合わせています。

更新方針:
- 反映元: src/app/Models 配下の4モデル、src/database/migrations 配下の各マイグレーション、添付のテーブル設計書CSV
- 本ドラフトに記載の制約・型・リレーションが現状の正です
- 以前の ER ドラフトにあった未実装カラム（例: attendance_corrections.user_id / target_date / approved_at / approved_by など）は、現状未採用のため本書には記載しません

---

## テーブル一覧
- users
- attendances
- break_times
- attendance_corrections

---

## users
- 目的: 一般/管理者ユーザーを管理
- 由来: 2014_10_12_000000_create_users_table.php

| カラム             | 型                 | NOT NULL | 既定値 | キー/制約             | 備考 |
|-------------------|--------------------|----------|--------|----------------------|------|
| id                | BIGINT UNSIGNED    | YES      |        | PK                   | AUTO_INCREMENT |
| name              | VARCHAR(255)       | YES      |        |                      |      |
| email             | VARCHAR(255)       | YES      |        | UNIQUE               |      |
| email_verified_at | TIMESTAMP          | NO       | NULL   |                      |      |
| password          | VARCHAR(255)       | YES      |        |                      |      |
| is_admin          | TINYINT(1)/BOOLEAN | YES      | false  |                      | Laravelでは boolean |
| remember_token    | VARCHAR(100)       | NO       | NULL   |                      | Laravel標準 |
| created_at        | TIMESTAMP          | NO       | NULL   |                      |      |
| updated_at        | TIMESTAMP          | NO       | NULL   |                      |      |

インデックス
- UNIQUE index on email
- （LaravelのforeignIdに伴うもの以外の追加インデックスは現状なし）

---

## attendances
- 目的: ユーザーごとの日次勤怠（出勤/退勤等）
- 由来: 2025_09_16_070120_create_attendances_table.php

| カラム      | 型              | NOT NULL | 既定値 | キー/制約                                  | 備考 |
|------------|-----------------|----------|--------|-------------------------------------------|------|
| id         | BIGINT UNSIGNED | YES      |        | PK                                        | AUTO_INCREMENT |
| user_id    | BIGINT UNSIGNED | YES      |        | FK -> users.id ON DELETE CASCADE          | foreignId により index 付与 |
| work_date  | DATE            | YES      |        |                                           |      |
| clock_in   | DATETIME        | YES      |        |                                           | 非NULL（現状仕様） |
| clock_out  | DATETIME        | NO       | NULL   |                                           | 退勤未確定時はNULL |
| note       | TEXT            | NO       | NULL   |                                           | 備考 |
| created_at | TIMESTAMP       | NO       | NULL   |                                           |      |
| updated_at | TIMESTAMP       | NO       | NULL   |                                           |      |

インデックス/ユニーク
- user_id に対するインデックス（FKに付随）
- unique(user_id, work_date) は現状「未設定」(実装には存在しません)

---

## break_times
- 目的: 勤怠中の休憩の開始・終了を複数記録
- 由来: 2025_09_16_071559_create_break_times_table.php

| カラム        | 型              | NOT NULL | 既定値 | キー/制約                                         | 備考 |
|--------------|-----------------|----------|--------|--------------------------------------------------|------|
| id           | BIGINT UNSIGNED | YES      |        | PK                                               | AUTO_INCREMENT |
| attendance_id| BIGINT UNSIGNED | YES      |        | FK -> attendances.id ON DELETE CASCADE           | foreignId により index 付与 |
| break_start  | DATETIME        | YES      |        |                                                  | 休憩開始 |
| break_end    | DATETIME        | NO       | NULL   |                                                  | 休憩中はNULL |
| created_at   | TIMESTAMP       | NO       | NULL   |                                                  |      |
| updated_at   | TIMESTAMP       | NO       | NULL   |                                                  |      |

---

## attendance_corrections
- 目的: 勤怠修正申請（出退勤/休憩/備考の希望値）
- 由来: 2025_09_16_071609_create_attendance_corrections_table.php

| カラム                | 型              | NOT NULL | 既定値   | キー/制約                                        | 備考 |
|----------------------|-----------------|----------|----------|-------------------------------------------------|------|
| id                   | BIGINT UNSIGNED | YES      |          | PK                                              | AUTO_INCREMENT |
| attendance_id        | BIGINT UNSIGNED | YES      |          | FK -> attendances.id ON DELETE CASCADE          | foreignId により index 付与 |
| requested_clock_in   | DATETIME        | NO       | NULL     |                                                 | 希望する出勤時刻 |
| requested_clock_out  | DATETIME        | NO       | NULL     |                                                 | 希望する退勤時刻 |
| requested_breaks     | JSON            | NO       | NULL     |                                                 | 例: [{"start":"HH:MM","end":"HH:MM"}, ...] |
| requested_note       | TEXT            | YES      |          |                                                 | 申請理由（必須） |
| status               | ENUM            | YES      | 'pending'| 値: 'pending', 'approved'                       | 初期値 'pending' |
| created_at           | TIMESTAMP       | NO       | NULL     |                                                 |      |
| updated_at           | TIMESTAMP       | NO       | NULL     |                                                 |      |

備考
- 本テーブルに user_id / approved_at / approved_by / target_date は「現状存在しません」

---

## リレーション（現状の実装に基づく）
- users 1 - n attendances
  - User hasMany Attendance
  - Attendance belongsTo User
- attendances 1 - n break_times
  - Attendance hasMany BreakTime
  - BreakTime belongsTo Attendance
- attendances 1 - n attendance_corrections
  - Attendance hasMany AttendanceCorrection
  - AttendanceCorrection belongsTo Attendance

（users と attendance_corrections の直接のリレーションは現状なし）

---

## モデルとの対応（キャスト/リレーションの整合）
- App\Models\User
  - casts: email_verified_at (datetime), is_admin (boolean)
  - relations: attendances() hasMany
- App\Models\Attendance
  - casts: work_date (date), clock_in (datetime), clock_out (datetime)
  - relations: user() belongsTo, breakTimes() hasMany, attendanceCorrections() hasMany
- App\Models\BreakTime
  - casts: break_start (datetime), break_end (datetime)
  - relations: attendance() belongsTo
- App\Models\AttendanceCorrection
  - casts: requested_clock_in (datetime), requested_clock_out (datetime), requested_breaks (array)
  - relations: attendance() belongsTo

すべて現行マイグレーション/設計書と整合しています。

---

## オプション（将来検討・変更提案：現状は未採用）
- 出勤の一意性を担保したい場合: attendances に unique(user_id, work_date)
- 修正申請の申請者や承認情報が必要な場合（以前のドラフトに存在した項目）:
  - attendance_corrections に以下を拡張
    - user_id (FK -> users.id)
    - approved_at (DATETIME, NULLABLE)
    - approved_by (FK -> users.id, NULLABLE)
    - target_date (DATE) など
  - 併せてモデルにリレーション追加
    - AttendanceCorrection::user(), ::approvedBy()
    - User::attendanceCorrections(), ::approvedAttendanceCorrections()

これらは現状の実装には存在しないため、本ドラフトでは「参考提案」に留めています。

---
（以上）