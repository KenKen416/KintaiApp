@extends('layouts.app')
@section('title', 'ログイン画面(管理者)')
@section('css')
<link rel="stylesheet" href="{{ asset('css/pages/user/register-login.css') }}">
@endsection
@section('content')

<div class="content-title">
  管理者ログイン
</div>

<div class="content-form">
  <form class="form-inner" action="" method="POST">
    @csrf
    <input type="hidden" name="context" value="admin">
    <div class="form-group">
      <label class="form-label" for="email">メールアドレス</label>
      <input type="email" id="email" name="email" class="form-input" value="{{ old('email') }}" required>
      @error('email')
      <div class="error-message">{{ $message }}</div>
      @enderror
    </div>
    <div class="form-group">
      <label class="form-label" for="password">パスワード</label>
      <input type="password" id="password" name="password" class="form-input">
      @error('password')
      <div class="error-message">{{ $message }}</div>
      @enderror
    </div>

    <div class="submit">
      <button type="submit" class="btn btn--login">管理者ログインする</button>
    </div>
  </form>
</div>
@endsection