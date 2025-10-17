@extends('layouts.app')
@section('title', '勤怠詳細')
@section('css')
<link rel="stylesheet" href="{{ asset('css/pages/user/attendance-detail.css') }}">
@endsection
@section('content')
@php
$date = $attendance->work_date; // Carbon (cast: date)
@endphp

<div class="content__inner">
  <h1 class="content__title">勤怠詳細</h1>
  <div class="detail-card" aria-label="勤怠詳細カード">
    <table class="detail-table">
      <tbody>
        <tr>
          <th scope="row">名前</th>
          <td class="u-center">{{ auth()->user()->name }}</td>
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

          {{-- 休憩行: 既存休憩分 + 追加1行 --}}
          @php
          $maxRows = max(2, $breaks->count() + 1);
          @endphp

          @for ($i = 0; $i < $maxRows; $i++)
            @php
            $b=$breaks[$i] ?? null;
            $start=old("requested_break_start.$i", $b?->break_start?->format('H:i'));
            $end = old("requested_break_end.$i", $b?->break_end?->format('H:i'));
            @endphp
            <tr>
              <th>休憩{{ $i === 0 ? '' : $i + 1 }}</th>
              <td>
                <input id="requested_break_start_{{ $i }}" type="time" name="requested_break_start[]" class="time-input" value="{{ $start }}">
                @error("requested_break_start.$i") <div class="error">{{ $message }}</div> @enderror
              </td>
              <td>
                <span class="tilde">〜</span>
              </td>
              <td>
                <input id="requested_break_end_{{ $i }}" type="time" name="requested_break_end[]" class="time-input" value="{{ $end }}">
                @error("requested_break_end.$i") <div class="error">{{ $message }}</div> @enderror
              </td>
            </tr>
            @endfor

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

  {{-- 承認待ちのときは表示のみ --}}
  @else
  <table class="detail-table">
    <tbody>
      <tr>
        <th>出勤・退勤</th>
        <td>
          {{ $attendance->clock_in?->format('H:i') ?? '' }}
        </td>
        <td class="tilde">
          〜
        </td>
        <td>
          {{ $attendance->clock_out?->format('H:i') ?? '' }}
        </td>
      </tr>

      @foreach ($breaks as $i => $b)
      <tr>
        <th>休憩{{ $i === 0 ? '' : $i + 1 }}</th>
        <td>
          {{ $b->break_start?->format('H:i') ?? '' }}
        </td>
        <td class="tilde">
          〜
        </td>
        <td>
          {{ $b->break_end?->format('H:i') ?? '' }}
        </td>
      </tr>
      @endforeach

      <tr>
        <th>備考</th>
        <td colspan="3">{{ $attendance->note }}</td>
      </tr>
    </tbody>
  </table>
</div>
<p id="detail-note" class="pending-note">＊承認待ちのため修正はできません。</p>
@endif

</div>
@endsection