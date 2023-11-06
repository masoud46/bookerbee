<?php

namespace Masoud46\LaravelApiSms\Services;

use Masoud46\LaravelApiSms\Contract\Sendable;

class Elks46 extends Sendable {
	protected $cost;
	protected $headers;
	protected $username;
	protected $password;
	protected $from;
	protected $whendelivered;
	protected $serviceUrl = 'https://api.46elks.com/a1/sms';
	protected $balanceUrl = 'https://api.46elks.com/a1/me';
	protected $dryrun = false;

	public function __construct($cost) {
		$this->cost = $cost;
		$this->username = config('api-sms.drivers.46elks.username');
		$this->password = config('api-sms.drivers.46elks.password');
		$this->from = config('api-sms.drivers.46elks.from');
		$this->whendelivered = config('api-sms.drivers.46elks.whendelivered');
		$this->headers = [
			'Content-type: application/x-www-form-urlencoded',
		];
	}

	protected function query($url, $body = []) {
		$method = empty($body) ? 'GET' : 'POST';
		$ch = curl_init($url);

		curl_setopt($ch, CURLOPT_HTTPHEADER, $this->headers);
		curl_setopt($ch, CURLOPT_USERPWD, $this->username . ':' . $this->password);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);

		if ($method === 'POST') {
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($body, "", "&"));
		}

		$result = curl_exec($ch);
		$statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

		curl_close($ch);

		switch ($statusCode) {
			case 200:
				$result = json_decode($result);
				break;
			case 0:
				$result = (object) ['message' => 'Host no found.'];
				break;
			default:
				$result = (object) ['message' => $result];
				break;
		}

		$result->success = $statusCode == 200;

		return $result;
	}

	protected function makePayload($payload) {
		if (array_key_exists('country', $payload)) $this->cost->country = $payload['country'];
		if (array_key_exists('dryrun', $payload)) $this->dryrun = $payload['dryrun'];
		if (array_key_exists('whendelivered', $payload)) $this->whendelivered = $payload['whendelivered'];

		$data = [
			'from' => $this->from,
			'to' => $payload['to'],
			'message' => $payload['message'],
			'dryrun' => $this->dryrun ? 'yes' : 'no',
		];
		
		if ($this->whendelivered) $data['whendelivered'] = $this->whendelivered;

		return $data;
	}

	protected function calculateCost($data) {
		$cost = property_exists($data, 'cost') // dryrun = false
			? $data->cost
			: (property_exists($data, 'estimated_cost') // dryrun = true
				? $data->estimated_cost
				: 0.1 // unknown
			);

		$this->cost->parts = $data->parts;
		$this->cost->cost = intval($cost);
	}

	public function estimate($payload) {
		$payload['dryrun'] = true;
		$data = $this->makePayload($payload);
		$result = $this->query($this->serviceUrl, $data);

		if ($result->success) {
			$this->calculateCost($result);
			$result = $this->cost;
			$result->success = true;
		}

		return $result;
	}

	public function send($payload) {
		$data = $this->makePayload($payload);

		if ($this->dryrun) return $this->estimate($payload);

		$result = $this->query($this->serviceUrl, $data);

		if ($result->success) {
			$this->calculateCost($result);
			$this->cost->sms_id = strval($result->id);
			$result = $this->cost;
			$result->success = true;
		}

		return $result;
	}

	public function balance() {
		$result = $this->query($this->balanceUrl);

		if ($result->success) {
			$result = (object) [
				'data' => floatval($result->balance / config('api-sms.price_multiplier')),
				'success' => true,
			];
		}

		return $result;
	}
}
