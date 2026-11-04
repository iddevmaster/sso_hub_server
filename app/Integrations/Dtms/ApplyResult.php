<?php

namespace App\Integrations\Dtms;

class ApplyResult
{
    public const CREATED = 'created';
    public const UPDATED = 'updated';
    public const RESTORED = 'restored';
    public const UNCHANGED = 'unchanged';
    public const FAILED = 'failed';

    private function __construct(
        public readonly string $outcome,
        public readonly string $type,
        public readonly string $entityId,
        public readonly ?string $message = null,
    ) {
    }

    public static function of(string $outcome, array $item, ?string $message = null): self
    {
        return new self($outcome, (string) ($item['type'] ?? ''), (string) ($item['id'] ?? ''), $message);
    }

    public static function failed(array $item, string $message): self
    {
        return self::of(self::FAILED, $item, $message);
    }

    public function isFailure(): bool
    {
        return $this->outcome === self::FAILED;
    }
}
