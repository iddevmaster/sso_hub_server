<?php

namespace Tests\Feature\Dtms;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_dtms_tables_and_columns_exist(): void
    {
        foreach (['dtms_branch_map', 'dtms_course_type_map', 'dtms_sync_state', 'dtms_sync_failures'] as $t) {
            $this->assertTrue(Schema::hasTable($t), $t);
        }
        $this->assertTrue(Schema::hasColumn('courses', 'dtms_id'));
        $this->assertTrue(Schema::hasColumn('users', 'dtms_student_id'));
        $this->assertTrue(Schema::hasColumn('user_has_courses', 'dtms_enrollment_id'));
    }
}
