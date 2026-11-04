<?php

namespace Tests\Feature\Dtms;

use App\Models\Branch;
use App\Models\DtmsBranchMap;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;
use Symfony\Component\Console\Output\BufferedOutput;
use Tests\TestCase;

class BranchMappingCommandsTest extends TestCase
{
    use RefreshDatabase;

    public $mockConsoleOutput = false;

    private string $out = '';

    private function run_(string $cmd, array $args = []): int
    {
        $buf = new BufferedOutput();
        $code = Artisan::call($cmd, $args, $buf);
        $this->out = $buf->fetch();

        return $code;
    }

    private function branch(string $brnId, string $name): Branch
    {
        return Branch::create(['name' => $name, 'agn' => '1', 'brn_id' => $brnId]);
    }

    private function map(string $code, ?string $brnId = null, ?string $uuid = null): DtmsBranchMap
    {
        return DtmsBranchMap::create([
            'dtms_id' => $uuid ?? (string) Str::uuid(),
            'dtms_code' => $code,
            'dtms_name' => 'DTMS '.$code,
            'brn_id' => $brnId,
            'is_active' => true,
        ]);
    }

    public function test_list_empty_hints_to_sync(): void
    {
        $this->assertSame(0, $this->run_('dtms:branches'));
        $this->assertStringContainsString('dtms:sync', $this->out);
    }

    public function test_list_mixed_data(): void
    {
        $this->branch('IDD0001', 'Mapped Branch');
        $this->branch('IDD0002', 'Free Branch');
        $this->map('KK', 'IDD0001');
        $this->map('NEW');

        $this->assertSame(0, $this->run_('dtms:branches'));
        $out = $this->out;
        $this->assertStringContainsString('IDD0001 (Mapped Branch)', $out);
        $this->assertStringContainsString('UNMAPPED', $out);
        $this->assertStringContainsString('IDD0002', $out);
        $this->assertStringContainsString('Free Branch', $out);
    }

    public function test_map_by_code(): void
    {
        $this->branch('IDD0003', 'B3');
        $row = $this->map('KK');

        $this->assertSame(0, $this->run_('dtms:map-branch', ['dtms_code' => 'KK', 'brn_id' => 'IDD0003']));
        $this->assertSame('IDD0003', $row->fresh()->brn_id);
        $this->assertStringContainsString('dtms:sync --retry-failures', $this->out);
    }

    public function test_map_by_uuid(): void
    {
        $this->branch('IDD0003', 'B3');
        $uuid = (string) Str::uuid();
        $row = $this->map('KK', null, $uuid);
        $this->map('KK2');

        $this->assertSame(0, $this->run_('dtms:map-branch', ['dtms_code' => $uuid, 'brn_id' => 'IDD0003']));
        $this->assertSame('IDD0003', $row->fresh()->brn_id);
    }

    public function test_unknown_code(): void
    {
        $this->branch('IDD0003', 'B3');

        $this->assertSame(1, $this->run_('dtms:map-branch', ['dtms_code' => 'ZZ', 'brn_id' => 'IDD0003']));
    }

    public function test_ambiguous_code(): void
    {
        $this->branch('IDD0003', 'B3');
        $a = $this->map('KK');
        $b = $this->map('KK');

        $this->assertSame(1, $this->run_('dtms:map-branch', ['dtms_code' => 'KK', 'brn_id' => 'IDD0003']));
        $this->assertStringContainsString('uuid', $this->out);
        $this->assertNull($a->fresh()->brn_id);
        $this->assertNull($b->fresh()->brn_id);
    }

    public function test_unknown_brn_id_does_not_create_branch(): void
    {
        $row = $this->map('KK');

        $this->assertSame(1, $this->run_('dtms:map-branch', ['dtms_code' => 'KK', 'brn_id' => 'NOPE']));
        $this->assertNull($row->fresh()->brn_id);
        $this->assertSame(0, Branch::count());
    }

    public function test_conflict_and_force(): void
    {
        $this->branch('IDD0003', 'B3');
        $a = $this->map('KK', 'IDD0003');
        $b = $this->map('LL');

        $this->assertSame(1, $this->run_('dtms:map-branch', ['dtms_code' => 'LL', 'brn_id' => 'IDD0003']));
        $this->assertNull($b->fresh()->brn_id);

        $this->assertSame(0, $this->run_('dtms:map-branch', ['dtms_code' => 'LL', 'brn_id' => 'IDD0003', '--force' => true]));
        $this->assertSame('IDD0003', $b->fresh()->brn_id);
        $this->assertSame('IDD0003', $a->fresh()->brn_id);
    }

    public function test_remap_same_row_is_not_conflict(): void
    {
        $this->branch('IDD0003', 'B3');
        $this->map('KK', 'IDD0003');

        $this->assertSame(0, $this->run_('dtms:map-branch', ['dtms_code' => 'KK', 'brn_id' => 'IDD0003']));
    }

    public function test_unmap(): void
    {
        $this->branch('IDD0003', 'B3');
        $row = $this->map('KK', 'IDD0003');

        $this->assertSame(0, $this->run_('dtms:map-branch', ['dtms_code' => 'KK', '--unmap' => true]));
        $this->assertNull($row->fresh()->brn_id);
    }

    public function test_branches_table_untouched(): void
    {
        $this->branch('IDD0001', 'One');
        $this->branch('IDD0002', 'Two');
        $this->map('KK');
        $before = Branch::orderBy('id')->get()->toArray();

        $this->run_('dtms:branches');
        $this->run_('dtms:map-branch', ['dtms_code' => 'KK', 'brn_id' => 'IDD0002']);
        $this->run_('dtms:map-branch', ['dtms_code' => 'KK', '--unmap' => true]);

        $this->assertEquals($before, Branch::orderBy('id')->get()->toArray());
    }
}
