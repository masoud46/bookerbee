<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class Event extends Model {
	use HasFactory;

	/**
	 * Set the reminders according to start time.
	 *
	 * @param  Array $patient
	 */
	public function setReminders(array $patient) {
		$email_hours = config('project.reminder_email_time');
		$email_time = Carbon::now()->addHours($email_hours);

		$sms_hours = config('project.reminder_sms_time');
		$sms_time = Carbon::now()->addHours($sms_hours);

		$this->reminder_email = isset($patient['email']) &&
			$patient['email'] !== null ? 0 : 2;

		$this->reminder_sms = isset($patient['phone']) &&
			$patient['phone'] !== null &&
			in_array("sms", Auth::user()->features) ? 0 : 1;

		if (Carbon::parse($this->start)->lessThanOrEqualTo($sms_time)) {
			$this->reminder_email = 2;
			$this->reminder_sms = 1;
		} else if (
			Carbon::parse($this->start)->lessThanOrEqualTo($email_time) &&
			isset($patient['email']) &&
			$patient['email'] !== null
		) {
			$this->reminder_email = 1;
		}
	}
}
