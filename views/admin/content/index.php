<?php /** @var array $fields @var array $values */ ?>
<div class="page-head"><h1 class="page-title">متن‌های سایت</h1>
  <p class="page-sub">متن‌های صفحه اصلی، دکمه‌ها و پیام‌ها را ویرایش کنید.</p>
</div>

<section class="glass detail-card detail-card--wide">
  <form id="contentForm">
    <?php foreach ($fields as $key => $meta): [$label, $type, $default] = $meta; ?>
    <div class="field">
      <label class="field__label" for="ct_<?= e($key) ?>"><?= e($label) ?></label>
      <?php if ($type === 'textarea'): ?>
      <textarea class="input" id="ct_<?= e($key) ?>" name="<?= e($key) ?>" rows="3"><?= e($values[$key]) ?></textarea>
      <?php else: ?>
      <input class="input" id="ct_<?= e($key) ?>" name="<?= e($key) ?>" value="<?= e($values[$key]) ?>">
      <?php endif; ?>
    </div>
    <?php endforeach; ?>
    <button type="submit" class="btn btn--primary">ذخیره متن‌ها</button>
  </form>
</section>
