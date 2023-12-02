<?php

namespace Masoud46\LaravelApiMail\Services;

use Carbon\Carbon;
use Masoud46\LaravelApiMail\Contract\Sendable;

class Postmark extends Sendable {
	protected $headers;
	protected $token;
	protected $sandbox_token;
	protected $from;
	protected $serviceUrl = 'https://api.postmarkapp.com/email';
	protected $balanceUrl = 'https://api.postmarkapp.com/stats/outbound?fromdate=%s&todate=%s';

	public function __construct() {
		$this->headers = [
			'Accept: application/json',
			'Content-Type: application/json',
		];
		$this->token = 'X-Postmark-Server-Token: ' . config('api-mail.drivers.postmark.server_token');
		$this->sandbox_token = 'X-Postmark-Server-Token: ' . config('api-mail.drivers.postmark.sandbox_token');
		$this->from = config('api-mail.drivers.postmark.from');
	}

	protected function query($url, $headers, $body = '') {
		$method = strlen($body) ? 'POST' : 'GET';
		$ch = curl_init($url);

		// curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		// curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

		if ($method === 'POST') {
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
		}

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

	public function makePayload($payload) {
		$from = makeEmailAddressString(array_key_exists("from", $payload) ? $payload["from"] : $this->from);
		$to = $payload["to"];
		$subject = $payload["subject"];
		$body = $payload["body"];

		$data = [
			"From" => $from,
			"To" => $to,
			"Subject" => $subject,
			"HtmlBody" => $body,
			"MessageStream" => 'outbound',
		];

		return json_encode($data);
	}

	public function send($payload) {
		$data = $this->makePayload($payload);
		$headers = array_merge($this->headers, [$this->token]);

		return  $this->query($this->serviceUrl, $data);
	}

	public function balance() {
		// $from = Carbon::now()->firstOfMonth()->format('Y-m-d');
		$from = '2023-01-01'; // For free account
		$to = Carbon::now()->lastOfMonth()->format('Y-m-d');

		$headers = array_merge($this->headers, [$this->token]);
		$result = $this->query(sprintf($this->balanceUrl, $from, $to), $headers);

		$headers = array_merge($this->headers, [$this->sandbox_token]);
		$sandbox_result = $this->query(sprintf($this->balanceUrl, $from, $to), $headers);
		
		$sent = $result->data->Sent + $sandbox_result->data->Sent;
		$data = config('api-mail.drivers.postmark.monthly_limit') - $sent;

		return (object) [
			'success' => true,
			'data' => $data,
		];
	}
}
