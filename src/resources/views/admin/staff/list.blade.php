@extends('layouts.app')

@section('title', 'スタッフ一覧（管理者）')

@section('css')
<link rel="stylesheet" href="{{ asset('css/pages/admin/staff-list.css') }}">
@endsection

@section('content')

<div class="content__inner">
  <h1 class="content__title">スタッフ一覧</h1>

  <div class="staff-card">
    <table class="staff-table">
      <thead>
        <tr>
          <th>名前</th>
          <th>メールアドレス</th>
          <th>月次勤怠</th>
        </tr>
      </thead>
      <tbody>
        @forelse ($users as $u)
        <tr>
          <td class="name-cell">{{ $u->name }}</td>
          <td class="email-cell">{{ $u->email }}</td>
          <td class="u-nowrap">
            {{-- 詳細ボタン: 当該スタッフの現在月の勤怠一覧へ遷移 --}}
            <a class="link-detail" href="{{ route('admin.staff.attendance', ['id' => $u->id]) }}">詳細</a>
          </td>
        </tr>
        @empty
        <tr>
          <td colspan="3" class="u-center">スタッフが見つかりません</td>
        </tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>
@endsection