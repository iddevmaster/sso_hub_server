<?php

namespace App\Http\Controllers;

use App\Models\Agency;
use App\Models\Branch;
use App\Models\Course;
use App\Models\Department;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
        $courses_name = optional($user->getCourse)->name;
        // result of $courses_name is "อบรมรถยนต์ 5 ชม.เพื่อไปสอบที่ขนส่ง" how to find "รถยน" in $courses_name
        if (strpos($courses_name, "รถยนต์") !== false) {
            $course_type = "car";
        } elseif (strpos($courses_name, "จักรยานยนต์") !== false) {
            $course_type = "motobike";
        } elseif (strpos($courses_name, "บรรทุก") !== false) {
            $course_type = "trailer";
        } else {
            $course_type = "car";
        }
        $citizen_id = Crypt::encrypt($user->email);
        return view('home', compact('course_type', 'user', 'citizen_id'));
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
        $agns = Agency::all();
        $brns = Branch::all();
        $perms = Permission::all();
        $roles = Role::all();
        $courses = Course::all();

        return view('pages.data-table', compact('agns', 'brns', 'perms', 'roles', 'courses'));
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
            ]);
        } elseif ($request->addType === 'course') {
            $last_course = Course::where('from', 1)->orderBy('id', 'desc')->first();
            Course::create([
                'code' => ($last_course ? $last_course->code + 1 : 0),
                'name' => $request->cname,
            ]);
        } elseif ($request->addType === 'brn') {
            Branch::create([
                'name' => $request->bName,
                'agn' => $request->bAgn,
            ]);
        } elseif ($request->addType === 'perm') {
            Permission::create(['name' => $request->permName]);
        } elseif ($request->addType === 'role') {
            Role::create(['name' => $request->roleName]);
        }
        return response()->json(['message' => $request->all()]);
    }

    public function updateData(Request $request) {
        if ($request->addType === 'agn') {
            Agency::find($request->eid)->update([
                'name' => $request->agnname,
            ]);
        } elseif ($request->addType === 'course') {
            Course::find($request->eid)->update([
                'name' => $request->cname,
            ]);
        } elseif ($request->addType === 'brn') {
            Branch::find($request->eid)->update([
                'name' => $request->bName,
                'agn' => $request->bAgn,
            ]);
        }
        return response()->json(['message' => $request->all()]);
    }

    public function deleteData(Request $request) {
        if ($request->deltype === 'agn') {
            Agency::find($request->delid)->delete();
        } elseif ($request->deltype === 'course') {
            Course::find($request->delid)->delete();
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
        $brn = Branch::find($request->brn);
        $user = User::create([
            'name' => $request->name,
            'email' => $request->uname,
            'password' => Hash::make($request->pass),
            'brn' => $brn->id,
            'agn' => optional($brn->getAgn)->id,
            'role' => '',
            'icon' => '',
        ]);

        if ($request->role) {
            $user->assignRole($request->role);
            $user->role = $request->role;
            $user->save();
        }

        return response()->json(['message' => $request->all()]);
    }

    public function updateUser(Request $request) {
        $brn = Branch::find($request->brn);
        $user = User::find($request->eid);
        $roles = Role::pluck('name');
        $user->name = $request->name;
        $user->email = $request->uname;
        $user->brn = $brn->id;
        $user->agn = optional($brn->getAgn)->id;

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

    public function customerTable() {
        $customers = User::role('customer')->orderBy('id', 'desc')->get();
        $courses = Course::orderBy('id', 'desc')->get();
        return view('pages.customer-table', compact('customers', 'courses'));
    }

    public function customerLogin() {
        return view('auth.customer-login');
    }
}
