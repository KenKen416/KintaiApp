@extends('layouts.app')

@section('title', '申請一覧（管理者）')

@section('css')
<link rel="stylesheet" href="{{ asset('css/pages/admin/attendance-corrections.css') }}">
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

  <nav class="request-tabs" role="tablist" aria-label="申請のタブ">
    @foreach ($tabs as $key => $label)
    @php $isActive = ($activeTab === $key); @endphp
    <a href="{{ route('admin.attendance_corrections.index', ['tab' => $key]) }}"
      class="request-tabs__item {{ $isActive ? 'is-active' : '' }}"
      role="tab"
      aria-selected="{{ $isActive ? 'true' : 'false' }}">
      {{ $label }}
    </a>
    @endforeach
  </nav>

  <hr class="tabs-separator" aria-hidden="true" />

  <div class="request-card" role="region" aria-labelledby="request-list-title">
    <table class="request-table" role="table" aria-describedby="request-list-desc">
      <caption id="request-list-title" class="sr-only">管理者用 申請一覧</caption>

      <thead>
        <tr>
          <th scope="col">状態</th>
          <th scope="col">名前</th>
          <th scope="col">対象日時</th>
          <th scope="col">申請理由</th>
          <th scope="col">申請日時</th>
          <th scope="col">詳細</th>
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
            {{ \Illuminate\Support\Str::limit($c->requested_note, 30) ?: '—' }}
          </td>

          <td class="u-nowrap">
            {{ optional($c->created_at)->format('Y/m/d') ?? '-' }}
          </td>

          <td class="u-nowrap">
            {{-- 管理者向け詳細があれば route('admin.attendance.show', ...) 等に変更してください --}}
            <a href="{{ route('attendance.detail', ['id' => $c->attendance_id]) }}" class="btn btn-sm">詳細</a>
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