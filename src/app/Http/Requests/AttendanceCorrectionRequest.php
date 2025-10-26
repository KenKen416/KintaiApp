<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use App\Models\Attendance;
use Illuminate\Support\Carbon;
use Illuminate\Support\Arr;

class AttendanceCorrectionRequest extends FormRequest
{
    public function authorize()
    {
        $attendanceId = $this->route('id');
        if (! $attendanceId) {
            return false;
        }

        $attendance = Attendance::find($attendanceId);
        if (! $attendance) {
            return false;
        }

        // ログインユーザが所有者であれば許可
        return $attendance->user_id === Auth::id();
    }

    public function rules()
    {
        return [
            'requested_clock_in' => ['nullable', 'regex:/^\d{2}:\d{2}$/'],
            'requested_clock_out' => ['nullable', 'regex:/^\d{2}:\d{2}$/'],

            'breaks' => ['array'],
            'breaks.*.break_start' => ['nullable', 'regex:/^\d{2}:\d{2}$/'],
            'breaks.*.break_end' => ['nullable', 'regex:/^\d{2}:\d{2}$/'],

            'requested_note' => ['required', 'string'],
        ];
    }

    public function messages()
    {
        return [
            'requested_clock_in.regex' => '出勤時刻の形式が不正です（HH:MM）。',
            'requested_clock_out.regex' => '退勤時刻の形式が不正です（HH:MM）。',
            'breaks.*.break_start.regex' => '時刻は HH:MM 形式で入力してください',
            'breaks.*.break_end.regex' => '時刻は HH:MM 形式で入力してください',
            'requested_note.required' => '備考を記入してください',
        ];
    }


    public function withValidator($validator): void
    {
        $validator->after(function ($v) {
            // 出勤・退勤
            $in  = $this->input('requested_clock_in');
            $out = $this->input('requested_clock_out');

            $inT = $in ? $this->parseTime($in) : null;
            $outT = $out ? $this->parseTime($out) : null;

            if ($in && $out) {
                if ($inT === null || $outT === null) {
                    // フォーマットエラーは個別ルールで拾う
                } elseif ($inT->gte($outT)) {
                    $v->errors()->add('requested_clock_in', '出勤時間もしくは退勤時間が不適切な値です');
                }
            }

            // breaks 配列から start/end を取得して検証
            $breaks = (array) $this->input('breaks', []);
            $bs = [];
            $be = [];
            foreach ($breaks as $b) {
                $bs[] = Arr::get($b, 'break_start', null);
                $be[] = Arr::get($b, 'break_end', null);
            }

            $n = max(count($bs), count($be));
            for ($i = 0; $i < $n; $i++) {
                $s = $bs[$i] ?? null;
                $e = $be[$i] ?? null;

                if ($s && $e) {
                    $sT = $this->parseTime($s);
                    $eT = $this->parseTime($e);
                    if ($sT === null || $eT === null) {
                        // 形式エラーは rules/messages で検出
                    } else {
                        if ($sT->gte($eT)) {
                            $v->errors()->add("breaks.{$i}.break_start", '休憩時間が不適切な値です');
                        }
                    }
                }

                // 出勤/退勤 と比較した整合チェック
                if ($s && $inT) {
                    $sT = $this->parseTime($s);
                    if ($sT && $sT->lt($inT)) {
                        $v->errors()->add("breaks.{$i}.break_start", '休憩時間が不適切な値です');
                    }
                }

                if ($s && $outT) {
                    $sT = $this->parseTime($s);
                    if ($sT && $outT && $sT->gt($outT)) {
                        $v->errors()->add("breaks.{$i}.break_start", '休憩時間が不適切な値です');
                    }
                }

                if ($e && $outT) {
                    $eT = $this->parseTime($e);
                    if ($eT && $eT->gt($outT)) {
                        $v->errors()->add("breaks.{$i}.break_end", '休憩時間もしくは退勤時間が不適切な値です');
                    }
                }
            }
        });
    }

    private function parseTime(?string $time): ?Carbon
    {
        if (empty($time)) {
            return null;
        }
        try {
            return Carbon::createFromFormat('H:i', $time);
        } catch (\Throwable $e) {
            return null;
        }
    }
}
