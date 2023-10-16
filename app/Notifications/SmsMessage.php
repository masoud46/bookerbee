<?php


namespace App\Notifications;

use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Psr7\Request;
use Illuminate\Support\Facades\DB;

class SmsMessage {

	protected string $country;
	protected string $provider;
	protected string $hlr;
	protected ?string $action;
	protected string $to;
	protected array $lines;
	protected string $dryrun;
	protected ?int $event_id;

	protected string $service;
	protected string $sender;
	protected string $message;

	protected array $cost_data;

	/**
	 * SmsMessage constructor.
	 * @param Array $params
	 */
	public function __construct(array $params = []) {
		$this->country = strtoupper($params['country'] ?? config('project.default_country_code'));
		$this->provider = $params['provider'] ?? config('project.sms.default_provider');
		$this->hlr = $params['hlr'] ?? false;
		$this->lines = [];
		$this->dryrun = false;
		$this->action = $params['action'] ?? null;
		$this->event_id = isset($params['event_id']) && is_numeric($params['event_id']) ?
			(intval($params['event_id']) > 0 ?
				intval($params['event_id']) :
				null
			) :
			null;

		$this->cost_data = [
			'event_id' => $this->event_id,
			'sms_id' => "",
			'action' => $this->action,
			'provider' => $this->provider,
			'country' => $this->country,
			'currency' => "EUR",
			'parts' => 0,
			'cost' => 0,
		];
	}

	protected function exceptionMessage($exception): mixed {
		if ($exception->hasResponse()) {
			$response = $exception->getResponse();
			return $response->getReasonPhrase();
		} else {
			$response = $exception->getHandlerContext();
			if (isset($response['error'])) {
				return $response['error'];
			}
		}

		return "Guzzle: unknown error.";
	}

	protected function estimate($client, $headers, $body): array {
		$result = [
			'success' => false,
			'data' => $this->cost_data,
		];

		try {
			switch ($this->provider) {
				case 'ovh':
					$estimation = $client->post('/sms/estimate', $body);
					$rate = $client->get('/sms/rates/destinations', [
						'country' => strtolower($this->country),
					]);

					$price = ceil($rate['price']['value'] * (1 + config('project.vat') / 100) * config('project.sms_price_multiplier'));
					$cost = intval($price * $estimation['parts']);

					$result['data']['parts'] = $estimation['parts'];
					$result['data']['cost'] = $cost;
					$result['data']['currency'] = $rate['price']['currencyCode'];
					$result['success'] = true;
					break;
				case 'smsto':
					$request = new \GuzzleHttp\Psr7\Request('POST', 'https://api.sms.to/sms/estimate', $headers, $body);
					$response = $client->sendAsync($request)->wait();
					$res = json_decode($response->getBody(), true);

					if (empty($res['errors'])) { // no errors
						$result['data']['parts'] = $res['sms_count'];
						$result['data']['cost'] = intval(ceil($res['estimated_cost'] * config('project.sms_price_multiplier')));
						$result['success'] = true;
					} else {
						$result['error'] = $res['errors'];
					}
					break;
			}
		} catch (ClientException $e) {
			$result['error'] = $this->exceptionMessage($e);
		} catch (\Exception $e) {
			$result['error'] = $e->getMessage();
		}

		return $result;
	}

	protected function addToDb($data) {
		try {
			DB::table('event_sms')->insert($data);
		} catch (\Throwable $th) {
		}
	}

	public function to($to): self {
		$this->to = $to;

		return $this;
	}

	public function line($line = ''): self {
		$this->lines[] = $line;

		return $this;
	}

	public function dryRun(): self {
		$this->dryrun = true;

		return $this;
	}

