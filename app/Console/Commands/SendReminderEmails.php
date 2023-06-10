<?php

namespace App\Console\Commands;

use App\Mail\AppointmentReminder;
use App\Models\Event;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendReminderEmails extends Command {
	/**
	 * The name and signature of the console command.
	 *
	 * @var string
	 */
	protected $signature = 'app:send-reminder-emails';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Send the user an email of their upcoming appointments';

	/**
	 * Log the job.
	 */
	private function log() {
	}

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
			"users.timezone",
			"users.firstname AS user_firstname",
			"users.lastname AS user_lastname",
			"users.email AS user_email",
			"users.phone_number AS user_phone_number",
			"countries.prefix AS user_phone_prefix",
			"patients.firstname AS patient_firstname",
			"patients.lastname AS patient_lastname",
			"patients.email AS patient_email",
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

		if (config('app.env') === 'local') echo var_export($events->toArray(), true);

		Log::channel('reminder')->info(sprintf("<JOB STARTED> +%s hours -> %s", $hours, $time->format("Y-m-d H:i:s")));

		$events->map(function ($event) {
			if ($event->patient_email) {
				Log::channel('reminder')->info("User: {$event->user_firstname}");
				Log::channel('reminder')->info("{$event['user_phone_prefix']} {$event['user_phone_number']}");
				Log::channel('reminder')->info($event->patient_email);
				Log::channel('reminder')->info("{$event->start} - {$event->end}");


				$event_array = $event->toArray();
				$event_array['user_phone'] = "{$event->user_phone_prefix} {$event->user_phone_number}";

				try {
					// Mail::to($event->patient_email)->send(new AppointmentReminder($event_array));
					Log::channel('reminder')->info(sprintf(
						"<Email sent> event: %s, email: %s",
						$event->id,
						$event->patient_email
					));

					Event::whereId($event->id)->update(['reminder' => 1]);
					Log::channel('reminder')->info("<Event updated> event: {$event->id}");
				} catch (\Throwable $th) {
					Log::channel('reminder')->info("!!! ERROR !!!");
					Log::channel('reminder')->info($th->__toString());
					Log::channel('reminder')->info(print_r($event_array, true));
				}
			}
		});

		Log::channel('reminder')->info(null);
	}
}
