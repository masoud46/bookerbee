<?php

namespace Masoud46\LaravelApiSms\Services;

use Masoud46\LaravelApiSms\Contract\Sendable;

class Ovh extends Sendable {
	protected $cost;
	protected $headers;
	protected $application_key;
	protected $application_secret;
	protected $consumer_key;
	protected $endpoint;
	protected $service;
	protected $sender;
	protected $timeUrl;
	protected $serviceUrl;
	protected $balanceUrl;
	protected $hlrUrl;
	protected $ratesUrl;
	protected $estimateUrl;
	protected $dryrun = false;

	private $time_delta = null;
	private $endpoints = [
		'ovh-eu'        => 'https://eu.api.ovh.com/1.0',
		'ovh-ca'        => 'https://ca.api.ovh.com/1.0',
		'ovh-us'        => 'https://api.us.ovhcloud.com/1.0',
		'kimsufi-eu'    => 'https://eu.api.kimsufi.com/1.0',
		'kimsufi-ca'    => 'https://ca.api.kimsufi.com/1.0',
		'soyoustart-eu' => 'https://eu.api.soyoustart.com/1.0',
		'soyoustart-ca' => 'https://ca.api.soyoustart.com/1.0',
		'runabove-ca'   => 'https://api.runabove.com/1.0',
	];

	public function __construct($cost) {
		$apiUrl = $this->endpoints[config('api-sms.drivers.ovh.endpoint')];
		$this->cost = $cost;
		$this->application_key = config('api-sms.drivers.ovh.application_key');
		$this->application_secret = config('api-sms.drivers.ovh.application_secret');
		$this->consumer_key = config('api-sms.drivers.ovh.consumer_key');
		$this->service = config('api-sms.drivers.ovh.service');
		$this->sender = config('api-sms.drivers.ovh.sender');
		$this->timeUrl = $apiUrl . '/auth/time';
		$this->serviceUrl = $apiUrl . '/sms/' . $this->service . '/jobs';
		$this->balanceUrl = $apiUrl . '/sms/' . $this->service;
		$this->hlrUrl = $apiUrl . '/sms/' . $this->service . '/hlr';
		$this->ratesUrl = $apiUrl . '/sms/rates/destinations?country=:country';
		$this->estimateUrl = $apiUrl . '/sms/estimate';
		$this->headers = [
			'Accept: application/json',
			'Content-type: application/json',
			'X-Ovh-Application: ' . $this->application_key,
			'X-Ovh-Consumer: ' . $this->consumer_key,
		];
	}

	protected function query($url, $body = '') {
		if (!$this->time_delta) {
			$ch = curl_init($this->timeUrl);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);

			$result = curl_exec($ch);
			$statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

			curl_close($ch);

			$result = json_decode($result);

			if ($statusCode == 200) {
				$serverTimestamp  = (int)(string)$result;
				$this->time_delta = $serverTimestamp - (int)\time();
			} else {
				if ($statusCode == 0) $result = (object) ['message' => 'Host no found.'];
				$result->success = false;

				return $result;
			}
		}

		$headers = $this->headers;
		$method = strlen($body) ? 'POST' : 'GET';
		$time = time() + $this->time_delta;
		$toSign =
			$this->application_secret . '+' .
			$this->consumer_key . '+' .
			$method . '+' .
			$url . '+' .
			$body . '+' .
			$time;
		$signature = '$1$' . sha1($toSign);

		$headers[] = 'X-Ovh-Timestamp: ' . $time;
		$headers[] = 'X-Ovh-Signature: ' . $signature;

		$ch = curl_init($url);

		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);

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

		if (array_key_exists('estimate', $payload)) {
			$data = [
				'message' => $payload['message'],
				'noStopClause' => true,
				'senderType' => 'alpha',
			];
		} else {
			$data = [
				'receivers' => [$payload['to']],
				'message' => $payload['message'],
				'charset' => 'UTF-8',
				'coding' => '7bit',
				'sender' => $this->sender,
				'noStopClause' => true,
				'priority' => 'high',
			];
		}

		return json_encode($data);
	}

	protected function calculateCost($data) {
		$url = str_replace(':country', strtolower($this->cost->country), $this->ratesUrl);
		$rates = $this->query($url);
		$parts = property_exists($data, 'totalCreditsRemoved') // send
			? intval($data->totalCreditsRemoved / $rates->credit)
			: (property_exists($data, 'parts') // estimate
				? $data->parts
				: 1 // unknown
			);
		$price = ceil($rates->price->value
			* (1 + config('api-sms.drivers.ovh.price_vat') / 100)
			* config('api-sms.price_multiplier'));
		$cost = intval($price * $parts);

		$this->cost->parts = $parts;
		$this->cost->cost = $cost;
		$this->cost->currency = $rates->price->currencyCode;
	}

	public function estimate($payload) {
		$payload['estimate'] = true;
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

		if ($this->dryrun) return $this->estimate($payload);

		$result = $this->query($this->serviceUrl, $data);

		if ($result->success) {
			$this->calculateCost($result);
			$this->cost->sms_id = strval($result->ids[0]);
			$result = $this->cost;
			$result->success = true;
		}

		return $result;
	}

	public function balance() {
		$result = $this->query($this->balanceUrl);

		if ($result->success) {
			$result = (object) [
				'data' => floatval($result->creditsLeft),
				'success' => true,
			];
		}

		return $result;
	}
}
