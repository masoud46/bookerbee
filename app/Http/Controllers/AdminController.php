<?php

namespace App\Http\Controllers;

use App\Console\Commands\Monitoring;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Routing\Controller;

class AdminController extends Controller {
	/**
	 * Create a new controller instance.
	 *
	 * @return void
	 */
	public function __construct() {
		$this->middleware('auth')->except(['ping']);
	}

	/**
	 * Answer to ping.
	 *
	 * @return String $pong
	 */
	public function ping() {
		$pong = "pong";
		return $pong;
	}

	/**
	 * Show admin dashboard.
	 *
	 * @return \Illuminate\Contracts\Support\Renderable
	 */
	public function index() {
		$entries = 'resources/js/pages/admin.js';

		return view('admin', compact('entries'));
	}

	/**
	 * Get log file content.
	 *
	 * @return \Illuminate\Http\Response
	 */
	public function log($log) {
		$path = storage_path() . "/logs/{$log}.log";
		$result = [
			'success' => false,
			'data' => 'file not found.',
		];

		if (file_exists($path)) {
			$data = file_get_contents($path);
			$result['data'] = strlen($data) ? $data : "[empty]";
			$result['success'] = true;
		}

		return response()->json($result);
	}

	/**
	 * Truncate log file.
	 *
	 * @return \Illuminate\Http\Response
	 */
	public function truncateLog($log) {
		$path = storage_path() . "/logs/{$log}.log";
		$result = [
			'success' => false,
			'data' => 'file not found.',
		];

		if (file_exists($path)) {
			$file = fopen($path, "w");
			fwrite($file, '');
			fclose($file);
			$result['success'] = true;
			$result['data'] = "[truncated]";
		}

		return response()->json($result);
	}

	/**
	 * Get monitoring report.
	 *
	 * @return \Illuminate\Http\Response
	 */
	public function monitoring() {
		$monitoring = (new Monitoring(true))->check();

		return response()->json($monitoring);
	}

	/**
	 * Order sms credit and pay for it.
	 *
	 * @param  Int  $credits
	 * @return \Illuminate\Http\Response
	 */
	public function buySMSCredits($credits = 100) {
		$result = [
			'success' => false,
			'data' => null,
			'production' => config('app.env') === 'production',
		];

		/* attention: /order/sms API and /me/order API are both needed */
		$ovh = new \Ovh\Api(
			config('project.ovh.application_key'),
			config('project.ovh.application_secret'),
			config('project.ovh.endpoint'),
			config('project.ovh.consumer_key'),
		);

		$service = config('project.sms.ovh.service');

		try {
			if ($result['production']) {
				$response = $ovh->get("/me/payment/method", array(
					'default' => true, // Filter on 'default' property (type: boolean)
				));

				if (count($response)) {
					$payment_method = $response[0];

					$response = $ovh->post("/order/sms/{$service}/credits", [
						'quantity' => $credits, // Min 100
					]);
					$order_id = $response['orderId'];
					sleep(5);

					$response = $ovh->post("/me/order/{$order_id}/pay", array(
						'paymentMethod' => ['id' => $payment_method],
					));
				}
			} else {
				$response = $ovh->get("/me/payment/method", array(
					'default' => true, // Filter on 'default' property (type: boolean)
				));

				if (count($response)) {
					$payment_method = $response[0];

					$response = $ovh->get("/me/payment/method/{$payment_method}");
					unset($response['icon']);
				}
			}

			$result['data'] = $response;
			$result['payment_method'] = $payment_method ?? null;
			$result['service'] = $service;
			$result['credits'] = $credits;
			$result['success'] = true;
		} catch (ClientException $e) {
			$response = $e->getResponse();
			$result['data'] = json_decode($response->getBody()->getContents(), true);
		} catch (\Exception $e) {
			$result['data'] = $e->getMessage();
		}

		return response()->json($result);
	}
}
