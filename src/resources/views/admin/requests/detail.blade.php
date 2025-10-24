@extends('layouts.app')

@section('title', '修正申請承認画面（管理者）')

@section('css')
<link rel="stylesheet" href="{{ asset('css/pages/admin/request-detail.css') }}">
@endsection

@section('content')
@php
$a = $attendance;
$corr = $correction;
$workDate = optional($a->work_date) ? \Illuminate\Support\Carbon::parse($a->work_date): '-';
$displayClockIn = $corr->requested_clock_in ? \Illuminate\Support\Carbon::parse($corr->requested_clock_in)->format('H:i') : optional($a->clock_in)?->format('H:i') ?? '';
$displayClockOut = $corr->requested_clock_out ? \Illuminate\Support\Carbon::parse($corr->requested_clock_out)->format('H:i') : optional($a->clock_out)?->format('H:i') ?? '';
$displayNote = $corr->requested_note ?: ($a->note ?: '—');
$displayBreaks = [];
if (! empty($corr->requested_breaks) && is_array($corr->requested_breaks)) {
$displayBreaks = $corr->requested_breaks;
} elseif (isset($a->breakTimes)) {
$displayBreaks = $a->breakTimes->map(fn($b) => [
'break_start' => $b->break_start,
'break_end' => $b->break_end,
])->toArray();
}
@endphp

<div class="content__inner">
  <h1 class="content__title">勤怠詳細</h1>

  <div class="request-card">
    <table class="request-table" role="table">
      <tbody>
        <tr>
          <th>名前</th>
          <td class="u-center">{{ optional($a->user)->name ?? '-' }}</td>
          <td></td>
          <td></td>
        </tr>

        <tr>
          <th>日付</th>
          <td class="u-center">{{ $workDate->format('Y年') }}</td>
          <td></td>
          <td class="u-center">{{ $workDate->format('n月j日') }}</td>
        </tr>

        <tr>
          <th>出勤・退勤</th>
          <td class="u-center">{{ $displayClockIn }}</td>
          <td class="tilde">〜</td>
          <td class="u-center">{{ $displayClockOut }}</td>
        </tr>
        @php
        $i = 1;
        @endphp
        @foreach ($displayBreaks as $b)
        <tr>
          <th>休憩{{ $i > 1 ? "$i" : '' }}</th>
          @php
          $bs = $b['break_start'] ? \Illuminate\Support\Carbon::parse($b['break_start'])->format('H:i') : '';
          $be = $b['break_end'] ? \Illuminate\Support\Carbon::parse($b['break_end'])->format('H:i') : '';
          @endphp
          <td class="u-center">{{ $bs }}</td>
          <td class="tilde">〜</td>
          <td class="u-center">{{ $be }}</td>
        </tr>
        @php $i++; @endphp
        @endforeach

        <tr>
          <th>備考</th>
          <td class="u-center">{{ $displayNote }}</td>
        </tr>
      </tbody>
    </table>
  </div>

  <div class="approve-actions">
    @if ($corr->status === 'approved')
    <button class="btn btn-approved" disabled>承認済み</button>
    @else
    <form method="POST" action="{{ route('stamp_correction_request.approve.perform', ['attendance_correct_request' => $corr->id]) }}" onsubmit="return confirm('この申請を承認します。よろしいですか？');">
      @csrf
      <button type="submit" class="btn btn-approve">承認</button>
    </form>
    @endif
  </div>
</div>
@endsection