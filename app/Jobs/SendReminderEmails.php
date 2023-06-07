<?php

use Carbon\Carbon;

class SendReminderEmails {


	/**
	 * Create a new message instance.
	 */
	public function __construct() {
		$time = Carbon::now();
		
		file_put_contents("reminder.txt", "reminder - {$time->toString()}", FILE_APPEND);
	}

}