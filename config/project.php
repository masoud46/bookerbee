<?php

$limits = env('APP_LOAD_INVOICE_LIMITS', [3, 6]);
if (!is_array($limits)) {
	try {
		$limits = json_decode($limits, true);
	} catch (\Throwable $th) {
		$limits = [3, 6];
	}
}
sort($limits);

return [
	'default_country_code' => env('APP_DEFAULT_COUNTRY_CODE', 'LU'),
	'only_last_invoice_editable' => env('APP_ONLY_LAST_INVOICE_EDITABLE', true),
	'load_invoice_limits' => $limits,

];
