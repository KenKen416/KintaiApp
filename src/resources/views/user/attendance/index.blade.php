@extends('layouts.app')
@section('title', '勤怠登録画面')
@section('css')
<link rel="stylesheet" href="{{ asset('css/pages/user/attendance.css') }}">
@endsection
@section('content')
<div class="content">
  <div class="status">
    @switch($status)
      @case('none')
        勤務外
        @break
      @case('working')
        出勤中
        @break
      @case('on_break')
        休憩中
        @break
      @default
        退勤済
    @endswitch
  </div>
  <div class="attendance-date">
    @php
      $weekDays = ['日', '月', '火', '水', '木', '金', '土'];
      $weekday = $weekDays[$now->dayOfWeek];
    @endphp
    {{ $now->format('Y年n月j日(' . $weekday . ')') }}
  </div>
  <div class="current-time">
    {{ $now->format('H:i') }}
  </div>
  @if ($status === 'finished')
    <div class="clock-out-message">
      お疲れ様でした。
    </div>
  @endif
  <div class="buttons">
    @if ($status === 'none')
      <form method="POST" action="{{ route('attendance.clock_in') }}">
        @csrf
        <button type="submit" class="btn btn--primary">出勤</button>
      </form>
    @elseif ($status === 'working')
      <form method="POST" action="{{ route('attendance.clock_out') }}">
        @csrf
        <button type="submit" class="btn btn--primary">退勤</button>
      </form>
      <form method="POST" action="{{ route('attendance.break_start') }}">
        @csrf
        <button type="submit" class="btn btn--secondary">休憩入</button>
    @elseif ($status === 'on_break')
      <form method="POST" action="{{ route('attendance.break_end') }}">
        @csrf
        <button type="submit" class="btn btn--secondary">休憩戻</button>
      </form>
    @endif
  </div>

@endsection