import { utils } from '../utils/utils'
import { Phones } from '../components/phoneNumber'
import { password } from '../shared/confirmPasswordModal'

import '../../scss/pages/user-profile.scss'
import '../../scss/components/phone-number.scss'


Phones.forEach(phone => {
	if (phone.id !== 'phone-number-component') {
		phone.number.onChange = () => {
			setProfileSaved(false)
		}
	}
})


password.get = password => {
	console.log(password);
	document.querySelector('body').classList.add('busy')
	profileForm.querySelector('input[type="hidden"][name="verify_password"]').value = password
	profileForm.submit()
}


function resetInvalidFields(parent) {
	parent.querySelectorAll('.is-invalid').forEach(invalid => {
		invalid.classList.remove('is-invalid')
	})
	parent.querySelectorAll('.invalid-feedback').forEach(feedback => {
		feedback.textContent = ''
	})

}

const profileForm = document.getElementById('profile-form')
const profileSaved = document.getElementById('profile-saved')

const updateEmailModal = document.getElementById('edit-email-modal')
const updateEmailInput = document.getElementById('new_email')
const updateEmailBtn = document.querySelector('#edit-email-modal .btn-update-email')

const updatePhoneModal = document.getElementById('edit-phone-modal')
const updatePhoneInput = document.querySelector('[name="phone_number"]')
const updatePhoneBtn = document.querySelector('#edit-phone-modal .btn-update-phone')
const verifyCodeInput = document.getElementById('verify-phone-code')
const verifyCodeBtn = document.querySelector('#edit-phone-modal .btn-verify-code')

// set profile saved state
function setProfileSaved(value) {
	profileSaved.value = value ? 'true' : 'false'
	profileSaved.dispatchEvent(new Event('change'))
}

// send email change request
async function changeEmail() {
	document.body.classList.add('sending-email')
	resetInvalidFields(updateEmailModal)

	const result = await utils.fetch({
		url: document.getElementById('edit-email-url').value,
		data: { email: updateEmailInput.value },
	})

	document.body.classList.remove('sending-email')

	if (result.success) {
		updateEmailModal.querySelector('.btn-close').click()
		utils.showAlert({ message: result.data, timeout: 0 })
	} else {
		updateEmailInput.classList.add('is-invalid')
		updateEmailInput.focus()
		updateEmailModal.querySelector('.invalid-feedback').textContent = result.error
	}
}

// send phone number change request
async function changePhone() {
	document.body.classList.add('sending-email')
	resetInvalidFields(updatePhoneModal)

	const result = await utils.fetch({
		url: document.getElementById('edit-phone-url').value,
		data: {
			phone_country_id: updatePhoneModal.querySelector('[name="phone_country_id"]').value,
			phone_number: updatePhoneInput.value,
		},
	})

	document.body.classList.remove('sending-email')

	if (result.success) {
		updatePhoneModal.classList.add('verify-code')
		verifyCodeInput.value = result.code
		verifyCodeInput.focus()
	} else {
		updatePhoneInput.classList.add('is-invalid')
		updatePhoneInput.focus()
		updatePhoneModal.querySelector('.invalid-feedback').textContent = result.error
	}
}

// send phone number change request
async function updatePhone() {
	document.body.classList.add('sending-email')
	resetInvalidFields(updatePhoneModal)

	const data = {
		phone_country_id: updatePhoneModal.querySelector('[name="phone_country_id"]').value,
		phone_number: updatePhoneInput.value,
		token: verifyCodeInput.value,
	}

	const result = await utils.fetch({
		method: 'PUT',
		url: document.getElementById('update-phone-url').value,
		data,
	})

	document.body.classList.remove('sending-email')

	if (result.success) {
		updatePhoneModal.querySelector('.btn-close').click()
		document.getElementById('user-phone').textContent = result.data
		utils.showAlert({ message: result.message })
	} else {
		utils.showAlert({ message: result.error, type: 'error' })
	}
}

// when any of the profile elements changes, set saved state to false
profileForm.querySelectorAll(utils.editableElements).forEach(element => {
	element.addEventListener('input', () => {
		setProfileSaved(false)
	})
})

profileSaved.addEventListener('change', () => {
	const message = document.getElementById('profile-not-saved-message')

	window.laravel.modified = profileSaved.value.toLowerCase() !== "true";

	if (profileSaved.value.toLowerCase() === 'true') {
		message.classList.add('invisible')
	} else {
		message.classList.remove('invisible')
	}
})


updateEmailModal.addEventListener('show.bs.modal', () => {
	updateEmailInput.value = ''
	resetInvalidFields(updateEmailModal)
})

updateEmailModal.addEventListener('shown.bs.modal', () => {
	updateEmailInput.focus()
})

updateEmailModal.addEventListener('hide.bs.modal', e => {
	if (document.body.classList.contains('busy')) {
		e.preventDefault()
	}
})

updateEmailInput.addEventListener('keypress', e => {
	if (e.key === 'Enter') {
		updateEmailInput.blur()
		changeEmail()
	}
})

updateEmailBtn.addEventListener('click', changeEmail)


updatePhoneModal.addEventListener('show.bs.modal', () => {
	updatePhoneInput.value = ''
	resetInvalidFields(updatePhoneModal)
})

updatePhoneModal.addEventListener('shown.bs.modal', () => {
	updatePhoneInput.focus()
})

updatePhoneModal.addEventListener('hide.bs.modal', e => {
	if (document.body.classList.contains('busy')) {
		e.preventDefault()
	}
})

updatePhoneModal.addEventListener('hidden.bs.modal', e => {
	updatePhoneModal.classList.remove('verify-code')
})

updatePhoneInput.addEventListener('keypress', e => {
	if (e.key === 'Enter') {
		updatePhoneInput.blur()
		changePhone()
	}
})

updatePhoneBtn.addEventListener('click', changePhone)
verifyCodeBtn.addEventListener('click', updatePhone)
