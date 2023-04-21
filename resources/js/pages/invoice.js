import { utils } from '../utils/utils'

import { options as patientNotes } from '../shared/patientNotesModal'

import '../../scss/shared/patient-notes-modal.scss'
import '../../scss/components/resetable-date.scss'
import '../../scss/pages/invoice.scss'


const formKey = document.getElementById('form-key')

const setPatientNotesData = () => {
	patientNotes.formKey = formKey?.value
	patientNotes.name = `${document.getElementById('patient-lastname')?.value}, ${document.getElementById('patient-firstname')?.value}`
	patientNotes.email = document.getElementById('patient-email').value
	patientNotes.phone = `${document.getElementById('patient-phone_prefix')?.value} ${document.getElementById('patient-phone_number')?.value}`
}

patientNotes.fetchUrl = document.getElementById('patient-notes-fetch-url').value
patientNotes.storeUrl = document.getElementById('patient-notes-store-url').value
setPatientNotesData()

const invoiceForm = document.getElementById('invoice-form')
const invoiceApps = document.getElementById('invoice-apps')
const removeApp = invoiceApps.querySelectorAll('.remove-app')
const addApp = document.getElementById('add-app')
const invoiceLocationCheck = document.getElementById('invoice-location_check')
const invoiceLocation = document.getElementById('invoice-location')
const invoiceSaved = document.getElementById('invoice-saved')
const invoiceNotSavedMessage = document.getElementById('invoice-not-saved-message')
const printInvoiceBtn = document.getElementById('print-invoice')
const currentSession = document.getElementById('invoice-session')
const sessionTypes = JSON.parse(document.getElementById('invoice-sessions-types').value)
const patientCategory = JSON.parse(document.getElementById('invoice-patient-category').value)

// reset the the given parent's form elements
function resetChildrenValues(parent) {
	parent.querySelectorAll(utils.editableElements).forEach(element => {
		const defaultValue = element.getAttribute('default-value')

		element.value = defaultValue ?? ''
	})
}

// set invoice saved state
function setInvoiceSaved(value) {
	invoiceSaved.value = value ? 'true' : 'false'
	invoiceSaved.dispatchEvent(new Event('change'))
}

// re-index the apps after each removal
function reIndexApps() {
	const apps = invoiceApps.querySelectorAll('.app-item')

	apps.forEach((app, index) => {
		app.querySelectorAll('[name]').forEach(child => {
			const name = child.getAttribute('name').split('-')

			name.pop()
			name.push(index)
			child.setAttribute('name', name.join('-'))
		})
	})
}

// reset appointment's elements according to the invoice session number
function resetAppSessions(app = null) {
	const visibleApps = invoiceApps.querySelectorAll('[name^="app-visible-"][value="visible"]')

	visibleApps.forEach((type, index) => {
		const parent = type.parentElement
		const wrapper = parent.querySelector('.app-type-wrapper')
		const newSession = parseInt(currentSession.value) + index

		const typeElement = wrapper.querySelector('.app-type')
		const descriptionElement = parent.querySelector('.app-description')
		const prevId = typeElement.value * 1
		const prevDescription = descriptionElement.value

		typeElement.classList.remove('app-type-changed')
		descriptionElement.classList.remove('app-type-changed')

		let id = 0
		let description = ''
		for (const type of sessionTypes) {
			if (!type.max_sessions) {
				type.max_sessions = 100000 // estimated max sessions!!
			}

			if (newSession <= type.max_sessions) {
				id = type.id
				if (id !== prevId) {
					if (patientCategory === 1) {
						description = type.description
					}
				} else {
					description = prevDescription
				}
				break
			}
		}

		wrapper.setAttribute('data-session', newSession)
		typeElement.value = id
		descriptionElement.value = description

		if (parent !== app) { // do not apply "changed" class to the added app
			setTimeout(() => {
				if (id !== prevId) typeElement.classList.add('app-type-changed')
				if (description !== prevDescription) descriptionElement.classList.add('app-type-changed')
			}, 0);
		}
	})
}

invoiceForm.addEventListener('submit', () => {
	document.querySelector('body').classList.add('busy')
})

// when any of the invoice elements changes, set saved state to false
invoiceForm.querySelectorAll(utils.editableElements).forEach(element => {
	element.addEventListener('input', () => {
		setInvoiceSaved(false)
	})
})

currentSession.addEventListener('change', () => {
	resetAppSessions()
})

invoiceSaved.addEventListener('change', () => {
	if (invoiceSaved.value.toLowerCase() === 'true') {
		invoiceNotSavedMessage.classList.add('invisible')
		if (printInvoiceBtn) printInvoiceBtn.classList.remove('disabled')
	} else {
		invoiceNotSavedMessage.classList.remove('invisible')
		if (printInvoiceBtn) printInvoiceBtn.classList.add('disabled')
	}
})

if (document.querySelector('.app-description').getAttribute('disabled') !== null) {
	invoiceApps.querySelectorAll('select.app-type').forEach(element => {
		element.addEventListener('change', e => {
			const select = e.target
			const option = select.options[select.selectedIndex]
			const description = option.getAttribute('data-description')
			const appIndex = select.getAttribute('name').split('-')[2]
			const input = document.querySelector(`[name="app-description-${appIndex}"]`)

			if (input) input.value = description
		})
	})
}

// manage add appointment button
if (addApp) {
	addApp.addEventListener('click', e => {
		const app = invoiceApps.querySelector('.app-item.d-none')

		if (app) {
			app.querySelector('[name^="app-visible-"]').value = 'visible'
			app.classList.remove('d-none')
			setInvoiceSaved(false)
			resetAppSessions(app)
		}

		if (invoiceApps.querySelectorAll('.app-item.d-none').length === 0) {
			addApp.classList.add('d-none')
		}
	})
}

// manage remove appointment button
removeApp.forEach(btn => {
	btn.addEventListener('click', e => {
		const app = e.currentTarget.parentElement.parentElement.parentElement.parentElement

		app.classList.add('d-none')
		// move the element to the end of its parent
		app.parentNode.appendChild(app)
		resetChildrenValues(app)
		reIndexApps()
		setInvoiceSaved(false)
		addApp.classList.remove('d-none')
		resetAppSessions()
	})
})

// show/hide the out of office form and set/reset it's elements
invoiceLocationCheck.addEventListener('change', e => {
	if (invoiceLocationCheck.checked) {
		invoiceLocation.classList.add('location-visible')
	} else {
		invoiceLocation.classList.remove('location-visible')
		// resetChildrenValues(invoiceLocation)
	}
	// setInvoiceSaved(false)
})

// resetAppSessions()

