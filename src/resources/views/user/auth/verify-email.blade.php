@extends('layouts.app')
@section('css')
<link rel="stylesheet" href="{{asset('css/pages/user/verify-email.css')}}">
@endsection
@section('title', 'メールアドレス認証')
@section('content')
<div class="verify-email">
  <p class="verify-email__message">登録していただいたメールアドレスに認証メールを送付しました。</p>
  <p class="verify-email__message">メール認証を完了してください</p>

  <a href="http://localhost:8025/" class="btn verify-button">認証はこちらから</a>

  <form method="POST" action=""
    class="verify-email__form">
    @csrf
    <button type="submit" class="verify-email__button">
      認証メールを再送する
    </button>
  </form>

  @endsection