import { utils } from '../utils/utils'
import { Phones } from '../components/phoneNumber'

import '../../scss/components/phone-number.scss'


Phones.forEach(phone => {
	phone.number.onChange = () => {
		setUserSaved(false)
	}
})


const userForm = document.getElementById('user-form')
const userSaved = document.getElementById('user-saved')

// set user saved state
function setUserSaved(value) {
	userSaved.value = value ? 'true' : 'false'
	userSaved.dispatchEvent(new Event('change'))
}

userForm.addEventListener('submit', () => {
	document.querySelector('body').classList.add('busy')
})

// when any of the user elements changes, set saved state to false
userForm.querySelectorAll(utils.editableElements).forEach(element => {
	element.addEventListener('input', () => {
		setUserSaved(false)
	})
})

userSaved.addEventListener('change', () => {
	const message = document.getElementById('user-not-saved-message')

	if (userSaved.value.toLowerCase() === 'true') {
		message.classList.add('invisible')
	} else {
		message.classList.remove('invisible')
	}
})

