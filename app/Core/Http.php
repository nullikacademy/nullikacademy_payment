<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Tiny cURL-based HTTP client for outbound calls (IPPanel, Telegram,
 * Tabdeal price feed).
 */
final class Http
{
    public static function get(string $url, array $headers = [], int $timeout = 15): array
    {
        return self::request('GET', $url, null, $headers, $timeout);
    }

    public static function postJson(string $url, array $payload, array $headers = [], int $timeout = 15): array
    {
        $headers['Content-Type'] = 'application/json';
        return self::request('POST', $url, json_encode($payload, JSON_UNESCAPED_UNICODE), $headers, $timeout);
    }

    public static function postForm(string $url, array $payload, array $headers = [], int $timeout = 15): array
    {
        $headers['Content-Type'] = 'application/x-www-form-urlencoded';
        return self::request('POST', $url, http_build_query($payload), $headers, $timeout);
    }

    private static function request(string $method, string $url, ?string $body, array $headers, int $timeout): array
    {
        $ch = curl_init($url);
        $headerLines = [];
        foreach ($headers as $name => $value) {
            $headerLines[] = "{$name}: {$value}";
        }

        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST  => $method,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => $timeout,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_HTTPHEADER     => $headerLines,
            CURLOPT_USERAGENT      => 'NullikAcademy/1.0',
        ]);

        if ($body !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        }

        $response = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            return ['ok' => false, 'status' => 0, 'error' => $error, 'body' => null, 'json' => null];
        }

        $json = json_decode((string) $response, true);

        return [
            'ok'     => $status >= 200 && $status < 300,
            'status' => $status,
            'error'  => $error ?: null,
            'body'   => $response,
            'json'   => is_array($json) ? $json : null,
        ];
    }
}
