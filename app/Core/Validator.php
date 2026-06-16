<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Rule-based validator. Supports: required, email, min:n, max:n,
 * numeric, integer, in:a,b,c, regex:/.../, persian, mobile_ir,
 * confirmed, boolean.
 */
final class Validator
{
    private array $errors = [];

    public function __construct(
        private array $data,
        private array $rules,
        private array $messages = []
    ) {
    }

    public static function make(array $data, array $rules, array $messages = []): self
    {
        return new self($data, $rules, $messages);
    }

    public function fails(): bool
    {
        $this->validate();
        return !empty($this->errors);
    }

    public function passes(): bool
    {
        return !$this->fails();
    }

    public function errors(): array
    {
        return $this->errors;
    }

    public function firstError(): ?string
    {
        foreach ($this->errors as $fieldErrors) {
            return $fieldErrors[0];
        }
        return null;
    }

    public function validated(): array
    {
        $result = [];
        foreach (array_keys($this->rules) as $field) {
            if (array_key_exists($field, $this->data)) {
                $result[$field] = $this->data[$field];
            }
        }
        return $result;
    }

    private function validate(): void
    {
        $this->errors = [];
        foreach ($this->rules as $field => $ruleSet) {
            $rules = is_array($ruleSet) ? $ruleSet : explode('|', $ruleSet);
            $value = $this->data[$field] ?? null;

            foreach ($rules as $rule) {
                [$name, $param] = array_pad(explode(':', $rule, 2), 2, null);
                $this->applyRule($field, $name, $param, $value);
            }
        }
    }

    private function applyRule(string $field, string $rule, ?string $param, mixed $value): void
    {
        $isEmpty = $value === null || $value === '';

        // Skip non-required rules when value is empty.
        if ($isEmpty && $rule !== 'required') {
            return;
        }

        $valid = match ($rule) {
            'required'  => !$isEmpty && !(is_array($value) && count($value) === 0),
            'email'     => filter_var($value, FILTER_VALIDATE_EMAIL) !== false,
            'numeric'   => is_numeric($value),
            'integer'   => filter_var($value, FILTER_VALIDATE_INT) !== false,
            'boolean'   => in_array($value, [true, false, 0, 1, '0', '1', 'true', 'false'], true),
            'min'       => $this->checkMin($value, (int) $param),
            'max'       => $this->checkMax($value, (int) $param),
            'in'        => in_array((string) $value, explode(',', (string) $param), true),
            'regex'     => @preg_match($param, (string) $value) === 1,
            'persian'   => preg_match('/^[\x{0600}-\x{06FF}\s\x{200C}a-zA-Z]+$/u', (string) $value) === 1,
            'mobile_ir' => preg_match('/^(?:0|98|\+98)?9\d{9}$/', (string) $value) === 1,
            'confirmed' => ($value === ($this->data[$field . '_confirmation'] ?? null)),
            default     => true,
        };

        if (!$valid) {
            $this->addError($field, $rule, $param);
        }
    }

    private function checkMin(mixed $value, int $min): bool
    {
        return is_numeric($value) ? $value >= $min : mb_strlen((string) $value) >= $min;
    }

    private function checkMax(mixed $value, int $max): bool
    {
        return is_numeric($value) ? $value <= $max : mb_strlen((string) $value) <= $max;
    }

    private function addError(string $field, string $rule, ?string $param): void
    {
        $key = "{$field}.{$rule}";
        $message = $this->messages[$key]
            ?? $this->messages[$field]
            ?? $this->defaultMessage($field, $rule, $param);
        $this->errors[$field][] = $message;
    }

    private function defaultMessage(string $field, string $rule, ?string $param): string
    {
        return match ($rule) {
            'required'  => 'این فیلد الزامی است.',
            'email'     => 'ایمیل وارد شده معتبر نیست.',
            'numeric'   => 'مقدار باید عددی باشد.',
            'integer'   => 'مقدار باید عدد صحیح باشد.',
            'min'       => "حداقل مقدار مجاز {$param} است.",
            'max'       => "حداکثر مقدار مجاز {$param} است.",
            'in'        => 'مقدار انتخابی معتبر نیست.',
            'persian'   => 'لطفاً فقط از حروف فارسی یا انگلیسی استفاده کنید.',
            'mobile_ir' => 'شماره موبایل معتبر نیست.',
            'confirmed' => 'تکرار مقدار مطابقت ندارد.',
            default     => 'مقدار وارد شده معتبر نیست.',
        };
    }
}
