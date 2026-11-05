<?php

namespace App\Integrations\Dtms;

use App\Models\Agency;
use App\Models\Course;
use App\Models\DtmsBranchMap;
use App\Models\DtmsCourseTypeMap;
use App\Models\DtmsSyncFailure;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Throwable;

class SnapshotApplier implements ItemApplier
{
    private const KEEP_STATUSES = ['active', 'pending_cancellation', 'completed'];

    /** true เมื่อมีการเขียนจริงระหว่าง apply หนึ่ง item (ใช้คำนวณ UPDATED / UNCHANGED) */
    private bool $changed = false;

    public function apply(array $item, bool $dryRun = false): ApplyResult
    {
        $schoolId = (string) config('dtms.school_id');
        if ($schoolId === '') {
            return ApplyResult::failed($item, 'DTMS_SCHOOL_ID not configured');
        }
        if ((string) ($item['school_id'] ?? '') !== $schoolId) {
            return ApplyResult::of(ApplyResult::UNCHANGED, $item, 'out of scope');
        }

        $this->changed = false;
        DB::beginTransaction();
        try {
            $result = $this->dispatch($item);
            if ($dryRun) {
                DB::rollBack();

                return $result;
            }
            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();
            if (! $dryRun) {
                $this->recordFailure($item, $e->getMessage());
            }

            return ApplyResult::failed($item, $e->getMessage());
        }

        $this->resolveFailures($item);

        return $result;
    }

    private function dispatch(array $item): ApplyResult
    {
        return match ($item['type'] ?? null) {
            'school' => $this->applySchool($item),
            'branch' => $this->applyBranch($item),
            'course' => $this->applyCourse($item),
            'student' => $this->applyStudent($item),
            default => throw new SnapshotRuleViolation('unknown item type: '.($item['type'] ?? 'null')),
        };
    }

    private function agencyId(): string
    {
        $agn = (string) config('dtms.agency_id');
        if ($agn === '' || ! Agency::where('agn_id', $agn)->exists()) {
            throw new SnapshotRuleViolation("agency {$agn} not found");
        }

        return $agn;
    }

    private function applySchool(array $item): ApplyResult
    {
        $this->agencyId();

        return ApplyResult::of(ApplyResult::UNCHANGED, $item);
    }

    private function applyBranch(array $item): ApplyResult
    {
        $data = $item['data'] ?? [];
        $map = DtmsBranchMap::firstOrNew(['dtms_id' => $item['id']]);
        $exists = $map->exists;
        $map->fill([
            'dtms_code' => $data['code'] ?? null,
            'dtms_name' => $data['name'] ?? null,
            'is_active' => ((bool) ($data['is_active'] ?? true) && ! (bool) ($data['deleted'] ?? false)) ? 1 : 0,
        ]);
        if (! $exists) {
            $map->save();

            return ApplyResult::of(ApplyResult::CREATED, $item);
        }
        if ($map->isDirty()) {
            $map->save();

            return ApplyResult::of(ApplyResult::UPDATED, $item);
        }

        return ApplyResult::of(ApplyResult::UNCHANGED, $item);
    }

    private function applyCourse(array $item): ApplyResult
    {
        $data = $item['data'] ?? [];
        $this->ensureCourse([
            'id' => $item['id'],
            'code' => $data['code'] ?? null,
            'name' => $data['name'] ?? null,
            'vehicle_type' => $data['vehicle_type'] ?? null,
        ], $created);

        if ($created) {
            return ApplyResult::of(ApplyResult::CREATED, $item);
        }

        return ApplyResult::of($this->changed ? ApplyResult::UPDATED : ApplyResult::UNCHANGED, $item);
    }

