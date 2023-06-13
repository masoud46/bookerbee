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
	'external_domains' => [
		'booker.psychosexo.lu',
		'invoice.psychosexo.clinic',
		'serge.com',
		'manager.eva.com',
	],

	'default_country_code' => env('APP_DEFAULT_COUNTRY_CODE', 'LU'),
	'default_timezone' => env('APP_DEFAULT_TIMEZONE', 'Europe/Luxembourg'),
	'reminder_email_time' => env('APP_REMINDER_EMAIL_TIME', '48'),
	'reminder_sms_time' => env('APP_REMINDER_SMS_TIME', '6'),
	'send_emails' => env('APP_SEND_EMAILS', true),
	'load_invoice_limits' => $limits,

];
