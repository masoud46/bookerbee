<?php

namespace Masoud46\LaravelApiSms\Facades;

use Illuminate\Support\Facades\Facade;


class ApiSms extends Facade {
	protected static function getFacadeAccessor() {
		return \Masoud46\LaravelApiSms\ApiSms::class;
	}
}
