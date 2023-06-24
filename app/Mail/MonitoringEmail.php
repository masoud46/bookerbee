<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class MonitoringEmail extends Mailable {
	use Queueable, SerializesModels;

	public $message;
	public $link;

	/**
	 * Create a new message instance.
	 */
	public function __construct(String $message, String $link = null) {
		$this->message = $message;
		$this->link = $link;
	}

	/**
	 * Get the message envelope.
	 */
	public function envelope(): Envelope {
		$subject = __("Monitoring Warning");

		return new Envelope(
			subject: $subject,
		);
	}

	/**
	 * Get the message content definition.
	 */
	public function content(): Content {
		$markdown = 'emails.monitoring';

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
