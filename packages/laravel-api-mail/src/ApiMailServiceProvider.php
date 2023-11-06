<?php

namespace Masoud46\LaravelApiMail;

use Illuminate\Support\ServiceProvider;

class ApiMailServiceProvider extends ServiceProvider {
	const CONFIG_PATH = __DIR__ . '/../config';

	public function boot() {
		$this->publishes([
			self::CONFIG_PATH => config_path()
		], 'config');
	}


	public function register() {
		$this->mergeConfigFrom(
			self::CONFIG_PATH . '/api-mail.php',
			'api-mail'
		);
	}
}
