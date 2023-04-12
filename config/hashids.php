<?php

return [

	/*
    |--------------------------------------------------------------------------
    | Default Connection Name
    |--------------------------------------------------------------------------
    |
    | Here you may specify which of the connections below you wish to use as
    | your default connection for all work. Of course, you may use many
    | connections at once using the manager class.
    |
    */

	'default' => 'main',
	// 'default' => 'alternative',

	/*
    |--------------------------------------------------------------------------
    | Hashids Connections
    |--------------------------------------------------------------------------
    |
    | Here are each of the connections setup for your application. Example
    | configuration has been included, but you may add as many connections as
    | you would like.
    |
    */

	'connections' => [

		'main' => [
			'salt' => env('HASHIDS_SALT', env('APP_KEY', '@Masoud Fathi')),
			'length' => intval(env('HASHIDS_LENGTH', 16)),
			'alphabet' => env('HASHIDS_ALPHABET', 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890'),
		],

		'alternative' => [
			'salt' => 'MY_AWESOME_SALT',
			'length' => 8,
			'alphabet' => 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890'
		],

	],

];
