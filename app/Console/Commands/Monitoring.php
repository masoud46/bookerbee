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

	/**
	 * Create a new console command instance.
	 *
	 * @return void
	 */
	public function __construct($for_admin_page = false) {
		$this->for_admin_page = $for_admin_page;
		$this->debug = !$for_admin_page && config('app.env') === 'local';

		parent::__construct();
	}

	/**
	 * Send report about critical status.
	 */
	private function sendCriticalReport($message, $link = null, $email_only = false, $mail_provider = "sendgrid", $sms_provider = "ovh") {
		if ($this->for_admin_page || $this->debug) {
			return;
		}

		$email = config('project.monitoring.email');
		$phone = config('project.monitoring.phone');

		try {
			Mail::mailer($mail_provider)
				->to($email)
				->send(new MonitoringEmail($message, $link));
		} catch (\Throwable $th) {
		}

		if (!$email_only) {
			try {
				$sms = new \App\Notifications\SmsMessage(['provider' => $sms_provider]);
				$sms->to(preg_replace('/\s+/', '', $phone))
					->line($message)
					->send();
			} catch (\Throwable $th) {
			}
		}

		sleep(3);
	}

	/**
	 * Execute the console command.
	 */
	public function handle() {
		$email_limit = intval(config('project.monitoring.email_limit'));

		$any_error = false;
		$result = [
			'sms' => [],
			'email' => [],
		];

		$filename = 'monitoring_report.json';
		if (Storage::missing($filename)) {
			Storage::put($filename, json_encode([
				'sms' => [
					'ovh' => false,
					'smsto' => false,
				],
				'email' => [
					'sendgrid' => false,
					'brevo' => false,
				],
			]), JSON_UNESCAPED_SLASHES);

			$old_mask = umask(0);
			@mkdir(storage_path("app/{$filename}"), 0777, true);
			umask($old_mask);
		}

		$report = Storage::json('monitoring_report.json');


		/*************************/
		/*** SMS - OVH credits ***/
		/*************************/
		if ($this->debug) echo "SMS credits - OVH" . PHP_EOL;

		$client = new \Ovh\Api(
			config('project.sms.ovh.application_key'),
			config('project.sms.ovh.application_secret'),
			config('project.sms.ovh.endpoint'),
			config('project.sms.ovh.consumer_key'),
		);
		$service = config('project.sms.ovh.service');

		try {
			$response = $client->get("/sms/{$service}");
			
			if (isset($response['creditsLeft'])) {
				$credits = floatval($response['creditsLeft']);

				if ($this->debug) echo $credits . PHP_EOL . PHP_EOL;

				$result['sms']['ovh'] = [
					'success' => true,
					'data' => $credits,
				];

				if ($credits < config('project.sms.ovh.critical_credit')) {
					$result['sms']['ovh']['success'] = false;

					if ($report['sms']['ovh'] === false) {
						$any_error = true;

						$message = "SMS critical credits - OVH: {$credits}";
						$this->sendCriticalReport($message);
						Log::channel('monitoring')->info($message);
						if ($this->debug) echo "*** REPORT SENT" . PHP_EOL . PHP_EOL;

						Log::channel('monitoring')->info("*** REPORT SENT.");
						$report['sms']['ovh'] = true;
					}
				} else {
					$report['sms']['ovh'] = false;
				}
			} else {
				$result['sms']['ovh']['success'] = false;

				if ($report['sms']['ovh'] === false) {
					$any_error = true;
					$data = json_encode($response, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
					$result['sms']['ovh'] = [
						'success' => false,
						'data' => $data,
					];

					$message = "SMS ERROR - OVH: {$data}";
					$this->sendCriticalReport($message, null, true);
					Log::channel('monitoring')->info($message);
					if ($this->debug) echo "*** REPORT SENT" . PHP_EOL . PHP_EOL;

					Log::channel('monitoring')->info("*** REPORT SENT.");
					$report['sms']['ovh'] = true;
				}
			}
		} catch (ClientException $e) {
			if ($report['sms']['ovh'] === false) {
				$any_error = true;

				$response = $e->getResponse();
				$error = $response->getBody()->getContents();

				if ($this->debug) echo "ERROR (ClientException): " . $error . PHP_EOL . PHP_EOL;
				$result['sms']['ovh'] = [
					'success' => false,
					'data' => $error,
				];

				$message = "SMS ERROR - OVH: {$error}";
				$this->sendCriticalReport($message, null, true);
				Log::channel('monitoring')->info($message);
				if ($this->debug) echo "*** REPORT SENT" . PHP_EOL . PHP_EOL;

				Log::channel('monitoring')->info("*** REPORT SENT.");
				$report['sms']['ovh'] = true;
			}
		} catch (\Exception $e) {
			if ($report['sms']['ovh'] === false) {
				$any_error = true;

				$error = $e->getMessage();
				if ($this->debug) echo "ERROR (Exception): " . $error . PHP_EOL . PHP_EOL;
				$result['sms']['ovh'] = [
					'success' => false,
					'data' => $error,
				];

				$message = "SMS ERROR - OVH: {$error}";
				$this->sendCriticalReport($message, null, true);
				Log::channel('monitoring')->info($message);
				if ($this->debug) echo "*** REPORT SENT" . PHP_EOL . PHP_EOL;

				Log::channel('monitoring')->info("*** REPORT SENT.");
				$report['sms']['ovh'] = true;
			}
		}


		/***************************/
		/*** SMS - SMSto credits ***/
		/***************************/
		if ($this->debug) echo "SMS - SMSto credits" . PHP_EOL;

		$client = new Client();
		$headers = [
			'Authorization' => 'Bearer ' . config('project.sms.smsto.api_key'),
			'Content-Type' => 'application/json',
		];

		try {
			$request = new Request('GET', 'https://auth.sms.to/api/balance', $headers);
			$response = $client->sendAsync($request)->wait();
			$response = json_decode($response->getBody(), true);
			
			if (isset($response['balance'])) {
				$credits = $response['balance'];

				if ($this->debug) echo $credits . PHP_EOL . PHP_EOL;

				$result['sms']['smsto'] = [
					'success' => false,
					'data' => $credits,
				];

				if ($credits < config('project.sms.smsto.critical_credit')) {
					$result['sms']['smsto']['success'] = false;

					if ($report['sms']['smsto'] === false) {
						$any_error = true;

						$message = "SMS critical credits - SMSto: {$credits}";
						$this->sendCriticalReport($message);
						Log::channel('monitoring')->info($message);
						if ($this->debug) echo "*** REPORT SENT" . PHP_EOL . PHP_EOL;

						Log::channel('monitoring')->info("*** REPORT SENT.");
						$report['sms']['smsto'] = true;
					}
				} else {
					$report['sms']['smsto'] = false;
				}
			} else {
				$any_error = true;
				$data = json_encode($response, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
				$result['sms']['smsto'] = [
					'success' => false,
					'data' => $data,
				];

				$message = "SMS ERROR - SMSto: {$data}";
				$this->sendCriticalReport($message);
				Log::channel('monitoring')->info($message);
				if ($this->debug) echo "*** REPORT SENT" . PHP_EOL . PHP_EOL;

				Log::channel('monitoring')->info("*** REPORT SENT.");
				$report['sms']['smsto'] = true;
			}
		} catch (ClientException $e) {
			if ($report['sms']['smsto'] === false) {
				$any_error = true;

				$response = $e->getResponse();
				$error = $response->getBody()->getContents();
				if ($this->debug) echo "ERROR (ClientException): " . $error . PHP_EOL . PHP_EOL;
				$result['sms']['smsto'] = [
					'success' => false,
					'data' => $error,
				];

				$message = "SMS ERROR - SMSto: {$error}";
				$this->sendCriticalReport($message, null, true);
				Log::channel('monitoring')->info($message);
				if ($this->debug) echo "*** REPORT SENT" . PHP_EOL . PHP_EOL;

				Log::channel('monitoring')->info("*** REPORT SENT.");
				$report['sms']['smsto'] = true;
			}
		} catch (\Exception $e) {
			if ($report['sms']['smsto'] === false) {
				$any_error = true;

				$error = $e->getMessage();
				if ($this->debug) echo "ERROR (Exception): " . $error . PHP_EOL . PHP_EOL;
				$result['sms']['smsto'] = [
					'success' => false,
					'data' => $error,
				];

				$message = "SMS ERROR - SMSto: {$error}";
				$this->sendCriticalReport($message, null, true);
				Log::channel('monitoring')->info($message);
				if ($this->debug) echo "*** REPORT SENT" . PHP_EOL . PHP_EOL;

				Log::channel('monitoring')->info("*** REPORT SENT.");
				$report['sms']['smsto'] = true;
			}
		}


		/********************************/
		/*** Email - SendGrid credits ***/
		/********************************/
		$url = 'https://api.sendgrid.com/v3/user/credits';
		$api_key = config('project.mail.sendgrid.admin_key');

		$ch = curl_init($url);
		$headers = [
			'Content-Type: application/json',
			'Authorization: Bearer ' . $api_key,
		];

		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

		$response = curl_exec($ch);
		curl_close($ch);

		if ($this->debug) echo "Email credits - SendGrid" . PHP_EOL;

		$email_only = false;

		if ($response === false) {
			$any_error = true;
			$message = 'Could not get a response';
			if ($this->debug) echo $message;
			$result['email']['sendgrid'] = [
				'success' => false,
				'data' => $message,
			];

			$this->sendCriticalReport($message, null, $email_only, "brevo");
			Log::channel('monitoring')->info($message);
			if ($this->debug) echo "*** REPORT SENT" . PHP_EOL . PHP_EOL;

			Log::channel('monitoring')->info("*** REPORT SENT.");
			$report['email']['sendgrid'] = true;
		} else {
			$res = json_decode($response, true);
			if (isset($res['remain']) || isset($res['message'])) {
				if ($this->debug) echo ($res['remain'] ?? $res['message']) . PHP_EOL . PHP_EOL;
				if (isset($res['remain'])) {
					$res = [
						'success' => true,
						'data' => intval($res['remain']),
					];
				} else {
					$res = [
						'success' => false,
						'data' => $res['message'],
					];
				}
			} else {
				$email_only = true;
				$res = [
					'success' => false,
					'data' => 'No $res[\'remain\']) and no $res[\'message\']',
				];
			}

			$result['email']['sendgrid'] = $res;

			if (!$res['success']) {
				if ($report['email']['sendgrid'] === false) {
					$any_error = true;

					$message = "Email ERROR - SendGrid: {$res['data']}";
					$this->sendCriticalReport($message, null, $email_only, "brevo");
					Log::channel('monitoring')->info($message);
					if ($this->debug) echo "*** REPORT SENT" . PHP_EOL . PHP_EOL;

					Log::channel('monitoring')->info("*** REPORT SENT.");
					$report['email']['sendgrid'] = true;
				}
			} else if ($res['data'] < $email_limit) {
				$result['email']['sendgrid']['success'] = false;
				if ($report['email']['sendgrid'] === false) {
					$any_error = true;

					$message = "Email critical credits - SendGrid: {$res['data']}";
					$this->sendCriticalReport($message, null, $email_only, "brevo");
					Log::channel('monitoring')->info($message);
					if ($this->debug) echo "*** REPORT SENT" . PHP_EOL . PHP_EOL;

					Log::channel('monitoring')->info("*** REPORT SENT.");
					$report['email']['sendgrid'] = true;
				}
			} else {
				$report['email']['sendgrid'] = false;
			}
		}


		/*****************************/
		/*** Email - Brevo credits ***/
		/*****************************/
		$url = 'https://api.brevo.com/v3/account';
		$api_key = config('project.mail.brevo.api_key');

		$ch = curl_init($url);
		$headers = [
			'accept: application/json',
			'api-key: ' . $api_key,
		];

		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

		$response = curl_exec($ch);
		curl_close($ch);

		if ($this->debug) echo "Email credits - Brevo" . PHP_EOL;

		$email_only = false;

		if ($response === false) {
			$result['email']['brevo'] = [
				'success' => false,
				'data' => 'Could not get a response',
			];
		} else {
			$res = json_decode($response, true);
			if (isset($res['plan'][0]['credits']) || isset($res['message'])) {
				if ($this->debug) echo ($res['plan'][0]['credits'] ?? $res['message']) . PHP_EOL . PHP_EOL;
				if (isset($res['plan'][0]['credits'])) {
					$res = [
						'success' => true,
						'data' => intval($res['plan'][0]['credits']),
					];
				} else {
					$res = [
						'success' => false,
						'data' => $res['message'],
					];
				}
			} else {
				$email_only = true;
				$res = [
					'success' => false,
					'data' => 'No $res[\'plan\'][0][\'credits\']) and no $res[\'message\']',
				];
			}

			$result['email']['brevo'] = $res;

			if (!$res['success']) {
				if ($report['email']['brevo'] === false) {
					$any_error = true;

					$message = "Email ERROR - Brevo: {$res['data']}";
					$this->sendCriticalReport($message, null, $email_only, "sendgrid");
					Log::channel('monitoring')->info($message);
					if ($this->debug) echo "*** REPORT SENT" . PHP_EOL . PHP_EOL;

					Log::channel('monitoring')->info("*** REPORT SENT.");
					$report['email']['brevo'] = true;
				}
			} else if ($res['data'] < $email_limit) {
				$result['email']['brevo']['success'] = false;
				if ($report['email']['brevo'] === false) {
					$any_error = true;

					$message = "Email critical credits - Brevo: {$res['data']}";
					$this->sendCriticalReport($message, null, $email_only, "sendgrid");
					Log::channel('monitoring')->info($message);
					if ($this->debug) echo "*** REPORT SENT" . PHP_EOL . PHP_EOL;

					Log::channel('monitoring')->info("*** REPORT SENT.");
					$report['email']['brevo'] = true;
				}
			} else {
				$report['email']['brevo'] = false;
			}
		}


		Storage::put('monitoring_report.json', json_encode($report), JSON_UNESCAPED_SLASHES);

		if ($any_error) {
			Log::channel('monitoring')->info("----------------------------------------");
		}

		return $result;
	}
}
