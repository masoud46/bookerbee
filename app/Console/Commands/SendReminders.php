<?php

namespace App\Console\Commands;

use App\Mail\AppointmentReminder;
use App\Models\Country;
use App\Models\Event;
use App\Models\Location;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Masoud46\LaravelApiMail\Facades\ApiMail;
use Masoud46\LaravelApiSms\Facades\ApiSms;
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;

class SendReminders extends Command {

	/**
	 * The default user_id to use for testing.
	 *
	 * @var int
	 */
	protected const DEFAULT_TEST_USER_ID = 2;

	/**
	 * The name and signature of the console command.
	 *
	 * @var string
	 */
	protected $signature = 'send:reminders {test_user_id?}';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Send reminder email/sms to patients about their upcoming appointments';

	protected $is_local;
	protected $is_production;
	protected $email_provider;
	protected $sms_provider;
	protected $countries;

	/**
	 * Send email.
	 * @param  Array $event
	 * @param  Array $payload
	 * @param Boolean $is_first
	 * @return Void
	 */
	protected function sendEmail($event, $payload, $is_first) {
		$n = $is_first ? 1 : 2;
		$nStr = $n === 1 ? 'FIRST' : 'SECOND';

		try {
			Log::channel('reminder')->info("[SENDING {$nStr} EMAIL ({$this->email_provider})]");

			$res = ApiMail::provider($this->email_provider)
				->send($payload);

			if ($res->success) {
				Log::channel('reminder')->info("[EMAIL SENT] {$payload['to']}");

				if (!$this->is_local) {
					try {
						Event::whereId($event['id'])->update(['reminder_email' => $n]);
					} catch (\Throwable $th) {
						Log::channel('reminder')->info("[!!! DB WARNING !!!]");
						Log::channel('reminder')->info($th->__toString());
						Log::channel('reminder')->info(print_r($event, true));
					}
				}

				Log::channel('reminder')->info("[EVENT REMINDER_EMAIL UPDATED TO {$n}] {$event['id']}");
			} else {
				Log::channel('reminder')->info("[!!! EMAIL ERROR !!!]");
				Log::channel('reminder')->info($res->message);
				Log::channel('reminder')->info(print_r($event, true));
			}
		} catch (\Exception $e) {
			Log::channel('reminder')->info("[!!! ERROR !!!]");
			Log::channel('reminder')->info($e->getMessage());
		}
	}

	/**
	 * Send SMS.
	 * @param  Array $event
	 * @param  Array $payload
	 * @return Void
	 */
	protected function sendSms($event, $payload) {
		try {
			Log::channel('reminder')->info("[SENDING SMS ({$this->sms_provider})] {$payload['to']}");

			$res = ApiSms::provider($this->sms_provider)
				->send($payload);

			if ($res->success) {
				Log::channel('reminder')->info("[SMS SENT]");

				if (!$this->is_local) {
					try {
						Event::whereId($event['id'])->update(['reminder_sms' => 1]);
					} catch (\Throwable $th) {
						Log::channel('reminder')->info("[!!! DB WARNING !!!]");
						Log::channel('reminder')->info($th->__toString());
						Log::channel('reminder')->info(print_r($event, true));
					}
				} else {
					Log::channel('reminder')->info(print_r($res, true));
				}

				if (!$this->is_production) {
					echo var_export($res, true) . PHP_EOL;
				}

				Log::channel('reminder')->info("[EVENT REMINDER_SMS UPDATED] {$event['id']}");
			} else {
				Log::channel('reminder')->info("[!!! SMS ERROR !!!]");
				Log::channel('reminder')->info(print_r($res->message, true));
			}
		} catch (\Exception $e) {
			Log::channel('reminder')->info("[!!! ERROR !!!]");
			Log::channel('reminder')->info($e->getMessage());
		}
	}

