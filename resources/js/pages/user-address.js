import { utils } from '../utils/utils'
import { password } from '../shared/confirmPasswordModal'

const addressForm = document.getElementById('address-form')
const addressSaved = document.getElementById('address-saved')

password.get = password => {
	document.querySelector('body').classList.add('busy')
	addressForm.querySelector('input[type="hidden"][name="verify_password"]').value = password
	addressForm.submit()
}

// set address saved state
function setAddressSaved(value) {
	addressSaved.value = value ? 'true' : 'false'
	addressSaved.dispatchEvent(new Event('change'))
}

// when any of the address elements changes, set saved state to false
addressForm.querySelectorAll(utils.editableElements).forEach(element => {
	element.addEventListener('input', () => {
		setAddressSaved(false)
	})
})

addressSaved.addEventListener('change', () => {
	const message = document.getElementById('address-not-saved-message')

	window.laravel.modified = addressSaved.value.toLowerCase() !== "true";

	if (addressSaved.value.toLowerCase() === 'true') {
		message.classList.add('invisible')
	} else {
		message.classList.remove('invisible')
	}
})
