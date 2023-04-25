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
// const editEmailModal = document.getElementById('edit-email-modal')
// const editEmailField = document.getElementById('user-new_email')
// const saveEmailBtn = document.querySelector('#edit-email-modal .btn-save')

// set profile saved state
function setProfileSaved(value) {
	profileSaved.value = value ? 'true' : 'false'
	profileSaved.dispatchEvent(new Event('change'))
}

// // send email change request
// async function changeEmail() {
// 	document.body.classList.add('busy')
// 	editEmailField.classList.remove('is-invalid')
// 	const result = await utils.fetch({
// 		url: document.getElementById('edit-email-url').value,
// 		data: { 'user-email': editEmailField.value },
// 	})
// 	console.log(result);
// 	document.body.classList.remove('busy')

// 	if (result.success) {
// 		editEmailModal.querySelector('.btn-close').click()
// 		utils.showMessage({ message: window.laravelEmailChangeSuccessMessage })
// 	} else {
// 		editEmailField.classList.add('is-invalid')
// 		editEmailField.focus()
// 		editEmailModal.querySelector('.invalid-feedback').textContent = result.data
// 	}
// }

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

// editEmailModal.addEventListener('shown.bs.modal', () => {
// 	editEmailField.focus()
// })

// editEmailField.addEventListener('keypress', e => {
// 	if (e.key === 'Enter') {
// 		changeEmail()
// 	}
// })
// saveEmailBtn.addEventListener('click', changeEmail)


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


