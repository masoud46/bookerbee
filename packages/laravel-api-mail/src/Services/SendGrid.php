<?php

namespace Masoud46\LaravelApiMail\Services;

use Masoud46\LaravelApiMail\Contract\Sendable;

class SendGrid extends Sendable {
	protected $key;
	protected $from;
	protected $serviceUrl = 'https://api.sendgrid.com/v3/mail/send';

	public function __construct() {
		$this->key = config('api-mail.drivers.sendgrid.api_key');
		$this->from = config('api-mail.drivers.sendgrid.from');
	}

	public function makePayload($payload) {
		$from = makeEmailAddressString(array_key_exists("from", $payload) ? $payload["from"] : $this->from);
		$to = $payload["to"];
		$subject = $payload["subject"];
		$body = $payload["body"];
		$toArray = [];

		if (is_string($to)) array_push($toArray, ["email" => $to]);
		if (is_array($to)) for ($i = 0; $i < count($to); $i++) array_push($toArray, ["email" => $to[$i]]);

		$data = [
			"personalizations" => [["to" => $toArray, "subject" => $subject]],
			"from" => ["email" => $from],
			"content" => [[
				"type" => "text/html",
				"value" => $body
			]],
		];

		return json_encode($data);
	}

	public function send($payload) {
		$data = $this->makePayload($payload);
		$headers = array();
		$headers[] = 'Content-type: application/json';
		$headers[] = 'Authorization: Bearer ' . $this->key;

		$ch = curl_init($this->serviceUrl);

		// curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		// curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

		$result = curl_exec($ch);
		$statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

		curl_close($ch);

		if ($statusCode == 202) {
			return (object) ['success' => true, 'message' => 'Sent.'];
		} else {
			$message = json_decode($result)->errors[0]->message;
			return (object) ['success' => false, 'message' => $message];
		}
	}
}
