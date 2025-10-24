@extends('layouts.app')

@section('title', '勤怠詳細（管理者）')

@section('css')
<link rel="stylesheet" href="{{ asset('css/pages/admin/attendance-detail.css') }}">
@endsection

@section('content')
@php
use Illuminate\Support\Carbon;
$a = $attendance;
$date = optional($a->work_date) ? Carbon::parse($a->work_date) : null;
@endphp

<div class="content__inner">
  <h1 class="content__title">勤怠詳細</h1>

  <div class="detail-card">
    <form method="POST" action="{{ route('admin.attendance.update', ['id' => $a->id]) }}">
      @csrf
      <table class="detail-table">
        <tbody>
          <tr>
            <th>名前</th>
            <td class="u-center" colspan="3">{{ optional($a->user)->name ?? '-' }}</td>
          </tr>

          <tr>
            <th>日付</th>
            <td class="u-center">{{ $date->format('Y年') }}</td>
            <td></td>
            <td class="u-center">{{ $date->format('n月j日') }}</td>
          </tr>

          <tr>
            <th>出勤・退勤</th>
            <td>
              <input type="time" name="clock_in" class="time-input" value="{{ old('clock_in', optional($a->clock_in)->format('H:i')) }}">
              @error('clock_in') <div class="error">{{ $message }}</div> @enderror
            </td>
            <td class="tilde">〜</td>
            <td>
              <input type="time" name="clock_out" class="time-input" value="{{ old('clock_out', optional($a->clock_out)->format('H:i')) }}">
              @error('clock_out') <div class="error">{{ $message }}</div> @enderror
            </td>
          </tr>

          {{-- 休憩行（既存休憩分を表示 + 1 行追加） --}}
          @php
          $existing = $breaks ?? collect();
          $maxRows = max(1, $existing->count() + 1);
          @endphp

          @for ($i = 0; $i < $maxRows; $i++)
            @php
            $b=$existing[$i] ?? null;
            $start=old("breaks.{$i}.break_start", $b?->break_start?->format('H:i'));
            $end = old("breaks.{$i}.break_end", $b?->break_end?->format('H:i'));
            @endphp
            <tr>
              <th>{{ $i === 0 ? '休憩' : '休憩' . ($i + 1) }}</th>
              <td>
                <input type="time" name="breaks[{{ $i }}][break_start]" class="time-input" value="{{ $start }}">
                @error("breaks.{$i}.break_start") <div class="error">{{ $message }}</div> @enderror
              </td>
              <td class="tilde">〜</td>
              <td>
                <input type="time" name="breaks[{{ $i }}][break_end]" class="time-input" value="{{ $end }}">
                @error("breaks.{$i}.break_end") <div class="error">{{ $message }}</div> @enderror
              </td>
            </tr>
            @endfor

            <tr>
              <th>備考</th>
              <td colspan="3">
                <textarea name="note" rows="4" class="note-input">{{ old('note', $a->note) }}</textarea>
                @error('note') <div class="error">{{ $message }}</div> @enderror
              </td>
            </tr>
        </tbody>
      </table>
  </div>
  <div class="detail-actions">
    <button type="submit" class="btn btn-primary">修正</button>
  </div>
  </form>

@endsection