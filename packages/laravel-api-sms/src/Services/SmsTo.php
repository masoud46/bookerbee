<?php

namespace Masoud46\LaravelApiSms\Services;

use Masoud46\LaravelApiSms\Contract\Sendable;

class SmsTo extends Sendable {
	protected $cost;
	protected $headers;
	protected $key;
	protected $sender_id;
	protected $callback_url;
	protected $serviceUrl	= 'https://api.sms.to/sms/send';
	protected $estimateUrl	= 'https://api.sms.to/sms/estimate';
	protected $balanceUrl	= 'https://auth.sms.to/api/balance';
	protected $dryrun = false;

	public function __construct($cost) {
		$this->cost = $cost;
		$this->key = config('api-sms.drivers.smsto.api_key');
		$this->sender_id = config('api-sms.drivers.smsto.sender_id');
		$this->callback_url = config('api-sms.drivers.smsto.callback_url');
		$this->headers = [
			'Content-type: application/json',
			'Authorization: Bearer ' . $this->key,
		];
	}

	protected function query($url, $body = '') {
		$method = strlen($body) ? 'POST' : 'GET';
		$ch = curl_init($url);

		curl_setopt($ch, CURLOPT_HTTPHEADER, $this->headers);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		// curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		// curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

		if ($method === 'POST') {
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
		}

		$result = curl_exec($ch);
		$statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

		curl_close($ch);

		$result = json_decode($result);

		if ($statusCode == 0) $result = (object) ['message' => 'Host no found.'];
		$result->success = $statusCode == 200;

		return $result;
	}

	protected function makePayload($payload) {
		if (array_key_exists('country', $payload)) $this->cost->country = $payload['country'];
		if (array_key_exists('dryrun', $payload)) $this->dryrun = $payload['dryrun'];
		if (array_key_exists('callback_url', $payload)) $this->callback_url = $payload['callback_url'];

		$data = [
			'to' => $payload['to'],
			'message' => $payload['message'],
			'sender_id' => $this->sender_id,
			'bypass_optout' => true,
		];

		if ($this->callback_url) $data['callback_url'] = $this->callback_url;

		return json_encode($data);
	}

	protected function calculateCost($data) {
		$this->cost->parts = $data->sms_count;
		$this->cost->cost = intval(ceil($data->estimated_cost * config('api-sms.price_multiplier')));
	}

	public function estimate($payload) {
		$data = $this->makePayload($payload);
		$result = $this->query($this->estimateUrl, $data);

		if ($result->success) {
			$this->calculateCost($result);
			$result = $this->cost;
			$result->success = true;
		}

		return $result;
	}

	public function send($payload) {
		$data = $this->makePayload($payload);
		$estimation = $this->estimate($payload);

		if (!$estimation->success || $this->dryrun) return $estimation;

		$result = $this->query($this->serviceUrl, $data);

		if ($result->success) {
			$this->cost->sms_id = strval($result->message_id);
			$result = $this->cost;
			$result->success = true;
		}

		return $result;
	}

	public function balance() {
		$result = $this->query($this->balanceUrl);

		if ($result->success) {
			$result = (object) [
				'data' => floatval($result->balance),
				'success' => true,
			];
		}

		return $result;
	}
}
