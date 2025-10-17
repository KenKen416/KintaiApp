<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use App\Models\Attendance;
use Illuminate\Support\Carbon;

class AttendanceCorrectionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
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

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'requested_clock_in'         => ['nullable', 'regex:/^\d{2}:\d{2}$/'],
            'requested_clock_out'        => ['nullable', 'regex:/^\d{2}:\d{2}$/'],
            'requested_break_start'      => ['array'],
            'requested_break_start.*'    => ['nullable', 'regex:/^\d{2}:\d{2}$/'],
            'requested_break_end'        => ['array'],
            'requested_break_end.*'      => ['nullable', 'regex:/^\d{2}:\d{2}$/'],
            'requested_note'             => ['required', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'requested_clock_in.regex'       => '時刻は HH:MM 形式で入力してください',
            'requested_clock_out.regex'      => '時刻は HH:MM 形式で入力してください',
            'requested_break_start.*.regex'  => '時刻は HH:MM 形式で入力してください',
            'requested_break_end.*.regex'    => '時刻は HH:MM 形式で入力してください',
            'requested_note.required'        => '備考を記入してください',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($v) {
            $in  = $this->input('requested_clock_in');
            $out = $this->input('requested_clock_out');

            if ($in && $out) {
                $inT  = $this->parseTime($in);
                $outT = $this->parseTime($out);
                if ($inT === null || $outT === null) {
                    // 形式エラーは個別に出るのでここでは省略
                } elseif ($inT->gte($outT)) {
                    $v->errors()->add('requested_clock_in', '出勤時間もしくは退勤時間が不適切な値です');
                }
            }

            $bs = (array) $this->input('requested_break_start', []);
            $be = (array) $this->input('requested_break_end', []);
            $n  = max(count($bs), count($be));

            for ($i = 0; $i < $n; $i++) {
                $s = $bs[$i] ?? null;
                $e = $be[$i] ?? null;

                if ($s && $e) {
                    $sT = $this->parseTime($s);
                    $eT = $this->parseTime($e);
                    if ($sT === null || $eT === null) {
                        // 形式エラーは個別メッセージで
                    } else {
                        if ($sT->gte($eT)) {
                            $v->errors()->add("requested_break_start.$i", '休憩時間が不適切な値です');
                        }
                    }
                }

                if ($s && $in) {
                    $sT = $this->parseTime($s);
                    $inT = $this->parseTime($in);
                    if ($sT !== null && $inT !== null && $sT->lt($inT)) {
                        $v->errors()->add("requested_break_start.$i", '休憩時間が不適切な値です');
                    }
                }

                if ($e && $out) {
                    $eT = $this->parseTime($e);
                    $outT = $this->parseTime($out);
                    if ($eT !== null && $outT !== null && $eT->gt($outT)) {
                        $v->errors()->add("requested_break_end.$i", '休憩時間もしくは退勤時間が不適切な値です');
                    }
                }
            }
        });
    }

    private function parseTime(string $time): ?Carbon
    {
        $dt = Carbon::createFromFormat('H:i', $time);
        return $dt ?: null;
    }
}