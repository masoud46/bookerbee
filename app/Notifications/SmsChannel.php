<?php

namespace App\Notifications;

// use Illuminate\Notifications\Notification;
use App\Notifications\Notification;

class SmsChannel {
	/**
	 * Send the given notification.
	 */
	public function send(object $notifiable, ReminderSms $notification): void {
		$message = $notification->toSms($notifiable);

		// Send notification to the $notifiable instance...
		// $sent = $message
		// 	->line('Hello World!')
		// 	->line(':o)')
		// 	->send();

		// Or use dryRun() for testing to send it, without sending it for real.
		$sent = $message
			->line('Hello World!')
			->line(':o)')
			->dryRun()
			->send();
	}
}
