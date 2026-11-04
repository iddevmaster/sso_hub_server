<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // สาขา DTMS -> brn_id ของ SSO เดิม (จับคู่ด้วยคำสั่ง dtms:map-branch ไม่แก้ตาราง branches)
        Schema::create('dtms_branch_map', function (Blueprint $table) {
            $table->id();
            $table->uuid('dtms_id')->unique();
            $table->string('dtms_code')->nullable();
            $table->string('dtms_name')->nullable();
            $table->string('brn_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // (vehicle type + ตัวเลือกต่างชาติ) -> course_types.code เดิม
        Schema::create('dtms_course_type_map', function (Blueprint $table) {
            $table->id();
            $table->string('vehicle_type_code');
            $table->boolean('is_foreign')->default(false);
            $table->string('course_type');
            $table->timestamps();
            $table->unique(['vehicle_type_code', 'is_foreign']);
        });

        // ตำแหน่งที่อ่าน feed ถึง (แถวเดียว)
        Schema::create('dtms_sync_state', function (Blueprint $table) {
            $table->id();
            $table->string('cursor')->default('0');
            $table->timestamp('last_success_at')->nullable();
            $table->timestamps();
        });

        Schema::create('dtms_sync_failures', function (Blueprint $table) {
            $table->id();
            $table->string('event_type');
            $table->string('entity_id');
            $table->text('error');
            $table->json('payload')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
            $table->index(['event_type', 'entity_id']);
        });

        Schema::table('courses', function (Blueprint $table) {
            $table->uuid('dtms_id')->nullable()->unique();
            $table->string('dtms_vehicle_type_code')->nullable(); // ใช้เลือก course_type ของต่างชาติตอน /api/user
        });
        Schema::table('users', function (Blueprint $table) {
            $table->uuid('dtms_student_id')->nullable()->index();
        });
        Schema::table('user_has_courses', function (Blueprint $table) {
            $table->uuid('dtms_enrollment_id')->nullable()->unique();
        });
    }

    public function down(): void
    {
        Schema::table('user_has_courses', fn (Blueprint $t) => $t->dropColumn('dtms_enrollment_id'));
        Schema::table('users', fn (Blueprint $t) => $t->dropColumn('dtms_student_id'));
        Schema::table('courses', fn (Blueprint $t) => $t->dropColumn(['dtms_id', 'dtms_vehicle_type_code']));
        Schema::dropIfExists('dtms_sync_failures');
        Schema::dropIfExists('dtms_sync_state');
        Schema::dropIfExists('dtms_course_type_map');
        Schema::dropIfExists('dtms_branch_map');
    }
};
