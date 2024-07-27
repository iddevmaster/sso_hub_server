<?php

namespace App\Http\Controllers;

use App\Models\Agency;
use App\Models\Branch;
use App\Models\Course;
use App\Models\CourseType;
use App\Models\Department;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Session;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        if (!session()->get('theme')) {
            session()->put('theme', 'light');
        }

        $user = auth()->user();
        $courses_list = $user->course ?? [];
        $course = Course::where('id', end($courses_list) ?? '')->first(['name']);
        if ($course->course_type) {
            $course_list = CourseType::where('code', $course->course_type)->first();
            // result of $courses_name is "อบรมรถยนต์ 5 ชม.เพื่อไปสอบที่ขนส่ง" how to find "รถยน" in $courses_name
            if (strpos($course_list->name, "รถยนต์") !== false) {
                $course_type = "car";
            } elseif (strpos($course_list->name, "จักรยานยนต์") !== false) {
                $course_type = "motobike";
            } elseif (strpos($course_list->name, "บรรทุก") !== false) {
                $course_type = "trailer";
            } else {
                $course_type = "car";
            }
        } else {
            $course_type = "car";
        }

        $user_branch = optional($user->getBrn)->name ?? '';
        if (strpos($user_branch, "พยัค") !== false) {
            $send_branch = "idmsPY";
        } elseif (strpos($user_branch, "สารคาม") !== false) {
            $send_branch = "idmsMK";
        } elseif (strpos($user_branch, "แก่งคอย") !== false) {
            $send_branch = "idmsTK";
        } elseif (strpos($user_branch, "ลำลูกกา") !== false) {
            $send_branch = "idmsLLK";
        } elseif (strpos($user_branch, "โปร") !== false) {
            $send_branch = "idmsPRO";
        } else {
            $send_branch = "idmskk";
        }

        return view('home', compact('course_type', 'user', 'send_branch'));
    }

    public function toggleTheme($isDark) {
        if ($isDark == 'true') {
            session()->put('theme', 'dark');
        } else {
            session()->put('theme', 'light');
        }
        return response()->json(['success' => "Theme is set to " . session()->get('theme'), 'theme' => session()->get('theme')]);
    }

    public function getDifferentAccount (Request $request) {
        // Log out the current user
        Auth::logout();
        // Set the intended url to the authorize url
        Session::put("url.intended", $request->current_url);
        // redirect to login form
        return redirect("login");
    }

    public function userTable() {
        $users = User::withoutRole('customer')->orderBy('id', 'desc')->get();
        $agns = Agency::all();
        $brns = Branch::all();
        $perms = Permission::all();
        $roles = Role::all();
        return view('pages.user-table', compact('users', 'agns', 'brns', 'perms', 'roles'));
    }

    public function dataTable() {
        if (Auth::user()->hasRole('admin')) {
            $courses = Course::all();
        } else {
            $courses = Course::where('agn', Auth::user()->agn)->get();
        }
        $agns = Agency::all();
        $brns = Branch::all();
        $perms = Permission::all();
        $roles = Role::all();
        $course_type = CourseType::all();

        return view('pages.data-table', compact('agns', 'brns', 'perms', 'roles', 'courses', 'course_type'));
    }

    public function permTable() {
        $users = User::all();
        $perms = Permission::all();
        $roles = Role::all();
        return view('pages.perm-table', compact('users', 'perms', 'roles'));
    }

    public function addData(Request $request) {
        if ($request->addType === 'agn') {
            Agency::create([
                'name' => $request->agnname,
                'prefix' => $request->prefix,
            ]);
        } elseif ($request->addType === 'course') {
            $course = Course::create([
                'code' => '',
                'name' => $request->cname,
                'agn' => $request->user()->agn,
                'course_type' => $request->ctype,
            ]);
            $course->code = $request->user()->getAgn->prefix . $course->id . date("Y");
            $course->save();
        } elseif ($request->addType === 'brn') {
            Branch::create([
                'name' => $request->bName,
                'agn' => $request->bAgn,
            ]);
        } elseif ($request->addType === 'perm') {
            Permission::create(['name' => $request->permName]);
        } elseif ($request->addType === 'role') {
            Role::create(['name' => $request->roleName]);
        } elseif ($request->addType === 'ctype') {
            $coursetype = CourseType::create(['name' => $request->typename, 'code' => '']);
            $coursetype->code = date("Y") . sprintf("%04d", $coursetype->id);
            $coursetype->save();
        }
        return response()->json(['message' => $request->all()]);
    }

    public function updateData(Request $request) {
        if ($request->addType === 'agn') {
            Agency::find($request->eid)->update([
                'name' => $request->agnname,
                'prefix' => $request->prefix,
            ]);
        } elseif ($request->addType === 'course') {
            Course::find($request->eid)->update([
                'name' => $request->cname,
                'course_type' => $request->ctype,
            ]);
        } elseif ($request->addType === 'brn') {
            Branch::find($request->eid)->update([
                'name' => $request->bName,
                'agn' => $request->bAgn,
            ]);
        } elseif ($request->addType === 'ctype') {
            CourseType::find($request->eid)->update([
                'name' => $request->typename,
            ]);
        }
        return response()->json(['message' => $request->all()]);
    }

    public function deleteData(Request $request) {
        if ($request->deltype === 'agn') {
            Agency::find($request->delid)->delete();
        } elseif ($request->deltype === 'course') {
            Course::find($request->delid)->delete();
        } elseif ($request->deltype === 'ctype') {
            CourseType::find($request->delid)->delete();
        } elseif ($request->deltype === 'brn') {
            Branch::find($request->delid)->delete();
        } elseif ($request->deltype === 'perm') {
            $users = User::permission($request->delid)->get();

            foreach ($users as $key => $user) {
                if ($user->hasPermissionTo($request->delid)) {
                    $user->revokePermissionTo($request->delid);
                }
            }

            Permission::findByName($request->delid)->delete();
        } elseif ($request->deltype === 'role') {
            $users = User::with('roles')->get();

            foreach ($users as $key => $user) {
                if ($user->hasRole($request->delid)) {
                    $user->removeRole($request->delid);
                }
            }

            Role::findByName($request->delid)->delete();
        }

        return response()->json(['message' => $request->all()]);
    }

    public function storeUser(Request $request) {
        try {
            $brn = Branch::where('brn_id' ,$request->brn)->first();
            $user = User::create([
                'prefix' => $request->prefix,
                'name' => $request->name,
                'lname' => $request->lname,
                'username' => $request->uname,
                'password' => Hash::make($request->pass),
                'brn' => $brn->brn_id,
                'agn' => optional($brn->getAgn)->agn_id,
                'role' => '',
                'icon' => '',
            ]);

            if ($request->role) {
                $user->assignRole($request->role);
                $user->role = $request->role;
                $user->save();
            }

            return response()->json(['message' => optional($brn->getAgn)->agn_id]);
        } catch (\Throwable $th) {
            //throw $th;
            // response error message and status
            return response()->json(['error' => $th->getMessage()], 500);
        }
    }

    public function updateUser(Request $request) {
        $brn = Branch::where('brn_id' ,$request->brn)->first();
        $user = User::find($request->eid);
        $roles = Role::pluck('name');
        $user->prefix = $request->prefix;
        $user->name = $request->name;
        $user->lname = $request->lname;
        $user->username = $request->uname;
        $user->brn = $brn->brn_id;
        $user->agn = optional($brn->getAgn)->agn_id;

        if ($request->pass) {
            $user->password = Hash::make($request->pass);
        }

        if ($request->role) {
            foreach ($roles as $role) {
                if ($user->hasRole($role)) {
                    $user->removeRole($role);
                }
            }

            $user->assignRole($request->role);
            $user->role = $request->role;
            $user->save();
        }

        $user->save();

        return response()->json(['message' => $request->all()]);
    }

    public function deleteUser(Request $request) {
        User::find($request->delId)->delete();
        return response()->json(['message' => $request->all()]);
    }

    public function customerTable(Request $request) {
        $search = $request->session()->get('search') ?? '';
        if (Auth::user()->hasRole('admin')) {
            $customers = User::role('customer')->where(function ($query) use ($search) {
                $query->where('username', 'LIKE', "%$search%")
                      ->orWhere('name', 'LIKE', "%$search%")
                      ->orWhere('lname', 'LIKE', "%$search%");
            })->orderBy('id', 'desc')->paginate(10);
        } else {
            $customers = User::role('customer')->where('brn', Auth::user()->brn)->where(function ($query) use ($search) {
                $query->where('username', 'LIKE', "%$search%")
                      ->orWhere('name', 'LIKE', "%$search%")
                      ->orWhere('lname', 'LIKE', "%$search%");
            })->orderBy('id', 'desc')->paginate(10);
        }
        $courses = Course::orderBy('id', 'desc')->get(['id', 'name']);

        return view('pages.customer-table', compact('customers', 'courses', 'search'));
    }

    public function customerLogin() {
        return view('auth.customer-login');
    }

    public function customerSearch(Request $request) {
        $search = $request->query('searchText');
        $request->session()->put('search', $search);

        return redirect()->route('customerTable');
    }
}
