<?php

namespace App\Console\Commands;

use App\Mail\AppointmentReminder;
use App\Models\Event;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;

class SendReminders extends Command {
	/**
	 * The name and signature of the console command.
	 *
	 * @var string
	 */
	protected $signature = 'send:reminders';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Send the user an email of their upcoming appointments';

	/**
	 * Execute the console command.
	 */
	public function handle() {
		$email_hours = config('project.reminder_email_time');
		$email_time = Carbon::now()->addHours($email_hours);

		$sms_hours = config('project.reminder_sms_time');
		$sms_time = Carbon::now()->addHours($sms_hours);

		Log::channel('reminder')->info("<JOB STARTED> +{$email_hours} hours -> {$email_time->format("Y-m-d H:i:s")}");

		$events = Event::select([
			"events.id",
			"events.start",
			"events.end",
			"events.user_id",
			"events.patient_id",
			"events.reminder_email",
			"events.reminder_sms",
			"users.timezone",
			"users.firstname AS user_firstname",
			"users.lastname AS user_lastname",
			"users.email AS user_email",
			"users.phone_number AS user_phone_number",
			"users.features AS user_features",
			"countries.prefix AS user_phone_prefix",
			"patients.firstname AS patient_firstname",
			"patients.lastname AS patient_lastname",
			"patients.email AS patient_email",
			"patients.phone_number AS patient_phone_number",
			"c.prefix AS patient_phone_prefix",
		])
			->where("events.category", "=", 1) // event is an appointment
			->where("events.status", "=", 1) // has not been canceled
			->where("start", ">", Carbon::now())
			->where("start", "<=", $email_time)
			->where(function ($query) {
				$query
					->where(function ($q) {
						$q->where("patients.email", "<>", null)
							->where("events.reminder_email", "<", 2); // 0:none 1:email at email_time 2:email at sms_time
					})
					->orWhere(function ($q) {
						$q->where("patients.phone_number", "<>", null)
							->where("events.reminder_sms", "=", 0); // 0:none 1:sent
					});
			})
			->join("users", "users.id", "=", "events.user_id")
			->join("patients", "patients.id", "=", "events.patient_id")
			->join("countries", "countries.id", "=", "users.phone_country_id")
			->leftJoin("countries AS c", "c.id", "=", "patients.phone_country_id")
			->get();

		if (config('app.env') === 'local') {
			echo var_export($events->toArray(), true) . PHP_EOL;
		}

		// send reminders
		$events->map(function ($event) use ($sms_time) {
			Log::channel('reminder')->info("User: {$event->user_lastname}, {$event->user_firstname} ({$event->user_id})");
			Log::channel('reminder')->info("Patient: {$event->patient_lastname}, {$event->patient_firstname} ({$event->patient_id}) ({$event->patient_locale})");
			Log::channel('reminder')->info("Event: {$event->start} - {$event->end} ({$event->id})");

			foreach ($features = explode(",", $event->user_features) as $index => $feature) {
				$features[$index] = trim($feature);
			};

			$event_array = $event->toArray();

			$number = ltrim($event->user_phone_number, '0');
			$event_array['user_phone'] = "{$event->user_phone_prefix} {$number}";

			$start = Carbon::parse($event->start);
			$event_array['remaining_time'] = $start->diffInHours(Carbon::now()->roundHour());

			LaravelLocalization::setLocale($event['patient_locale']);

			if ($start->lessThanOrEqualTo($sms_time)) {

				if ($event->patient_email && $event->reminder_email < 2) {
					$mail_provider = config('app.env') === 'production'
						? config('project.mail.default_provider')
						: config('project.mail.default_dev_provider');

					if (config('app.env') === 'production' || config('project.send_emails')) {
						try {
							Log::channel('reminder')->info("<SENDING SECOND EMAIL>");

							Mail::mailer($mail_provider)
								->to($event->patient_email)
								->send(new AppointmentReminder($event_array));

							Log::channel('reminder')->info("<EMAIL SENT> {$event->patient_email}");

							if (config('app.env') === 'production') {
								Event::whereId($event->id)->update(['reminder_email' => 2]);
							}

							Log::channel('reminder')->info("<EVENT REMINDER_EMAIL UPDATED TO 2> {$event->id}");
						} catch (\Throwable $th) {
							Log::channel('reminder')->info("<!!! ERROR !!!>");
							Log::channel('reminder')->info($th->__toString());
							Log::channel('reminder')->info(print_r($event_array, true));
						}
					}
				}

				if (
					$event->patient_phone_number &&
					in_array("sms", $features) &&
					$event->reminder_sms === 0
				) {

					$result = ['success' => true];
					$user_name = ucfirst($event->user_firstname) . " " . strtoupper($event->user_lastname);
					$patient_name = ucfirst($event->patient_firstname);
					$number = ltrim($event->patient_phone_number, '0');
					$number = "{$event->patient_phone_prefix} {$number}";
					$message = __("Your appointment with :name will start in about :time hours.", [
						'name' => $user_name,
						'time' => $event_array['remaining_time'],
					]);

					if ($event->patient_email) {
						$message .= " " . __("A detailed email has been sent to you.");
					}

					$sms = new \App\Notifications\SmsMessage(['xxxprovider' => "smsto"]);
					$sms = $sms->to(preg_replace('/\s+/', '', $number))
						->line(__("Hello :name", ['name' => $patient_name]) . ",")
						->line($message);

					try {
						Log::channel('reminder')->info("<SENDING SMS> {$number}");

						if (config('app.env') === 'production') {
							$result = $sms->send();
						} else if (config('project.send_sms')) {
							// $result = $sms->send();
							$result = $sms->dryRun()->send();
						}

						if ($result['success']) {
							Log::channel('reminder')->info("<SMS SENT>");

							if (config('app.env') === 'production') {
								Event::whereId($event->id)->update(['reminder_sms' => 1]);
							} else {
								$result['number'] = $number;
								Log::channel('reminder')->info(print_r($result, true));
							}

							Log::channel('reminder')->info("<EVENT REMINDER_SMS UPDATED> {$event->id}");
						} else {
							Log::channel('reminder')->info("<!!! SMS ERROR !!!>");
							Log::channel('reminder')->info(print_r($result, true));
						}
					} catch (\Throwable $th) {
						Log::channel('reminder')->info("<!!! ERROR !!!>");
						Log::channel('reminder')->info($th->__toString());
						Log::channel('reminder')->info(print_r($event->toArray(), true));
					}
				}
			} else if ($event->reminder_email === 0 && $event->patient_email) {
				$mail_provider = config('app.env') === 'production'
					? config('project.mail.default_provider')
					: config('project.mail.default_dev_provider');

				if (config('app.env') === 'production' || config('project.send_emails')) {
					try {
						Log::channel('reminder')->info("<SENDING FIRST EMAIL>");

						Mail::mailer($mail_provider)
							->to($event->patient_email)
							->send(new AppointmentReminder($event_array));

						Log::channel('reminder')->info("<EMAIL SENT> {$event->patient_email}");

						if (config('app.env') === 'production') {
							Event::whereId($event->id)->update(['reminder_email' => 1]);
						}

						Log::channel('reminder')->info("<EVENT REMINDER_EMAIL UPDATED TO 1> {$event->id}");
					} catch (\Throwable $th) {
						Log::channel('reminder')->info("<!!! ERROR !!!>");
						Log::channel('reminder')->info($th->__toString());
						Log::channel('reminder')->info(print_r($event_array, true));
					}
				}
			}

			Log::channel('reminder')->info("----------------------------------------------");
		});

		Log::channel('reminder')->info("==============================================");
	}
}
