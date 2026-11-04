<?php

namespace Tests\Feature\Dtms;

use App\Models\Agency;
use App\Models\Branch;
use App\Models\DtmsBranchMap;
use App\Models\DtmsCourseTypeMap;
use App\Models\DtmsSyncFailure;
use App\Models\DtmsSyncState;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * ต่อของจริงทั้งสายด้วยกัน: คำสั่ง dtms:sync -> SyncRunner -> DtmsClient (Http::fake) -> SnapshotApplier -> DB
 */
class EndToEndSyncTest extends TestCase
{
    use RefreshDatabase;

    private const SCHOOL = '11111111-1111-4111-8111-111111111111';
    private const BRANCH = '22222222-2222-4222-8222-222222222222';

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'dtms.url' => 'https://dtms.test',
            'dtms.token' => 'test-token',
            'dtms.school_id' => self::SCHOOL,
            'dtms.agency_id' => 'IDD0002',
        ]);
        Agency::create(['name' => 'IDDrives', 'agn_id' => 'IDD0002']);
        Branch::create(['name' => 'โนนทัน', 'agn' => '1', 'brn_id' => 'IDD0003']);
        DtmsCourseTypeMap::create(['vehicle_type_code' => 'CAR', 'is_foreign' => false, 'course_type' => 'T1']);
    }

    private function fixture(string $name): array
    {
        return json_decode(file_get_contents(base_path("tests/Fixtures/dtms/$name.json")), true);
    }

    public function test_unmapped_branch_parks_student_then_retry_after_mapping_creates_user(): void
    {
        Http::fake(['dtms.test/*' => Http::response($this->fixture('01-new-student'))]);

        // รอบแรก: สาขายังไม่จับคู่ -> นักเรียนล้มเหลว (พักไว้) แต่ไม่ crash และ cursor เลื่อน
        $this->artisan('dtms:sync')->assertExitCode(1);
        $this->assertSame(0, User::where('username', '1100000000011')->count());
        $this->assertSame(1, DtmsSyncFailure::whereNull('resolved_at')->count());
        $this->assertSame(1, DtmsBranchMap::whereNull('brn_id')->count());
        $this->assertNotSame('0', DtmsSyncState::first()->cursor);

        // แอดมินจับคู่สาขา แล้วประมวลผลรายการที่พักไว้ซ้ำ
        $this->artisan('dtms:map-branch', ['dtms_code' => 'KK', 'brn_id' => 'IDD0003'])->assertExitCode(0);
        $this->artisan('dtms:sync', ['--retry-failures' => true])->assertExitCode(0);

        $user = User::where('username', '1100000000011')->first();
        $this->assertNotNull($user);
        $this->assertSame('IDD0003', $user->brn);
        $this->assertSame(0, DtmsSyncFailure::whereNull('resolved_at')->count());

        // รอบถัดไปได้ข้อมูลเดิมซ้ำ -> ไม่เปลี่ยนอะไร (idempotent)
        $this->artisan('dtms:sync')->assertExitCode(0);
        $this->assertSame(1, User::where('username', '1100000000011')->count());
    }

    public function test_dry_run_through_the_command_writes_nothing(): void
    {
        Http::fake(['dtms.test/*' => Http::response($this->fixture('01-new-student'))]);
        DtmsBranchMap::create(['dtms_id' => self::BRANCH, 'dtms_code' => 'KK', 'brn_id' => 'IDD0003']);

        $this->artisan('dtms:sync', ['--dry-run' => true])->assertExitCode(0);

        $this->assertSame(0, User::count());
        $this->assertSame(0, DtmsSyncFailure::count());
        $this->assertSame('0', optional(DtmsSyncState::first())->cursor ?? '0');
    }
}
