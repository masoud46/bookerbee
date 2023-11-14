<?php

namespace App\Console\Commands;

use App\Mail\MonitoringEmail;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Psr7\Request;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Masoud46\LaravelApiMail\Facades\ApiMail;
use Masoud46\LaravelApiSms\Facades\ApiSms;

class Monitoring extends Command {
	/**
	 * The name and signature of the console command.
	 *
	 * @var string
	 */
	protected $signature = 'app:monitoring';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = "Send warning email and sms to developer about quotas etc...";

	protected $for_admin_page;
	protected $debug;
	protected $providers;
	protected $report_file;
	protected $report;
	protected $channel = 'monitoring';
	protected $any_error = false;
	protected $result = [
		'email' => [],
		'sms' => [],
	];

	/**
	 * Create a new console command instance.
	 *
	 * @return void
	 */
	public function __construct($for_admin_page = false) {
		$this->for_admin_page = $for_admin_page;
		$this->debug = !$for_admin_page && config('app.env') === 'local';
		$this->providers = [
			'email' => array_keys(array_filter(config('api-mail.drivers'), function ($driver) {
				return $driver['active'];
			})),
			'sms' => array_keys(array_filter(config('api-sms.drivers'), function ($driver) {
				return $driver['active'];
			})),
		];

		$this->report_file = config('project.monitoring.filename');
		if (Storage::missing($this->report_file)) {
			$providers = ['email' => [], 'sms' => []];
			foreach ($this->providers as $service => $values)
				foreach ($values as $provider)
					$providers[$service][$provider] = false;
			Storage::put($this->report_file, json_encode($providers), JSON_UNESCAPED_SLASHES);

			$old_mask = umask(0);
			@mkdir(storage_path("app/{$this->report_file}"), 0777, true);
			umask($old_mask);
		}
		$this->report = Storage::json($this->report_file);

		parent::__construct();
	}

	/**
	 * Send critical status or error report.
	 */
	private function sendReport($providers, $subject, $message) {
		if ($this->for_admin_page) return;

		if (isset($providers['email'])) {
			$to = config('project.monitoring.email');
			$payload = [
				'to' => $to,
				'subject' => $subject,
				'body' => (new MonitoringEmail($message))->render(),
			];
			try {
				$email = ApiMail::provider($providers['email']);
				if (!$email->send($payload)->success) { // Retry once
					sleep(2);
					$email->send($payload);
				}
				if ($this->debug) echo "email ({$providers['email']}): {$to} <- {$message}" . PHP_EOL;
			} catch (\Exception $e) {
				echo $e->getMessage();
			}
		}

		if (isset($providers['sms'])) {
			$to = preg_replace('/\s+/', '', config('project.monitoring.phone'));
			$payload = [
				// 'country' => $country_code,
				'to' => $to,
				'message' => $message,
				'dryrun' => config('app.env') !== 'production',
			];
			try {
				$sms = ApiSms::provider($providers['sms']);
				if (!$sms->send($payload)->success) { // Retry once
					sleep(2);
					$sms->send($payload);
				}
				if ($this->debug) echo "sms ({$providers['sms']}): {$to} <- {$message}" . PHP_EOL;
			} catch (\Exception $e) {
				echo $e->getMessage();
			}
		}

		sleep(2);
	}

