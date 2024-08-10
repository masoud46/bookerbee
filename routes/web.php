<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PatientController;
use App\Http\Controllers\PatientNoteController;
use App\Http\Controllers\SendEmailController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\TestController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
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

// Test routes
// Signed URL
Route::get('/subscribe/{id}/{email}', [UserController::class, 'sign'])->name('subscribe');
Route::get('/unsubscribe/{id}/{email}', function (Request $request, $id, $email) {
	if (!$request->hasValidSignature()) {
		abort(401);
	}

	dd($id, $email);
})->name('unsubscribe');


Route::get('/ping', [AdminController::class, 'ping'])->name('ping');


// Route::prefix(LaravelLocalization::setLocale())->middleware(['localeSessionRedirect', 'localizationRedirect'])->group(
Route::prefix(LaravelLocalization::setLocale())->middleware(['localeCookieRedirect', 'localizationRedirect'])->group(
	function () {
		Config::set('recaptcha_locale', LaravelLocalization::getCurrentLocale());

		Route::get('/event/export/{id}', [EventController::class, 'export'])->name('event.export');
		Route::get('/account/email/{token}', [UserController::class, 'updateEmail'])->name('account.email.update');

		Auth::routes([
			'register' => false,
		]);

		Route::get('/', [HomeController::class, 'index'])->name('home');
		Route::get('/logout', [LoginController::class, 'logout'])->name('logout.get');
		Route::get('/account/suspended', [UserController::class, 'index'])->name('account.suspended');

		Route::group(['middleware' => ['userIsAdmin']], function () {
			Route::get('/admin', [AdminController::class, 'index'])->name('admin');
			Route::get('/admin/log/{log}', [AdminController::class, 'log'])->name('admin.log');
			Route::get('/admin/truncate/log/{log}', [AdminController::class, 'truncateLog'])->name('admin.truncate.log');
			Route::get('/admin/monitoring', [AdminController::class, 'monitoring'])->name('admin.monitoring');
			Route::post('/admin/sms/buy/{credits}', [AdminController::class, 'buySMSCredits'])->name('admin.sms.buy');
			Route::get('/admin/sms/cost/{user_id}/{start}/{end}', [AdminController::class, 'getSmsCost'])->name('admin.sms.cost');
		});

		Route::group(['middleware' => ['userIsNotSuspended']], function () {
			Route::get('/account/profile', [UserController::class, 'edit'])->name('account.profile');
			Route::get('/account/address', [UserController::class, 'edit'])->name('account.address');
			Route::put('/account/profile', [UserController::class, 'update'])->name('account.profile.update');
			Route::put('/account/address', [UserController::class, 'update'])->name('account.address.update');
			Route::post('/account/password', [UserController::class, 'confirmPassword'])->name('account.password');
			Route::post('/account/email', [UserController::class, 'updateEmailRequest'])->name('account.email');
			Route::post('/account/phone', [UserController::class, 'updatePhoneRequest'])->name('account.phone');
			Route::put('/account/phone', [UserController::class, 'UpdatePhone'])->name('account.phone.update');

			Route::get('/settings', [SettingsController::class, 'edit'])->name('settings');
			Route::put('/settings', [SettingsController::class, 'update'])->name('settings.update');

			Route::group(['middleware' => ['userIsActive']], function () {
				Route::get('/invoices', [InvoiceController::class, 'index'])->name('invoices');
				Route::post('/invoice', [InvoiceController::class, 'store'])->name('invoice.store');
				Route::get('/invoice/search/{limit}', [InvoiceController::class, 'index'])->name('invoice.index');
				Route::get('/invoice/new/{patient}', [InvoiceController::class, 'create'])->name('invoice.new');
				Route::get('/invoice/print/{invoice}', [InvoiceController::class, 'print'])->name('invoice.print');
				Route::get('/invoice/disable/{invoice}', [InvoiceController::class, 'disable'])->name('invoice.disable');
				Route::post('/invoice/report', [InvoiceController::class, 'report'])->name('invoice.report.print');
				Route::get('/invoice/report/{start}/{end}', [InvoiceController::class, 'report'])
					->withoutMiddleware('localeCookieRedirect')
					->name('invoice.report.export');
				Route::get('/invoice/{invoice}', [InvoiceController::class, 'show'])->name('invoice.show');

				Route::get('/patients', [PatientController::class, 'index'])->name('patients');
				Route::post('/patient', [PatientController::class, 'store'])->name('patient.store');
				Route::get('/patient/new', [PatientController::class, 'show'])->name('patient.new');
				Route::get('/patient/list', [PatientController::class, 'list'])->name('patient.list');
				Route::post('/patient/notes', [PatientNoteController::class, 'index'])->name('patient.notes');
				Route::post('/patient/notes/store', [PatientNoteController::class, 'store'])->name('patient.notes.store');
				Route::post('/patient/autocomplete', [PatientController::class, 'autocomplete'])->name('patient.autocomplete');
				Route::get('/patient/{patient?}', [PatientController::class, 'show'])->name('patient.show');

				Route::get('/agenda', [EventController::class, 'index'])->name('agenda.index');
				Route::get('/events', [EventController::class, 'fetch'])->name('event.fetch');
				Route::post('/event', [EventController::class, 'store'])->name('event.add');
				Route::put('/event/{event}', [EventController::class, 'update'])->name('event.update');
				Route::delete('/event/{event}', [EventController::class, 'destroy'])->name('event.cancel');
			});
		});
	}
);
