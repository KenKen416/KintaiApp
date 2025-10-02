<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>@yield('title', '勤怠管理')</title>
  <link rel="stylesheet" href="{{ asset('css/sanitize.css') }}">
  <link rel="stylesheet" href="{{ asset('css/app.css') }}">
  <link rel="stylesheet" href="{{asset('css/components/button.css')}}">
  <link rel="stylesheet" href="{{asset('css/components/form.css')}}">
  @yield('css')
</head>
<body>
  <header class="site-header">
    <div class="site-header__inner">
      <div class="site-header__logo">
        <img class="logo__image" src="{{ asset('images/logo.svg') }}" alt="ロゴ画像">
      </div>
      @php($__nav = $nav ?? 'none')
      @switch($__nav)
        @case('user')
          @include('partials.nav.user')
          @break
        @case('admin')
          @include('partials.nav.admin')
          @break
        @case('user-after')
          @include('partials.nav.user-after')
          @break
        @case('none')
        @default
          @include('partials.nav.none')
      @endswitch
    </div>
  </header>

  @include('partials.flash')
  <main class="site-main">
    @yield('content')
  </main>

</body>
</html>