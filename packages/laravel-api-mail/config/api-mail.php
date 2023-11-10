<?php
return [
	'default_provider' => env('APIMAIL_DEFAULT_PROVIDER', 'brevo'),
	'default_dev_provider' => env('APIMAIL_DEFAULT_DEV_PROVIDER', 'sendgrid'),

	'drivers' => [
		'brevo' => [
			'api_key' => env('APIMAIL_BREVO_APIKEY'),
			'from' => [
				'name' => env('APIMAIL_BREVO_FROM_NAME'),
				'email' => env('APIMAIL_BREVO_FROM_EMAIL'),
			],
		],
		'sendgrid' => [
			'api_key' => env('APIMAIL_SENDGRID_APIKEY'),
			'from' => [
				'name' => env('APIMAIL_SENDGRID_FROM_NAME'),
				'email' => env('APIMAIL_SENDGRID_FROM_EMAIL'),
			],
		],
		'mailgun' => [
			'api_key' => env('APIMAIL_MAILGUN_APIKEY'),
			'domain' => env('APIMAIL_MAILGUN_DOMAIN'),
			'from' => [
				'name' => env('APIMAIL_MAILGUN_FROM_NAME'),
				'email' => env('APIMAIL_MAILGUN_FROM_EMAIL'),
			],
		],
	]
];
