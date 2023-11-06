<?php
return [
	// ATT: providers' name, 10 characters max.
	'default_provider' => env('APISMS_DEFAULT_PROVIDER', 'ovh'),
	'default_country_code' => env('APISMS_DEFAULT_COUNTRY_CODE', 'LU'),
	'price_multiplier' => env('APISMS_PRICE_MULTIPLIER', 10000),

	'drivers' => [
		'46elks' => [
			'username' => env('APISMS_46ELKS_USERNAME', null),
			'password' => env('APISMS_46ELKS_PASSWORD', null),
			'from' => env('APISMS_46ELKS_FROM', null),
			'whendelivered' => env('APISMS_46ELKS_WHENDELIVERED', null),
			'critical_credit' => floatval(env('APISMS_46ELKS_CRITICAL_CREDIT', 10)),
		],
		'ovh' => [
			'application_key' => env('APISMS_OVH_APPLICATIONKEY', null),
			'application_secret' => env('APISMS_OVH_APPLICATIONSECRET', null),
			'consumer_key' => env('APISMS_OVH_CONSUMERKEY', null),
			'endpoint' => env('APISMS_OVH_ENDPOINT', null),
			'service' => env('APISMS_OVH_SERVICE', null),
			'sender' => env('APISMS_OVH_SENDER', null),
			'price_vat' => env('APISMS_OVH_PRICE_VAT', 21),
			'critical_credit' => floatval(env('APISMS_OVH_CRITICAL_CREDIT', 100)),
		],
		'smsto' => [
			'api_key' => env('APISMS_SMSTO_APIKEY', null),
			'sender_id' => env('APISMS_SMSTO_SENDERID', null),
			'callback_url' => env('APISMS_SMSTO_CALLBACKURL', null),
			'critical_credit' => floatval(env('APISMS_SMSTO_CRITICAL_CREDIT', 10)),
		],
	]
];
