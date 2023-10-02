<?php

namespace App\Console\Commands;

use App\Mail\AppointmentReminder;
use App\Models\Event;
use App\Models\Location;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
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

	/**
	 * Send the reminders.
	 */
	protected function send($test_user_id) {
		$is_local = config('app.env') === 'local';
		$is_production = config('app.env') === 'production';

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
			"users.address2_line1",
			"users.address2_line2",
			"users.address2_line3",
			"users.address2_code",
			"users.address2_city",
			"users.email AS user_email",
			"users.phone_number AS user_phone_number",
			"users.features AS user_features",
			"users.is_admin AS user_is_admin",
			"settings.duration",
			"settings.msg_email",
			"settings.msg_sms",
			"countries.name AS user_address_country",
			"countries2.name AS user_address2_country",
			"phone_countries.prefix AS user_phone_prefix",
			"patients.firstname AS patient_firstname",
			"patients.lastname AS patient_lastname",
			"patients.locale AS patient_locale",
			"patients.email AS patient_email",
			"patients.phone_number AS patient_phone_number",
			"patients.phone_country_id AS patient_phone_country_id",
			"c.code AS patient_phone_country_code",
			"c.prefix AS patient_phone_prefix",
		])
			->where("events.category", "=", 1) // event is an appointment
			->where("events.status", "=", 1) // has not been canceled
			->where("start", ">", Carbon::now())
			->where("start", "<=", $email_time)
			->where(function ($query) use($is_production, $test_user_id) {
				if (!$is_production) { // if not production, get test-user's events only
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
			->join("countries", "countries.id", "=", "users.address_country_id")
			->join("countries AS phone_countries", "phone_countries.id", "=", "users.phone_country_id")
			->leftJoin("countries AS countries2", "countries2.id", "=", "users.address2_country_id")
			->leftJoin("countries AS c", "c.id", "=", "patients.phone_country_id")
			->get();

		if (!$is_production) {
			echo var_export($events->toArray(), true) . PHP_EOL;
			echo "events: {$events->count()}" . PHP_EOL;
			echo "test user: $test_user_id" . PHP_EOL;
		}

		$mail_provider = $is_production
			? config('project.mail.default_provider')
			: config('project.mail.default_dev_provider');

		// send reminders
		$events->map(function ($event) use ($is_local, $sms_time, $mail_provider) {
			$event_array = $event->toArray();

			$number = ltrim($event->user_phone_number, '0');
			$event_array['user_phone'] = "{$event->user_phone_prefix} {$number}";

			$start = Carbon::parse($event->start);
			$event_array['remaining_time'] = $start->diffInHours(Carbon::now()->floorUnit('hour'));

			$location = Location::whereId($event->location_id)->first();
			$msg_email = $event->msg_email ? json_decode($event->msg_email, true) : [];
			$msg_sms = $event->msg_sms ? json_decode($event->msg_sms, true) : [];

			$event_array['address'] = $location->code === "009b" ? [
				"line1" => $event->address2_line1,
				"line2" => $event->address2_line2,
				"line3" => $event->address2_line3,
				"code" => $event->address2_code,
				"city" => $event->address2_city,
				"country" => __($event->user_address2_country, [], $event['patient_locale']),
			] : [
				"line1" => $event->address_line1,
				"line2" => $event->address_line2,
				"line3" => $event->address_line3,
				"code" => $event->address_code,
				"city" => $event->address_city,
				"country" => __($event->user_address_country, [], $event['patient_locale']),
			];

			$event_array['msg_email'] = $msg_email[$event['patient_locale']] ?? array_shift($msg_email) ?? null;
			$event_array['msg_sms'] = $msg_sms[$event['patient_locale']] ?? array_shift($msg_sms) ?? null;
			// echo var_export($event_array, true) . PHP_EOL;

			if ($start->lessThanOrEqualTo($sms_time)) {
				Log::channel('reminder')->info("User: {$event->user_lastname}, {$event->user_firstname} ({$event->user_id})");
				Log::channel('reminder')->info("Patient: {$event->patient_lastname}, {$event->patient_firstname} ({$event->patient_id}) ({$event->patient_locale})");
				Log::channel('reminder')->info("Event: {$event->start} - {$event->end} ({$event->id})");

				LaravelLocalization::setLocale($event['patient_locale']);

				$features = explode(",", $event->user_features);
				foreach ($features as $index => $feature) {
					$features[$index] = trim($feature);
				};

				if ($event->reminder_email < 2 && $event->patient_email) {
					if (!$is_local || config('project.send_emails')) {
						try {
							Log::channel('reminder')->info("[SENDING SECOND EMAIL]");

							Mail::mailer($mail_provider)
								->to($event->patient_email)
								->send(new AppointmentReminder($event_array));

							Log::channel('reminder')->info("[EMAIL SENT] {$event->patient_email}");

							if (!$is_local) {
								Event::whereId($event->id)->update(['reminder_email' => 2]);
							}

							Log::channel('reminder')->info("[EVENT REMINDER_EMAIL UPDATED TO 2] {$event->id}");
						} catch (\Throwable $th) {
							Log::channel('reminder')->info("[!!! ERROR !!!]");
							Log::channel('reminder')->info($th->__toString());
							Log::channel('reminder')->info(print_r($event_array, true));
						}
					}
				}

				if (
					$event->reminder_sms === 0 &&
					$event->patient_phone_number &&
					in_array("sms", $features)
				) {

					$result = ['success' => true];
					$user_name = ucfirst($event->user_firstname) . " " . strtoupper($event->user_lastname);
					$patient_name = ucfirst($event->patient_firstname);
					$number = ltrim($event->patient_phone_number, '0');
					$number = "{$event->patient_phone_prefix} {$number}";
					$country_id = $event->patient_phone_country_id;
					$country_code = $event->patient_phone_country_code;
					$message = __("Your appointment with :name will start in about :time hours.", [
						'name' => $user_name,
						'time' => $event_array['remaining_time'],
					]);

					if ($event->patient_email) {
						$message .= " " . __("A detailed email has been sent to you.");
					}

					$sms = new \App\Notifications\SmsMessage([
						'event_id' => $event_array['id'],
						'action' => "remind",
						'country' => $country_code,
					]);
					$sms = $sms
						->to(preg_replace('/\s+/', '', $number))
						->line(__("Hello :name", ['name' => $patient_name]) . ",")
						->line($message)
						->line(__("Address:") . " " . makeOneLineAddress($event_array['address']));

					if ($event_array['msg_sms']) {
						$sms = $sms->line()->line($event_array['msg_sms']);
					}

					try {
						Log::channel('reminder')->info("[SENDING SMS] {$number}");

						if ($is_production || config('project.send_sms')) {
							$result = $sms->send();
						} else {
							$result = $sms->dryRun()->send();
						}

						if ($result['success']) {
							Log::channel('reminder')->info("[SMS SENT]");

							if (!$is_local) {
								Event::whereId($event->id)->update(['reminder_sms' => 1]);
							} else {
								Log::channel('reminder')->info(print_r($result['data'], true));
							}

							Log::channel('reminder')->info("[EVENT REMINDER_SMS UPDATED] {$event->id}");
						} else {
							Log::channel('reminder')->info("[!!! SMS ERROR !!!]");
							Log::channel('reminder')->info(print_r($result, true));
						}
					} catch (\Throwable $th) {
						Log::channel('reminder')->info("[!!! ERROR !!!]");
						Log::channel('reminder')->info($th->__toString());
						Log::channel('reminder')->info(print_r($event->toArray(), true));
					}
				}

				Log::channel('reminder')->info("----------------------------------------------");
			} else if ($event->reminder_email === 0 && $event->patient_email) {
				Log::channel('reminder')->info("User: {$event->user_lastname}, {$event->user_firstname} ({$event->user_id})");
				Log::channel('reminder')->info("Patient: {$event->patient_lastname}, {$event->patient_firstname} ({$event->patient_id}) ({$event->patient_locale})");
				Log::channel('reminder')->info("Event: {$event->start} - {$event->end} ({$event->id})");

				LaravelLocalization::setLocale($event['patient_locale']);

				if (!$is_local || config('project.send_emails')) {
					try {
						Log::channel('reminder')->info("[SENDING FIRST EMAIL]");

						Mail::mailer($mail_provider)
							->to($event->patient_email)
							->send(new AppointmentReminder($event_array));

						Log::channel('reminder')->info("[EMAIL SENT] {$event->patient_email}");

						if (!$is_local) {
							Event::whereId($event->id)->update(['reminder_email' => 1]);
						}

						Log::channel('reminder')->info("[EVENT REMINDER_EMAIL UPDATED TO 1] {$event->id}");
					} catch (\Throwable $th) {
						Log::channel('reminder')->info("[!!! ERROR !!!]");
						Log::channel('reminder')->info($th->__toString());
						Log::channel('reminder')->info(print_r($event_array, true));
					}
				}

				Log::channel('reminder')->info("----------------------------------------------");
			}
		});
	}

	/**
	 * Execute the console command.
	 */
	public function handle() {
		$test_user_id = intval($this->argument('test_user_id'));
		$test_user_id = $test_user_id > 0 ? $test_user_id : self::DEFAULT_TEST_USER_ID;

		$this->send($test_user_id);
	}
}
