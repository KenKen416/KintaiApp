@extends('layouts.app')
@section('title', 'ログイン画面(一般ユーザー)')
@section('css')
<link rel="stylesheet" href="{{ asset('css/pages/user/register-login.css') }}">
@endsection
@section('content')

<div class="content-title">
  ログイン
</div>

<div class="content-form">
  <form class="form-inner" action="" method="POST">
    @csrf

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
      <button type="submit" class="btn btn--login">ログインする</button>
    </div>
  </form>
</div>
<div class="register-link">
  <a class="hyperlink" href="">会員登録はこちら</a>
</div>
@endsection