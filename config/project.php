<?php

$limits = env('APP_LOAD_INVOICE_LIMITS', [3, 6]);
if (!is_array($limits)) { // specified as a json string in .env
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
		'manager.eva.com',
		'serge.com',
	],

	'app_name' => env('APP_NAME', 'BookerBee'),
	'vat' => env('APP_VAT', 21),
	'default_country_code' => env('APP_DEFAULT_COUNTRY_CODE', 'LU'),
	'default_timezone' => env('APP_DEFAULT_TIMEZONE', 'Europe/Luxembourg'),
	'load_invoice_limits' => $limits,
	'event_action_max_length' => intval(env('APP_EVENT_ACTION_MAX_LENGTH', 15)),
	'sms_price_multiplier' => intval(env('APP_SMS_PRICE_MULTIPLIER', 10000)),
	'token_expiration_time' => intval(env('APP_TOKEN_EXPIRATION_TIME', 60)),

	'send_emails' => env('APP_SEND_EMAILS', true),
	'send_sms' => env('APP_SEND_SMS', true),
	'reminder_email_time' => intval(env('APP_REMINDER_EMAIL_TIME', 48)),
	'reminder_sms_time' => intval(env('APP_REMINDER_SMS_TIME', 26)),

	'monitoring' => [
		'email' => env('APP_MONITORING_EMAIL', null),
		'phone' => env('APP_MONITORING_PHONE', null),
		'email_limit' => intval(env('APP_MONITORING_EMAIL_LIMIT', 50)),
	],

	'mail' => [
		'default_provider' => env('MAIL_MAILER', 'brevo'),
		'default_dev_provider' => env('MAIL_MAILER_DEV', 'brevo'),
		'sendgrid' => [
			'api_key' => env('SENDGRID_API_KEY', null),
			'admin_key' => env('SENDGRID_ADMIN_KEY', null),
		],
		'brevo' => [
			'api_key' => env('BREVO_API_KEY', null),
			'smtp_key' => env('BREVO_SMTP_KEY', null),
		],
	],

	'sms' => [
		// ATT: provider's name, 10 characters max.
		'default_provider' => env('SMS_DEFAULT_PROVIDER', 'ovh'),
		'smsto' => [
			'api_key' => env('SMS_SMSTO_API_KEY', null),
			'sender_id' => env('SMS_SMSTO_SENDER_ID', null),
			'critical_credit' => floatval(env('SMS_SMSTO_CRITICAL_CREDIT', 10)),
		],
		'ovh' => [
			'application_key' => env('SMS_OVH_APPLICATION_KEY', null),
			'application_secret' => env('SMS_OVH_APPLICATION_SECRET', null),
			'consumer_key' => env('SMS_OVH_CONSUMER_KEY', null),
			'endpoint' => env('SMS_OVH_ENDPOINT', null),
			'service' => env('SMS_OVH_SERVICE', null),
			'sender' => env('SMS_OVH_SENDER', null),
			'critical_credit' => floatval(env('SMS_OVH_CRITICAL_CREDIT', 100)),
		],
	],

	'ovh' => [
		'application_key' => env('OVH_APPLICATION_KEY', null),
		'application_secret' => env('OVH_APPLICATION_SECRET', null),
		'consumer_key' => env('OVH_CONSUMER_KEY', null),
		'endpoint' => env('OVH_ENDPOINT', null),
	],

];
