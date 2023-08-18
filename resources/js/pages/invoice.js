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
const invoiceSessions = document.getElementById('invoice-sessions')
const removeSession = invoiceSessions.querySelectorAll('.remove-session')
const addSession = document.getElementById('add-session')
const invoiceLocationCheck = document.getElementById('invoice-location_check')
const invoiceLocation = document.getElementById('invoice-location')
const invoiceSaved = document.getElementById('invoice-saved')
const invoiceNotSavedMessage = document.getElementById('invoice-not-saved-message')
const printInvoiceBtn = document.getElementById('print-invoice')
const currentSession = document.getElementById('invoice-session')
const sessionTypes = JSON.parse(document.getElementById('invoice-sessions-types').value)
const patientCategory = JSON.parse(document.getElementById('invoice-patient-category').value)
const disableInvoiceBtn = document.getElementById('disable-invoice')

const typeChangeAlert = document.getElementById('type-change-alert').value != 0


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

// re-index the sessions after each removal
function reIndexSessions() {
	const sessions = invoiceSessions.querySelectorAll('.session-item')

	sessions.forEach((session, index) => {
		session.querySelectorAll('[name]').forEach(child => {
			const name = child.getAttribute('name').split('-')

			name.pop()
			name.push(index)
			child.setAttribute('name', name.join('-'))
		})
	})
}

// reset session's elements according to the invoice session number
function resetSessionNumber(session = null) {
	const visibleSessions = invoiceSessions.querySelectorAll('[name^="session-visible-"][value="visible"]')

	let changed = false
	visibleSessions.forEach((type, index) => {
		const parent = type.parentElement
		const wrapper = parent.querySelector('.session-type-wrapper')
		const newSession = parseInt(currentSession.value) + index

		const typeElement = wrapper.querySelector('.session-type')
		const descriptionElement = parent.querySelector('.session-description')
		const prevId = typeElement.value * 1
		const prevDescription = descriptionElement.value

		typeElement.classList.remove('session-type-changed')
		descriptionElement.classList.remove('session-type-changed')

		let id = 0
		let description = ''
		for (const sessionType of sessionTypes) {
			if (!sessionType.max_sessions) {
				sessionType.max_sessions = 100000 // estimated max sessions!!
			}

			if (newSession <= sessionType.max_sessions) {
				id = sessionType.id
				if (id !== prevId) {
					changed = true

					if (patientCategory === 1) {
						description = sessionType.description
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

		if (parent !== session) { // do not apply "changed" class to the added session
			setTimeout(() => {
				if (id !== prevId) typeElement.classList.add('session-type-changed')
				if (description !== prevDescription) descriptionElement.classList.add('session-type-changed')
			}, 0);
		}
	})

	// when session type changes, show a warning message
	if (typeChangeAlert && changed) {
		const message = '<div style="padding: 30px">' + window.laravel.messages.sessionWarning + '</div>'
		if (session) { // a new session added
			const sessions = [...invoiceSessions.querySelectorAll('.session-item:not(.d-none)')]
			const addedSession = sessions.pop()
			const prevSession = sessions.pop()
			const addedMaxSessions = sessionTypes.find(type => type.id == addedSession.querySelector('.session-type').value).max_sessions
			const prevMaxSessions = sessionTypes.find(type => type.id == prevSession.querySelector('.session-type').value).max_sessions

			if (addedMaxSessions !== prevMaxSessions) {
				utils.showAlert({ message, timeout: 0, type: 'warning' })
			}
		} else { // initial session changed
			utils.showAlert({ message, timeout: 0, type: 'warning' })
		}
	}
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
	resetSessionNumber()
})

invoiceSaved.addEventListener('change', () => {
	window.laravel.modified = invoiceSaved.value.toLowerCase() !== "true"

	if (invoiceSaved.value.toLowerCase() === 'true') {
		invoiceNotSavedMessage.classList.add('invisible')
		if (printInvoiceBtn) printInvoiceBtn.classList.remove('disabled')
	} else {
		invoiceNotSavedMessage.classList.remove('invisible')
		if (printInvoiceBtn) printInvoiceBtn.classList.add('disabled')
	}
})

if (document.querySelector('.session-description').getAttribute('disabled') !== null) {
	invoiceSessions.querySelectorAll('select.session-type').forEach(element => {
		element.addEventListener('change', e => {
			const select = e.target
			const option = select.options[select.selectedIndex]
			const description = option.getAttribute('data-description')
			const sessionIndex = select.getAttribute('name').split('-')[2]
			const input = document.querySelector(`[name="session-description-${sessionIndex}"]`)

			if (input) input.value = description
		})
	})
}

// manage add session button
addSession?.addEventListener('click', e => {
	const session = invoiceSessions.querySelector('.session-item.d-none')

	if (session) {
		session.querySelector('[name^="session-visible-"]').value = 'visible'
		session.classList.remove('d-none')
		setInvoiceSaved(false)
		resetSessionNumber(session)
	}

	if (invoiceSessions.querySelectorAll('.session-item.d-none').length === 0) {
		addSession.classList.add('d-none')
	}
})

// manage remove session button
removeSession.forEach(btn => {
	btn.addEventListener('click', e => {
		const session = e.currentTarget.parentElement.parentElement.parentElement.parentElement

		session.classList.add('d-none')
		// move the element to the end of its parent
		session.parentNode.appendChild(session)
		resetChildrenValues(session)
		reIndexSessions()
		setInvoiceSaved(false)
		addSession.classList.remove('d-none')
		resetSessionNumber()
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

// disable the invoice
disableInvoiceBtn?.addEventListener('click', e => {
	utils.showConfirmation(window.laravel.messages.irreversibleAction, () => {
		document.location.assign(document.getElementById('disable-invoice-url').value)
	})
})


if (typeChangeAlert && addSession) { // if Add session button is visible
	for (const type of sessionTypes) {
		if (currentSession.value - 1 == type.max_sessions) {
			const message = '<div style="padding: 30px">' + window.laravel.messages.sessionWarning + '</div>'
			utils.showAlert({ message, timeout: 0, type: 'warning' })
			break
		}
	}
}


// resetSessionNumber()

