<?php

class Validator
{
    private array $errors = [];
    private array $data = [];

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public static function make(array $data): self
    {
        return new self($data);
    }

    public function required(string $field, string $label = ''): self
    {
        $value = $this->data[$field] ?? null;
        if ($value === null || (is_string($value) && trim($value) === '') || (is_array($value) && $value === [])) {
            $this->errors[$field] = ($label !== '' ? $label : self::label($field)) . ' is required.';
        }

        return $this;
    }

    public function email(string $field): self
    {
        $value = trim((string)($this->data[$field] ?? ''));
        if ($value !== '' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $this->errors[$field] = self::label($field) . ' must be a valid email address.';
        }

        return $this;
    }

    public function min(string $field, int $length): self
    {
        $value = (string)($this->data[$field] ?? '');
        if ($value !== '' && mb_strlen($value) < $length) {
            $this->errors[$field] = self::label($field) . ' must be at least ' . $length . ' characters.';
        }

        return $this;
    }

    public function numeric(string $field): self
    {
        $value = $this->data[$field] ?? null;
        if ($value !== null && $value !== '' && !is_numeric($value)) {
            $this->errors[$field] = self::label($field) . ' must be numeric.';
        }

        return $this;
    }

    public function inArray(string $field, array $allowed): self
    {
        $value = $this->data[$field] ?? null;
        if ($value !== null && $value !== '' && !in_array($value, $allowed, true)) {
            $this->errors[$field] = self::label($field) . ' is not a valid option.';
        }

        return $this;
    }

    public function passes(): bool
    {
        return $this->errors === [];
    }

    public function fails(): bool
    {
        return !$this->passes();
    }

    public function errors(): array
    {
        return $this->errors;
    }

    public function firstError(): string
    {
        return reset($this->errors) ?: '';
    }

    private static function label(string $field): string
    {
        return ucwords(str_replace(['_', '-'], ' ', $field));
    }
}
