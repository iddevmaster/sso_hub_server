<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\User as Customer;
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
            'citizen_id' => 'required|unique:users,email|max:255',
            'name' => 'required|max:255',
            'pass' => 'required|max:13|min:8',
            'course' => 'required',
        ], [
            'citizen_id.max' => 'Citizen ID must be 1-13 character.',
            'pass.max' => 'Password must be 8-13 character.',
            'pass.min' => 'Password must be 8-13 character.',
        ]);
        $cust = Customer::create([
            'email' => $request->citizen_id,
            'password' => Hash::make($request->pass),
            'name' => $request->name,
            'gender' => $request->gend,
            'address' => $request->addr,
            'province' => $request->prov,
            'dob' => $request->dob,
            'phone' => $request->phone,
            'course' => $request->course,
            'staff' => $request->user()->id,
            'brn' => $request->user()->getBrn->name,
            'agn' => $request->user()->getAgn->name,
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
            'email' => $request->id,
            'name' => $request->name,
            'gender' => $request->gend,
            'address' => $request->addr,
            'province' => $request->prov,
            'dob' => $request->dob,
            'phone' => $request->phone,
            'course' => $request->course
        ]);

        if ($request->pass) {
            $request->validate([
                'pass' => 'required|max:13|min:8',
            ], [
                'pass.max' => 'Password must be 8-13 character.',
                'pass.min' => 'Password must be 8-13 character.',
            ]);
            $cust->password = Hash::make($request->pass);
            $cust->save();
        }

        return response()->json(['message' => $request->all()]);
    }

    public function deleteCustomer(Request $request) {
        $cust = Customer::find($request->delId);
        $cust->delete();
        return response()->json(['message' => $request->delId]);
    }

    public function importData(Request $request) {
        $datas = json_decode($request->data);

        foreach ($datas as $data) {
            $cus = Customer::where('email', $data[7])->count();
            if (!($cus > 0)) {
                $cust = Customer::create([
                    "name" => $data[0],
                    "gender" => $data[1],
                    "brn" => $data[3],
                    "agn" => $data[2],
                    "course" => $data[4],
                    "phone" => $data[5],
                    "address" => $data[6],
                    "email" => $data[7],
                    "password" => Hash::make($data[7]),
                    'role' => 'customer'
                ]);
                $cust->assignRole('customer');
            }
        }
        return response()->json(['success' => "Data has been saved!", 'count' => count($datas ?? [])]);
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
}
