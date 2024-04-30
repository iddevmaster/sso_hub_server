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
Route::get('/customer-login', [App\Http\Controllers\HomeController::class, 'customerLogin'])->withoutMiddleware('auth')->name('customer-login');
Route::post('/customer-auth', [App\Http\Controllers\CustomerController::class, 'customerAuth'])->withoutMiddleware('auth')->name('customer-auth');
Route::get('/logmeout', function (Request $request) {
    // if (auth()->guard('web')->check()) {
    //     auth()->guard('web')->logout();
    // } elseif (auth()->guard('customer')->check()) {
    //     auth()->guard('customer')->logout();
    // }
    auth()->logout();
    auth()->guard('customer')->logout();
    return redirect()->route('customer-login');
})->name('logmeout')->withoutMiddleware('auth')->middleware('auth:customer');
// Route::get('/', function () {
//     return redirect()->route('login');
// });

Auth::routes();
Route::get('/main', [App\Http\Controllers\HomeController::class, 'index'])->name('index');
Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home')->withoutMiddleware('auth')->middleware('auth:customer');
Route::get('/users', [App\Http\Controllers\HomeController::class, 'userTable'])->name('userTable');
Route::get('/data', [App\Http\Controllers\HomeController::class, 'dataTable'])->name('dataTable');
// Route::get('/permissions', [App\Http\Controllers\HomeController::class, 'permTable'])->name('user.perm');
Route::get('/customers', [App\Http\Controllers\HomeController::class, 'customerTable'])->name('customerTable');
Route::post('/different-account', [App\Http\Controllers\HomeController::class, 'getDifferentAccount'])->name('different-account');
Route::post('/users/store', [App\Http\Controllers\HomeController::class, 'storeUser'])->name('user.store');
Route::post('/users/update', [App\Http\Controllers\HomeController::class, 'updateUser'])->name('user.update');
Route::post('/users/delete', [App\Http\Controllers\HomeController::class, 'deleteUser'])->name('user.delete');
Route::post('/data/add', [App\Http\Controllers\HomeController::class, 'addData'])->name('data.add');
Route::post('/data/update', [App\Http\Controllers\HomeController::class, 'updateData'])->name('data.update');
Route::post('/data/delete', [App\Http\Controllers\HomeController::class, 'deleteData'])->name('data.delete');

Route::post('/customer/store', [App\Http\Controllers\CustomerController::class, 'storeCustomer'])->name('customer.store');
Route::post('/customer/update', [App\Http\Controllers\CustomerController::class, 'updateCustomer'])->name('customer.update');
Route::post('/customer/delete', [App\Http\Controllers\CustomerController::class, 'deleteCustomer'])->name('customer.delete');

Route::post('/customer/import', [App\Http\Controllers\CustomerController::class, 'importData'])->name('importData');
Route::post('/customer/delete-permanently', [App\Http\Controllers\CustomerController::class, 'deletePerm'])->name('delete-permanently');
Route::post('/customer/reuse', [App\Http\Controllers\CustomerController::class, 'reuseCustomer'])->name('customer-reuse');
Route::get('/customer/clean', [App\Http\Controllers\CustomerController::class, 'cleanCustomer'])->name('customer-clean');

Route::get('/customers/deleted', [App\Http\Controllers\CustomerController::class, 'deletedCustomer'])->name('deleted-customers');
