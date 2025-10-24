<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AttendanceListController;
use App\Http\Controllers\AttendanceDetailController;
use App\Http\Controllers\StampCorrectionRequestController;
use App\Http\Controllers\AttendanceCorrectionAdminController;
use App\Http\Controllers\AttendanceAdminController;
use App\Http\Controllers\StaffAdminController;

// 管理者ログイン（GET表示）
Route::get('/admin/login', [LoginController::class, 'create'])->name('admin.login');

// 管理者ログイン（POST認証）
Route::post('/admin/login', [LoginController::class, 'store'])->name('admin.login.store');

Route::get('/attendance', function () {
    return view('user.attendance.index', ['nav' => 'user'] );
})->name('attendance.index');

//管理者ログイン状態が必要なルート
Route::middleware(['auth', 'admin'])->group(function () {

  // 管理者：日次勤怠一覧
  Route::get('/admin/attendance/list', [AttendanceAdminController::class, 'index'])
    ->name('admin.attendance.list');
  // 管理者：勤怠詳細（管理者からの詳細遷移先）
  Route::get('/admin/attendance/{id}', [AttendanceAdminController::class, 'show'])
    ->name('admin.attendance.show');
  Route::post('/admin/attendance/{id}', [AttendanceAdminController::class, 'update'])
    ->name('admin.attendance.update');
  // 修正申請の承認確認画面（管理者）
  Route::get('/stamp_correction_request/approve/{attendance_correct_request}', [AttendanceCorrectionAdminController::class, 'show'])
    ->name('stamp_correction_request.approve');
  // 承認実行（管理者）
  Route::post('/stamp_correction_request/approve/{attendance_correct_request}', [AttendanceCorrectionAdminController::class, 'approve'])
    ->name('stamp_correction_request.approve.perform');
  // スタッフ一覧（管理者）
  Route::get('/admin/staff/list', [StaffAdminController::class, 'index'])
    ->name('admin.staff.list');
  // スタッフ別 月次勤怠一覧（管理者）
  Route::get('/admin/attendance/staff/{id}', [StaffAdminController::class, 'attendance'])
    ->name('admin.staff.attendance');
  // CSV 出力（当該ユーザーの現在月の勤怠）
  Route::get('/admin/attendance/staff/{id}/export', [StaffAdminController::class, 'exportAttendance'])
    ->name('admin.staff.attendance.export');
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