<?php

namespace App\Http\Controllers;

use App\Console\Commands\Monitoring;
use App\Models\Event;
use App\Models\User;
use Carbon\Carbon;
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
		$monitoring = (new Monitoring(true))->handle();

		return response()->json($monitoring);
	}

	/**
	 * Get the SMS cost between two dates
	 *
	 * @param  Integer $user_id
	 * @param  String $start
	 * @param  String $end
	 * @return Array
	 */
	public function getSMSCost($user_id, $start, $end) {
		$result = ['success' =>  false];

		$user = User::select([
			'users.id',
			'users.firstname',
			'users.lastname',
			'users.timezone',
		])->whereId($user_id)->first();

		$start_date = (new Carbon($start, $user->timezone))
			->startOfDay()
			->timezone('UTC')
			->toDateTimeString();
		$end_date = (new Carbon($end, $user->timezone))
			->endOfDay()
			->timezone('UTC')
			->toDateTimeString();

		$events = Event::select([
			"events.id",
			"events.status",
			"event_sms.action",
			"event_sms.provider",
			"event_sms.currency",
			"event_sms.parts",
			"event_sms.cost",
			"event_sms.created_at",
			"countries.name AS country",
		])
			->join("event_sms", "event_sms.event_id", "=", "events.id")
			->join("countries", "countries.code", "=", "event_sms.country")
			->where("events.user_id", "=", $user_id)
			->whereBetween("event_sms.created_at", [$start_date, $end_date])
			// ->whereDate("event_sms.created_at", '>=', $start_date)
			// ->whereDate("event_sms.created_at", '<=', $end_date)
			->orderBy('event_sms.created_at')
			->get();

		$add = 0;
		$delete = 0;
		$update = 0;
		$remind = 0;
		$total = 0;

		foreach ($events as $event) {
			$total += $event->cost;
			$event->cost /= config('project.sms_price_multiplier');

			switch ($event->action) {
				case 'add':
					$add++;
					break;
				case 'update':
					$update++;
					break;
				case 'delete':
					$delete++;
					break;
				case 'remind':
					$remind++;
					break;
			}
		}

		$count = $events->count();

		$cost = $total * 100 / config('project.sms_price_multiplier');
		$cost = ceil($cost);
		$cost = number_format($cost / 100, 2);

		$first = $events->first();
		$last = $events->last();
		$first_event = null;
		$last_event = null;

		if ($first) {
			$first_event = $first->toArray();
			$first = Carbon::parse($first->created_at)->format('Y-m-d H:i:s');
		}
		if ($last) {
			$last_event = $last->toArray();
			$last = Carbon::parse($last->created_at)->format('Y-m-d H:i:s');
		}
		$user = $user->toArray();
		$events = $events->toArray();
		$result['data'] = compact(
			'start_date',
			'end_date',
			'add',
			'update',
			'delete',
			'remind',
			'count',
			'total',
			'cost',
			'first',
			'last',
			'first_event',
			'last_event',
			'user',
			'events',
		);
		$result['success'] = true;

		// dd($user->toArray(), $add, $update, $delete, $remind, $total, $first, $last, $events->toArray());
		dd($result['data']);

		return $result;
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
