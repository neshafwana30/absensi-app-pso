<?php

use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\HolidayController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PositionController;
use App\Http\Controllers\PresenceController;
use Illuminate\Support\Facades\Route;

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

// GRUP ROUTE YANG WAJIB LOGIN (AUTH)
Route::middleware('auth')->group(function () {

    // ==========================================
    // 🛡️ HAK AKSES: ADMIN & OPERATOR ONLY
    // ==========================================
    Route::middleware('role:admin,operator')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard.index');
        
        // Positions (Jabatan)
        Route::resource('/positions', PositionController::class)->only(['index', 'create']);
        Route::get('/positions/edit', [PositionController::class, 'edit'])->name('positions.edit');
        
        // Employees (Karyawan)
        Route::resource('/employees', EmployeeController::class)->only(['index', 'create']);
        Route::get('/employees/edit', [EmployeeController::class, 'edit'])->name('employees.edit');
        
        // Holidays (Hari Libur)
        Route::resource('/holidays', HolidayController::class)->only(['index', 'create']);
        Route::get('/holidays/edit', [HolidayController::class, 'edit'])->name('holidays.edit');
        Route::delete('/holidays/{id}', [HolidayController::class, 'destroy'])->name('holidays.destroy');
        
        // Attendances (Sesi Absensi)
        Route::resource('/attendances', AttendanceController::class)->only(['index', 'create']);
        Route::get('/attendances/edit', [AttendanceController::class, 'edit'])->name('attendances.edit');

        // Presences (Kehadiran & Modul Monitor QR)
        Route::resource('/presences', PresenceController::class)->only(['index']);
        Route::get('/presences/qrcode', [PresenceController::class, 'showQrcode'])->name('presences.qrcode');
        Route::get('/presences/qrcode/download-pdf', [PresenceController::class, 'downloadQrCodePDF'])->name('presences.qrcode.download-pdf');
        
        // 🎯 FIX UTAMA: Rute Regenerate QR Code ditempatkan aman di kamar Admin
        Route::patch('/presences/{attendance}/regenerate-qrcode', [PresenceController::class, 'regenerateQrCode'])->name('presences.qrcode.regenerate');

        Route::get('/presences/{attendance}', [PresenceController::class, 'show'])->name('presences.show');
        
        // Not Present Data (Karyawan Mangkir/Alpa)
        Route::get('/presences/{attendance}/not-present', [PresenceController::class, 'notPresent'])->name('presences.not-present');
        Route::post('/presences/{attendance}/not-present', [PresenceController::class, 'notPresent']);
        
        // Manipulasi Data Hadir & Terima Izin Manual dari Admin
        Route::post('/presences/{attendance}/present', [PresenceController::class, 'presentUser'])->name('presences.present');
        Route::post('/presences/{attendance}/acceptPermission', [PresenceController::class, 'acceptPermission'])->name('presences.acceptPermission');
        
        // Daftar Dokumen Izin Masuk Karyawan
        Route::get('/presences/{attendance}/permissions', [PresenceController::class, 'permissions'])->name('presences.permissions');
    });

    // ==========================================
    // 👤 HAK AKSES: USER / KARYAWAN BIASA
    // ==========================================
    Route::middleware('role:user')->name('home.')->group(function () {
        Route::get('/', [HomeController::class, 'index'])->name('index');
        Route::get('/absensi/{attendance}', [HomeController::class, 'show'])->name('show');
        Route::get('/absensi/{attendance}/permission', [HomeController::class, 'permission'])->name('permission');
    });

    // Logout Sesi Sistem
    Route::delete('/logout', [AuthController::class, 'logout'])->name('auth.logout');
});

// GRUP ROUTE UNTUK TAMU (GUEST / BELUM LOGIN)
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'index'])->name('auth.login');
    Route::post('/login', [AuthController::class, 'authenticate']);
});