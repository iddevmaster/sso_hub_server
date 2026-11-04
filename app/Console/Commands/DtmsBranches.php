<?php

namespace App\Console\Commands;

use App\Models\Branch;
use App\Models\DtmsBranchMap;
use Illuminate\Console\Command;

class DtmsBranches extends Command
{
    protected $signature = 'dtms:branches';

    protected $description = 'List DTMS branches and their mapping to SSO branches, plus SSO branches not yet mapped';

    public function handle(): int
    {
        $maps = DtmsBranchMap::orderBy('dtms_code')->get();

        if ($maps->isEmpty()) {
            $this->info('No DTMS branches found. Run `php artisan dtms:sync` first.');

            return self::SUCCESS;
        }

        $ssoNames = Branch::pluck('name', 'brn_id');

        $rows = $maps->map(function (DtmsBranchMap $m) use ($ssoNames) {
            $mapped = $m->brn_id !== null && $m->brn_id !== '';

            return [
                $m->dtms_code,
                $m->dtms_name,
                $m->dtms_id,
                $mapped ? $m->brn_id.' ('.($ssoNames[$m->brn_id] ?? 'SSO branch not found').')' : '*** UNMAPPED ***',
                $m->is_active ? 'yes' : 'no',
            ];
        })->all();

        $this->info('DTMS branches');
        $this->table(['Code', 'Name', 'DTMS id', 'SSO branch', 'Active'], $rows);

        $unmapped = $maps->filter(fn ($m) => empty($m->brn_id))->count();
        if ($unmapped > 0) {
            $this->warn("{$unmapped} DTMS branch(es) not mapped. Use: php artisan dtms:map-branch {dtms_code} {brn_id}");
        }

        $used = $maps->pluck('brn_id')->filter()->all();
        $free = Branch::whereNotIn('brn_id', $used)->orderBy('brn_id')->get(['brn_id', 'name']);

        $this->info('SSO branches not yet mapped');
        if ($free->isEmpty()) {
            $this->line('(none)');
        } else {
            $this->table(['brn_id', 'Name'], $free->map(fn ($b) => [$b->brn_id, $b->name])->all());
        }

        return self::SUCCESS;
    }
}
