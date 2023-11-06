<?php

if (!function_exists('apiMail')) {
	function apiMail() {
		return new Masoud46\LaravelApiMail\ApiMail();
	}
}

if (!function_exists('makeEmailAddressString')) {
	//RFC 2822
	/**
	 * @param $from
	 * @return string
	 */
	function makeEmailAddressString($from) {
		if (
			!is_array($from) ||
			!array_key_exists('name', $from) ||
			!array_key_exists('email', $from)
		) {
			throw  new \Exception('Email "from" must be an array of "name" and "email" address.');
		}

		return $from['name'] . " <" . $from['email'] . ">";
	}
}
