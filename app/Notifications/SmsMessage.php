<?php


namespace App\Notifications;

use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Psr7\Request;

class SmsMessage {

	protected String $provider;
	protected String $hlr;
	protected string $to;
	protected array $lines;
	protected string $dryrun;

	/**
	 * SmsMessage constructor.
	 * @param String $provider
	 */
	public function __construct(Array $params = []) {
		$this->provider = $params['provider'] ?? config('project.sms.default_provider');
		$this->hlr = $params['hlr'] ?? false;
		$this->lines = [];
		$this->dryrun = false;
	}

	public function line($line = ''): self {
		$this->lines[] = $line;

		return $this;
	}

	public function to($to): self {
		$this->to = $to;

		return $this;
	}

	public function send(): mixed {
		$result = [
			'success' => false,
			'data' => null,
		];

		if (!$this->provider || !$this->to) {
			$result['data'] = 'SMS not correct.';

			return $result;
		}

		// $message = join("\n", $this->lines) . "\n[" . config('app.name') . "]";
		$message = join("\n", $this->lines);

		switch ($this->provider) {
			case 'ovh':
				$ovh = new \Ovh\Api(
					config('project.sms.ovh.application_key'),
					config('project.sms.ovh.application_secret'),
					config('project.sms.ovh.endpoint'),
					config('project.sms.ovh.consumer_key'),
				);

				$service = config('project.sms.ovh.service');
				$sender = config('project.sms.ovh.sender');

				try {
					if ($this->dryrun) {
						$result['data'] = [
							'credits' => $ovh->get("/sms/{$service}")['creditsLeft'],
						];
						$result['success'] = true;

						return $result;
					}

					$ok = !$this->hlr;

					if ($this->hlr) {
						$content = ['receivers' => [$this->to]];
						$response = $ovh->post("/sms/{$service}/hlr", $content);
						$result['hlr'] = [];

						if (count($response['ids']) > 0) {
							do {
								$result['hlr'] = $ovh->get("/sms/{$service}/hlr/" . $response['ids'][0]);
							} while ($result['hlr']['status'] === "todo");

							$ok = $result['hlr']['reachable'];
						}
					}

					if ($ok) {
						$content = [
							'charset' => "UTF-8",
							'class' => "phoneDisplay",
							'coding' => "7bit",
							'noStopClause' => true,
							'priority' => "high",
							'senderForResponse' => false,
							'validityPeriod' => 2880,
							'sender' => $sender,
							'receivers' => [$this->to],
							'message' => $message,
						];

						$result['data'] = $ovh->post("/sms/{$service}/jobs", $content);
						$result['success'] = count($result['data']['validReceivers']) > 0;
					}
				} catch (ClientException $e) {
					$response = $e->getResponse();
					$result['data'] = $response->getBody()->getContents();
				} catch (\Exception $e) {
					$result['data'] = $e->getMessage();
				}
				break;

			case "smsto":
				$client = new \GuzzleHttp\Client();
				$headers = [
					'Authorization' => 'Bearer ' . config('project.sms.smsto.api_key'),
					'Content-Type' => 'application/json',
				];

				try {
					if ($this->dryrun) {
						$request = new \GuzzleHttp\Psr7\Request('GET', 'https://auth.sms.to/api/balance', $headers);
						$response = $client->sendAsync($request)->wait();

						$result['data'] = json_decode($response->getBody(), true);
						$result['success'] = true;

						return $result;
					}

					$body = [
						"to" => $this->to,
						"message" => $message,
						"bypass_optout" => true,
						"sender_id" => config('project.sms.smsto.sender'),
						// "callback_url" => "https://example.com/callback/handler",
					];
					$request = new Request('POST', 'https://api.sms.to/sms/send', $headers, json_encode($body));
					$response = $client->sendAsync($request)->wait();

					$result['data'] = json_decode($response->getBody(), true);
					$result['success'] = true;
				} catch (ClientException $e) {
					$response = $e->getResponse();
					$result['data'] = $response->getBody()->getContents();
				} catch (\Exception $e) {
					$result['data'] = $e->getMessage();
				}

				break;
		}

		return $result;
	}

	public function dryRun(): self {
		$this->dryrun = true;

		return $this;
	}
}
