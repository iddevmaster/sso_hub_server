<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\User_has_course;
use Illuminate\Console\Command;

class UpdateUserCourse extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update:usercourse';

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
        $users = User::select('id', 'course')->get();

        foreach ($users as $user) {
            $courses = $user->course;
            foreach ($courses ?? [] as $course) {
                $user_has_course = User_has_course::where('user_id', $user->id)->where('course_id', $course)->count();
                if (!$user_has_course) {
                    User_has_course::create([
                        'user_id' => $user->id,
                        'course_id' => $course
                    ]);
                    echo "Create User_has_course success!! \n";
                } else {
                    echo "User " . $user->id . "and course " . $course . " has exist \n";
                }
            }
        }

        echo "...............Update User course success!! ..............\n";
    }
}