	public function send(): mixed {
		$result = [
			'success' => false,
			'data' => null,
			'error' => false,
		];

		if (!$this->provider || !$this->to) {
			$result['error'] = 'SMS not correct.';

			return $result;
		}

		// $this->message = join("\n", $this->lines) . "\n[" . config('app.name') . "]";
		$this->message = join("\n", $this->lines);

		try {
			switch ($this->provider) {
				case 'ovh':
					$client = new \Ovh\Api(
						config('project.sms.ovh.application_key'),
						config('project.sms.ovh.application_secret'),
						config('project.sms.ovh.endpoint'),
						config('project.sms.ovh.consumer_key'),
					);

					$this->service = config('project.sms.ovh.service');
					$this->sender = config('project.sms.ovh.sender');

					if ($this->dryrun) {
						$estimation = $this->estimate($client, null, [
							'message' => $this->message,
							'noStopClause' => true,
							'senderType' => "alpha",
						]);

						// $this->addToDb($estimation['data']);
						return $estimation;
					}

					$reachable = !$this->hlr;

					if ($this->hlr) {
						$content = ['receivers' => [$this->to]];
						$response = $client->post("/sms/{$this->service}/hlr", $content);
						$result['hlr'] = [];

						if (count($response['ids']) > 0) {
							do {
								$result['hlr'] = $client->get("/sms/{$this->service}/hlr/" . $response['ids'][0]);
							} while ($result['hlr']['status'] === "todo");

							$reachable = $result['hlr']['reachable'];
						}
					}

					if ($reachable) {
						$body = [
							'charset' => "UTF-8",
							'class' => "phoneDisplay",
							'coding' => "7bit",
							'noStopClause' => true,
							'priority' => "high",
							'senderForResponse' => false,
							'validityPeriod' => 2880,
							'sender' => $this->sender,
							'receivers' => [$this->to],
							'message' => $this->message,
						];

						$data = $client->post("/sms/{$this->service}/jobs", $body);
						$result['success'] = empty($data['invalidReceivers']) && !empty($data['validReceivers']);

						if ($result['success']) {
							if ($this->event_id) {
								$rate = $client->get('/sms/rates/destinations', [
									'country' => strtolower($this->country),
								]);
								$parts = intval($data['totalCreditsRemoved'] / $rate['credit']);
								$price = ceil($rate['price']['value'] * (1 + config('project.vat') / 100) * config('project.sms_price_multiplier'));
								$cost = intval($price * $parts);

								$result['data'] = array_merge($this->cost_data, [
									'sms_id' => strval($data['ids'][0]),
									'parts' => $parts,
									'cost' => $cost,
								]);

								$this->addToDb($result['data']);
							}
						} else {
							$result['error'] = "unknown error!";
						}
					}
					break;

				case "smsto":
					$client = new \GuzzleHttp\Client();
					$headers = [
						'Authorization' => 'Bearer ' . config('project.sms.smsto.api_key'),
						'Content-Type' => 'application/json',
					];

					$this->sender = config('project.sms.smsto.sender_id');

					$body = json_encode([
						"to" => $this->to,
						"message" => $this->message,
						"bypass_optout" => true,
						"sender_id" => $this->sender,
						// "callback_url" => "https://example.com/callback/handler",
					]);

					$estimation = $this->estimate($client, $headers, $body);

					if ($this->dryrun) {
						// $this->addToDb($estimation['data']);
						return $estimation;
					}

					if ($estimation['success']) {
						$request = new Request('POST', 'https://api.sms.to/sms/send', $headers, $body);
						$response = $client->sendAsync($request)->wait();
						$data = json_decode($response->getBody(), true);

						$result['success'] = $data['success'];

						if ($result['success']) {
							if ($this->event_id) {
								$result['data'] = array_merge($estimation['data'], [
									'sms_id' => $data['message_id'],
								]);

								$this->addToDb($result['data']);
							}
						} else {
							$result['error'] = "unknown error!";
						}
					} else {
						$result['error'] = $estimation['error'];
					}
					break;
			}
		} catch (ClientException $e) {
			$result['error'] = $this->exceptionMessage($e);
		} catch (\Exception $e) {
			$result['error'] = $e->getMessage();
		}

		return $result;
	}
}
