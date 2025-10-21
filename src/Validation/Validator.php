<?php

declare(strict_types=1);

namespace App\Validation;

final class Validator
{
    /** @var array<string, string> */
    private array $errors = [];

    /** @param array<string, mixed> $data */
    public function __construct(private readonly array $data)
    {
    }

    public static function make(array $data): self
    {
        return new self($data);
    }

    public function requireString(string $field, int $min = 1, ?int $max = null): self
    {
        $value = $this->data[$field] ?? null;
        if (!is_string($value) || trim($value) === '') {
            $this->errors[$field] = 'The ' . $field . ' field is required.';
            return $this;
        }

        $length = mb_strlen(trim($value));
        if ($min > 0 && $length < $min) {
            $this->errors[$field] = 'The ' . $field . ' must be at least ' . $min . ' characters.';
        }

        if ($max !== null && $length > $max) {
            $this->errors[$field] = 'The ' . $field . ' must be at most ' . $max . ' characters.';
        }

        return $this;
    }

    public function email(string $field): self
    {
        $value = $this->data[$field] ?? null;
        if (!is_string($value) || filter_var($value, FILTER_VALIDATE_EMAIL) === false) {
            $this->errors[$field] = 'The ' . $field . ' must be a valid email address.';
        }

        return $this;
    }

    public function optionalString(string $field, int $min = 0, ?int $max = null): self
    {
        if (!array_key_exists($field, $this->data) || $this->data[$field] === null) {
            return $this;
        }

        $value = $this->data[$field];
        if (!is_string($value)) {
            $this->errors[$field] = 'The ' . $field . ' must be a string.';
            return $this;
        }

        $length = mb_strlen(trim($value));
        if ($min > 0 && $length < $min) {
            $this->errors[$field] = 'The ' . $field . ' must be at least ' . $min . ' characters.';
        }

        if ($max !== null && $length > $max) {
            $this->errors[$field] = 'The ' . $field . ' must be at most ' . $max . ' characters.';
        }

        return $this;
    }

    public function requireInt(string $field): self
    {
        $value = $this->data[$field] ?? null;
        if (!is_int($value)) {
            $this->errors[$field] = 'The ' . $field . ' must be an integer.';
        }

        return $this;
    }

    public function optionalInt(string $field): self
    {
        if (!array_key_exists($field, $this->data) || $this->data[$field] === null) {
            return $this;
        }

        if (!is_int($this->data[$field])) {
            $this->errors[$field] = 'The ' . $field . ' must be an integer.';
        }

        return $this;
    }

    public function requireArray(string $field): self
    {
        $value = $this->data[$field] ?? null;
        if (!is_array($value)) {
            $this->errors[$field] = 'The ' . $field . ' must be an array.';
        }

        return $this;
    }

    public function arrayOfIntegers(string $field): self
    {
        $value = $this->data[$field] ?? null;
        if (!is_array($value)) {
            $this->errors[$field] = 'The ' . $field . ' must be an array.';

            return $this;
        }

        foreach ($value as $index => $item) {
            if (!is_int($item)) {
                $this->errors[$field . '.' . $index] = 'All values in ' . $field . ' must be integers.';
            }
        }

        return $this;
    }

    /**
     * @param array<mixed> $allowed
     */
    public function in(string $field, array $allowed): self
    {
        $value = $this->data[$field] ?? null;
        if ($value === null || !in_array($value, $allowed, true)) {
            $this->errors[$field] = 'The ' . $field . ' value is invalid.';
        }

        return $this;
    }

    /**
     * @param array<mixed> $allowed
     */
    public function optionalIn(string $field, array $allowed): self
    {
        if (!array_key_exists($field, $this->data) || $this->data[$field] === null) {
            return $this;
        }

        if (!in_array($this->data[$field], $allowed, true)) {
            $this->errors[$field] = 'The ' . $field . ' value is invalid.';
        }

        return $this;
    }

    public function date(string $field, string $format = 'Y-m-d'): self
    {
        $value = $this->data[$field] ?? null;
        if (!is_string($value) || !$this->isValidDate($value, $format)) {
            $this->errors[$field] = 'The ' . $field . ' must be a valid date with format ' . $format . '.';
        }

        return $this;
    }

    public function optionalDate(string $field, string $format = 'Y-m-d'): self
    {
        if (!array_key_exists($field, $this->data) || $this->data[$field] === null || $this->data[$field] === '') {
            return $this;
        }

        if (!is_string($this->data[$field]) || !$this->isValidDate((string)$this->data[$field], $format)) {
            $this->errors[$field] = 'The ' . $field . ' must be a valid date with format ' . $format . '.';
        }

        return $this;
    }

    /** @return array<string, string> */
    public function errors(): array
    {
        return $this->errors;
    }

    public function fails(): bool
    {
        return $this->errors !== [];
    }

    private function isValidDate(string $value, string $format): bool
    {
        $date = \DateTimeImmutable::createFromFormat($format, $value);

        return $date !== false && $date->format($format) === $value;
    }
}
