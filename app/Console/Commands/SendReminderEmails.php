<?php

namespace App\Console\Commands;

use App\Mail\AppointmentReminder;
use App\Models\Event;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use PhpParser\Node\Stmt\TryCatch;

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
	 * Execute the console command.
	 */
	public function handle() {
		// test
		// $time = Carbon::now();
		// file_put_contents(__DIR__ . "/reminder.txt", "reminder - {$time->toString()}\n", FILE_APPEND);

		$now = Carbon::now();
		$time = Carbon::now()->addHours(config('project.reminder_email_time'));
		$events = Event::select([
			"events.start",
			"events.end",
			"users.timezone",
			"users.firstname AS user_firstname",
			"users.lastname AS user_lastname",
			"patients.firstname AS patient_firstname",
			"patients.lastname AS patient_lastname",
			"patients.email AS patient_email",
		])
			->where("events.category", "=", 1)
			->where("events.reminder", "=", 0)
			->where("start", "<=", $time)
			->join("users", "users.id", "=", "events.user_id")
			->join("patients", "patients.id", "=", "events.patient_id")
			// ->limit(1)
			->get();

		echo var_export($events->toArray(), true);

		if ($events->count()) {
			file_put_contents(__DIR__ . "/reminder.txt", "{$now->toISOString()} - {$time->toISOString()}\n", FILE_APPEND);
			foreach ($events as $event) {
				if ($event->patient_email) {
					file_put_contents(
						__DIR__ . "/reminder.txt",
						"{$event->user_firstname}\n{$event->patient_email}\n{$event->start} - {$event->end}\n",
						FILE_APPEND
					);

					try {
						// Mail::to($event->patient_email)->send(new AppointmentReminder($event->toArray()));
						// $event->reminder = 1;
						// $event->save();
					} catch (\Throwable $th) {
					}
				}
			}

			file_put_contents(
				__DIR__ . "/reminder.txt",
				"\n",
				FILE_APPEND
			);
		}

		// $events = Event::whereUserId(Auth::user()->id);
		// $users->map(function ($user) {
		// 	Mail::to($user->email)
		// 		->send(new AppointmentReminder($event));
		// 	// $user->notify(new AppointmentReminder($event));
		// });
	}
}
