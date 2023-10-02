<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ChangeEmailAddress extends Mailable {
	use Queueable, SerializesModels;

	public $url;
	public $timeout;

	/**
	 * Create a new message instance.
	 */
	public function __construct(String $url, int $timeout) {
		$this->url = $url;
		$this->timeout = $timeout;
	}

	/**
	 * Get the message envelope.
	 */
	public function envelope(): Envelope {
		$subject = __("Change email address notification");

		return new Envelope(
			subject: $subject,
		);
	}

	/**
	 * Get the message content definition.
	 */
	public function content(): Content {
		$markdown = 'emails.change-email';

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
