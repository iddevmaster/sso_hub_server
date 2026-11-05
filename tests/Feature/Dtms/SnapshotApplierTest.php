<?php

namespace Tests\Feature\Dtms;

use App\Integrations\Dtms\ApplyResult;
use App\Integrations\Dtms\SnapshotApplier;
use App\Models\Agency;
use App\Models\Branch;
use App\Models\Course;
use App\Models\DtmsBranchMap;
use App\Models\DtmsCourseTypeMap;
use App\Models\DtmsSyncFailure;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SnapshotApplierTest extends TestCase
{
    use RefreshDatabase;

    private const SCHOOL = '11111111-1111-4111-8111-111111111111';
    private const BRANCH = '22222222-2222-4222-8222-222222222222';

    private SnapshotApplier $applier;

    protected function setUp(): void
    {
        parent::setUp();
        config(['dtms.school_id' => self::SCHOOL, 'dtms.agency_id' => 'IDD0002']);
        Agency::create(['name' => 'IDDrives', 'agn_id' => 'IDD0002']);
        Branch::create(['name' => 'โนนทัน', 'agn' => '1', 'brn_id' => 'IDD0001']);
        DtmsBranchMap::create(['dtms_id' => self::BRANCH, 'dtms_code' => 'KK', 'brn_id' => 'IDD0001']);
        foreach ([['CAR', 'T1'], ['MC', 'T2']] as [$v, $code]) {
            DtmsCourseTypeMap::create(['vehicle_type_code' => $v, 'is_foreign' => false, 'course_type' => $code]);
        }
        $this->applier = new SnapshotApplier();
    }

    private function items(string $fixture): array
    {
        return json_decode(file_get_contents(base_path("tests/Fixtures/dtms/$fixture.json")), true)['items'];
    }

    private function applyAll(string $fixture, bool $dry = false): array
    {
        return array_map(fn ($i) => $this->applier->apply($i, $dry), $this->items($fixture));
    }

    private function student(string $fixture, int $index = null): array
    {
        $students = array_values(array_filter($this->items($fixture), fn ($i) => $i['type'] === 'student'));

        return $students[$index ?? 0];
    }

    private function links(User $u, bool $trashed = false)
    {
        $q = DB::table('user_has_courses')->where('user_id', $u->id);

        return $trashed ? $q->whereNotNull('deleted_at') : $q->whereNull('deleted_at');
    }

    public function test_new_student_creates_everything(): void
    {
        $r = $this->applyAll('01-new-student');
        $this->assertSame(
            [ApplyResult::UNCHANGED, ApplyResult::UPDATED, ApplyResult::CREATED, ApplyResult::CREATED],
            array_map(fn ($x) => $x->outcome, $r)
        );

        $u = User::where('username', '1100000000011')->first();
        $this->assertTrue(Hash::check('1100000000011', $u->password));
        $this->assertSame('customer', $u->role);
        $this->assertTrue($u->hasRole('customer'));
        $this->assertSame('นาย', $u->prefix);
        $this->assertSame('สมชาย', $u->name);
        $this->assertSame('ใจดี', $u->lname);
        $this->assertSame('Mr. Somchai Jaidee', $u->fname_eng);
        $this->assertNull($u->lname_eng);
        $this->assertSame('IDD0002', $u->agn);
        $this->assertSame('IDD0001', $u->brn);
        $this->assertSame('TH', $u->nationality);
        $this->assertSame('a0000000-0000-4000-8000-000000000001', $u->dtms_student_id);

        $course = Course::where('dtms_id', '33333333-3333-4333-8333-333333333331')->first();
        $this->assertSame('CAR-01', $course->code);
        $this->assertSame('T1', $course->course_type);
        $this->assertSame('IDD0002', $course->agn);

        $link = $this->links($u)->first();
        $this->assertSame($course->id, (int) $link->course_id);
        $this->assertSame('e0000000-0000-4000-8000-000000000001', $link->dtms_enrollment_id);
        $this->assertSame('2026-11-04', substr($link->created_at, 0, 10));
    }

    public function test_branch_item_never_touches_branches_table(): void
    {
        $before = Branch::all()->toArray();
        $this->applyAll('01-new-student');
        $this->assertSame($before, Branch::all()->toArray());
        $map = DtmsBranchMap::where('dtms_id', self::BRANCH)->first();
        $this->assertSame('IDD0001', $map->brn_id);
        $this->assertSame('สาขาตัวอย่าง', $map->dtms_name);
        $this->assertTrue((bool) $map->is_active);
    }

    public function test_branch_deleted_marks_inactive(): void
    {
        $item = $this->items('01-new-student')[1];
        $item['data']['deleted'] = true;
        $this->assertSame(ApplyResult::UPDATED, $this->applier->apply($item)->outcome);
        $this->assertFalse((bool) DtmsBranchMap::where('dtms_id', self::BRANCH)->value('is_active'));
    }

    public function test_idempotent_second_run_is_unchanged(): void
    {
        foreach (['01-new-student', '03-two-courses-different-vehicle', '04-nulls', '05-all-completed', '06-substituted-student'] as $f) {
            $this->applyAll($f);
            foreach ($this->applyAll($f) as $r) {
                $this->assertSame(ApplyResult::UNCHANGED, $r->outcome, "$f {$r->type}");
            }
        }
    }

    public function test_cancelled_only_unknown_student_is_not_created(): void
    {
        $r = $this->applier->apply($this->student('02-cancelled-only'));
        $this->assertSame(ApplyResult::UNCHANGED, $r->outcome);
        $this->assertSame(0, User::count());
    }

    public function test_cancelled_enrollment_soft_deletes_link_of_existing_student(): void
    {
        $item = $this->student('01-new-student');
        $this->applyAll('01-new-student');
        $item['data']['enrollments'][0]['status'] = 'cancelled';
        $r = $this->applier->apply($item);
        $this->assertSame(ApplyResult::UPDATED, $r->outcome);
        $u = User::where('username', '1100000000011')->first();
        $this->assertSame(0, $this->links($u)->count());
        $this->assertSame(1, $this->links($u, true)->count());
        $this->assertSame(ApplyResult::UNCHANGED, $this->applier->apply($item)->outcome);

        // กลับมา active อีกครั้ง -> restore แถวเดิม
        $item['data']['enrollments'][0]['status'] = 'active';
        $this->assertSame(ApplyResult::UPDATED, $this->applier->apply($item)->outcome);
        $this->assertSame(1, $this->links($u)->count());
    }

    public function test_two_courses_different_vehicle_types(): void
    {
        $this->applyAll('03-two-courses-different-vehicle');
        $u = User::where('username', '1100000000033')->first();
        $this->assertSame(2, $this->links($u)->count());
        $latest = $this->links($u)->orderByDesc('created_at')->first();
        $this->assertSame('T2', Course::find($latest->course_id)->course_type);
        $this->assertSame('MC-01', Course::find($latest->course_id)->code);
    }

    public function test_nulls_are_accepted(): void
    {
        $r = $this->applyAll('04-nulls');
        $this->assertFalse(collect($r)->contains(fn ($x) => $x->isFailure()));
        $u = User::where('username', '1100000000044')->first();
        $this->assertNull($u->prefix);
        $this->assertNull($u->fname_eng);
        $this->assertSame('TH', $u->nationality);
        $this->assertSame(1, $this->links($u)->count());
    }

    public function test_all_completed_keeps_links(): void
    {
        $this->applyAll('05-all-completed');
        $u = User::where('username', '1100000000055')->first();
        $this->assertSame(2, $this->links($u)->count());
        $this->assertSame('TH', $u->nationality);
    }

    public function test_non_13_digit_id_number_is_foreign(): void
    {
        $r = $this->applyAll('09-foreign-student');
        $this->assertFalse(collect($r)->contains(fn ($x) => $x->isFailure()));
        $u = User::where('username', 'AB1234567')->first();
        $this->assertSame('EN', $u->nationality);
        $this->assertSame('CAR', Course::where('code', 'CAR-01')->value('dtms_vehicle_type_code'));
    }

    public function test_id_number_with_trailing_spaces_is_trimmed_for_username_and_password(): void
    {
        $item = $this->student('09-foreign-student');
        $item['data']['id_number'] = 'AB1234567    ';

        $this->applier->apply($item);

        $u = User::where('username', 'AB1234567')->first();
        $this->assertSame('AB1234567', $u->getRawOriginal('username'));
        $this->assertTrue(Hash::check('AB1234567', $u->password));
        $this->assertSame('EN', $u->nationality);
    }

    public function test_substituted_student_item_set(): void
    {
        $r = $this->applyAll('06-substituted-student');
        $this->assertSame(
            [ApplyResult::UNCHANGED, ApplyResult::UPDATED, ApplyResult::CREATED, ApplyResult::UNCHANGED, ApplyResult::CREATED],
            array_map(fn ($x) => $x->outcome, $r)
        );
        $this->assertNull(User::where('username', '1100000000066')->first());
        $new = User::where('username', '1100000000077')->first();
        $this->assertSame(1, $this->links($new)->count());
    }

    public function test_empty_page_has_nothing_to_apply(): void
    {
        $this->assertSame([], $this->items('07-empty-page'));
    }

    public function test_unmapped_vehicle_type_records_failure_without_crash(): void
    {
        $r = $this->applyAll('08-unmapped-vehicle-type');
        $this->assertSame(ApplyResult::FAILED, $r[2]->outcome);
        $this->assertSame(ApplyResult::FAILED, $r[3]->outcome);
        $this->assertSame(0, Course::count());
        $this->assertSame(0, User::count());
        $f = DtmsSyncFailure::where('event_type', 'student')->first();
        $this->assertNotNull($f);
        $this->assertNull($f->resolved_at);
        $this->assertSame($this->student('08-unmapped-vehicle-type'), $f->payload);
        $this->assertSame(2, DtmsSyncFailure::count());
    }

    public function test_failure_is_upserted_then_resolved_on_success(): void
    {
        $item = $this->student('08-unmapped-vehicle-type');
        $this->applier->apply($item);
        $this->applier->apply($item);
        $this->assertSame(1, DtmsSyncFailure::count());

        DtmsCourseTypeMap::create(['vehicle_type_code' => 'TRAILER', 'is_foreign' => false, 'course_type' => 'T3']);
        $this->assertSame(ApplyResult::CREATED, $this->applier->apply($item)->outcome);
        $this->assertNotNull(DtmsSyncFailure::first()->resolved_at);
        $this->assertSame(1, User::count());
    }

    public function test_branch_not_mapped_skips_whole_student(): void
    {
        DtmsBranchMap::query()->update(['brn_id' => null]);
        $r = $this->applier->apply($this->student('01-new-student'));
        $this->assertSame(ApplyResult::FAILED, $r->outcome);
        $this->assertSame('branch not mapped', $r->message);
        $this->assertSame(0, User::count());
        $this->assertSame(0, Course::count());
        $this->assertSame(0, DB::table('user_has_courses')->count());
        $this->assertSame('branch not mapped', DtmsSyncFailure::first()->error);

        DtmsBranchMap::query()->delete();
        $this->assertSame(ApplyResult::FAILED, $this->applier->apply($this->student('01-new-student'))->outcome);
        $this->assertSame(1, DtmsSyncFailure::count());
    }

    public function test_existing_user_found_by_username_is_updated_without_touching_password(): void
    {
        $u = User::create(['name' => 'เก่า', 'username' => '1100000000011', 'password' => 'secret-old', 'brn' => 'IDD0009', 'agn' => 'IDD0002', 'role' => 'customer']);
        $hash = $u->password;
        $r = $this->applier->apply($this->student('01-new-student'));
        $this->assertSame(ApplyResult::UPDATED, $r->outcome);
        $u->refresh();
        $this->assertSame($hash, $u->password);
        $this->assertSame('สมชาย', $u->name);
        $this->assertSame('IDD0001', $u->brn);
        $this->assertSame('a0000000-0000-4000-8000-000000000001', $u->dtms_student_id);
        $this->assertSame(1, User::count());
    }

    public function test_existing_user_found_by_dtms_student_id_even_if_username_changed(): void
    {
        User::create(['name' => 'x', 'username' => 'changed-username', 'password' => 'p', 'brn' => 'IDD0001', 'agn' => 'IDD0002', 'role' => 'customer'])
            ->forceFill(['dtms_student_id' => 'a0000000-0000-4000-8000-000000000001'])->save();
        $this->applier->apply($this->student('01-new-student'));
        $this->assertSame(1, User::withTrashed()->count());
    }

    public function test_soft_deleted_user_is_restored(): void
    {
        $u = User::create(['name' => 'เก่า', 'username' => '1100000000011', 'password' => 'p', 'brn' => 'IDD0001', 'agn' => 'IDD0002', 'role' => 'customer']);
        $u->delete();
        $r = $this->applier->apply($this->student('01-new-student'));
        $this->assertSame(ApplyResult::RESTORED, $r->outcome);
        $this->assertNull($u->fresh()->deleted_at);
        $this->assertSame(1, User::withTrashed()->count());
        $this->assertSame(ApplyResult::UNCHANGED, $this->applier->apply($this->student('01-new-student'))->outcome);
    }

    public function test_legacy_user_has_course_row_is_adopted(): void
    {
        $u = User::create(['name' => 'เก่า', 'username' => '1100000000011', 'password' => 'p', 'brn' => 'IDD0001', 'agn' => 'IDD0002', 'role' => 'customer']);
        $c = Course::create(['code' => 'CAR-01', 'name' => 'ชื่อเก่า', 'from' => 1, 'agn' => 'IDD0002']);
        DB::table('user_has_courses')->insert(['user_id' => $u->id, 'course_id' => $c->id, 'created_at' => '2020-01-01 00:00:00', 'updated_at' => '2020-01-01 00:00:00']);

        $this->applyAll('01-new-student');

        $this->assertSame(1, Course::count());
        $c->refresh();
        $this->assertSame('33333333-3333-4333-8333-333333333331', $c->dtms_id);
        $this->assertSame('อบรมรถยนต์ 15 ชม.', $c->name);
        $this->assertSame('T1', $c->course_type);
        $this->assertSame(1, DB::table('user_has_courses')->count());
        $row = DB::table('user_has_courses')->first();
        $this->assertSame('e0000000-0000-4000-8000-000000000001', $row->dtms_enrollment_id);
        $this->assertSame('2026-11-04', substr($row->created_at, 0, 10));
    }

    public function test_soft_deleted_link_with_same_enrollment_is_restored(): void
    {
        $this->applyAll('01-new-student');
        DB::table('user_has_courses')->update(['deleted_at' => now()]);
        $r = $this->applier->apply($this->student('01-new-student'));
        $this->assertSame(ApplyResult::UPDATED, $r->outcome);
        $this->assertSame(1, DB::table('user_has_courses')->whereNull('deleted_at')->count());
        $this->assertSame(1, DB::table('user_has_courses')->count());
    }

    public function test_out_of_scope_and_school_items(): void
    {
        $item = $this->student('01-new-student');
        $item['school_id'] = '99999999-9999-4999-8999-999999999999';
        $r = $this->applier->apply($item);
        $this->assertSame(ApplyResult::UNCHANGED, $r->outcome);
        $this->assertSame('out of scope', $r->message);
        $this->assertSame(0, User::count());

        $school = $this->items('01-new-student')[0];
        $this->assertSame(ApplyResult::UNCHANGED, $this->applier->apply($school)->outcome);

        Agency::query()->delete();
        $this->assertSame(ApplyResult::FAILED, $this->applier->apply($school)->outcome);
    }

    public function test_school_id_not_configured_fails_without_writing(): void
    {
        config(['dtms.school_id' => '']);
        $r = $this->applier->apply($this->student('01-new-student'));
        $this->assertSame(ApplyResult::FAILED, $r->outcome);
        $this->assertSame('DTMS_SCHOOL_ID not configured', $r->message);
        $this->assertSame(0, User::count());
        $this->assertSame(0, DtmsSyncFailure::count());
    }

    public function test_dry_run_writes_nothing_but_reports_outcome(): void
    {
        $r = $this->applyAll('01-new-student', true);
        $this->assertSame(
            [ApplyResult::UNCHANGED, ApplyResult::UPDATED, ApplyResult::CREATED, ApplyResult::CREATED],
            array_map(fn ($x) => $x->outcome, $r)
        );
        $this->assertSame(0, User::count());
        $this->assertSame(0, Course::count());
        $this->assertSame(0, DB::table('user_has_courses')->count());
        $this->assertFalse(DtmsBranchMap::where('dtms_code', 'KK')->where('dtms_name', 'สาขาตัวอย่าง')->exists());
    }

    public function test_dry_run_does_not_record_or_resolve_failures(): void
    {
        $r = $this->applyAll('08-unmapped-vehicle-type', true);
        $this->assertSame(ApplyResult::FAILED, $r[3]->outcome);
        $this->assertSame(0, DtmsSyncFailure::count());

        $item = $this->student('08-unmapped-vehicle-type');
        $this->applier->apply($item);
        DtmsCourseTypeMap::create(['vehicle_type_code' => 'TRAILER', 'is_foreign' => false, 'course_type' => 'T3']);
        $this->assertSame(ApplyResult::CREATED, $this->applier->apply($item, true)->outcome);
        $this->assertNull(DtmsSyncFailure::first()->resolved_at);
        $this->assertSame(0, User::count());
    }
}