    /** หา/ผูก/สร้าง Course จากข้อมูล course (ใช้ทั้ง item ชนิด course และ course ที่ฝังใน enrollment) */
    private function ensureCourse(array $c, &$created = null): Course
    {
        $created = false;
        $vehicle = (string) ($c['vehicle_type']['code'] ?? '');
        $type = DtmsCourseTypeMap::where('vehicle_type_code', $vehicle)->where('is_foreign', false)->value('course_type');
        if ($type === null) {
            throw new SnapshotRuleViolation("course type not mapped for vehicle type '{$vehicle}'");
        }

        $course = Course::where('dtms_id', $c['id'])->first();
        if (! $course && ! empty($c['code'])) {
            $course = Course::where('code', $c['code'])->whereNull('dtms_id')->first();
            if ($course) {
                $course->forceFill(['dtms_id' => $c['id']]);
            }
        }
        if (! $course) {
            $course = new Course();
            $course->forceFill([
                'dtms_id' => $c['id'],
                'code' => $c['code'] ?? null,
                'name' => $c['name'] ?? null,
                'from' => 4, // 4 = DTMS (1 = SSO, 2 = EM, 3 = POS)
                'agn' => $this->agencyId(),
                'course_type' => $type,
                'dtms_vehicle_type_code' => $vehicle,
            ])->save();
            $created = true;
            $this->changed = true;

            return $course;
        }

        $course->forceFill(['name' => $c['name'] ?? $course->name, 'course_type' => $type, 'dtms_vehicle_type_code' => $vehicle]);
        if ($course->isDirty()) {
            $course->save();
            $this->changed = true;
        }

        return $course;
    }

    private function applyStudent(array $item): ApplyResult
    {
        $data = $item['data'] ?? [];
        // DTMS อาจส่ง id_number มาพร้อมช่องว่างท้าย (เช่น CHAR ที่เติมช่องว่าง) ต้องตัดก่อน ไม่เช่นนั้น
        // รหัสผ่านเริ่มต้นจะมีช่องว่างติดไปด้วย แต่หน้า login ตัดช่องว่างทิ้ง (TrimStrings) จึงเข้าไม่ได้
        $idNumber = trim((string) ($data['id_number'] ?? ''));
        if ($idNumber === '') {
            throw new SnapshotRuleViolation('student id_number missing');
        }
        $enrollments = $data['enrollments'] ?? [];
        $live = array_values(array_filter($enrollments, fn ($e) => ($e['status'] ?? null) !== 'cancelled'));
        foreach ($live as $e) {
            if (! in_array($e['status'] ?? null, self::KEEP_STATUSES, true)) {
                throw new SnapshotRuleViolation('unknown enrollment status: '.($e['status'] ?? 'null'));
            }
        }
        usort($live, fn ($a, $b) => strtotime($a['enrolled_at']) <=> strtotime($b['enrolled_at']));
        $latest = $live ? end($live) : null;

        $user = User::withTrashed()->where('dtms_student_id', $item['id'])->first()
            ?? User::withTrashed()->where('username', $idNumber)->first();

        if (! $user && ! $latest) {
            return ApplyResult::of(ApplyResult::UNCHANGED, $item);
        }

        $brnId = null;
        if ($latest) {
            $brnId = DtmsBranchMap::where('dtms_id', $latest['branch']['id'] ?? '')->value('brn_id');
            if ($brnId === null || $brnId === '') {
                throw new SnapshotRuleViolation('branch not mapped');
            }
        }

        $agn = $this->agencyId();
        $created = false;
        $restored = false;
        $names = [
            'prefix' => $data['title_prefix'] ?? null,
            'name' => $data['first_name'] ?? '',
            'lname' => $data['last_name'] ?? null,
            'fname_eng' => $data['fullname_en'] ?? null,
            'dtms_student_id' => $item['id'],
        ];

        if (! $user) {
            // ต่างชาติ = เลขบัตรไม่ใช่ตัวเลข 13 หลัก (DTMS ไม่ส่ง course_option แล้ว)
            $foreign = IdNumber::isForeign($idNumber);
            $user = new User();
            $user->forceFill($names + [
                'username' => $idNumber,
                'password' => Hash::make($idNumber),
                'role' => 'customer',
                'agn' => $agn,
                'brn' => $brnId,
                'nationality' => $foreign ? 'EN' : 'TH',
            ])->save();
            Role::findOrCreate('customer');
            $user->assignRole('customer');
            $created = true;
        } else {
            if ($user->trashed()) {
                $user->restore();
                $restored = true;
                $this->changed = true;
            }
            $user->forceFill($names);
            if ($latest) {
                $user->forceFill(['brn' => $brnId, 'agn' => $agn]);
            }
            if ($user->isDirty()) {
                $user->save();
                $this->changed = true;
            }
        }

        foreach ($enrollments as $e) {
            $this->applyEnrollment($user, $e);
        }

        if ($created) {
            return ApplyResult::of(ApplyResult::CREATED, $item);
        }
        if ($restored) {
            return ApplyResult::of(ApplyResult::RESTORED, $item);
        }

        return ApplyResult::of($this->changed ? ApplyResult::UPDATED : ApplyResult::UNCHANGED, $item);
    }

