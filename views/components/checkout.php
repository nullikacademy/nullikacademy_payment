<?php
/** @var array $payment */
$card = (string) ($payment['card_number'] ?? '');
$iban = (string) ($payment['iban'] ?? '');
$accountName = (string) ($payment['account_name'] ?? '');
$flag = asset('images/iran-flag.svg');
?>
<div class="checkout" id="checkout" aria-hidden="true" role="dialog" aria-modal="true" aria-label="فرایند خرید">
  <div class="checkout__backdrop" data-checkout-close></div>

  <div class="checkout__dialog glass">
    <button class="checkout__close" data-checkout-close aria-label="بستن">
      <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
    </button>

    <!-- Minimal segmented progress bar -->
    <div class="checkout__progress">
      <div class="progress-bar" id="progressBar">
        <?php for ($i = 1; $i <= 6; $i++): ?>
        <span class="progress-seg<?= $i === 1 ? ' is-active' : '' ?>" data-seg="<?= $i ?>"></span>
        <?php endfor; ?>
      </div>
    </div>

    <div class="checkout__body">

      <!-- STEP 1: MOBILE -->
      <section class="cstep is-active" data-step="1">
        <h3 class="cstep__title">تأیید شماره موبایل</h3>
        <p class="cstep__hint">شماره موبایل خود را وارد کنید تا کد تأیید برایتان ارسال شود.</p>

        <div class="field" id="mobileField">
          <label class="field__label">شماره موبایل</label>
          <div class="phone-input">
            <span class="phone-input__prefix" aria-hidden="true">
              <img src="<?= e($flag) ?>" alt="ایران" width="22" height="16"><b>+۹۸</b>
            </span>
            <input type="tel" inputmode="numeric" autocomplete="tel" id="mobileInput"
                   class="phone-input__field" placeholder="۹۱۲۳۴۵۶۷۸۹" maxlength="11">
          </div>
          <span class="field__error" id="mobileError"></span>
        </div>
        <button class="btn btn--primary btn--block" id="sendOtpBtn">ارسال کد تأیید</button>
      </section>

      <!-- STEP 2: OTP -->
      <section class="cstep" data-step="2">
        <h3 class="cstep__title">کد تأیید</h3>
        <p class="cstep__hint">کد ۴ رقمی ارسال‌شده به <b id="otpMobileLabel" dir="ltr"></b> را وارد کنید.</p>
        <div class="otp-inputs" id="otpInputs" dir="ltr">
          <input type="text" inputmode="numeric" autocomplete="one-time-code" maxlength="1" class="otp-box">
          <input type="text" inputmode="numeric" maxlength="1" class="otp-box">
          <input type="text" inputmode="numeric" maxlength="1" class="otp-box">
          <input type="text" inputmode="numeric" maxlength="1" class="otp-box">
        </div>
        <span class="field__error" id="otpError"></span>
        <div class="otp-actions">
          <button class="link-btn" id="resendOtpBtn" disabled>ارسال مجدد (<span id="otpCountdown">۶۰</span>)</button>
          <button class="link-btn" id="editMobileBtn">ویرایش شماره</button>
        </div>
      </section>

      <!-- STEP 3: PERSONAL INFO -->
      <section class="cstep" data-step="3">
        <h3 class="cstep__title">اطلاعات شخصی</h3>
        <div class="field">
          <label class="field__label">نام</label>
          <input type="text" id="firstNameInput" class="input" autocomplete="given-name" placeholder="نام">
          <span class="field__error" id="firstNameError"></span>
        </div>
        <div class="field">
          <label class="field__label">نام خانوادگی</label>
          <input type="text" id="lastNameInput" class="input" autocomplete="family-name" placeholder="نام خانوادگی">
          <span class="field__error" id="lastNameError"></span>
        </div>
        <div class="cstep__nav">
          <button class="btn btn--ghost" data-prev>بازگشت</button>
          <button class="btn btn--primary" id="personalNextBtn">ادامه</button>
        </div>
      </section>

      <!-- STEP 4: ACCOUNT INFO (mode A/B) -->
      <section class="cstep" data-step="4">
        <h3 class="cstep__title" id="accountTitle">اطلاعات حساب خود را وارد کنید</h3>
        <div class="field">
          <label class="field__label">ایمیل</label>
          <input type="email" id="emailInput" class="input" dir="ltr" autocomplete="email" placeholder="you@example.com">
          <span class="field__error" id="emailError"></span>
        </div>

        <!-- Mode A: password -->
        <div class="field" id="passwordField">
          <label class="field__label">رمز عبور</label>
          <div class="password-input">
            <input type="password" id="passwordInput" class="input" dir="ltr" autocomplete="new-password" placeholder="••••••••">
            <button type="button" class="password-input__toggle" id="passwordToggle" aria-label="نمایش رمز">
              <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7z"/><circle cx="12" cy="12" r="3"/></svg>
            </button>
          </div>
          <span class="field__error" id="passwordError"></span>
        </div>

        <!-- Mode B: organization id -->
        <div class="field" id="orgField" hidden>
          <label class="field__label">شناسه سازمانی (Organization ID)</label>
          <input type="text" id="orgInput" class="input" dir="ltr" placeholder="org-xxxxxxxx">
          <span class="field__error" id="orgError"></span>
        </div>

        <div class="cstep__nav">
          <button class="btn btn--ghost" data-prev>بازگشت</button>
          <button class="btn btn--primary" id="accountNextBtn">ادامه</button>
        </div>
      </section>

      <!-- STEP 5: REVIEW + PAYMENT -->
      <section class="cstep" data-step="5">
        <h3 class="cstep__title">بررسی سفارش</h3>
        <div class="review-card glass" id="reviewCard"></div>

        <h4 class="cstep__subtitle">روش پرداخت</h4>
        <div class="pay-methods">
          <label class="pay-method is-disabled">
            <input type="radio" name="payMethod" value="online" disabled>
            <span class="pay-method__body">
              <span class="pay-method__title">درگاه آنلاین</span>
              <span class="pay-method__tag">به‌زودی</span>
            </span>
          </label>
          <label class="pay-method is-selected">
            <input type="radio" name="payMethod" value="card_to_card" checked>
            <span class="pay-method__body">
              <span class="pay-method__title">کارت به کارت</span>
              <span class="pay-method__tag pay-method__tag--ok">فعال</span>
            </span>
          </label>
        </div>

        <!-- Pasargad-inspired premium card -->
        <div class="bank-card">
          <div class="bank-card__top">
            <img class="bank-card__logo bank-card__logo--bank" src="<?= e(asset('images/pasargad.png')) ?>" alt="بانک پاسارگاد" height="48">
            <img class="bank-card__logo bank-card__logo--shetab" src="<?= e(asset('images/shetab.svg')) ?>" alt="شتاب" height="24">
          </div>
          <div class="bank-card__row">
            <span class="bank-card__label">شماره کارت</span>
            <div class="bank-card__value">
              <span id="cardNumber" dir="ltr"><?= e($card) ?></span>
              <button class="copy-btn" data-copy="<?= e(preg_replace('/\D/', '', $card)) ?>" aria-label="کپی شماره کارت">کپی</button>
            </div>
          </div>
          <div class="bank-card__row">
            <span class="bank-card__label">شماره شبا</span>
            <div class="bank-card__value">
              <span id="ibanNumber" dir="ltr"><?= e($iban) ?></span>
              <button class="copy-btn" data-copy="<?= e(preg_replace('/\s/', '', $iban)) ?>" aria-label="کپی شبا">کپی</button>
            </div>
          </div>
          <div class="bank-card__row bank-card__row--name">
            <span class="bank-card__label">به نام</span>
            <span class="bank-card__holder"><?= e($accountName) ?></span>
          </div>
        </div>

        <p class="pay-note" id="payNote"></p>

        <div class="cstep__nav">
          <button class="btn btn--ghost" data-prev>بازگشت</button>
          <button class="btn btn--primary" id="paidBtn">پرداخت کردم</button>
        </div>
      </section>

      <!-- STEP 6: RECEIPT UPLOAD -->
      <section class="cstep" data-step="6">
        <h3 class="cstep__title">بارگذاری رسید پرداخت</h3>
        <p class="cstep__hint">تصویر رسید واریز را بارگذاری کنید. فرمت‌های مجاز: jpg، png، webp (حداکثر ۱۰ مگابایت).</p>

        <div class="uploader" id="uploader" tabindex="0" role="button" aria-label="بارگذاری رسید">
          <input type="file" id="receiptInput" accept="image/jpeg,image/png,image/webp" hidden>
          <div class="uploader__placeholder" id="uploaderPlaceholder">
            <span class="uploader__icon">
              <svg viewBox="0 0 24 24" width="26" height="26" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
            </span>
            <span class="uploader__text">فایل را بکشید و رها کنید یا کلیک کنید</span>
          </div>
          <img class="uploader__preview" id="receiptPreview" alt="پیش‌نمایش رسید" hidden>
        </div>
        <span class="field__error" id="receiptError"></span>

        <div class="cstep__nav">
          <button class="btn btn--ghost" data-prev>بازگشت</button>
          <button class="btn btn--primary" id="submitOrderBtn" disabled>ثبت نهایی سفارش</button>
        </div>
      </section>

    </div>
  </div>
</div>
