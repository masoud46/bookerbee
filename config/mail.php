<?php

return [

	/*
    |--------------------------------------------------------------------------
    | Default Mailer
    |--------------------------------------------------------------------------
    |
    | This option controls the default mailer that is used to send any email
    | messages sent by your application. Alternative mailers may be setup
    | and used as needed; however, this mailer will be used by default.
    |
    */

	'default' => env('MAIL_MAILER', 'smtp'),

	/*
    |--------------------------------------------------------------------------
    | Mailer Configurations
    |--------------------------------------------------------------------------
    |
    | Here you may configure all of the mailers used by your application plus
    | their respective settings. Several examples have been configured for
    | you and you are free to add your own as your application requires.
    |
    | Laravel supports a variety of mail "transport" drivers to be used while
    | sending an e-mail. You will specify which one you are using for your
    | mailers below. You are free to add additional mailers as required.
    |
    | Supported: "smtp", "sendmail", "mailgun", "ses", "ses-v2",
    |            "postmark", "log", "array", "failover"
    |
    */

	'mailers' => [
		'smtp' => [
			'transport' => 'smtp',
			'host' => env('MAIL_HOST', 'smtp.mailgun.org'),
			'port' => env('MAIL_PORT', 587),
			'encryption' => env('MAIL_ENCRYPTION', 'tls'),
			'username' => env('MAIL_USERNAME'),
			'password' => env('MAIL_PASSWORD'),
			'timeout' => null,
			'local_domain' => env('MAIL_EHLO_DOMAIN'),
		],

		'ses' => [
			'transport' => 'ses',
		],

		'mailgun' => [
			'transport' => 'mailgun',
			// 'client' => [
			//     'timeout' => 5,
			// ],
		],

		'postmark' => [
			'transport' => 'postmark',
			// 'client' => [
			//     'timeout' => 5,
			// ],
		],

		'sendmail' => [
			'transport' => 'sendmail',
			'path' => env('MAIL_SENDMAIL_PATH', '/usr/sbin/sendmail -bs -i'),
		],

		'log' => [
			'transport' => 'log',
			'channel' => env('MAIL_LOG_CHANNEL'),
		],

		'array' => [
			'transport' => 'array',
		],

		'failover' => [
			'transport' => 'failover',
			'mailers' => [
				'smtp',
				'log',
			],
		],

		'sendgrid' => [
			'transport' => env('SENDGRID_MAILER'),
			'host' => env('SENDGRID_HOST'),
			'port' => env('SENDGRID_PORT'),
			'encryption' => env('SENDGRID_ENCRYPTION'),
			'username' => env('SENDGRID_USERNAME'),
			'password' => env('SENDGRID_PASSWORD'),
			'timeout' => null,
			'local_domain' => env('MAIL_EHLO_DOMAIN'),
		],

		'brevo' => [
			'transport' => env('BREVO_MAILER'),
			'host' => env('BREVO_HOST'),
			'port' => env('BREVO_PORT'),
			'encryption' => env('BREVO_ENCRYPTION'),
			'username' => env('BREVO_USERNAME'),
			'password' => env('BREVO_PASSWORD'),
			'timeout' => null,
			'local_domain' => env('MAIL_EHLO_DOMAIN'),
		],

		'mailtrap' => [
			'transport' => 'smtp',
			'host' => env('MAILTRAP_MAIL_HOST', 'smtp.mailgun.org'),
			'port' => env('MAILTRAP_MAIL_PORT', 2525),
			'encryption' => env('MAILTRAP_MAIL_ENCRYPTION', 'tls'),
			'username' => env('MAILTRAP_MAIL_USERNAME'),
			'password' => env('MAILTRAP_MAIL_PASSWORD'),
			'timeout' => null,
			'local_domain' => env('MAIL_EHLO_DOMAIN'),
		],

	],

	/*
    |--------------------------------------------------------------------------
    | Global "From" Address
    |--------------------------------------------------------------------------
    |
    | You may wish for all e-mails sent by your application to be sent from
    | the same address. Here, you may specify a name and address that is
    | used globally for all e-mails that are sent by your application.
    |
    */

	// 'from' => [
	// 	'address' => env('MAIL_FROM_ADDRESS', 'hello@example.com'),
	// 	'name' => env('MAIL_FROM_NAME', 'Example'),
	// ],
	'from' => [
		'address' => env(strtoupper(env('MAIL_DEFAULT_PROVIDER', 'sendgrid')).'_FROM_ADDRESS', 'hello@example.com'),
		'name' => env(strtoupper(env('MAIL_DEFAULT_PROVIDER', 'sendgrid')) . '_FROM_NAME', 'Example'),
	],

	/*
    |--------------------------------------------------------------------------
    | Markdown Mail Settings
    |--------------------------------------------------------------------------
    |
    | If you are using Markdown based email rendering, you may configure your
    | theme and component paths here, allowing you to customize the design
    | of the emails. Or, you may simply stick with the Laravel defaults!
    |
    */

	'markdown' => [
		'theme' => 'default',

		'paths' => [
			resource_path('views/vendor/mail'),
		],
	],

];