    private function applyEnrollment(User $user, array $e): void
    {
        $row = DB::table('user_has_courses')->where('dtms_enrollment_id', $e['id'])->first();
        $now = now()->format('Y-m-d H:i:s');

        if (($e['status'] ?? null) === 'cancelled') {
            if ($row && $row->deleted_at === null) {
                DB::table('user_has_courses')->where('dtms_enrollment_id', $e['id'])
                    ->update(['deleted_at' => $now, 'updated_at' => $now]);
                $this->changed = true;
            }

            return;
        }

        $course = $this->ensureCourse($e['course'] ?? []);
        $enrolledAt = Carbon::parse($e['enrolled_at'])->setTimezone(config('app.timezone'))->format('Y-m-d H:i:s');

        if (! $row) {
            // แถวเดิมของระบบเก่า (ไม่มี dtms_enrollment_id) หรือแถวคอร์สเดียวกันที่เคยผูกกับ enrollment อื่น
            // (primary key คือ user_id + course_id จึงมีได้แถวเดียว)
            $legacy = DB::table('user_has_courses')
                ->where('user_id', $user->id)->where('course_id', $course->id)->first();
            if ($legacy) {
                DB::table('user_has_courses')->where('user_id', $user->id)->where('course_id', $course->id)
                    ->update(['dtms_enrollment_id' => $e['id'], 'deleted_at' => null,
                        'created_at' => $enrolledAt, 'updated_at' => $now]);
            } else {
                DB::table('user_has_courses')->insert([
                    'user_id' => $user->id, 'course_id' => $course->id, 'dtms_enrollment_id' => $e['id'],
                    'created_at' => $enrolledAt, 'updated_at' => $now, 'deleted_at' => null,
                ]);
            }
            $this->changed = true;

            return;
        }

        $update = [];
        if ($row->deleted_at !== null) {
            $update['deleted_at'] = null;
        }
        if ((int) $row->user_id !== (int) $user->id) {
            $update['user_id'] = $user->id;
        }
        if ((int) $row->course_id !== (int) $course->id) {
            $update['course_id'] = $course->id;
        }
        if ($row->created_at === null || Carbon::parse($row->created_at)->format('Y-m-d H:i:s') !== $enrolledAt) {
            $update['created_at'] = $enrolledAt;
        }
        if ($update) {
            DB::table('user_has_courses')->where('dtms_enrollment_id', $e['id'])
                ->update($update + ['updated_at' => $now]);
            $this->changed = true;
        }
    }

    private function recordFailure(array $item, string $error): void
    {
        $type = (string) ($item['type'] ?? '');
        $id = (string) ($item['id'] ?? '');
        $failure = DtmsSyncFailure::where('event_type', $type)->where('entity_id', $id)
            ->whereNull('resolved_at')->first() ?? new DtmsSyncFailure(['event_type' => $type, 'entity_id' => $id]);
        $failure->fill(['error' => $error, 'payload' => $item, 'resolved_at' => null])->save();
    }

    private function resolveFailures(array $item): void
    {
        DtmsSyncFailure::where('event_type', (string) ($item['type'] ?? ''))
            ->where('entity_id', (string) ($item['id'] ?? ''))
            ->whereNull('resolved_at')
            ->update(['resolved_at' => now()]);
    }
}
