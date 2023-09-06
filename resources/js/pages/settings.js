import { utils } from '../utils/utils'


const settingsForm = document.getElementById('settings-form')
const settingsSaved = document.getElementById('settings-saved')
const msgEmailCheck = document.getElementById('settings-msg_email_checked')
const msgEmail = document.getElementById('settings-msg_email')
const msgSmsCheck = document.getElementById('settings-msg_sms_checked')
const msgSms = document.getElementById('settings-msg_sms')

// set settings saved state
function setSettingsSaved(value) {
	settingsSaved.value = value ? 'true' : 'false'
	settingsSaved.dispatchEvent(new Event('change'))
}

settingsForm.addEventListener('submit', e => {
	const breakVal = document.getElementById('settings-cal_break').value
	const slotVal = document.getElementById('settings-cal_slot').value

	if (breakVal * 2 > slotVal) {
			e.preventDefault();
			utils.showMessage(window.laravel.messages.errorBrealSlot)

			return
	}

	document.querySelector('body').classList.add('busy')
})

// when any of the settings elements changes, set saved state to false
settingsForm.querySelectorAll(utils.editableElements).forEach(element => {
	element.addEventListener('input', () => {
		setSettingsSaved(false)
	})
})

// show/hide the email personal message
msgEmailCheck.addEventListener('change', e => {
	if (msgEmailCheck.checked) {
		msgEmail.classList.add('messages-visible')
	} else {
		msgEmail.classList.remove('messages-visible')
	}
	setSettingsSaved(false)
})

// show/hide the sms personal message
msgSmsCheck.addEventListener('change', e => {
	if (msgSmsCheck.checked) {
		msgSms.classList.add('messages-visible')
	} else {
		msgSms.classList.remove('messages-visible')
	}
	setSettingsSaved(false)
})

settingsSaved.addEventListener('change', () => {
	const message = document.getElementById('settings-not-saved-message')

	window.laravel.modified = settingsSaved.value.toLowerCase() !== "true";

	if (settingsSaved.value.toLowerCase() === 'true') {
		message.classList.add('invisible')
	} else {
		message.classList.remove('invisible')
	}
})

