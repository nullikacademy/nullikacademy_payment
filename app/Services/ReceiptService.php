<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Config;
use App\Models\Receipt;
use RuntimeException;

/**
 * Secure receipt upload handling. Validates MIME + extension + size,
 * generates a random filename and stores OUTSIDE the public web root.
 */
final class ReceiptService
{
    /**
     * @param array $file A single $_FILES entry.
     * @return array{ok:bool,message:string,token?:string,path?:string}
     */
    public function store(array $file): array
    {
        $cfg = Config::get('services.upload');

        if (!isset($file['tmp_name']) || $file['error'] !== UPLOAD_ERR_OK) {
            return ['ok' => false, 'message' => 'خطا در بارگذاری فایل.'];
        }

        if (!is_uploaded_file($file['tmp_name'])) {
            return ['ok' => false, 'message' => 'فایل نامعتبر است.'];
        }

        if ((int) $file['size'] > (int) $cfg['max_size']) {
            return ['ok' => false, 'message' => 'حجم فایل نباید بیش از ۱۰ مگابایت باشد.'];
        }

        // Validate real MIME via fileinfo (never trust the client header).
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = (string) $finfo->file($file['tmp_name']);
        if (!in_array($mime, $cfg['allowed_mimes'], true)) {
            return ['ok' => false, 'message' => 'فرمت فایل مجاز نیست. فقط jpg، png و webp پذیرفته می‌شود.'];
        }

        $ext = $this->extensionFor($mime);
        if (!in_array($ext, $cfg['allowed_ext'], true)) {
            return ['ok' => false, 'message' => 'پسوند فایل مجاز نیست.'];
        }

        // Confirm it is a genuine image.
        if (@getimagesize($file['tmp_name']) === false) {
            return ['ok' => false, 'message' => 'فایل تصویری معتبر نیست.'];
        }

        $dir = base_path(Config::get('app.receipts_path', 'storage/receipts')) . '/' . date('Y/m');
        if (!is_dir($dir) && !@mkdir($dir, 0750, true) && !is_dir($dir)) {
            throw new RuntimeException('Cannot create receipts directory.');
        }

        $filename = bin2hex(random_bytes(20)) . '.' . $ext;
        $absolute = $dir . '/' . $filename;

        if (!move_uploaded_file($file['tmp_name'], $absolute)) {
            return ['ok' => false, 'message' => 'ذخیره فایل با خطا مواجه شد.'];
        }
        @chmod($absolute, 0640);

        // Store relative path (relative to receipts root).
        $relative = date('Y/m') . '/' . $filename;
        $record = Receipt::store(
            $relative,
            substr((string) ($file['name'] ?? 'receipt'), 0, 255),
            $mime,
            (int) $file['size']
        );

        return [
            'ok'      => true,
            'message' => 'رسید با موفقیت بارگذاری شد.',
            'token'   => $record['token'],
            'path'    => $relative,
        ];
    }

    public function absolutePath(string $relative): string
    {
        return base_path(Config::get('app.receipts_path', 'storage/receipts')) . '/' . ltrim($relative, '/');
    }

    private function extensionFor(string $mime): string
    {
        return match ($mime) {
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/webp' => 'webp',
            default      => 'bin',
        };
    }
}
