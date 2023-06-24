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

	private $is_admin = false;

	/**
	 * Create a new console command instance.
	 *
	 * @return void
	 */
	public function __construct($is_admin = false) {
		$this->is_admin = $is_admin;

		parent::__construct();
	}

	/**
	 * Send report about critical status.
	 */
	private function sendCriticalReport($message, $link = null, $email_only = false, $mail_provider = "brevo", $sms_provider = "ovh") {
		if (!$this->is_admin) {
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
	}

	/**
	 * Check status.
	 */
	public function check() {
		$sms_limit = intval(config('project.monitoring.sms_limit'));
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
		if (!$this->is_admin) echo "SMS credits - OVH" . PHP_EOL;

		$ovh = new \Ovh\Api(
			config('project.sms.ovh.application_key'),
			config('project.sms.ovh.application_secret'),
			config('project.sms.ovh.endpoint'),
			config('project.sms.ovh.consumer_key'),
		);
		$service = config('project.sms.ovh.service');

		try {
			$response = $ovh->get("/sms/{$service}");
			$credits = floatval($response['creditsLeft']);

			if (!$this->is_admin) echo $credits . PHP_EOL . PHP_EOL;

			$result['sms']['ovh'] = [
				'success' => true,
				'data' => $credits,
			];

			if ($credits < $sms_limit) {
				$result['sms']['ovh']['success'] = false;

				if ($report['sms']['ovh'] === false) {
					$any_error = true;

					$this->sendCriticalReport("SMS critical credits - OVH: {$credits}");
					Log::channel('monitoring')->info("SMS critical credits - OVH: {$credits}");
					if (!$this->is_admin) echo "*** REPORT SENT" . PHP_EOL . PHP_EOL;

					Log::channel('monitoring')->info("*** REPORT SENT.");
					$report['sms']['ovh'] = true;
				}
			} else {
				$report['sms']['ovh'] = false;
			}
		} catch (ClientException $e) {
			if ($report['sms']['ovh'] === false) {
				$any_error = true;

				$response = $e->getResponse();
				$error = $response->getBody()->getContents();

				if (!$this->is_admin) echo "ERROR (ClientException): " . $error . PHP_EOL . PHP_EOL;
				$result['sms']['ovh'] = [
					'success' => false,
					'data' => $error,
				];

				Log::channel('monitoring')->info("SMS ERROR - OVH! :: {$error}");

				$this->sendCriticalReport("SMS ERROR - OVH: {$error}", null, true);
				if (!$this->is_admin) echo "*** REPORT SENT" . PHP_EOL . PHP_EOL;

				Log::channel('monitoring')->info("*** REPORT SENT.");
				$report['sms']['ovh'] = true;
			}
		} catch (\Exception $e) {
			if ($report['sms']['ovh'] === false) {
				$any_error = true;

				$error = $e->getMessage();
				if (!$this->is_admin) echo "ERROR (Exception): " . $error . PHP_EOL . PHP_EOL;
				$result['sms']['ovh'] = [
					'success' => false,
					'data' => $error,
				];

				Log::channel('monitoring')->info("SMS ERROR - OVH! :: {$error}");

				$this->sendCriticalReport("SMS ERROR - OVH: {$error}", null, true);
				if (!$this->is_admin) echo "*** REPORT SENT" . PHP_EOL . PHP_EOL;

				Log::channel('monitoring')->info("*** REPORT SENT.");
				$report['sms']['ovh'] = true;
			}
		}


		/***************************/
		/*** SMS - SMSto credits ***/
		/***************************/
		if (!$this->is_admin) echo "SMS - SMSto credits" . PHP_EOL;

		$client = new Client();
		$headers = [
			'Authorization' => 'Bearer ' . config('project.sms.smsto.api_key'),
			'Content-Type' => 'application/json',
		];

		try {
			$request = new Request('GET', 'https://auth.sms.to/api/balance', $headers);
			$response = $client->sendAsync($request)->wait();
			$credits = json_decode($response->getBody(), true)['balance'];

			if (!$this->is_admin) echo $credits . PHP_EOL . PHP_EOL;

			$result['sms']['smsto'] = [
				'success' => false,
				'data' => $credits,
			];

			if ($credits < $sms_limit) {
				$result['sms']['smsto']['success'] = false;

				if ($report['sms']['smsto'] === false) {
					$any_error = true;

					$this->sendCriticalReport("SMS critical credits - SMSto: {$credits}");
					Log::channel('monitoring')->info("SMS critical credits - SMSto: {$credits}");
					if (!$this->is_admin) echo "*** REPORT SENT" . PHP_EOL . PHP_EOL;

					Log::channel('monitoring')->info("*** REPORT SENT.");
					$report['sms']['smsto'] = true;
				}
			} else {
				$report['sms']['smsto'] = false;
			}
		} catch (ClientException $e) {
			if ($report['sms']['smsto'] === false) {
				$any_error = true;

				$response = $e->getResponse();
				$error = $response->getBody()->getContents();
				if (!$this->is_admin) echo "ERROR (ClientException): " . $error . PHP_EOL . PHP_EOL;
				$result['sms']['smsto'] = [
					'success' => false,
					'data' => $error,
				];

				Log::channel('monitoring')->info("SMS ERROR - SMSto! :: {$error}");

				$this->sendCriticalReport("SMS ERROR - SMSto: {$error}", null, true);
				if (!$this->is_admin) echo "*** REPORT SENT" . PHP_EOL . PHP_EOL;

				Log::channel('monitoring')->info("*** REPORT SENT.");
				$report['sms']['smsto'] = true;
			}
		} catch (\Exception $e) {
			if ($report['sms']['smsto'] === false) {
				$any_error = true;

				$error = $e->getMessage();
				if (!$this->is_admin) echo "ERROR (Exception): " . $error . PHP_EOL . PHP_EOL;
				$result['sms']['smsto'] = [
					'success' => false,
					'data' => $error,
				];

				Log::channel('monitoring')->info("SMS ERROR - SMSto! :: {$error}");

				$this->sendCriticalReport("SMS ERROR - SMSto: {$error}", null, true);
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

		if (!$this->is_admin) echo "Email credits - SendGrid" . PHP_EOL;

		if ($response === false) {
			if (!$this->is_admin) echo 'Could not get a response';
			$result['email']['sendgrid'] = [
				'success' => false,
				'data' => 'Could not get a response',
			];
		} else {
			if (!$this->is_admin) echo json_decode($response, true)['remain'] . PHP_EOL . PHP_EOL;
			$res = json_decode($response, true);
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

			$result['email']['sendgrid'] = $res;

			if (!$res['success']) {
				if ($report['email']['sendgrid'] === false) {
					$any_error = true;

					$this->sendCriticalReport("Email ERROR - SendGrid: {$res['data']}", null, false, "brevo");
					if (!$this->is_admin) echo "*** REPORT SENT" . PHP_EOL . PHP_EOL;

					Log::channel('monitoring')->info("*** REPORT SENT.");
					$report['email']['sendgrid'] = true;
				}
			} else if ($res['data'] < $email_limit) {
				$result['email']['sendgrid']['success'] = false;
				if ($report['email']['sendgrid'] === false) {
					$any_error = true;

					$this->sendCriticalReport("Email critical credits - SendGrid: {$res['data']}", null, false, "brevo");
					Log::channel('monitoring')->info("Email critical credits - SendGrid: {$res['data']}");
					if (!$this->is_admin) echo "*** REPORT SENT" . PHP_EOL . PHP_EOL;

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

		if (!$this->is_admin) echo "Email credits - Brevo" . PHP_EOL;

		if ($response === false) {
			$result['email']['brevo'] = [
				'success' => false,
				'data' => 'Could not get a response',
			];
		} else {
			if (!$this->is_admin) echo json_decode($response, true)['plan'][0]['credits'] . PHP_EOL . PHP_EOL;
			$res = json_decode($response, true);
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

			$result['email']['brevo'] = $res;

			if (!$res['success']) {
				if ($report['email']['brevo'] === false) {
					$any_error = true;

					$this->sendCriticalReport("Email ERROR - Brevo: {$res['data']}", null, false, "sendgrid");
					if (!$this->is_admin) echo "*** REPORT SENT" . PHP_EOL . PHP_EOL;

					Log::channel('monitoring')->info("*** REPORT SENT.");
					$report['email']['brevo'] = true;
				}
			} else if ($res['data'] < $email_limit) {
				$result['email']['brevo']['success'] = false;
				if ($report['email']['brevo'] === false) {
					$any_error = true;

					$this->sendCriticalReport("Email critical credits - Brevo: {$res['data']}", null, false, "sendgrid");
					Log::channel('monitoring')->info("Email critical credits - Brevo: {$res['data']}");
					if (!$this->is_admin) echo "*** REPORT SENT" . PHP_EOL . PHP_EOL;

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

	/**
	 * Execute the console command.
	 */
	public function handle() {
		$this->check();
	}
}
