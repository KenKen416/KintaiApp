@extends('layouts.app')

@section('title', $staff->name . 'さんの勤怠')

@section('css')
<link rel="stylesheet" href="{{ asset('css/pages/admin/staff-month.css') }}">
@endsection

@section('content')
@php
use Illuminate\Support\Carbon;
$month = $displayMonth instanceof Carbon ? $displayMonth : Carbon::parse($displayMonth);
$prev = $prevMonth ?? $month->copy()->subMonth()->format('Y-m');
$next = $nextMonth ?? $month->copy()->addMonth()->format('Y-m');

// 分 -> H:MM フォーマット
$formatMinutes = function ($min) {
if (is_null($min) || $min === '') {
return '';
}
$h = intdiv($min, 60);
$m = $min % 60;
return sprintf('%d:%02d', $h, $m);
};

// 曜日（日本語）
$jpWeek = function ($date) {
if (! $date) return '';
$w = (int) $date->format('w');
$names = ['日','月','火','水','木','金','土'];
return $names[$w] ?? '';
};
@endphp

<main class="content__inner">
  <h1 class="content__title">{{ $staff->name }}さんの勤怠</h1>

  <div class="date-nav">
    <a class="date-nav__prev" href="{{ route('admin.staff.attendance', ['id' => $staff->id, 'month' => $prev]) }}" aria-label="前月へ">← 前月</a>

    <div class="date-nav__center">
      <img src="{{ asset('images/calendar.png') }}" alt="カレンダーアイコン" class="calendar-icon">
      <span class="date-text">{{ $month->format('Y/m') }}</span>
    </div>

    <div class="date-nav__right">
      <a class="date-nav__next" href="{{ route('admin.staff.attendance', ['id' => $staff->id, 'month' => $next]) }}" >翌月 →</a>
    </div>
  </div>

  <section class="attendance-table-card">
    <table class="attendance-table">
      <thead>
        <tr>
          <th scope="col">日付</th>
          <th scope="col">出勤</th>
          <th scope="col">退勤</th>
          <th scope="col">休憩</th>
          <th scope="col">合計</th>
          <th scope="col">詳細</th>
        </tr>
      </thead>
      <tbody>
        @foreach ($rows as $row)
        @php
        $a = $row->attendance; // null の場合あり
        $date = $row->date;
        $clockIn = $row->clock_in ?: '';
        $clockOut = $row->clock_out ?: '';
        $breakTotal = $formatMinutes($row->break_total_minutes);
        $workTotal = $formatMinutes($row->work_total_minutes);
        $weekday = $date ? $jpWeek($date) : '';
        @endphp
        <tr>
          <td class="date-cell">
            {{ optional($date)->format('m/d') }}{{ $weekday ? '（' . $weekday . '）' : '' }}
          </td>
          <td class="time-cell">{{ $clockIn ?: '' }}</td>
          <td class="time-cell">{{ $clockOut ?: '' }}</td>
          <td class="time-cell">{{ $breakTotal ?: '' }}</td>
          <td class="time-cell">{{ $workTotal ?: '' }}</td>
          <td class="u-nowrap">
            @if ($a)
            <a class="link-detail" href="{{ route('admin.attendance.show', ['id' => $a->id]) }}" aria-label="勤怠詳細へ">詳細</a>
            @else
            <span class="no-data"></span>
            @endif
          </td>
        </tr>
        @endforeach
      </tbody>
    </table>
  </section>
  <div class="csv">
    <a class="btn btn-csv" href="{{ route('admin.staff.attendance.export', ['id' => $staff->id, 'month' => $month->format('Y-m')]) }}" aria-label="CSV出力">CSV出力</a>
  </div>
</main>
@endsection