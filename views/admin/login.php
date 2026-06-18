<section class="admin-login">
  <div class="admin-login__card glass">
    <img src="<?= e(asset('images/Nullik-Academy-Logo.svg')) ?>" alt="نالیک آکادمی" height="44" class="admin-login__logo">
    <h1 class="admin-login__title">ورود به پنل مدیریت</h1>
    <p class="admin-login__sub">برای ادامه وارد حساب مدیریتی خود شوید.</p>

    <form id="loginForm" novalidate>
      <div class="field">
        <label class="field__label">نام کاربری</label>
        <input type="text" id="username" name="username" class="input" autocomplete="username" required>
      </div>
      <div class="field">
        <label class="field__label">رمز عبور</label>
        <div class="password-input">
          <input type="password" id="password" name="password" class="input" autocomplete="current-password" required>
          <button type="button" class="password-input__toggle" data-toggle="password" aria-label="نمایش رمز">
            <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7z"/><circle cx="12" cy="12" r="3"/></svg>
          </button>
        </div>
      </div>
      <label class="admin-login__remember">
        <input type="checkbox" id="remember" name="remember"> مرا به خاطر بسپار
      </label>
      <span class="field__error" id="loginError"></span>
      <button type="submit" class="btn btn--primary btn--block" id="loginBtn">ورود</button>
    </form>
  </div>
</section>
