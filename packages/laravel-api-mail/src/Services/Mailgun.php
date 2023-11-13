<?php

namespace Masoud46\LaravelApiMail\Services;

use Masoud46\LaravelApiMail\Contract\Sendable;

class Mailgun extends Sendable {
	protected $key;
	protected $domain;
	protected $from;
	protected $serviceUrl;

	public function __construct() {
		$this->key = config('api-mail.drivers.mailgun.api_key');
		$this->domain = config('api-mail.drivers.mailgun.domain');
		$this->from = config('api-mail.drivers.mailgun.from');
		$this->serviceUrl = "https://api.mailgun.net/v3/" . $this->domain . "/messages";
	}

	public function makePayload($payload) {
		$from = makeEmailAddressString(array_key_exists("from", $payload) ? $payload["from"] : $this->from);
		$to = $payload["to"];
		$subject = $payload["subject"];
		$body = $payload["body"];
		$toString = null;

		if (is_string($to)) $toString = $payload["to"];
		if (is_array($to))$toString = implode(",", $payload["to"]);

		$data = [
			"from" => $from,
			"to" => $toString,
			"subject" => $subject,
			"html" => $body,
		];

		if (array_key_exists("cc", $payload)) {
			if (is_string($payload["cc"])) $data['cc'] = $payload["cc"];
			if (is_array($payload["cc"])) $data['cc'] = implode(",", $payload["cc"]);
		}

		return $data;
	}

	public function send($payload) {
		$data = $this->makePayload($payload);

		$ch = curl_init($this->serviceUrl);

		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		curl_setopt($ch, CURLOPT_USERPWD, "api:" . $this->key);

		$response = curl_exec($ch);
		$statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

		curl_close($ch);

		$result = (object) [
			'success' => $statusCode < 300,
			'status' => $statusCode,
			'response' => $response,
		];

		if ($result->success) {
			if ($statusCode == 200) $result->data = json_decode($response);
		} else {
			$result->message = $statusCode == 0 ? 'Host no found.' : rtrim($response, "\n\r");
		}

		return $result;
	}

	public function balance() {
		return (object) [
			'success' => false,
			'message' => 'This function is not implemented yet.',
		];
	}
}
