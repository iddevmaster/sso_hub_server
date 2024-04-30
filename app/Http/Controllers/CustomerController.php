<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Customer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class CustomerController extends Controller
{
    public function customerAuth(Request $request) : RedirectResponse
    {
        $credentials = $request->validate([
            'citizen_id' => ['required'],
            'password' => ['required'],
        ]);

        if (Auth::guard('customer')->attempt($credentials)) {
            $request->session()->regenerate();
            return redirect('/home');
        }

        return back()->withErrors([
            'loginError' => 'Citizen ID or Password does not match.',
        ]);
    }

    public function storeCustomer(Request $request) {
        $request->validate([
            'id' => 'required|unique:customers,citizen_id|max:13',
            'name' => 'required|max:255',
            'pass' => 'required|max:13|min:8',
            'course' => 'required',
        ], [
            'id.max' => 'Citizen ID must be 1-13 character.',
            'pass.max' => 'Password must be 8-13 character.',
            'pass.min' => 'Password must be 8-13 character.',
        ]);
        $cust = Customer::create([
            'citizen_id' => $request->id,
            'password' => Hash::make($request->pass),
            'full_name' => $request->name,
            'gender' => $request->gend ?? '-Unknow-',
            'address' => $request->addr ?? '-Unknow-',
            'province' => $request->prov ?? '-Unknow-',
            'dob' => $request->dob ?? '-Unknow-',
            'phone' => $request->phone ?? '-Unknow-',
            'course' => $request->course,
            'staff' => $request->user()->id,
            'brn' => $request->user()->brn
        ]);
        return response()->json(['message' => $request->all()]);
    }

    public function updateCustomer(Request $request) {
        $request->validate([
            'id' => 'required|max:13',
            'name' => 'required|max:255',
        ], [
            'id.max' => 'Citizen ID must be 1-13 character.',
        ]);

        $cust = Customer::find($request->oid);
        $cust->update([
            'citizen_id' => $request->id,
            'full_name' => $request->name,
            'gender' => $request->gend ?? '-Unknow-',
            'address' => $request->addr ?? '-Unknow-',
            'province' => $request->prov ?? '-Unknow-',
            'dob' => $request->dob ?? '-Unknow-',
            'phone' => $request->phone ?? '-Unknow-',
        ]);

        if ($request->pass) {
            $cust->course = $request->course;
            $cust->save();
        }

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
        Customer::where('citizen_id', $request->delId)->delete();
        return response()->json(['message' => $request->delId]);
    }

    public function importData(Request $request) {
        $datas = json_decode($request->data);

        foreach ($datas as $data) {
            $cus = Customer::find($data[7]);
            if (!$cus) {
                Customer::create([
                    "full_name" => $data[0],
                    "gender" => $data[1],
                    "brn" => json_encode([$data[3],$data[2]]),
                    "course" => $data[4],
                    "phone" => $data[5],
                    "address" => $data[6],
                    "citizen_id" => $data[7],
                    "password" => Hash::make($data[7]),
                    "province" => '-Unknow-',
                    "dob" => '-Unknow-',
                    "staff" => auth()->user()->id
                ]);
            }
        }
        return response()->json(['success' => "Data has been saved!", 'count' => count($datas ?? [])]);
    }

    public function deletedCustomer() {
        $customers = Customer::onlyTrashed()->orderBy('created_at', 'desc')->get();
        return view('pages.deleted-customer', compact('customers'));
    }

    public function deletePerm(Request $request) {
        Customer::onlyTrashed()->where('citizen_id' , $request->delId)->forceDelete();
        return response()->json(['message' => $request->delId]);
    }

    public function reuseCustomer(Request $request) {
        Customer::where('citizen_id' , $request->reuid)->restore();
        return response()->json(['message' => $request->reuid]);
    }

    public function cleanCustomer() {
        Customer::onlyTrashed()->forceDelete();
        return response()->json(['message' => "Success"]);
    }
}
