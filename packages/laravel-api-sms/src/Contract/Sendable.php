<?php

namespace Masoud46\LaravelApiSms\Contract;

abstract class Sendable {
	protected function query($url, $body) {
	}
	protected function makePayload($payload) {
	}
	protected function calculateCost($data) {
	}
	public function estimate($payload) {
	}
	public function send($payload) {
	}
	public function balance() {
	}
}
