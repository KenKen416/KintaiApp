<nav class="nav" aria-label="グローバル">
  <div class="nav-inner">
    <ul class="nav-list">
      <li class="nav-item">
        <a class="nav-link" href="">今月の出勤一覧</a>
      </li>
      <li class="nav-item">
        <a class="nav-link" href="">申請一覧</a>
      </li>
    </ul>

    <div class="nav-actions">
      <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit" class="nav-link nav-link-button">ログアウト</button>
      </form>
    </div>
  </div>
</nav>