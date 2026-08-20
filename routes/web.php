<?php

use App\Http\Controllers\Admin\IntakeUploadDownloadController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\DocumentDownloadController;
use App\Http\Controllers\ReceiptController;
use App\Models\IntakeSubmission;
use App\Models\Package;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Public
Route::get('/', function () {
    $packages = Package::where('is_active', true)->orderBy('sort_order')->get()->keyBy('slug');

    return view('welcome', compact('packages'));
})->name('home');
Route::get('/contact', fn () => view('contact'))->name('contact');

// Auth
Route::get('/login', fn () => view('auth.login'))->name('login');
Route::get('/register', function (Request $request) {
    if ($request->filled('package')) {
        session(['intended_package' => $request->query('package')]);
    }

    return view('auth.register');
})->name('register');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

// Client portal — guest-accessible: a first-time visitor creates their account as part of
// paying for a package in Step 1, matching the marketplace's combined signup + payment flow.
Route::get('/portal', fn () => view('portal'))->name('portal');

Route::middleware('auth')->group(function () {
    Route::get('/documents/{document}/download', [DocumentDownloadController::class, 'show'])
        ->name('documents.download');
    Route::get('/orders/{order}/receipt', [ReceiptController::class, 'show'])
        ->name('orders.receipt');
});

// Admin panel
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', fn () => view('admin.dashboard'))->name('dashboard');
    Route::get('/submissions', fn () => view('admin.submissions'))->name('submissions');
    Route::get('/submissions/{submission}', fn (IntakeSubmission $submission) => view('admin.submission-detail', compact('submission')))
        ->name('submissions.show');
    Route::get('/documents', fn () => view('admin.documents'))->name('documents');
    Route::get('/leads', fn () => view('admin.leads'))->name('leads');
    Route::get('/packages', fn () => view('admin.packages'))->name('packages');
    Route::get('/packages/create', fn () => view('admin.packages-form'))->name('packages.create');
    Route::get('/packages/{package}/edit', fn (Package $package) => view('admin.packages-form', compact('package')))
        ->name('packages.edit');
    Route::get('/intake-uploads/{upload}/download', [IntakeUploadDownloadController::class, 'show'])
        ->name('uploads.download');
});
