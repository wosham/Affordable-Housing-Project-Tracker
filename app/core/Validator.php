<?php
// Validator — form/request validation with chainable rules
class Validator
{
    private array $errors = [];
    private array $data   = [];

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
        // TODO: Phase 1 — Check field is present and not empty
        return $this;
    }

    public function email(string $field): self
    {
        // TODO: Phase 1 — Validate email format
        return $this;
    }

    public function min(string $field, int $length): self
    {
        // TODO: Phase 1 — Minimum string length
        return $this;
    }

    public function numeric(string $field): self
    {
        // TODO: Phase 1 — Must be numeric
        return $this;
    }

    public function inArray(string $field, array $allowed): self
    {
        // TODO: Phase 1 — Value must be in allowed list
        return $this;
    }

    public function passes(): bool  { return empty($this->errors); }
    public function fails(): bool   { return !$this->passes(); }
    public function errors(): array { return $this->errors; }
    public function firstError(): string { return reset($this->errors) ?: ''; }
}
