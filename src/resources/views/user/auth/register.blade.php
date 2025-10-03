@extends('layouts.app')
@section('title', '会員登録画面')
@section('css')
<link rel="stylesheet" href="{{ asset('css/pages/user/register-login.css') }}">
@endsection
@section('content')

<div class="content-title">
  会員登録
</div>

<div class="content-form">
  <form class="form-inner" action="{{ route('register') }}" method="POST">
    @csrf
    <div class="form-group">
      <label class="form-label" for="name">名前</label>
      <input type="text" id="name" name="name" class="form-input" value="{{ old('name') }}">
      @error('name')
        <div class="error-message">{{ $message }}</div>
      @enderror
    </div>
    <div class="form-group">
      <label class="form-label" for="email">メールアドレス</label>
      <input type="email" id="email" name="email" class="form-input" value="{{ old('email') }}" >
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
    <div class="form-group">
      <label class="form-label" for="password_confirmation">パスワード確認</label>
      <input type="password" id="password_confirmation" name="password_confirmation" class="form-input">
      @error('password_confirmation')
        <div class="error-message">{{ $message }}</div>
      @enderror
    </div>
    <div class="submit">
      <button type="submit" class="btn btn--register">登録</button>
    </div>
  </form>
</div>
<div class="login-link">
  <a class="hyperlink"href="">ログインはこちら</a>
</div>
@endsection