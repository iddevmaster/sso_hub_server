<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Auth::routes();
Route::get('/', [App\Http\Controllers\HomeController::class, 'index'])->name('index');
Route::get('/toggle-theme/{isdark}', [App\Http\Controllers\HomeController::class, 'toggleTheme'])->name('toggleTheme');
Route::get('/users', [App\Http\Controllers\HomeController::class, 'userTable'])->name('userTable')->middleware('admin');
Route::get('/data', [App\Http\Controllers\HomeController::class, 'dataTable'])->name('dataTable')->middleware('adminAndStaff');
// Route::get('/permissions', [App\Http\Controllers\HomeController::class, 'permTable'])->name('user.perm');
Route::get('/customers', [App\Http\Controllers\HomeController::class, 'customerTable'])->name('customerTable')->middleware('adminAndStaff');
Route::post('/different-account', [App\Http\Controllers\HomeController::class, 'getDifferentAccount'])->name('different-account');
Route::post('/users/store', [App\Http\Controllers\HomeController::class, 'storeUser'])->name('user.store')->middleware('adminAndStaff');
Route::post('/users/update', [App\Http\Controllers\HomeController::class, 'updateUser'])->name('user.update')->middleware('adminAndStaff');
Route::post('/users/delete', [App\Http\Controllers\HomeController::class, 'deleteUser'])->name('user.delete')->middleware('adminAndStaff');
Route::post('/data/add', [App\Http\Controllers\HomeController::class, 'addData'])->name('data.add')->middleware('adminAndStaff');
Route::post('/data/update', [App\Http\Controllers\HomeController::class, 'updateData'])->name('data.update')->middleware('adminAndStaff');
Route::post('/data/delete', [App\Http\Controllers\HomeController::class, 'deleteData'])->name('data.delete')->middleware('adminAndStaff');

Route::post('/customer/store', [App\Http\Controllers\CustomerController::class, 'storeCustomer'])->name('customer.store')->middleware('adminAndStaff');
Route::post('/customer/update', [App\Http\Controllers\CustomerController::class, 'updateCustomer'])->name('customer.update')->middleware('adminAndStaff');
Route::post('/customer/delete', [App\Http\Controllers\CustomerController::class, 'deleteCustomer'])->name('customer.delete')->middleware('adminAndStaff');

Route::post('/customer/import', [App\Http\Controllers\CustomerController::class, 'importData'])->name('importData')->middleware('adminAndStaff');
Route::post('/customer/delete-permanently', [App\Http\Controllers\CustomerController::class, 'deletePerm'])->name('delete-permanently')->middleware('admin');
Route::post('/customer/reuse', [App\Http\Controllers\CustomerController::class, 'reuseCustomer'])->name('customer-reuse')->middleware('admin');
Route::get('/customer/clean', [App\Http\Controllers\CustomerController::class, 'cleanCustomer'])->name('customer-clean')->middleware('admin');

Route::get('/customers/deleted', [App\Http\Controllers\CustomerController::class, 'deletedCustomer'])->name('deleted-customers')->middleware('admin');
