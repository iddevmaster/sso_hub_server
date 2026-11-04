<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\CourseType;
use App\Models\User;
use App\Models\User_has_course;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Passport\Passport;
use Tests\TestCase;

class ApiUserTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // The api guard builds a Passport resource server that needs a public key,
        // even with actingAs. Generate throwaway keys in a temp dir (nothing global).
        $dir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'passport-test-keys';
        if (! is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        if (! is_file($dir . '/oauth-public.key')) {
            $key = \phpseclib3\Crypt\RSA::createKey(2048);
            file_put_contents($dir . '/oauth-private.key', (string) $key);
            file_put_contents($dir . '/oauth-public.key', (string) $key->getPublicKey());
        }
        Passport::loadKeysFrom($dir);
    }

    private function makeUser(string $role = 'customer'): User
    {
        return User::create([
            'name' => 'Test',
            'username' => 'u' . uniqid(),
            'password' => bcrypt('secret'),
            'role' => $role,
            'brn' => 'IDD0003',
            'agn' => 'IDD0002',
        ]);
    }

    private function makeCourse(string $typeCode, string $typeName): Course
    {
        CourseType::firstOrCreate(['code' => $typeCode], ['name' => $typeName]);

        return Course::create(['code' => 'C' . uniqid(), 'name' => 'Course', 'course_type' => $typeCode]);
    }

    private function link(User $user, Course $course, string $createdAt): void
    {
        User_has_course::create(['user_id' => $user->id, 'course_id' => $course->id]);
        // composite pivot key: model save() cannot update, so set created_at directly
        DB::table('user_has_courses')
            ->where('user_id', $user->id)->where('course_id', $course->id)
            ->update(['created_at' => $createdAt]);
    }

    public function test_student_without_courses_gets_empty_courses(): void
    {
        $user = $this->makeUser();
        Passport::actingAs($user, ['view-user']);

        $this->getJson('/api/user')
            ->assertOk()
            ->assertJsonStructure(['name', 'username', 'role', 'courses', 'branch', 'agency'])
            ->assertJsonPath('courses', []);
    }

    public function test_student_with_one_course_type(): void
    {
        $user = $this->makeUser();
        $this->link($user, $this->makeCourse('20240001', 'Car'), '2026-01-01 00:00:00');
        Passport::actingAs($user, ['view-user']);

        $this->getJson('/api/user')
            ->assertOk()
            ->assertJsonCount(1, 'courses')
            ->assertJsonPath('courses.0.course_type', '20240001')
            ->assertJsonPath('courses.0.name', 'Car');
    }

    public function test_latest_course_wins(): void
    {
        $user = $this->makeUser();
        $this->link($user, $this->makeCourse('20240001', 'Car'), '2026-01-01 00:00:00');
        $this->link($user, $this->makeCourse('20240002', 'Motorbike'), '2026-02-01 00:00:00');
        Passport::actingAs($user, ['view-user']);

        $this->getJson('/api/user')
            ->assertOk()
            ->assertJsonCount(1, 'courses')
            ->assertJsonPath('courses.0.course_type', '20240002')
            ->assertJsonPath('courses.0.name', 'Motorbike');
    }

    public function test_latest_course_without_type_gives_empty_courses(): void
    {
        $user = $this->makeUser();
        $course = Course::create(['code' => 'X', 'name' => 'No type']);
        $this->link($user, $course, '2026-01-01 00:00:00');
        Passport::actingAs($user, ['view-user']);

        $this->getJson('/api/user')->assertOk()->assertJsonPath('courses', []);
    }

    public function test_staff_gets_empty_courses(): void
    {
        $user = $this->makeUser('staff');
        Passport::actingAs($user, ['view-user']);

        $this->getJson('/api/user')->assertOk()->assertJsonPath('courses', []);
    }

    private function dtmsCourse(string $vehicle): Course
    {
        CourseType::firstOrCreate(['code' => 'T-CAR'], ['name' => 'รถยนต์']);
        CourseType::firstOrCreate(['code' => 'T-CAR-F'], ['name' => 'รถยนต์ต่างชาติ']);
        DB::table('dtms_course_type_map')->insertOrIgnore([
            ['vehicle_type_code' => 'CAR', 'is_foreign' => false, 'course_type' => 'T-CAR'],
            ['vehicle_type_code' => 'CAR', 'is_foreign' => true, 'course_type' => 'T-CAR-F'],
        ]);

        $course = new Course();
        $course->forceFill([
            'code' => 'D' . uniqid(), 'name' => 'DTMS course', 'course_type' => 'T-CAR',
            'dtms_vehicle_type_code' => $vehicle,
        ])->save();

        return $course;
    }

    public function test_foreign_id_number_gets_foreign_course_type_for_dtms_course(): void
    {
        $user = $this->makeUser();
        $user->forceFill(['username' => 'AB1234567'])->save();
        $this->link($user, $this->dtmsCourse('CAR'), '2026-01-01 00:00:00');
        Passport::actingAs($user, ['view-user']);

        $this->getJson('/api/user')
            ->assertOk()
            ->assertJsonPath('courses.0.course_type', 'T-CAR-F')
            ->assertJsonPath('courses.0.name', 'รถยนต์ต่างชาติ');
    }

    public function test_thai_13_digit_id_keeps_normal_course_type_for_dtms_course(): void
    {
        $user = $this->makeUser();
        $user->forceFill(['username' => '1234567890123'])->save();
        $this->link($user, $this->dtmsCourse('CAR'), '2026-01-01 00:00:00');
        Passport::actingAs($user, ['view-user']);

        $this->getJson('/api/user')->assertOk()->assertJsonPath('courses.0.course_type', 'T-CAR');
    }

    public function test_foreign_without_foreign_mapping_falls_back_to_course_type(): void
    {
        $user = $this->makeUser();
        $user->forceFill(['username' => 'AB1234567'])->save();
        $this->link($user, $this->dtmsCourse('MC'), '2026-01-01 00:00:00');
        Passport::actingAs($user, ['view-user']);

        $this->getJson('/api/user')->assertOk()->assertJsonPath('courses.0.course_type', 'T-CAR');
    }
}
