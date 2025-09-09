# ER Draft (初版)

このファイルはスキーマ確定前のドラフトです。マイグレーション確定後に “(FIXED)” として更新します。

## テーブル一覧
- users
- attendances
- breaks
- attendance_correction_requests
(必要最小限のみ。不要な Laravel デフォルトテーブルは生成しない方針)

---

## users
| カラム | 型 | 属性/制約 | 説明 |
|-------|----|-----------|------|
| id | BIGINT UNSIGNED | PK, AUTO_INCREMENT | |
| name | VARCHAR(255) | NOT NULL | |
| email | VARCHAR(255) | UNIQUE, NOT NULL | |
| password | VARCHAR(255) | NOT NULL | |
| is_admin | TINYINT(1) | DEFAULT 0 | 0:一般 1:管理者 |
| email_verified_at | TIMESTAMP | NULLABLE | 応用(メール認証) |
| remember_token | VARCHAR(100) | NULLABLE | Laravel標準 |
| created_at / updated_at | TIMESTAMP | | |

Index/Unique:
- unique email
- index is_admin (任意：管理画面絞り込み高速化)

## attendances
| カラム | 型 | 属性/制約 | 説明 |
|-------|----|-----------|------|
| id | BIGINT UNSIGNED | PK | |
| user_id | BIGINT UNSIGNED | FK -> users.id | |
| work_date | DATE | NOT NULL | 1日1レコード想定 |
| clock_in | DATETIME | NULLABLE | |
| clock_out | DATETIME | NULLABLE | |
| note | TEXT | NULLABLE | 備考 |
| created_at / updated_at | TIMESTAMP | | |

Constraints:
- unique (user_id, work_date)
- index user_id

## breaks
| カラム | 型 | 属性/制約 | 説明 |
|-------|----|-----------|------|
| id | BIGINT UNSIGNED | PK | |
| attendance_id | BIGINT UNSIGNED | FK -> attendances.id | |
| break_start | DATETIME | NOT NULL | |
| break_end | DATETIME | NULLABLE | 休憩中は NULL |
| created_at / updated_at | TIMESTAMP | | |

Index:
- index (attendance_id, break_start)

## attendance_correction_requests
| カラム | 型 | 属性/制約 | 説明 |
|-------|----|-----------|------|
| id | BIGINT UNSIGNED | PK | |
| attendance_id | BIGINT UNSIGNED | FK -> attendances.id | 既存勤怠 |
| user_id | BIGINT UNSIGNED | FK -> users.id | 冗長：検索速度目的 |
| target_date | DATE | NOT NULL | attendances.work_date 再掲 |
| requested_clock_in | DATETIME | NULLABLE | 修正希望値 |
| requested_clock_out | DATETIME | NULLABLE | 修正希望値 |
| requested_breaks | JSON | NULLABLE | [{start:"HH:MM",end:"HH:MM"}, ...] |
| requested_note | TEXT | NOT NULL | 修正理由 (備考) |
| status | ENUM('pending','approved') | DEFAULT 'pending' | |
| approved_at | DATETIME | NULLABLE | |
| approved_by | BIGINT UNSIGNED | FK -> users.id NULLABLE | 管理者 |
| created_at / updated_at | TIMESTAMP | | |

Index:
- index status
- index user_id
- index attendance_id

## リレーション
users 1 - n attendances  
attendances 1 - n breaks  
attendances 1 - n attendance_correction_requests (論理的には 0 or 多)  
users 1 - n attendance_correction_requests (申請者)  
users 1 - n (approved_by) attendance_correction_requests (承認者 / 管理者)

## 想定状態遷移 (打刻)
- 勤務外 -> (clock_in 記録) -> 出勤中
- 出勤中 -> (休憩開始 breaks.insert start) -> 休憩中
- 休憩中 -> (休憩終了 breaks.update end) -> 出勤中
- 出勤中 -> (clock_out 記録) -> 退勤済

状態判定は attendances と breaks の “最新 break の end が NULL か” で動的に決定 (status カラムは現時点保持しない方針)

## 今後の検討メモ (後で削除)
- requested_breaks の JSON フォーマット: バリデーション簡素化のため start/end のみ (日付は target_date に統一)
- 承認時: attendance の実データを requested_* で更新し、breaks を再生成 or 差分反映 (実装時に詳細決定)

(END)