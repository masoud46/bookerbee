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
		'office.psychosexo.lu',
		'invoice.psychosexo.clinic',
		'serge.com',
		'manager.eva.com',
	],

	'default_country_code' => env('APP_DEFAULT_COUNTRY_CODE', 'LU'),
	'default_timezone' => env('APP_DEFAULT_TIMEZONE', 'Europe/Luxembourg'),
	'send_emails' => env('APP_SEND_EMAILS', true),
	'send_sms' => env('APP_SEND_SMS', true),
	'reminder_email_time' => env('APP_REMINDER_EMAIL_TIME', '48'),
	'reminder_sms_time' => env('APP_REMINDER_SMS_TIME', '26'),
	'load_invoice_limits' => $limits,

	'sms' => [
		'default_provider' => env('SMS_DEFAULT_PROVIDER', 'ovh'),
		'smsto' => [
			'api_key' => env('SMS_SMSTO_API_KEY', null),
			'sender_id' => env('SMS_SMSTO_SENDER_ID', null),
		],

		'ovh' => [
			'application_key' => env('SMS_OVH_APPLICATION_KEY', null),
			'application_secret' => env('SMS_OVH_APPLICATION_SECRET', null),
			'consumer_key' => env('SMS_OVH_CONSUMER_KEY', null),
			'endpoint' => env('SMS_OVH_ENDPOINT', null),
			'sender' => env('SMS_OVH_SENDER', null),
		],
	],

];
