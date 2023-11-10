<?php

namespace Masoud46\LaravelApiMail\Services;

use Masoud46\LaravelApiMail\Contract\Sendable;

class Brevo extends Sendable {
	protected $key;
	protected $from;
	protected $serviceUrl = 'https://api.brevo.com/v3/smtp/email';

	public function __construct() {
		$this->key = config('api-mail.drivers.brevo.api_key');
		$this->from = config('api-mail.drivers.brevo.from');
	}

	public function makePayload($payload) {
		$from = array_key_exists("from", $payload) ? $payload["from"] : $this->from;
		$to = $payload["to"];
		$subject = $payload["subject"];
		$body = $payload["body"];
		$toArray = [];

		if (is_string($to)) array_push($toArray, ["email" => $to]);
		if (is_array($to)) for ($i = 0; $i < count($to); $i++) array_push($toArray, ["email" => $to[$i]]);

		$data = [
			"sender" => $from,
			"to" => $toArray,
			"subject" => $subject,
			"htmlContent" => $body
		];

		return json_encode($data);
	}

	public function send($payload) {
		$data = $this->makePayload($payload);
		$headers = array();
		$headers[] = 'accept: application/json';
		$headers[] = 'content-type: application/json';
		$headers[] = 'api-key: ' . $this->key;

		$ch = curl_init($this->serviceUrl);

		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

		$result = curl_exec($ch);
		$statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

		curl_close($ch);

		if ($statusCode != 201 && $statusCode != 202) {
			return (object) [
				'success' => false,
				'message' => $statusCode == 0 ? 'Host no found.' : json_decode($result)->error,
			];
		}

		return (object) ['success' => true];
	}
}
