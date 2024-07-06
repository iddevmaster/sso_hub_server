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
    $courses_list = User_has_course::where('user_id', $request->user()->id)->get(['course_id']);
    $agn = Agency::where('agn_id', $request->user()->agn)->first(['name', 'agn_id']);
    $brn = Branch::where('brn_id', $request->user()->brn)->first(['name', 'brn_id']);
    // $courses = App\Models\Course::whereIn('id', $courses_list)->get(['code', 'name']);
    $courses = DB::table('courses')
                ->join('course_types', 'courses.course_type', '=', 'course_types.code')
                ->select('courses.course_type', 'course_types.name')
                ->whereIn('courses.id', $courses_list)
                ->get();
    $user_data = [
        "name" => ($request->user()->prefix ? $request->user()->prefix . ' ' : '') . $request->user()->name . ( $request->user()->lname ? (' ' . $request->user()->lname) : ''),
        "username" => $request->user()->username,
        "role" => $request->user()->role,
        "courses" => $courses,
        "branch" => $brn,
        "agency" => $agn,
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
