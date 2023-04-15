import { utils } from '../utils/utils'
import { Phones } from '../components/phoneNumber'

import '../../scss/components/phone-number.scss'


Phones.forEach(phone => {
	phone.number.onChange = () => {
		setProfileSaved(false)
	}
})


const profileForm = document.getElementById('profile-form')
const profileSaved = document.getElementById('profile-saved')

// set profile saved state
function setProfileSaved(value) {
	profileSaved.value = value ? 'true' : 'false'
	profileSaved.dispatchEvent(new Event('change'))
}

profileForm.addEventListener('submit', () => {
	document.querySelector('body').classList.add('busy')
})

// when any of the profile elements changes, set saved state to false
profileForm.querySelectorAll(utils.editableElements).forEach(element => {
	element.addEventListener('input', () => {
		setProfileSaved(false)
	})
})

profileSaved.addEventListener('change', () => {
	const message = document.getElementById('profile-not-saved-message')

	if (profileSaved.value.toLowerCase() === 'true') {
		message.classList.add('invisible')
	} else {
		message.classList.remove('invisible')
	}
})


const addressForm = document.getElementById('address-form')
const addressSaved = document.getElementById('address-saved')

// set address saved state
function setAddressSaved(value) {
	addressSaved.value = value ? 'true' : 'false'
	addressSaved.dispatchEvent(new Event('change'))
}

addressForm.addEventListener('submit', () => {
	document.querySelector('body').classList.add('busy')
})

// when any of the address elements changes, set saved state to false
addressForm.querySelectorAll(utils.editableElements).forEach(element => {
	element.addEventListener('input', () => {
		setAddressSaved(false)
	})
})

addressSaved.addEventListener('change', () => {
	const message = document.getElementById('address-not-saved-message')

	if (addressSaved.value.toLowerCase() === 'true') {
		message.classList.add('invisible')
	} else {
		message.classList.remove('invisible')
	}
})