	/**
	 * Log error/critical status and send report.
	 */
	private function inform($service, $provider, $message, $critical = true) {
		if ($this->report[$service][$provider] === false) {
			$this->any_error = true;

			if ($this->debug) echo "{$service} ERROR - {$provider}: {$message}" . PHP_EOL;
			$this->result[$service][$provider] = [
				'success' => false,
				'data' => $message,
			];

			$subject = "Monitoring: {$service} - {$provider}";
			$message = "{$service} ERROR - {$provider}: {$message}";
			$providers = [
				'email' => config('api-mail.default_provider'),
				'sms' => config('api-sms.default_provider'),
			];

			// Switch provider if it is the default provider.
			// Except for critical SMS ballance where there is no other provider
			if (
				$provider === $providers[$service] &&
				($service != 'sms' || count($this->providers[$service]) > 1)
			) {
				$filtered = array_values(
					array_filter($this->providers[$service], function ($p) use ($provider) {
						return $p !== $provider;
					})
				);

				if (count($filtered)) {
					$providers[$service] = $filtered[0];
				} else {
					unset($providers[$service]);
				}
			}

			// OVH issue: do not send SMS if OVH server is not responding
			if ($service === 'sms' && $provider === 'ovh' && str_contains($message, 'cURL error 28')) {
				unset($providers['sms']);
			}

			// Do not send SMS if the report is not critical
			if (!$critical) {
				unset($providers['sms']);
			}

			Log::channel($this->channel)->info($message);

			$this->sendReport($providers, $subject, $message);
			Log::channel($this->channel)->info("*** REPORT SENT.");
			if ($this->debug) echo "*** REPORT SENT" . PHP_EOL . PHP_EOL;

			$this->report[$service][$provider] = true;
		}
	}

	/**
	 * Execute the console command.
	 */
	public function handle() {
		foreach ($this->providers as $service => $values) {
			if ($this->debug) echo strtoupper(PHP_EOL . "{$service} balance") . PHP_EOL;
			foreach ($values as $provider) {
				$this->result[$service][$provider] = ['success' => false];
				$api = $service === 'email' ? 'mail' : 'sms';
				$limit = config("api-{$api}.drivers.{$provider}.monthly_limit");
				$critical_balance = config("api-{$api}.drivers.{$provider}.critical_balance");
				if ($this->debug) echo "{$provider}: ";

				try {
// if ($provider === 'sendgrid') $z = ApiMail::provider('test')->balance();
					$result = $api === 'mail'
						? ApiMail::provider($provider)->balance()
						: ApiSms::provider($provider)->balance();

					if ($result->success) {
						$data = $result->data ?? null;
						if ($data) {
							$result->critical = $data <= $critical_balance;
							$this->result[$service][$provider] = [
								'success' => true,
								'data' => $data,
								'limit' => $limit,
								'critical' => $result->critical,
							];
							if ($this->debug) echo $data . PHP_EOL;

							if ($this->result[$service][$provider]['critical']) {
								if ($this->debug) echo '---CRITICAL---' . PHP_EOL;
								$this->inform($service, $provider, "Low balance :: {$data}");
							} else {
								$this->report[$service][$provider] = false;
							}
						} else {
							$data = "no data :: status:{$result->status} - response:{$result->response}";
							$this->result[$service][$provider] = [
								'success' => false,
								'data' => $data,
								'critical' => false,
							];
							if ($this->debug) echo '---NO DATA---' . PHP_EOL . $data . PHP_EOL;
							$this->inform($service, $provider, $data, false); // Not critical
							$this->report[$service][$provider] = false;
						}
					} else {
						$message = "success false :: message:{$result->message}";
						if (isset($result->status)) $message .= " - status:{$result->status}";
						if (isset($result->response)) $message .= " - response:{$result->response}";
						$this->result[$service][$provider]['message'] = $message;
						$this->inform($service, $provider, $message);
						if ($this->debug) echo $message . PHP_EOL;
					}
				} catch (\Exception $e) {
					$message = "exception :: {$e->getMessage()}" . PHP_EOL . PHP_EOL . "{$e->getFile()} - line {$e->getLine()}";
					$this->result[$service][$provider]['message'] = $message;
					$this->inform($service, $provider, $message);
					if ($this->debug) echo $message . PHP_EOL;
				}
			}
		}

		Storage::put($this->report_file, json_encode($this->report), JSON_UNESCAPED_SLASHES);

		if ($this->any_error) {
			Log::channel($this->channel)->info("----------------------------------------");
		}

		if ($this->debug) echo PHP_EOL;

		return $this->result;
	}
}
