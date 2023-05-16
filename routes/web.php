<?php

use App\Http\Controllers\EventController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\PatientController;
use App\Http\Controllers\PatientNoteController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SendEmailController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

use Illuminate\Support\Facades\Route;
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;

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

// Route::get('/ss', [UserController::class, 'ss']);

// Signed URL test
Route::get('/subscribe/{id}/{email}', [UserController::class, 'sign'])->name('subscribe');
Route::get('/unsubscribe/{id}/{email}', function (Request $request, $id, $email) {
	if (!$request->hasValidSignature()) {
		abort(401);
	}

	dd($id, $email);
})->name('unsubscribe');


// Route::prefix(LaravelLocalization::setLocale())->middleware(['localeSessionRedirect', 'localizationRedirect'])->group(
Route::prefix(LaravelLocalization::setLocale())->middleware(['localeCookieRedirect', 'localizationRedirect'])->group(
	function () {

		Auth::routes([
			'register' => false,
		]);

		Route::get('/', [InvoiceController::class, 'index'])->name('home');
		Route::post('/invoice', [InvoiceController::class, 'store'])->name('invoice.store');
		Route::get('/invoice/search/{limit}', [InvoiceController::class, 'index'])->name('invoice.index');
		Route::get('/invoice/new/{patient}', [InvoiceController::class, 'create'])->name('invoice.new');
		Route::get('/invoice/{invoice}', [InvoiceController::class, 'show'])->name('invoice.show');
		Route::get('/invoice/{invoice}', [InvoiceController::class, 'show'])->name('invoice.show');
		Route::get('/invoice/print/{invoice}', [InvoiceController::class, 'print'])->name('invoice.print');
		Route::get('/invoice/disable/{invoice}', [InvoiceController::class, 'disable'])->name('invoice.disable');

		Route::get('/patient', [PatientController::class, 'index'])->name('patient.index');
		Route::post('/patient', [PatientController::class, 'store'])->name('patient.store');
		Route::get('/patient/new', [PatientController::class, 'show'])->name('patient.new');
		Route::get('/patient/list', [PatientController::class, 'list'])->name('patient.list');
		Route::post('/patient/notes', [PatientNoteController::class, 'index'])->name('patient.notes');
		Route::post('/patient/notes/store', [PatientNoteController::class, 'store'])->name('patient.notes.store');
		Route::post('/patient/autocomplete', [PatientController::class, 'autocomplete'])->name('patient.autocomplete');
		Route::get('/patient/{patient?}', [PatientController::class, 'show'])->name('patient.show');

		Route::get('/agenda', [EventController::class, 'index'])->name('agenda.index');
		Route::post('/event', [EventController::class, 'store'])->name('event.add');
		Route::put('/event/{event}', [EventController::class, 'update'])->name('event.update');
		Route::delete('/event/{event}', [EventController::class, 'destroy'])->name('event.cancel');

		Route::get('/profile', [UserController::class, 'edit'])->name('profile');
		Route::put('/profile', [UserController::class, 'update'])->name('profile.update');
		Route::post('/profile/email', [UserController::class, 'email'])->name('profile.email');

		Route::get('/settings', [SettingsController::class, 'edit'])->name('settings');
		Route::put('/settings', [SettingsController::class, 'update'])->name('settings.update');

		Route::get('/report', [ReportController::class, 'index'])->name('report');


		Route::get('/send/change-email', [SendEmailController::class, 'sendChangeEmail'])->name('email.change-email');
		Route::get('/send/change-password', [SendEmailController::class, 'sendChangePassword'])->name('email.change-password');
	}
);