	/**
	 * Send the reminders.
	 * @param  Integer $test_user_id
	 * @return Void
	 */
	protected function send($test_user_id) {
		$this->is_local = config('app.env') === 'local';
		$this->is_production = config('app.env') === 'production';

		$email_hours = config('project.reminder_email_time');
		$email_time = Carbon::now()->addHours($email_hours);

		$sms_hours = config('project.reminder_sms_time');
		$sms_time = Carbon::now()->addHours($sms_hours);

		$events = Event::select([
			"events.id",
			"events.start",
			"events.end",
			"events.user_id",
			"events.patient_id",
			"events.location_id",
			"events.reminder_email",
			"events.reminder_sms",
			"users.timezone",
			"users.firstname AS user_firstname",
			"users.lastname AS user_lastname",
			"users.address_line1",
			"users.address_line2",
			"users.address_line3",
			"users.address_code",
			"users.address_city",
			"users.address_country_id",
			"users.address2_line1",
			"users.address2_line2",
			"users.address2_line3",
			"users.address2_code",
			"users.address2_city",
			"users.address2_country_id",
			"users.email AS user_email",
			"users.phone_number AS user_phone_number",
			"users.phone_country_id AS user_phone_country_id",
			"users.features AS user_features",
			"users.is_admin AS user_is_admin",
			"settings.duration",
			"settings.msg_email",
			"settings.msg_sms",
			"patients.firstname AS patient_firstname",
			"patients.lastname AS patient_lastname",
			"patients.locale AS patient_locale",
			"patients.email AS patient_email",
			"patients.phone_number AS patient_phone_number",
			"patients.phone_country_id AS patient_phone_country_id",
			"event_locations.name AS location_name",
			"event_locations.address AS location_address",
			"event_locations.code AS location_code",
			"event_locations.city AS location_city",
			"event_locations.country_id AS location_country_id",
		])
			->where("events.category", "=", 1) // event is an appointment
			->where("events.status", "=", 1) // has not been canceled
			->where("start", ">", Carbon::now())
			->where("start", "<=", $email_time)
			->where(function ($query) use ($test_user_id) {
				if (!$this->is_production) { // if not production, get test-user's events only
					$query->where("events.user_id", "=", $test_user_id); // test-user
				}
			})
			->where(function ($query) {
				$query
					->where(function ($sub_query) {
						$sub_query
							->where("patients.email", "<>", null)
							->where("events.reminder_email", "<", 2); // 0:none 1:email at email_time 2:email at sms_time
					})
					->orWhere(function ($sub_query) {
						$sub_query
							->where("patients.phone_number", "<>", null)
							->where("events.reminder_sms", "=", 0); // 0:none 1:sent
					});
			})
			->join("users", "users.id", "=", "events.user_id")
			->join("settings", "settings.id", "=", "events.user_id")
			->join("patients", "patients.id", "=", "events.patient_id")
			->leftJoin("event_locations", "event_locations.event_id", "=", "events.id")
			->get();

		if (!$this->is_production) {
			echo var_export($events->toArray(), true) . PHP_EOL;
			echo "events: {$events->count()}" . PHP_EOL;
			echo "test user: $test_user_id" . PHP_EOL;
		}

		if ($events->count()) {
			$this->countries = array_column(
				Country::all()->toArray(),
				null,
				'id'
			);

			$this->email_provider = $this->is_production
				? config('api-mail.default_provider')
				: config('api-mail.default_dev_provider');

			$this->sms_provider = $this->is_production
				? config('api-sms.default_provider')
				: config('api-sms.default_dev_provider');

			// Send reminders
			$events->map(function ($event) use ($sms_time) {
				LaravelLocalization::setLocale($event->patient_locale);

				$event_array = $event->toArray();

				$number = ltrim($event->user_phone_number, '0');
				$event_array['user_phone'] = "{$this->countries[$event->user_phone_country_id]['prefix']} {$number}";

				$start = Carbon::parse($event->start);
				$event_array['remaining_time'] = $start->diffInHours(Carbon::now()->floorUnit('hour'));

				$msg_email = $event->msg_email ? json_decode($event->msg_email, true) : [];
				$msg_sms = $event->msg_sms ? json_decode($event->msg_sms, true) : [];

				if ($event->location_name) {
					$event_array['address'] = [
						"line1" => $event->location_name,
						"line2" => $event->location_address,
						"code" => $event->location_code,
						"city" => $event->location_city,
						"country" => __($this->countries[$event->location_country_id]['name'], [], $event['patient_locale']),
					];
				} else {
					$location = Location::whereId($event->location_id)->first();

					switch ($location->code) {
						case '009':
							$event_array['address'] = [
								"line1" => $event->address_line1,
								"line2" => $event->address_line2,
								"line3" => $event->address_line3,
								"code" => $event->address_code,
								"city" => $event->address_city,
								"country" => __($this->countries[$event->address_country_id]['name'], [], $event['patient_locale']),
							];
							break;
						case '009b':
							$event_array['address'] = [
								"line1" => $event->address2_line1,
								"line2" => $event->address2_line2,
								"line3" => $event->address2_line3,
								"code" => $event->address2_code,
								"city" => $event->address2_city,
								"country" => __($this->countries[$event->address2_country_id]['name'], [], $event['patient_locale']),
							];
							break;
						case '003':
							$event_array['address'] = ['line1' => __('Your residence')];
							break;
					}
				}

				$event_array['msg_email'] = $msg_email[$event['patient_locale']] ?? array_shift($msg_email) ?? null;
				$event_array['msg_sms'] = $msg_sms[$event['patient_locale']] ?? array_shift($msg_sms) ?? null;

				$payload = [
					'to' => $event->patient_email,
					'subject' => __("Appointment reminder"),
					'body' => (new AppointmentReminder($event_array))->render(),
				];

				if (!$this->is_production) {
					echo var_export($event_array, true) . PHP_EOL;
				}

				if ($start->lessThanOrEqualTo($sms_time)) {
					Log::channel('reminder')->info("User: {$event->user_lastname}, {$event->user_firstname} ({$event->user_id})");
					Log::channel('reminder')->info("Patient: {$event->patient_lastname}, {$event->patient_firstname} ({$event->patient_id}) ({$event->patient_locale})");
					Log::channel('reminder')->info("Event: {$event->start} - {$event->end} ({$event->id})");

					$features = explode(",", $event->user_features);
					foreach ($features as $index => $feature) {
						$features[$index] = trim($feature);
					};

					if ($event->reminder_email < 2 && $event->patient_email) {
						if (!$this->is_local || config('project.send_emails')) {
							$this->sendEmail($event_array, $payload, false);
						}
					}

					if (
						$event->reminder_sms === 0 &&
						$event->patient_phone_number &&
						in_array("sms", $features)
					) {

						$user_name = ucfirst($event->user_firstname) . " " . strtoupper($event->user_lastname);
						$patient_name = ucfirst($event->patient_firstname);
						$country_code = $this->countries[$event->patient_phone_country_id]['code'];
						$number = ltrim($event->patient_phone_number, '0');
						$number = "{$this->countries[$event->patient_phone_country_id]['prefix']} {$number}";
						$to = preg_replace('/\s+/', '', $number);
						$message =
							__("Hello :name", ['name' => $patient_name]) . ",\n" .
							__("Your appointment with :name will start in about :time hours.", [
								'name' => $user_name,
								'time' => $event_array['remaining_time'],
							]);

						if ($event->patient_email) {
							$message .= " " . __("A detailed email has been sent to you.");
						}

						if ($event_array['msg_sms']) {
							$message .= "\n\n" . $event_array['msg_sms'];
						}

						$payload = [
							'country' => $country_code,
							'to' => $to,
							'message' => $message,
							'dryrun' => !$this->is_production && !config('project.send_sms'),
						];

						$this->sendSms($event_array, $payload);
					}

					Log::channel('reminder')->info("----------------------------------------------");
				} else if ($event->reminder_email === 0 && $event->patient_email) {
					Log::channel('reminder')->info("User: {$event->user_lastname}, {$event->user_firstname} ({$event->user_id})");
					Log::channel('reminder')->info("Patient: {$event->patient_lastname}, {$event->patient_firstname} ({$event->patient_id}) ({$event->patient_locale})");
					Log::channel('reminder')->info("Event: {$event->start} - {$event->end} ({$event->id})");

					if (!$this->is_local || config('project.send_emails')) {
						$this->sendEmail($event_array, $payload, true);
					}

					Log::channel('reminder')->info("----------------------------------------------");
				}
			});
		}
	}

	/**
	 * Execute the console command.
	 * @return Void
	 */
	public function handle() {
		$test_user_id = intval($this->argument('test_user_id'));
		$test_user_id = $test_user_id > 0 ? $test_user_id : self::DEFAULT_TEST_USER_ID;

		$this->send($test_user_id);
	}
}
