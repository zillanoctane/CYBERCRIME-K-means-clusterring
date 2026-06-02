<?php

use App\Http\Controllers\AnalysisController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CybercrimeController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ReportController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login');

// =========================================================
// Guest
// =========================================================
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
});

// =========================================================
// Authenticated — semua role
// =========================================================
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Lihat data — semua role boleh
    Route::get('/cybercrime', [CybercrimeController::class, 'index'])->name('cybercrime.index');

    // Lihat hasil analisis — semua role boleh melihat
    Route::get('/analisis', [AnalysisController::class, 'index'])->name('analysis.index');
    Route::get('/analisis/{run}', [AnalysisController::class, 'show'])->name('analysis.show');

    Route::get('/laporan/{run}/pdf', [ReportController::class, 'exportPdf'])->name('reports.pdf');
    Route::get('/laporan/{run}/excel', [ReportController::class, 'exportExcel'])->name('reports.excel');
});

// =========================================================
// Authenticated — Admin & Analis
// =========================================================
Route::middleware(['auth', 'role:admin,analis'])->group(function () {
    Route::get('/cybercrime/create', [CybercrimeController::class, 'create'])->name('cybercrime.create');
    Route::post('/cybercrime', [CybercrimeController::class, 'store'])->name('cybercrime.store');
    Route::get('/cybercrime/import', [CybercrimeController::class, 'importForm'])->name('cybercrime.import-form');
    Route::post('/cybercrime/import', [CybercrimeController::class, 'import'])->name('cybercrime.import');
    Route::get('/cybercrime/{cybercrime}/edit', [CybercrimeController::class, 'edit'])->name('cybercrime.edit');
    Route::put('/cybercrime/{cybercrime}', [CybercrimeController::class, 'update'])->name('cybercrime.update');

    Route::get('/analisis/baru/buat', [AnalysisController::class, 'create'])->name('analysis.create');
    Route::post('/analisis/elbow', [AnalysisController::class, 'elbow'])->name('analysis.elbow');
    Route::post('/analisis/preview-count', [AnalysisController::class, 'previewCount'])->name('analysis.preview-count');
    Route::post('/analisis', [AnalysisController::class, 'store'])->name('analysis.store');
});

// =========================================================
// Authenticated — Admin only
// =========================================================
Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::delete('/cybercrime/{cybercrime}', [CybercrimeController::class, 'destroy'])->name('cybercrime.destroy');
    Route::delete('/analisis/{run}', [AnalysisController::class, 'destroy'])->name('analysis.destroy');
});
