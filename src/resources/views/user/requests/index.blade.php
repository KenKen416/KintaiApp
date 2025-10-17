@extends('layouts.app')
@section('title', '申請一覧')
@section('css')
<link rel="stylesheet" href="{{ asset('css/pages/user/requests.css') }}">
@endsection

@section('content')
@php
$tabs = [
'pending' => '承認待ち',
'approved' => '承認済み',
];
@endphp

<div class="content__inner">
  <h1 class="content__title">申請一覧</h1>

  <nav class="request-tabs">
    @foreach ($tabs as $key => $label)
    @php
    $isActive = ($activeTab === $key);
    @endphp
    <a href="{{ route('stamp_correction_request.list', ['tab' => $key]) }}"
      class="request-tabs__item {{ $isActive ? 'is-active' : '' }}">
      {{ $label }}
    </a>
    @endforeach
  </nav>

  <hr class="tabs-separator" />

  <div class="request-card">
    <table class="request-table">
      <thead>
        <tr>
          <th>状態</th>
          <th>名前</th>
          <th>対象日時</th>
          <th>申請理由</th>
          <th>申請日時</th>
          <th>詳細</th>
        </tr>
      </thead>

      <tbody>
        @forelse ($corrections as $c)
        <tr>
          <td class="u-nowrap">
            {{ $c->status === 'pending' ? '承認待ち' : '承認済み' }}
          </td>

          <td>
            {{ optional($c->attendance->user)->name ?? '-' }}
          </td>

          <td class="u-nowrap">
            {{ optional($c->attendance->work_date)->format('Y/m/d') ?? '-' }}
          </td>

          <td>
            {{ Str::limit($c->requested_note, 6) ?: '—' }}
          </td>

          <td class="u-nowrap">
            {{ optional($c->created_at)->format('Y/m/d') ?? '-' }}
          </td>

          <td class="u-nowrap">
            @if ($isAdmin)
            <a href="" class="link">詳細</a>
            @else
            <a href="{{ route('attendance.detail', ['id' => $c->attendance_id]) }}" class="link">詳細</a>
            @endif
          </td>
        </tr>
        @empty
        <tr>
          <td colspan="6" class="u-center">表示する申請はありません</td>
        </tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>
@endsection