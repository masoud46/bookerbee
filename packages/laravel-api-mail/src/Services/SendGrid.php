<?php

namespace Masoud46\LaravelApiMail\Services;

use Masoud46\LaravelApiMail\Contract\Sendable;

class SendGrid extends Sendable {
	protected $headers;
	protected $api_key;
	protected $admin_key;
	protected $key;
	protected $from;
	protected $serviceUrl = 'https://api.sendgrid.com/v3/mail/send';
	protected $balanceUrl = 'https://api.sendgrid.com/v3/user/credits';

	public function __construct() {
		$this->headers = ['Content-type: application/json'];
		$this->api_key = config('api-mail.drivers.sendgrid.api_key');
		$this->admin_key = config('api-mail.drivers.sendgrid.admin_key');
		$this->key = 'Authorization: Bearer %s';
		$this->from = config('api-mail.drivers.sendgrid.from');
	}

	protected function query($url, $body = '') {
		$method = strlen($body) ? 'POST' : 'GET';
		$ch = curl_init($url);

		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		// curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		// curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

		if ($method === 'POST') {
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
			curl_setopt($ch, CURLOPT_HTTPHEADER, array_merge($this->headers, [sprintf($this->key, $this->api_key)]));
		} else {
			curl_setopt($ch, CURLOPT_HTTPHEADER, array_merge($this->headers, [sprintf($this->key, $this->admin_key)]));
		}

		$response = curl_exec($ch);
		$statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

		curl_close($ch);

		$result = (object) ['success' => $statusCode < 300];

		if ($result->success) {
			if ($statusCode == 200) $result->data = json_decode($response);
		} else {
			$result->message = $statusCode == 0 ? 'Host no found.' : rtrim($response, "\n\r");
		}

		return $result;
		
		
		
		
		// if ($statusCode == 0) $result = (object) ['message' => 'Host no found.'];
		// $result->success = $statusCode == 200;

		// return $result;
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

		return  $this->query($this->serviceUrl, $data);
	}

	public function balance() {
		$result = $this->query($this->balanceUrl);

		if ($result->success) {
			$result->data = $result->data->remain;
		}

		return $result;
	}
}
