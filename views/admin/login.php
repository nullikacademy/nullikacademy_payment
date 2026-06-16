<section class="admin-login">
  <div class="admin-login__card glass">
    <img src="<?= e(asset('images/logo.svg')) ?>" alt="نولیک آکادمی" height="44" class="admin-login__logo">
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
          <button type="button" class="password-input__toggle" data-toggle="password" aria-label="نمایش رمز">👁</button>
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
