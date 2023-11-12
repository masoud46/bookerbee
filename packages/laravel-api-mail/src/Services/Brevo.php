<?php

namespace Masoud46\LaravelApiMail\Services;

use Masoud46\LaravelApiMail\Contract\Sendable;

class Brevo extends Sendable {
	protected $headers;
	protected $from;
	protected $serviceUrl = 'https://api.brevo.com/v3/smtp/email';
	protected $balanceUrl = 'https://api.brevo.com/v3/account';

	public function __construct() {
		$this->headers = [
			'accept: application/json',
			'content-type: application/json',
			'api-key: ' . config('api-mail.drivers.brevo.api_key'),
		];
		$this->from = config('api-mail.drivers.brevo.from');
	}

	protected function query($url, $body = '') {
		$method = strlen($body) ? 'POST' : 'GET';
		$ch = curl_init($url);

		curl_setopt($ch, CURLOPT_HTTPHEADER, $this->headers);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);

		if ($method === 'POST') {
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
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

		return  $this->query($this->serviceUrl, $data);
	}

	public function balance() {
		$result = $this->query($this->balanceUrl);

		if ($result->success) {
			$plan = $result->data->plan;

			if (count($plan)) {
				$result->data = $result->data->plan[0]->credits;
			} else {
				$result->success = false;
				$result->message = 'No plan assigned to the account!';
				unset($result->data);
			}
		}

		return $result;
	}
}
