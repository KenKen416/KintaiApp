@extends('layouts.app')
@section('title', '勤怠詳細')
@section('css')
<link rel="stylesheet" href="{{ asset('css/pages/user/attendance-detail.css') }}">
@endsection
@section('content')
@php
$date = $attendance->work_date;
@endphp

<div class="content__inner">
  <h1 class="content__title">勤怠詳細</h1>
  <div class="detail-card" aria-label="勤怠詳細カード">
    <table class="detail-table">
      <tbody>
        <tr>
          <th scope="row">名前</th>
          <td class="u-center">{{ $attendance->user->name ?? auth()->user()->name }}</td>
          <td></td>
          <td></td>
        </tr>
        <tr>
          <th scope="row">日付</th>
          <td class="u-center">{{ $date->format('Y年') }}</td>
          <td></td>
          <td class="u-center">{{ $date->format('n月j日') }}</td>
        </tr>
      </tbody>
    </table>


    {{-- 編集可能なフォーム（承認待ちでないとき） --}}
    @if (! $isPending)
    <form method="POST" action="{{ route('attendance.detail.correction.store', ['id' => $attendance->id]) }}" class="detail-form">
      @csrf
      <table class="detail-table">
        <tbody>
          <tr>
            <th>出勤・退勤</th>
            <td>
              <input id="requested_clock_in" type="time" name="requested_clock_in" class="time-input" value="{{ old('requested_clock_in', optional($attendance->clock_in)->format('H:i')) }}">
              @error('requested_clock_in') <div class="error">{{ $message }}</div> @enderror
            </td>
            <td>
              <span class="tilde">〜</span>
            </td>
            <td>
              <input id="requested_clock_out" type="time" name="requested_clock_out" class="time-input" value="{{ old('requested_clock_out', optional($attendance->clock_out)->format('H:i')) }}">
              @error('requested_clock_out') <div class="error">{{ $message }}</div> @enderror
            </td>
          </tr>

          {{-- 休憩入力（既存の UI 構造を維持） --}}
          @php
          $existingBreaks = $attendance->breakTimes->values();
          @endphp
          @foreach ($existingBreaks as $i => $b)
          <tr>
            <th>休憩{{ $i === 0 ? '' : $i + 1 }}</th>
            <td>
              <input type="time" name="breaks[{{ $i }}][break_start]" class="time-input" value="{{ old("breaks.$i.break_start", optional($b->break_start)->format('H:i')) }}">
            </td>
            <td class="tilde">〜</td>
            <td>
              <input type="time" name="breaks[{{ $i }}][break_end]" class="time-input" value="{{ old("breaks.$i.break_end", optional($b->break_end)->format('H:i')) }}">
            </td>
          </tr>
          @endforeach

          {{-- 新規休憩入力欄（1つ追加分） --}}
          <tr>
            <th>休憩{{ $existingBreaks->count() === 0 ? '' : $existingBreaks->count() + 1 }}</th>
            <td>
              <input type="time" name="breaks[{{ $existingBreaks->count() }}][break_start]" class="time-input" value="{{ old("breaks." . $existingBreaks->count() . ".break_start") }}">
            </td>
            <td class="tilde">〜</td>
            <td>
              <input type="time" name="breaks[{{ $existingBreaks->count() }}][break_end]" class="time-input" value="{{ old("breaks." . $existingBreaks->count() . ".break_end") }}">
            </td>
          </tr>

          <tr>
            <th>備考</th>
            <td colspan="3">
              <textarea name="requested_note" rows="4" placeholder="修正理由などを入力してください">{{ old('requested_note', $attendance->note) }}</textarea>
              @error('requested_note') <div class="error">{{ $message }}</div> @enderror
            </td>
          </tr>
        </tbody>
      </table>
  </div>
  <div class="detail-actions">
    <button type="submit" class="btn btn-primary">修正</button>
  </div>
  </form>

  {{-- 承認待ちのときは表示のみ（申請内容を優先表示） --}}
  @else
  @php
  $corr = $pendingCorrection;
  $formatTime = fn($v) => $v ? \Illuminate\Support\Carbon::parse($v)->format('H:i') : '';
  // requested_breaks may be array (cast) or null
  $displayBreaks = [];
  if (! empty($corr->requested_breaks) && is_array($corr->requested_breaks)) {
  foreach ($corr->requested_breaks as $b) {
  $displayBreaks[] = [
  'break_start' => $b['break_start'] ?? null,
  'break_end' => $b['break_end'] ?? null,
  ];
  }
  } else {
  foreach ($breaks as $b) {
  $displayBreaks[] = [
  'break_start' => $b->break_start,
  'break_end' => $b->break_end,
  ];
  }
  }

  $displayClockIn = $corr->requested_clock_in ? $formatTime($corr->requested_clock_in) : ($attendance->clock_in ? $formatTime($attendance->clock_in) : '');
  $displayClockOut = $corr->requested_clock_out ? $formatTime($corr->requested_clock_out) : ($attendance->clock_out ? $formatTime($attendance->clock_out) : '');
  $displayNote = $corr->requested_note ?: ($attendance->note ?: '—');
  @endphp

  <table class="detail-table">
    <tbody>
      <tr>
        <th>出勤・退勤</th>
        <td>
          {{ $displayClockIn }}
        </td>
        <td class="tilde">〜</td>
        <td>
          {{ $displayClockOut }}
        </td>
      </tr>

      @foreach ($displayBreaks as $i => $b)
      <tr>
        <th>休憩{{ $i === 0 ? '' : $i + 1 }}</th>
        <td>
          {{ $b['break_start'] ? \Illuminate\Support\Carbon::parse($b['break_start'])->format('H:i') : '' }}
        </td>
        <td class="tilde">〜</td>
        <td>
          {{ $b['break_end'] ? \Illuminate\Support\Carbon::parse($b['break_end'])->format('H:i') : '' }}
        </td>
      </tr>
      @endforeach

      <tr>
        <th>備考</th>
        <td colspan="3">{{ $displayNote }}</td>
      </tr>
    </tbody>
  </table>
</div>

<p id="detail-note" class="pending-note">＊承認待ちのため修正はできません。</p>
@endif

</div>
@endsection