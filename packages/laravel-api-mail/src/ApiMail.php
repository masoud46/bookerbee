<?php

namespace Masoud46\LaravelApiMail;

use Masoud46\LaravelApiMail\Services\Brevo;
use Masoud46\LaravelApiMail\Services\Mailgun;
use Masoud46\LaravelApiMail\Services\SendGrid;

class ApiMail {
	protected $providers;
	protected $driver;
	protected $mailService;

	public function __construct() {
		$provider = config('api-mail.default_provider') ?? '';
		$this->providers = array_keys(config('api-mail.drivers') ?? []);
		$this->checkProvider($provider);
		$this->setProvider($provider);
	}

	private function checkProvider($provider) {
		if (!in_array($provider, $this->providers)) {
			throw new \Exception('Invalid email service provider "' . $provider . '". You can choose: ' . implode(', ', $this->providers));
		}
	}

	private function setProvider($provider) {
		$this->checkProvider($provider);
		$this->driver = $provider;

		switch ($this->driver) {
			case 'brevo':
				$this->mailService = new Brevo();
				break;
			case 'mailgun':
				$this->mailService = new Mailgun();
				break;
			case 'sendgrid':
				$this->mailService = new SendGrid();
				break;
		}
	}

	private function validatePayload($payload) {
		if (!array_key_exists('to', $payload)) {
			throw  new \Exception('Email "to" address is required.');
		}
		if (!array_key_exists('subject', $payload)) {
			throw  new \Exception('Email "subject" is required.');
		}
		if (!array_key_exists('body', $payload)) {
			throw  new \Exception('Email "body" is required.');
		}
		if (array_key_exists('from', $payload)) {
			if (
				!is_array($payload['from']) ||
				!array_key_exists('name', $payload['from']) ||
				!array_key_exists('email', $payload['from'])
			) {
				throw  new \Exception('Email "from" must be an array of "name" and "email" address.');
			}
		}
	}

	/**
	 * @param $provider
	 * @return object
	 */
	public function provider($provider = null) {
		$this->setProvider($provider ?? config('api-mail.default_provider'));
		return $this;
	}

	/**
	 * @param $payload
	 * @return array|void
	 * @throws \Exception
	 */
	public function send($payload) {
		$this->validatePayload($payload);
		return $this->mailService->send($payload);
	}
}
