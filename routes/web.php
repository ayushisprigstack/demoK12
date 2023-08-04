<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\InventoryController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\GoogleAuthController;
use App\Http\Controllers\DeviceTypeController;
use App\Mail\MyTestMail;
use App\Http\Controllers\SchoolController;
use App\Http\Controllers\SentEmailController;
use App\Http\Controllers\FedexController;
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

//Route::get('/', [DeviceTypeController::class, 'allDevice']);

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    
});
 Route::get('/upload', [InventoryController::class, 'showform']);
    Route::post('/upload', [InventoryController::class, 'store']); 

Route::get('/test', function () {
    return view('test1');
});

Route::get('/send-email', [SchoolController::class, 'Test']);
Route::get('/email', function () {
    return view('emails.addUserMail');
});
//require __DIR__.'/auth.php';
