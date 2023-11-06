<?php

namespace Masoud46\LaravelApiSms;

use Masoud46\LaravelApiSms\Services\Elks46;
use Masoud46\LaravelApiSms\Services\Ovh;
use Masoud46\LaravelApiSms\Services\SmsTo;

class ApiSms {
	protected $providers;
	protected $driver;
	protected $smsService;
	protected $cost;

	public function __construct() {
		$provider = config('api-sms.default_provider') ?? '';
		$this->providers = array_keys(config('api-sms.drivers') ?? []);
		$this->checkProvider($provider);
		$this->cost = (object) [
			'sms_id' => '',
			'provider' => $provider,
			'country' => config('api-sms.default_country_code'),
			'currency' => 'EUR',
			'parts' => 0,
			'cost' => 0,
		];
		$this->setProvider($provider);
	}

	private function checkProvider($provider) {
		if (!in_array($provider, $this->providers)) {
			throw new \Exception('Invalid SMS service provider "' . $provider . '". You can choose: ' . implode(', ', $this->providers));
		}
	}

	private function setProvider($provider) {
		$this->checkProvider($provider);
		$this->driver = $this->cost->provider = $provider;

		switch ($this->driver) {
			case '46elks':
				$this->smsService = new Elks46($this->cost);
				break;
			case 'ovh':
				$this->smsService = new Ovh($this->cost);
				break;
			case 'smsto':
				$this->smsService = new SmsTo($this->cost);
				break;
		}
	}

	private function validatePayload($payload) {
		if (!array_key_exists('to', $payload)) {
			throw  new \Exception('"to" number required');
		}
		if (!array_key_exists('message', $payload)) {
			throw  new \Exception('sms "message" required');
		}
	}

	/**
	 * @param $provider
	 * @return object
	 */
	public function provider($provider = null) {
		$this->setProvider($provider ?? config('api-sms.default_provider'));
		return $this;
	}

	/**
	 * @param $payload
	 * @return array|void
	 * @throws \Exception
	 */
	public function estimate($payload) {
		$this->validatePayload($payload);
		return $this->smsService->estimate($payload);
	}

	/**
	 * @param $payload
	 * @return array|void
	 * @throws \Exception
	 */
	public function send($payload) {
		$this->validatePayload($payload);
		return $this->smsService->send($payload);
	}

	/**
	 * @return array|void
	 * @throws \Exception
	 */
	public function balance() {
		return $this->smsService->balance();
	}
}
