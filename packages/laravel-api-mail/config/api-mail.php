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
			'monthly_limit' => intval(env('APIMAIL_BREVO_MONTHLY_LIMIT')),
			'critical_balance' => intval(env('APIMAIL_BREVO_CRITICAL_BALANCE', 50)),
			'active' => (bool) env('APIMAIL_BREVO_ACTIVE', false),
		],
		'sendgrid' => [
			'api_key' => env('APIMAIL_SENDGRID_APIKEY'),
			'admin_key' => env('APIMAIL_SENDGRID_ADMINKEY'),
			'from' => [
				'name' => env('APIMAIL_SENDGRID_FROM_NAME'),
				'email' => env('APIMAIL_SENDGRID_FROM_EMAIL'),
			],
			'monthly_limit' => intval(env('APIMAIL_SENDGRID_MONTHLY_LIMIT')),
			'critical_balance' => intval(env('APIMAIL_SENDGRID_CRITICAL_BALANCE', 50)),
			'active' => (bool) env('APIMAIL_SENDGRID_ACTIVE', false),
		],
		'postmark' => [
			'server_token' => env('APIMAIL_POSTMARK_SERVERTOKEN'),
			'sandbox_token' => env('APIMAIL_POSTMARK_SANDBOXTOKEN'),
			'from' => [
				'name' => env('APIMAIL_POSTMARK_FROM_NAME'),
				'email' => env('APIMAIL_POSTMARK_FROM_EMAIL'),
			],
			'monthly_limit' => intval(env('APIMAIL_POSTMARK_MONTHLY_LIMIT')),
			'critical_balance' => intval(env('APIMAIL_POSTMARK_CRITICAL_BALANCE', 50)),
			'active' => (bool) env('APIMAIL_POSTMARK_ACTIVE', false),
		],
		'mailgun' => [
			'api_key' => env('APIMAIL_MAILGUN_APIKEY'),
			'domain' => env('APIMAIL_MAILGUN_DOMAIN'),
			'from' => [
				'name' => env('APIMAIL_MAILGUN_FROM_NAME'),
				'email' => env('APIMAIL_MAILGUN_FROM_EMAIL'),
			],
			'monthly_limit' => intval(env('APIMAIL_MAILGUN_MONTHLY_LIMIT')),
			'critical_balance' => intval(env('APIMAIL_MAILGUN_CRITICAL_BALANCE', 50)),
			'active' => (boolean) env('APIMAIL_MAILGUN_ACTIVE', false),
		],
	]
];
