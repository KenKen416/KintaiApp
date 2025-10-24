@extends('layouts.app')

@section('title', '勤怠一覧（管理者）')

@section('css')
<link rel="stylesheet" href="{{ asset('css/pages/admin/attendance-list.css') }}">
@endsection

@section('content')
@php
use Illuminate\Support\Carbon;
$date = $displayDate instanceof Carbon ? $displayDate : Carbon::parse($displayDate);
// 前日 / 翌日のリンク用
$prev = $date->copy()->subDay()->format('Y-m-d');
$next = $date->copy()->addDay()->format('Y-m-d');

// helper: minutes -> H:MM
$formatMinutes = function($min) {
if (is_null($min)) return '';
$h = intdiv($min, 60);
$m = $min % 60;
return sprintf('%d:%02d', $h, $m);
};
@endphp

<div class="content__inner">
  <h1 class="content__title"> {{ $date->format('Y年n月j日') }}の勤怠</h1>

  <div class="date-nav">
    <a class="date-nav__prev" href="{{ route('admin.attendance.list', ['date' => $prev]) }}">← 前日</a>

    <div class="date-nav__center">
      <img src="{{ asset('images/calendar.png') }}" alt="カレンダーアイコン" class="calendar-icon">
      <span class="date-text">{{ $date->format('Y/m/d') }}</span>
    </div>

    <a class="date-nav__next" href="{{ route('admin.attendance.list', ['date' => $next]) }}">翌日 →</a>
  </div>

  <div class="attendance-table-card">
    <table class="attendance-table">
      <thead>
        <tr>
          <th>名前</th>
          <th>出勤</th>
          <th>退勤</th>
          <th>休憩</th>
          <th>合計</th>
          <th>詳細</th>
        </tr>
      </thead>
      <tbody>
        @forelse ($rows as $row)
        @php
        $a = $row->attendance;
        $clockIn = $row->clock_in ? $row->clock_in->format('H:i') : '';
        $clockOut = $row->clock_out ? $row->clock_out->format('H:i') : '';
        $breakTotal = $formatMinutes($row->break_total_minutes);
        $workTotal = $formatMinutes($row->work_total_minutes);
        @endphp
        <tr>
          <td class="name-cell">{{ optional($row->user)->name ?? '-' }}</td>
          <td class="time-cell">{{ $clockIn }}</td>
          <td class="time-cell">{{ $clockOut }}</td>
          <td class="time-cell">{{ $breakTotal ?: '-' }}</td>
          <td class="time-cell">{{ $workTotal ?: '-' }}</td>
          <td class="u-nowrap">
            <a class="link-detail" href="{{ route('admin.attendance.show', ['id' => $a->id]) }}">詳細</a>
          </td>
        </tr>
        @empty
        <tr>
          <td colspan="6" class="u-center">該当日の勤怠データはありません</td>
        </tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>
@endsection