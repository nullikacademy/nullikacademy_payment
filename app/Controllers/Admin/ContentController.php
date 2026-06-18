<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Models\Setting;
use App\Services\AuthService;

/**
 * Manage editable site texts (landing page strings, buttons, messages).
 * Values are stored in settings under the text_* keys.
 */
final class ContentController extends Controller
{
    /**
     * Editable fields: key => [label, type(text|textarea), default].
     */
    private function fields(): array
    {
        return [
            'brand_name'        => ['نام برند', 'text', 'نالیک آکادمی'],
            'og_title'          => ['عنوان اشتراک‌گذاری (og:title)', 'text', 'مارکت‌پلیس اشتراک هوش مصنوعی'],
            'hero_eyebrow'      => ['برچسب بالای عنوان', 'text', 'پلتفرم تخصصی هوش مصنوعی'],
            'hero_title'        => ['عنوان اصلی (هیرو)', 'text', 'اشتراک ابزارهای هوش مصنوعی را ساده و امن بخرید'],
            'hero_title_highlight' => ['بخش برجستهٔ عنوان', 'text', 'ساده و امن'],
            'hero_subtitle'     => ['زیرعنوان هیرو', 'textarea', 'ChatGPT، Claude، Midjourney و ده‌ها ابزار دیگر — تحویل سریع، پرداخت امن و پشتیبانی ۲۴ ساعته.'],
            'support_button'    => ['متن دکمه پشتیبانی', 'text', 'ارتباط با پشتیبانی'],
            'footer_tagline'    => ['شعار فوتر', 'text', 'مارکت‌پلیس اشتراک ابزارهای هوش مصنوعی، سریع و امن.'],
            'success_title'     => ['عنوان صفحه موفقیت', 'text', 'سفارش با موفقیت ثبت شد'],
            'success_message'   => ['پیام صفحه موفقیت', 'textarea', 'سفارش شما طی ۲۴ ساعت آینده بررسی می‌شود.'],
        ];
    }

    /** GET /admin/content */
    public function index(Request $request): never
    {
        $fields = $this->fields();
        $values = [];
        foreach ($fields as $key => $meta) {
            $values[$key] = site_text($key, $meta[2]);
        }

        $this->view('admin/content/index', [
            'title'  => 'متن‌های سایت',
            'admin'  => (new AuthService())->user(),
            'fields' => $fields,
            'values' => $values,
        ], 'admin/layouts/admin');
    }

    /** POST /admin/content */
    public function update(Request $request): never
    {
        $this->ensureCsrf($request);

        foreach (array_keys($this->fields()) as $key) {
            $value = $request->input($key);
            if ($value !== null) {
                Setting::set('text_' . $key, trim((string) $value), 'content');
            }
        }

        Response::success([], 'متن‌ها ذخیره شد.');
    }
}
