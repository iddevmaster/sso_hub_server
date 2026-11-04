<?php

namespace App\Console\Commands;

use App\Models\Branch;
use App\Models\DtmsBranchMap;
use Illuminate\Console\Command;

class DtmsMapBranch extends Command
{
    protected $signature = 'dtms:map-branch {dtms_code : DTMS branch code or DTMS uuid} {brn_id? : SSO branch brn_id (e.g. IDD0003)} {--force : Allow the brn_id to be already mapped to another DTMS branch} {--unmap : Clear the mapping of this DTMS branch}';

    protected $description = 'Map a DTMS branch (by code or uuid) to an existing SSO branch, or unmap it';

    public function handle(): int
    {
        $key = (string) $this->argument('dtms_code');

        $rows = DtmsBranchMap::where('dtms_id', $key)->get();
        if ($rows->isEmpty()) {
            $rows = DtmsBranchMap::where('dtms_code', $key)->get();
        }

        if ($rows->isEmpty()) {
            $this->error("DTMS branch '{$key}' not found. Run `php artisan dtms:branches` to see the list.");

            return self::FAILURE;
        }
        if ($rows->count() > 1) {
            $this->error("DTMS code '{$key}' matches {$rows->count()} branches. Use the DTMS uuid instead:");
            foreach ($rows as $r) {
                $this->line("  {$r->dtms_id}  {$r->dtms_name}");
            }

            return self::FAILURE;
        }

        $row = $rows->first();

        if ($this->option('unmap')) {
            $row->brn_id = null;
            $row->save();
            $this->info("Unmapped DTMS branch {$row->dtms_code} ({$row->dtms_id}).");

            return self::SUCCESS;
        }

        $brnId = (string) $this->argument('brn_id');
        if ($brnId === '') {
            $this->error('brn_id is required (or use --unmap).');

            return self::FAILURE;
        }

        $branch = Branch::where('brn_id', $brnId)->first();
        if (! $branch) {
            $this->error("SSO branch '{$brnId}' does not exist. Branches are not created by this command.");

            return self::FAILURE;
        }

        $other = DtmsBranchMap::where('brn_id', $brnId)->where('id', '!=', $row->id)->first();
        if ($other && ! $this->option('force')) {
            $this->error("brn_id '{$brnId}' is already mapped to DTMS branch {$other->dtms_code} ({$other->dtms_id}). Use --force to map it anyway.");

            return self::FAILURE;
        }

        $row->brn_id = $brnId;
        $row->save();

        $this->info("Mapped DTMS branch {$row->dtms_code} ({$row->dtms_id}) -> {$brnId} ({$branch->name}).");
        $this->line('Run `php artisan dtms:sync --retry-failures` to process students waiting on this branch.');

        return self::SUCCESS;
    }
}
