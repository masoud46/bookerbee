<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReminderSms extends Notification {
	use Queueable;

	/**
	 * Create a new notification instance.
	 */
	public function __construct() {
		//
	}

	/**
	 * Get the notification's delivery channels.
	 *
	 * @return array<int, string>
	 */
	public function via(object $notifiable): array {
		// return ['mail'];
		return [SmsChannel::class];
	}

	/**
	 * Get the mail representation of the notification.
	 */
	// public function toMail(object $notifiable): MailMessage
	// {
	//     return (new MailMessage)
	//                 ->line('The introduction to the notification.')
	//                 ->action('Notification Action', url('/'))
	//                 ->line('Thank you for using our application!');
	// }

	/**
	 * Get the mail representation of the notification.
	 */
	public function toSms(object $notifiable, array $params = []): SmsMessage {
		// We are assuming we are notifying a user or a model that has a phone attribute/field. 
		// And the phone number is correctly formatted.
		return (new SmsMessage($params))
			// ->line("These aren't the droids you are looking for.")
			// ->from('ObiWan')
			->to($notifiable->phone);
	}

	/**
	 * Get the array representation of the notification.
	 *
	 * @return array<string, mixed>
	 */
	public function toArray(object $notifiable): array {
		return [
			//
		];
	}
}
