<?php

namespace App\Integrations\Dtms;

class SyncReport
{
    public const ERROR_AUTH = 'auth';
    public const ERROR_CURSOR_EXPIRED = 'cursor_expired';
    public const ERROR_UNAVAILABLE = 'unavailable';

    public bool $skipped = false;
    public bool $stalled = false;
    public ?string $error = null;
    public ?string $errorMessage = null;
    public int $pages = 0;
    public ?string $cursor = null;

    /** @var array<string,int> */
    public array $counts = [
        ApplyResult::CREATED => 0,
        ApplyResult::UPDATED => 0,
        ApplyResult::RESTORED => 0,
        ApplyResult::UNCHANGED => 0,
        ApplyResult::FAILED => 0,
    ];

    /** @var array<string,array<string,int>> type => outcome => count */
    public array $byType = [];

    /** @var array<int,ApplyResult> */
    public array $failures = [];

    public function add(ApplyResult $r): void
    {
        $this->counts[$r->outcome] = ($this->counts[$r->outcome] ?? 0) + 1;
        $this->byType[$r->type][$r->outcome] = ($this->byType[$r->type][$r->outcome] ?? 0) + 1;
        if ($r->isFailure()) {
            $this->failures[] = $r;
        }
    }

    public function failedCount(): int
    {
        return $this->counts[ApplyResult::FAILED];
    }

    public function total(): int
    {
        return array_sum($this->counts);
    }

    public function isSuccessful(): bool
    {
        return $this->error === null && $this->failedCount() === 0 && ! $this->stalled;
    }
}
