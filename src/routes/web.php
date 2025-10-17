<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AttendanceListController;
use App\Http\Controllers\AttendanceDetailController;
use App\Http\Controllers\StampCorrectionRequestController;
use App\Http\Controllers\AttendanceCorrectionAdminController;

// 管理者ログイン（GET表示）
Route::get('/admin/login', [LoginController::class, 'create'])->name('admin.login');

// 管理者ログイン（POST認証）
Route::post('/admin/login', [LoginController::class, 'store'])->name('admin.login.store');

Route::get('/attendance', function () {
    return view('user.attendance.index', ['nav' => 'user'] );
})->name('attendance.index');

//管理者ログイン状態が必要なルート
Route::middleware(['auth', 'admin'])->group(function () {




});

// ユーザーログイン状態が必要なルート
Route::middleware(['auth', 'verified'])->group(function () {
  //勤怠登録関連
  Route::get('/attendance', [AttendanceController::class, 'index'])->name('attendance.index');
  Route::post('/attendance/clock-in', [AttendanceController::class, 'clockIn'])->name('attendance.clock_in');
  Route::post('/attendance/break/start', [AttendanceController::class, 'breakStart'])->name('attendance.break_start');
  Route::post('/attendance/break/end', [AttendanceController::class, 'breakEnd'])->name('attendance.break_end');
  Route::post('/attendance/clock-out', [AttendanceController::class, 'clockOut'])->name('attendance.clock_out');

  //勤怠実績一覧
  Route::get('/attendance/list', [AttendanceListController::class, 'index'])->name('attendance.list');

  //勤怠実績詳細
  Route::get('/attendance/detail/{id}', [AttendanceDetailController::class, 'show'])->name('attendance.detail');
  Route::post('/attendance/detail/{id}/correction', [AttendanceDetailController::class, 'storeCorrection'])->name('attendance.detail.correction.store');

  //申請一覧
  // 申請一覧（一般ユーザー）
  Route::get('/stamp_correction_request/list', [StampCorrectionRequestController::class, 'index'])
    ->name('stamp_correction_request.list');
});