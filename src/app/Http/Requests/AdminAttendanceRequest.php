<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Carbon;
use App\Models\Attendance;

class AdminAttendanceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * 管理者チェックと対象勤怠の存在確認を行います。
     *
     * @return bool
     */
    public function authorize(): bool
    {
        $user = Auth::user();
        if (! $user || ! ($user->is_admin ?? false)) {
            return false;
        }

        $attendanceId = $this->route('id');
        if (! $attendanceId) {
            return false;
        }
        $attendance = Attendance::find($attendanceId);
        return (bool) $attendance;
    }

    /**
     * バリデーションルール
     *
     * 備考（note）は管理者による修正時は必須とします。
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'clock_in'                => ['nullable', 'regex:/^\d{2}:\d{2}$/'],
            'clock_out'               => ['nullable', 'regex:/^\d{2}:\d{2}$/'],
            'breaks'                  => ['nullable', 'array'],
            'breaks.*.break_start'    => ['nullable', 'regex:/^\d{2}:\d{2}$/'],
            'breaks.*.break_end'      => ['nullable', 'regex:/^\d{2}:\d{2}$/'],
            // ここで必須化（管理者が修正する際は備考必須）
            'note'                    => ['required', 'string'],
        ];
    }

    /**
     * 日本語メッセージのカスタマイズ
     *
     * @return array
     */
    public function messages(): array
    {
        return [
            'clock_in.regex' => '出勤時刻の形式が不正です（HH:MM）。',
            'clock_out.regex' => '退勤時刻の形式が不正です（HH:MM）。',
            'breaks.array' => '休憩情報の形式が不正です。',
            'breaks.*.break_start.regex' => '休憩開始時刻の形式が不正です（HH:MM）。',
            'breaks.*.break_end.regex' => '休憩終了時刻の形式が不正です（HH:MM）。',
            'note.required' => '備考を記入してください',
            'note.string' => '備考は文字列で入力してください',
        ];
    }

    /**
     * 追加の検証（時刻の前後関係や休憩の整合性チェック）
     *
     * withValidator を利用して複雑なチェックを行います。
     *
     * @param \Illuminate\Validation\Validator $validator
     * @return void
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($v) {
            $attendanceId = $this->route('id');
            $attendance = Attendance::find($attendanceId);
            if (! $attendance) {
                $v->errors()->add('attendance', '対象の勤怠が見つかりません。');
                return;
            }

            $date = $attendance->work_date instanceof Carbon
                ? $attendance->work_date->format('Y-m-d')
                : Carbon::parse($attendance->work_date)->format('Y-m-d');

            // parse helper (HH:MM -> Carbon)
            $parse = function ($time) use ($date) {
                if (! $time) return null;
                try {
                    return Carbon::parse("{$date} {$time}");
                } catch (\Throwable $e) {
                    return null;
                }
            };

            $in = $parse($this->input('clock_in'));
            $out = $parse($this->input('clock_out'));

            if ($in && $out && $in->gte($out)) {
                $v->errors()->add('clock_in', '出勤時間もしくは退勤時間が不適切な値です');
            }

            // breaks validation: each break_start < break_end, and within [in, out] if both exist
            $breaks = $this->input('breaks', []);
            if (is_array($breaks)) {
                foreach ($breaks as $i => $b) {
                    $bs = $parse($b['break_start'] ?? null);
                    $be = $parse($b['break_end'] ?? null);

                    if (($b['break_start'] ?? null) && ! $bs) {
                        $v->errors()->add("breaks.{$i}.break_start", '休憩開始時刻の形式が不正です');
                    }
                    if (($b['break_end'] ?? null) && ! $be) {
                        $v->errors()->add("breaks.{$i}.break_end", '休憩終了時刻の形式が不正です');
                    }

                    if ($bs && $be && $bs->gte($be)) {
                        $v->errors()->add("breaks.{$i}.break_start", '休憩時間が不適切な値です');
                    }

                    if ($in && $bs && $bs->lt($in)) {
                        $v->errors()->add("breaks.{$i}.break_start", '休憩時間が不適切な値です');
                    }
                    if ($out && $be && $be->gt($out)) {
                        $v->errors()->add("breaks.{$i}.break_end", '休憩時間もしくは退勤時間が不適切な値です');
                    }
                }
            }
        });
    }
}
