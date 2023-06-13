<?php

namespace App\Console\Commands;

use App\Mail\AppointmentReminder;
use App\Models\Event;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;

class SendReminderEmails extends Command {
	/**
	 * The name and signature of the console command.
	 *
	 * @var string
	 */
	protected $signature = 'emails:send-reminders';

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
		$hours = config('project.reminder_email_time');
		$time = Carbon::now()->addHours($hours);
		$events = Event::select([
			"events.id",
			"events.start",
			"events.end",
			"users.id AS user_id",
			"users.timezone",
			"users.firstname AS user_firstname",
			"users.lastname AS user_lastname",
			"users.email AS user_email",
			"users.phone_number AS user_phone_number",
			"countries.prefix AS user_phone_prefix",
			"patients.id AS patient_id",
			"patients.firstname AS patient_firstname",
			"patients.lastname AS patient_lastname",
			"patients.email AS patient_email",
			"patients.locale AS patient_locale",
		])
			->where("events.category", "=", 1) // event is an appointment
			->where("events.status", "=", 1) // has not been canceled
			->where("events.reminder", "=", 0) // reminder has not been sent
			->where("patients.email", "<>", null) // reminder has not been sent
			->where("start", "<=", $time)
			->join("users", "users.id", "=", "events.user_id")
			->join("patients", "patients.id", "=", "events.patient_id")
			->join("countries", "countries.id", "=", "users.phone_country_id")
			// ->limit(1)
			->get();

		if (config('app.env') === 'local') {
			echo var_export($events->toArray(), true) . PHP_EOL;
		}

		Log::channel('reminder')->info("<JOB STARTED> +{$hours} hours -> {$time->format("Y-m-d H:i:s")}");

		$events->map(function ($event) {
			if ($event->patient_email) {
				Log::channel('reminder')->info("User: {$event->user_lastname}, {$event->user_firstname} ({$event->user_id})");
				Log::channel('reminder')->info("Patient: {$event->patient_lastname}, {$event->patient_firstname} ({$event->patient_id}) ({$event->patient_locale})");
				Log::channel('reminder')->info("Event: {$event->start} - {$event->end} ({$event->id})");

				$event_array = $event->toArray();
				$event_array['user_phone'] = "{$event->user_phone_prefix} {$event->user_phone_number}";

				LaravelLocalization::setLocale($event['patient_locale']);

				try {
					if (config('app.env') === 'production') {
						Mail::to($event->patient_email)->send(new AppointmentReminder($event_array));
					} else if (config('project.send_emails')) {
						Mail::mailer('brevo')->to($event->patient_email)->send(new AppointmentReminder($event_array));
					}
					Log::channel('reminder')->info("<EMAIL SENT> {$event->patient_email}");

					Event::whereId($event->id)->update(['reminder' => 1]);
					Log::channel('reminder')->info("<EVENT UPDATED> {$event->id}");
				} catch (\Throwable $th) {
					Log::channel('reminder')->info("<!!! ERROR !!!>");
					Log::channel('reminder')->info($th->__toString());
					Log::channel('reminder')->info(print_r($event_array, true));
				}
			}
		});

		Log::channel('reminder')->info("----------------------------------------------");
	}
}
