import { utils } from '../utils/utils'
import { Phones } from '../components/phoneNumber'
import { options as patientNotes } from '../shared/patientNotesModal'

import '../../scss/shared/patient-notes-modal.scss'
import '../../scss/components/phone-number.scss'
import '../../scss/pages/patient.scss'


Phones.forEach(phone => {
	phone.number.onChange = () => {
		setPatientSaved(false)
	}
})


const formKey = document.getElementById('form-key')

const setPatientNotesData = () => {
	patientNotes.formKey = formKey.value
	patientNotes.name = `${document.getElementById('patient-lastname').value}, ${document.getElementById('patient-firstname').value}`
	patientNotes.email = document.getElementById('patient-email').value
	patientNotes.phone = `${document.getElementById('patient-phone_prefix').value} ${document.getElementById('patient-phone_number').value}`
}

patientNotes.fetchUrl = document.getElementById('patient-notes-fetch-url').value
patientNotes.storeUrl = document.getElementById('patient-notes-store-url').value
setPatientNotesData()


const patientForm = document.getElementById('patient-form')
const patientSaved = document.getElementById('patient-saved')
const patientNotSavedMessage = document.getElementById('patient-not-saved-message')
const newInvoiceBtn = document.getElementById('new-invoice')

// set patient saved state
function setPatientSaved(value) {
	patientSaved.value = value ? 'true' : 'false'
	patientSaved.dispatchEvent(new Event('change'))
}

patientForm.addEventListener('submit', () => {
	document.querySelector('body').classList.add('busy')
})

// when any of the patient elements changes, set saved state to false
patientForm.querySelectorAll(utils.editableElements).forEach(element => {
	element.addEventListener('input', () => {
		setPatientSaved(false)
	})
})

patientSaved.addEventListener('change', () => {
	window.laravel.modified = patientSaved.value.toLowerCase() !== "true";

	if (patientSaved.value.toLowerCase() === 'true') {
		patientNotSavedMessage.classList.add('invisible')
		if (newInvoiceBtn) newInvoiceBtn.classList.remove('disabled')
	} else {
		patientNotSavedMessage.classList.remove('invisible')
		if (newInvoiceBtn) newInvoiceBtn.classList.add('disabled')
	}
})

