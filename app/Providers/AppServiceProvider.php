<?php

namespace App\Providers;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;
use NumberFormatter;

class AppServiceProvider extends ServiceProvider {
	/**
	 * Register any application services.
	 */
	public function register(): void {
		//
	}

	/**
	 * Bootstrap any application services.
	 */
	public function boot(): void {
		// default parameters for currency operations
		App::singleton('DEFAULT_CURRENCY_PARAMS', function () {
			$locale = LaravelLocalization::getCurrentLocaleRegional();

			return [
				'locale' => $locale,
				'currency' => "EUR",
				'symbol' => false,
			];
		});

		// form errors
		App::singleton('ERRORS', function () {
			return [
				'form' => __("Please correct the fields marked in red."),
				'form2' => __("Please verify the provided information."),
				'all_required' => __("All fields are mandatory."),
				'required' => __("This field is required."),
				'email' => __("Please verify the email address."),
				'regex' =>
				[
					'global' => __("Please verify this field."),
					'code' => __("Please check the registration number."),
					'price' => __("Invalid price format."),
					'location' => __("Please fill in your secondary address first, or choose another location."),
				],
				'unique' => [
					'email' => __("This email address is already in use."),
					'code' => __("This registration number is already in use."),
					'user_code' => __("This code is already in use."),
				],
				'numeric' => __("This field must be numeric."),
				'session' => __("The initial session must be :initial_session or grater."),
				'date' => __("Please enter a valid date."),
				'iban' => __("Please verify your bank account number."),
			];
		});

	}
}
