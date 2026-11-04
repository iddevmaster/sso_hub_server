<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Course;
use App\Models\User as Customer;
use App\Models\User_has_course;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Str;

class CustomerController extends Controller
{
    // public function customerAuth(Request $request) : RedirectResponse
    // {
    //     $credentials = $request->validate([
    //         'citizen_id' => ['required'],
    //         'password' => ['required'],
    //     ]);

    //     if (Auth::guard('customer')->attempt($credentials)) {
    //         $request->session()->regenerate();
    //         return redirect('/');
    //     }

    //     return back()->withErrors([
    //         'loginError' => 'Citizen ID or Password does not match.',
    //     ]);
    // }

    public function storeCustomer(Request $request) {
        $request->validate([
            'citizen_id' => 'required|unique:users,username|max:255',
            'name' => 'required|max:255',
            'pass' => 'required|max:13|min:8',
        ], [
            'citizen_id.max' => 'Citizen ID must be 1-13 character.',
            'pass.max' => 'Password must be 8-13 character.',
            'pass.min' => 'Password must be 8-13 character.',
        ]);
        $cust = Customer::create([
            'username' => $request->citizen_id,
            'password' => Hash::make($request->pass),
            'prefix' => $request->prefix,
            'name' => $request->name,
            'lname' => $request->lname,
            'gender' => $request->gend,
            'address' => $request->addr,
            'province' => $request->prov,
            'dob' => $request->dob,
            'phone' => $request->phone,
            'staff' => $request->user()->id,
            'brn' => $request->user()->brn,
            'agn' => $request->user()->agn,
            'role' => 'customer'
        ]);

        $cust->assignRole('customer');

        return response()->json(['message' => $request->all()]);
    }

    public function updateCustomer(Request $request) {
        $request->validate([
            'id' => 'required',
            'name' => 'required|max:255',
        ]);

        $cust = Customer::find($request->oid);
        $cust->update([
            'username' => $request->id,
            'prefix' => $request->prefix,
            'name' => $request->name,
            'lname' => $request->lname,
            'gender' => $request->gend,
            'address' => $request->addr,
            'province' => $request->prov,
            'dob' => $request->dob,
            'phone' => $request->phone,
        ]);

        if ($request->pass) {
            $request->validate([
                'pass' => 'required|max:13|min:8',
            ], [
                'pass.max' => 'Password must be 8-13 character.',
                'pass.min' => 'Password must be 8-13 character.',
            ]);
            $cust->password = Hash::make($request->pass);
        }

        $cust->save();

        return response()->json(['message' => $request->all()]);
    }

    public function deleteCustomer(Request $request) {
        $cust = Customer::find($request->delId);
        $cust->delete();
        return response()->json(['message' => $request->delId]);
    }

    public function importData(Request $request) {
        try {
            $datas = json_decode($request->data);

            foreach ($datas as $data) {
                $cust = Customer::where('username', $data[7])->first();
                $course = Course::where('name', $data[4])->first();
                if (!($course ?? false)) {
                    $course = Course::create([
                        'code' => '',
                        'name' => $data[4],
                    ]);
                    $course->code = $request->user()->getAgn->prefix . $course->id . date("Y");
                    $course->save();
                }
                if (!($cust ?? false)) {
                    $cust = Customer::create([
                        "name" => $data[0],
                        "gender" => $data[1],
                        "brn" => $request->user()->getBrn->name,
                        "agn" => $request->user()->getAgn->name,
                        "phone" => $data[5],
                        "address" => $data[6],
                        "username" => $data[7],
                        "password" => Hash::make($data[7]),
                        'role' => 'customer'
                    ]);
                    $cust->assignRole('customer');
                }

                $cus_course = $cust->course;
                if (count($cus_course ?? []) > 0) {
                    if (!in_array($course->id, $cus_course)) {
                        $cus_course[] = $course->id;
                        $cust->course = json_encode($cus_course);
                    }
                } else {
                    $cust->course = json_encode([$course->id]);
                }
                $cust->save();
            }
            return response()->json(['success' => "Data has been saved!", 'count' => count($datas ?? [])]);
        } catch (\Throwable $th) {
            return response()->json(['error' => $th->getMessage()]);
        }
    }

    public function deletedCustomer() {
        $customers = Customer::role('customer')->onlyTrashed()->orderBy('created_at', 'desc')->get();
        return view('pages.deleted-customer', compact('customers'));
    }

    public function deletePerm(Request $request) {
        Customer::onlyTrashed()->where('id' , $request->delId)->forceDelete();
        return response()->json(['message' => $request->delId]);
    }

    public function reuseCustomer(Request $request) {
        Customer::where('id' , $request->reuid)->restore();
        return response()->json(['message' => $request->reuid]);
    }

    public function cleanCustomer() {
        Customer::onlyTrashed()->forceDelete();
        return response()->json(['message' => "Success"]);
    }

    public function addCourse(Request $request, $uid) {
        try {
            $user_has_course = User_has_course::where('user_id', $uid)->where('course_id', $request->courseSelect)->count();
            if (!$user_has_course) {
                User_has_course::create([
                    'user_id' => $uid,
                    'course_id' => $request->courseSelect
                ]);
            }
            return back()->with('success', 'บันทึกข้อมูลสำเร็จ!');
        } catch (\Throwable $th) {
            //throw $th;
            return back()->withErrors($th->getMessage());
        }
    }

    public function removeCourse($uid, $cid) {
        User_has_course::where('user_id', $uid)->where('course_id', $cid)->delete();
        return response()->json(['message' => $uid. ' ' . $cid]);
    }
}
