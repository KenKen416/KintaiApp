@extends('layouts.app')
@section('title', '勤怠一覧')
@section('css')
<link rel="stylesheet" href="{{ asset('css/pages/user/attendance-list.css') }}">
@endsection
@section('content')
<div class="content__inner">
  <h1 class="content__title">勤怠一覧</h1>
  <div class="attendance-list__month-bar">
    <a href="{{ route('attendance.list', ['month' => $prevMonth]) }}" class="attendance-list__month-link">← 前月</a>
    <div class="month-current">
      <img src="{{ asset('images/calendar.png') }}" alt="カレンダーアイコン" class="calendar-icon">
      <span class="month-current__text">{{ $displayMonth->format('Y/n') }}</span>
    </div>
    <a href="{{ route('attendance.list', ['month' => $nextMonth]) }}" class="attendance-list__month-link">翌月 →</a>
  </div>
  <div class="attendance-list__table-wrap">
    <table class="attendance-list__table">
      <thead class="attendance-list__thead">
        <tr class="attendance-list__tr">
          <th class="attendance-list__th attendance-list__th--date">日付</th>
          <th class="attendance-list__th attendance-list__th--clock-in">出勤</th>
          <th class="attendance-list__th attendance-list__th--clock-out">退勤</th>
          <th class="attendance-list__th attendance-list__th--break">休憩</th>
          <th class="attendance-list__th attendance-list__th--total">合計</th>
          <th class="attendance-list__th attendance-list__th--detail">詳細</th>
        </tr>
      </thead>
      <tbody class="attendance-list__tbody">
        @foreach ($days as $day)
        @php
        $weekDays = ['日', '月', '火', '水', '木', '金', '土'];
        $weekday = $weekDays[$day['date']->dayOfWeek];
        @endphp
        <tr class="attendance-list__tr">
          <td class="attendance-list__td">{{ $day['date']->format('m/d(' . $weekday . ')') }}</td>
          <td class="attendance-list__td">{{ $day['clock_in'] ?: '' }}</td>
          <td class="attendance-list__td">{{ $day['clock_out'] ?: '' }}</td>
          <td class="attendance-list__td">{{ $day['break_total'] ?: '' }}</td>
          <td class="attendance-list__td">{{ $day['work_total'] ?: '' }}</td>
          <td class="attendance-list__td">
            @if ($day['has_detail'])
            <a href="{{ route('attendance.detail', ['id' => $day['attendance']->id]) }}" class="attendance-list__td--detail">詳細</a>
            @endif
          </td>
        </tr>
        @endforeach
      </tbody>
    </table>
  </div>
</div>
@endsection