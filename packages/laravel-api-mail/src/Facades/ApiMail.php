<?php

namespace Masoud46\LaravelApiMail\Facades;

use Illuminate\Support\Facades\Facade;


class ApiMail extends Facade {
	protected static function getFacadeAccessor() {
		return \Masoud46\LaravelApiMail\ApiMail::class;
	}
}
