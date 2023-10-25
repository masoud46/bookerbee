<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AppointmentEmail extends Mailable {
	use Queueable, SerializesModels;

	public $action;
	public $event;
	public $old_event;

	/**
	 * Create a new message instance.
	 */
	public function __construct(String $action, Array $event, Array $old_event = null) {
		$this->action = $action;
		$this->event = $event;
		$this->old_event = $old_event;
	}

	/**
	 * Get the message envelope.
	 */
	public function envelope(): Envelope {
		$subject = __("New appointment");

		if ($this->action === "update") {
			if ($this->old_event) {
				$subject = __("Your appointment has been rescheduled");
			} else {
				$subject = __("The location of you appointment has been changed");
			}
		} else if ($this->action === "delete") {
			$subject = __("Your appointment has been canceled");
		}

		return new Envelope(
			subject: $subject,
		);
	}

	/**
	 * Get the message content definition.
	 */
	public function content(): Content {
		$markdown = 'emails.appointment-added';

		if ($this->action === "update") {
			$markdown = 'emails.appointment-updated';
		} else if ($this->action === "delete") {
			$markdown = 'emails.appointment-canceled';
		}

		return new Content(
			markdown: $markdown,
		);
	}

	/**
	 * Get the attachments for the message.
	 *
	 * @return array<int, \Illuminate\Mail\Mailables\Attachment>
	 */
	public function attachments(): array {
		return [];
	}
}
