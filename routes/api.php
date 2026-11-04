<?php

use App\Models\Agency;
use App\Models\Branch;
use App\Models\Course;
use App\Models\CourseType;
use App\Models\User_has_course;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:api', 'scope:view-user')->get('/user', function (Request $request) {
    $agn = Agency::where('agn_id', $request->user()->agn)->first(['name', 'agn_id']);
    $brn = Branch::where('brn_id', $request->user()->brn)->first(['name', 'brn_id']);
    // $courses = App\Models\Course::whereIn('id', $courses_list)->get(['code', 'name']);

    if ($request->user()->role !== 'staff') {
        $courses_list = User_has_course::where('user_id', $request->user()->id)->orderBy('created_at', 'desc')->get(['course_id']);
        $courses = DB::table('courses')
                    ->join('course_types', 'courses.course_type', '=', 'course_types.code')
                    ->select('courses.course_type', 'course_types.name')
                    ->where('courses.id', optional($courses_list->first())->course_id)
                    ->get();
        // ต่างชาติ (เลขบัตรไม่ใช่ 13 หลัก) ของคอร์สที่มาจาก DTMS: ใช้ประเภทคอร์สต่างชาติตามประเภทรถ
        $firstCourse = $courses_list->first();
        if ($firstCourse && \App\Integrations\Dtms\IdNumber::isForeign((string) $request->user()->username)) {
            $vehicle = Course::where('id', $firstCourse->course_id)->value('dtms_vehicle_type_code');
            if ($vehicle) {
                $foreignType = DB::table('dtms_course_type_map')
                    ->join('course_types', 'dtms_course_type_map.course_type', '=', 'course_types.code')
                    ->where('dtms_course_type_map.vehicle_type_code', $vehicle)
                    ->where('dtms_course_type_map.is_foreign', true)
                    ->select('course_types.code as course_type', 'course_types.name')
                    ->first();
                if ($foreignType) {
                    $courses = collect([$foreignType]);
                }
            }
        }
        // if ($request->user()->nationality == 'TH') {
        //     $courses = DB::table('courses')
        //             ->join('course_types', 'courses.course_type', '=', 'course_types.code')
        //             ->select('courses.course_type', 'course_types.name')
        //             ->where('courses.id', $courses_list[0]->course_id)
        //             ->get();
        // } else {
        //     $courseType = CourseType::where('code', "20240010")->get(['code', 'name']);
        //     $courses = [
        //         [
        //             "course_type" => $courseType[0]->code,
        //             "name" => $courseType[0]->name,
        //         ]

        //     ];
        // }
    }
    $user_data = [
        "name" => ($request->user()->prefix ? $request->user()->prefix . ' ' : '') . $request->user()->name . ( $request->user()->lname ? (' ' . $request->user()->lname) : ''),
        "username" => $request->user()->username,
        "role" => $request->user()->role,
        "courses" => $courses ?? [],
        "branch" => $brn ?? [],
        "agency" => $agn ?? [],
    ];

    return $user_data;
});

Route::middleware('auth:api', 'scope:i-prompt')->get('/i-prompt', function (Request $request) {
    if ($request->user()->can('i-prompt')) {
        return $request->user();
    } else {
        return ['message' => 'Permission denied'];
    }
});

Route::middleware('auth:api', 'scope:kst-plus')->get('/kst-plus', function (Request $request) {
    return $request->user();
});

Route::middleware('auth:api')->get('/logmeout', function (Request $request) {
    $user = $request->user();
    // $accessToken = $user->token();
    $accessTokens = DB::table("oauth_access_tokens")->where("user_id", $user->id)->get();
    foreach ($accessTokens as $accessToken) {
        DB::table("oauth_refresh_tokens")->where("access_token_id", $accessToken->id)->delete();
    }

    $accessTokens = DB::table("oauth_access_tokens")->where("user_id", $user->id)->delete();
    // DB::table("oauth_refresh_tokens")->where("access_token_id", $accessToken->id)->delete();
    // $accessToken->delete();
    return response()->json([
        "message" => "Revoked"
    ]);
});

Route::middleware('auth.basic')->get('/courses', function () {
    $courses = Course::get(['code', 'name']);
    $res = [];
    foreach ($courses as $course) {
        $charsOnly = preg_replace('/\d+/', '', $course->code);
        $agn = App\Models\Agency::where('prefix', $charsOnly)->first();
        $res[] = [
            "code" => $course->code,
            "name" => $course->name,
            "agn" => $agn->name,
        ];
    }
    return $res;
});

Route::withoutMiddleware('auth.basic')->get('/courseType', function () {
    $courses = CourseType::get(['code', 'name']);
    return $courses;
});
