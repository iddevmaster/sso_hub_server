<?php

namespace App\Console\Commands;

use App\Models\Course;
use App\Models\CourseType;
use Illuminate\Console\Command;

class UpdateCourseType extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update:coursetype';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $courses = Course::all();
        $carcode = CourseType::where('name', 'รถยนต์')->first();
        if (!$carcode) {
            $carcode = CourseType::create([
                'code' => '',
                'name' => 'รถยนต์',
            ]);
            $carcode->code = date("Y") . sprintf("%04d", $carcode->id);
            $carcode->save();
        }

        $motocode = CourseType::where('name', 'รถจักรยานยนต์')->first();
        if (!$motocode) {
            $motocode = CourseType::create([
                'code' => '',
                'name' => 'รถจักรยานยนต์',
            ]);
            $motocode->code = date("Y") . sprintf("%04d", $motocode->id);
            $motocode->save();
        }

        $carcodef = CourseType::where('name', 'รถยนต์ต่างชาติ')->first();
        if (!$carcodef) {
            $carcodef = CourseType::create([
                'code' => '',
                'name' => 'รถยนต์ต่างชาติ',
            ]);
            $carcodef->code = date("Y") . sprintf("%04d", $carcodef->id);
            $carcodef->save();
        }

        $motocodef = CourseType::where('name', 'รถจักรยานยนต์ต่างชาติ')->first();
        if (!$motocodef) {
            $motocodef = CourseType::create([
                'code' => '',
                'name' => 'รถจักรยานยนต์ต่างชาติ',
            ]);
            $motocodef->code = date("Y") . sprintf("%04d", $motocodef->id);
            $motocodef->save();
        }

        foreach ($courses as $course) {
            $course_name = $course->name;
            if ((strpos($course_name, "รถยนต์") || strpos($course_name, "รย.")) && !strpos($course_name, "ต่างชาติ")) {
                $course_type = $carcode->code;
            } elseif ((strpos($course_name, "จักรยานยนต์") || strpos($course_name, "จยย.")) && !strpos($course_name, "ต่างชาติ")) {
                $course_type = $motocode->code;
            } elseif ((strpos($course_name, "รถยนต์") || strpos($course_name, "รย.")) && strpos($course_name, "ต่างชาติ")) {
                $course_type = $carcodef->code;
            } elseif ((strpos($course_name, "จักรยานยนต์") || strpos($course_name, "จยย.")) && strpos($course_name, "ต่างชาติ")) {
                $course_type = $motocodef->code;
            } else {
                $course_type = null;
            }
            $course->course_type = $course_type;
            $course->save();
        }
    }
}
