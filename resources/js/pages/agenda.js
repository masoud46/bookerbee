
import { Modal, Popover } from 'bootstrap';

import { Calendar, globalPlugins } from '@fullcalendar/core'
import { toMoment, toMomentDuration } from '@fullcalendar/moment'
import momentTimezonePlugin from '@fullcalendar/moment-timezone'
import allLocales from '@fullcalendar/core/locales-all'
import rrulePlugin from '@fullcalendar/rrule'
import interactionPlugin from '@fullcalendar/interaction'
import dayGridPlugin from '@fullcalendar/daygrid'
import timeGridPlugin from '@fullcalendar/timegrid'
import listPlugin from '@fullcalendar/list'
import bootstrap5Plugin from '@fullcalendar/bootstrap5'

import { Pickers } from '../components/patientPicker'
import '../../scss/components/resetable-date.scss'

import './../../scss/pages/agenda.scss'
import { utils } from '../utils/utils';


/*
 * +++ RULES +++
 *
 * 1. Only recurring LOCK events must be set as -> display: 'background'
 * 2. Recurring LOCK events must always be places after the other events on the list
 *    (att: "prepend" the new events and "append" the new recurring events)
 * 3. Recurring LOCK ALL DAY events must be placed before the other recurring LOCK events
 * 4. Recurring events' dtstart MUST be provided on local time zone
 *
 */

/*
 * +++ LOGIC +++
 *
 * 1. If an event's start is a date (without time), it is an "all day" event
 * 2. The "lastSelectOverlap" property is used to allow locking all day if there
 *    are ONLY "recurring background lock" events on that day.
 *
 */


// replace bootstrap icon classes with fontawesome
bootstrap5Plugin.themeClasses.bootstrap5.prototype.baseIconClass = 'fas';
bootstrap5Plugin.themeClasses.bootstrap5.prototype.iconOverridePrefix = 'fa-';
bootstrap5Plugin.themeClasses.bootstrap5.prototype.iconClasses = {
	close: 'fa-times',
	prev: 'fa-chevron-left',
	next: 'fa-chevron-right',
	prevYear: 'fa-angle-double-left',
	nextYear: 'fa-angle-double-right',
};
bootstrap5Plugin.themeClasses.bootstrap5.prototype.rtlIconClasses = {
	prev: 'fa-chevron-right',
	next: 'fa-chevron-left',
	prevYear: 'fa-angle-double-right',
	nextYear: 'fa-angle-double-left',
};
bootstrap5Plugin.themeClasses.bootstrap5.prototype.classes.button = 'btn btn-sm btn-secondary'


// document.body.classList.add('busy', 'busy-agenda')

const popoverKeyIds = [
	'btn-popover-add-event',
	'btn-popover-lock-slot',
	'btn-popover-edit-event',
	'btn-popover-edit-slot',
	'btn-popover-delete-event',
	'btn-popover-unlock-slot',
]
const modal = document.getElementById('calendar-modal')
modal.props = {
	patients: [],
	actionClass: 'modal-event-action-',
	header: modal.querySelector('.modal-title'),
	title: modal.querySelector('.calendar-event-title'),
	locationContainer: document.getElementById('event-location-container'),
	location_id: document.getElementById('calendar-event-location'),
	location: {
		name: document.getElementById('event-location-name'),
		address: document.getElementById('event-location-address'),
		code: document.getElementById('event-location-code'),
		city: document.getElementById('event-location-city'),
		country_id: document.getElementById('event-location-country_id'),
	},
	patient: modal.querySelector('.patient-picker-input'),
	rdvInfo: modal.querySelector('.calendar-event-rdv-info'),
	patientName: modal.querySelector('.event-patient-name'),
	patientEmail: modal.querySelector('.event-patient-email'),
	patientPhone: modal.querySelector('.event-patient-phone'),
	patientPhoneCountryId: null,
	patientPhoneCountryCode: null,
	rdvHasEmail: modal.querySelector('.calendar-event-has-email'),
	rdvNoNotification: modal.querySelector('.calendar-event-no-notification'),
	oldStartDate: modal.querySelector('.calendar-old-event-start-date'),
	oldStartTime: modal.querySelector('.calendar-old-event-start-time'),
	oldEndDate: modal.querySelector('.calendar-old-event-end-date'),
	oldEndTime: modal.querySelector('.calendar-old-event-end-time'),
	startDate: modal.querySelector('.calendar-event-start-date'),
	startTime: modal.querySelector('.calendar-event-start-time'),
	endDate: modal.querySelector('.calendar-event-end-date'),
	endTime: modal.querySelector('.calendar-event-end-time'),
	recurrCheck: modal.querySelector('.event-recurr-switch'),
	recurrFrequency: modal.querySelector('.event-recurr-frequency'),
	recurrLimit: document.getElementById('event-recurr-limit'),
	recurrDays: [...modal.querySelectorAll('.event-recurr-day')],
	actionButton: modal.querySelector('.btn.calendar-event-action'),
	dismissed: false,
}

/* Custom properties */
const customProps = {
	lastSelectOverlap: null,
	event: null,
	oldEvent: null,
	revert: null,
	popover: null,
	action: new Modal(modal),
	hidePopover: () => {
		if (customProps.popover) {
			customProps.popover.hide()
			delete customProps.popover
		}
	}
}


// hide locked events' day title from listWeek view, if there is no other event
const hideLockedEvents = view => {
	if (view.type === 'listWeek') {
		setTimeout(() => {
			view.calendar.el.querySelectorAll('.fc-list-day').forEach(day => {
				let next = day.nextSibling
				while (next && next.classList.contains('fc-locked-event')) {
					next = next.nextSibling
				}
				if (next?.classList.contains('fc-list-day')) {
					day.remove()
				}
			})
		}, 0);
	}
}

const removeClassStartsWith = (node, className) => {
	[...node.classList].forEach(name => {
		if (name.startsWith(className)) {
			node.classList.remove(name)
		}
	})
}

/// validate location information
const validateLocation = () => {
	const form = modal.props.locationContainer.querySelector('&>div')
	let isValid = true

	modal.props.locationContainer.querySelectorAll('input').forEach(input => {
		input.value = input.value.trim()

		if (input.value.length) {
			input.classList.remove('is-invalid')
		} else {
			input.classList.add('is-invalid')
			isValid = false
		}
	})

	if (isValid) {
		form.classList.remove('is-invalid')
	} else {
		form.classList.add('is-invalid')
		form.querySelector('input.is-invalid').focus()
	}

	return isValid
}

// Show/hide appointment specific elements
const setRdvInfo = (patientInfo, props = null) => {
	if (props) {
		modal.props.location_id.value = props.location_id
		modal.props.location_id.dispatchEvent(new Event('change', {}))

		modal.props.location.name.value = props.location ? props.location.name : null
		modal.props.location.address.value = props.location ? props.location.address : null
		modal.props.location.code.value = props.location ? props.location.code : null
		modal.props.location.city.value = props.location ? props.location.city : null
		modal.props.location.country_id.value = props.location ? props.location.country_id : window.laravel.defaultCountryId
	}

	modal.props.patientName.textContent = patientInfo.name
	modal.props.patientName.setAttribute('data-patient-id', patientInfo.id)
	modal.props.patientName.setAttribute('data-patient-locale', patientInfo.locale)
	modal.props.patientEmail.textContent = patientInfo.email ?? ''
	modal.props.patientPhone.textContent = patientInfo.phone ?? ''
	modal.props.patientPhoneCountryId = patientInfo.phoneCountryId ?? null
	modal.props.patientPhoneCountryCode = patientInfo.phoneCountryCode ?? null

	if (patientInfo.email || patientInfo.phone) {
		modal.props.patientEmail.parentNode.classList.remove('d-none')
		modal.props.rdvHasEmail.classList.remove('d-none')
		modal.props.rdvNoNotification.classList.add('d-none')
	} else {
		modal.props.patientEmail.parentNode.classList.add('d-none')
		modal.props.rdvHasEmail.classList.add('d-none')
		modal.props.rdvNoNotification.classList.remove('d-none')
	}

	if (patientInfo.phone) {
		modal.props.patientPhone.parentNode.classList.remove('d-none')
	} else {
		modal.props.patientPhone.parentNode.classList.add('d-none')
	}

	modal.props.rdvInfo.classList.remove('d-none')
	modal.props.actionButton.classList.remove('d-none')
}

// Add/Update/Delete event
const storeEvent = async (action, event, oldEvent = null) => {
	const method = [EVENT_ACTION_ADD, EVENT_ACTION_LOCK].indexOf(action) !== -1
		? 'POST'
		: (
			[EVENT_ACTION_CANCEL, EVENT_ACTION_UNLOCK].indexOf(action) !== -1
				? 'DELETE'
				: 'PUT'
		)
	const url = ['PUT', 'DELETE'].indexOf(method) === -1
		? window.laravel.agenda.actions[action].url
		: window.laravel.agenda.actions[action].url.replace('?id', event.id)

	let message = window.laravel.agenda.actions[action].message
	let error = false

	event = JSON.parse(JSON.stringify(event))

	// if (event.allDay) {
	// 	event.start = event.startStr
	// 	event.end = event.endStr
	// }

	// event.extendedProps.location_id = event.extendedProps?.patient?.id // event is an appointment
	// 	? parseInt(modal.props.location_id.value)
	// 	: null
	if (event.extendedProps?.patient) { // event is an appointment
		event.extendedProps.location_id = parseInt(modal.props.location_id.value)
	}

	delete event.startStr
	delete event.endStr
	delete event.jsEvent
	delete event.view

	document.body.classList.add('sending-email')

	try {
		const result = await utils.fetch({
			method,
			url,
			data: {
				event,
				oldEvent,
			}
		})

		if (result.success) {
			switch (method) {
				case 'POST':
					event.id = result.id
					event.extendedProps = event.extendedProps ?? {}
					event.extendedProps.category = result.category
					event.extendedProps.location_id = result.location_id
					if (result.classNames) event.classNames = result.classNames
					if (result.display) event.display = result.display
					calendar.addEvent(event)
					break;
				case 'PUT':
					customProps.event.setProp('classNames', result.classNames ?? [])
					customProps.event.setExtendedProp('location_id', event.extendedProps.location_id)
					if (event.extendedProps.location) {
						const location = {
							'name': event.extendedProps.location.name,
							'address': event.extendedProps.location.address,
							'code': event.extendedProps.location.code,
							'city': event.extendedProps.location.city,
							'country_id': event.extendedProps.location.country_id,
						}
						customProps.event.setExtendedProp('location', location)
					}
					if (action === EVENT_ACTION_UPDATE_LOCK && customProps.event.title !== event.title) {
						customProps.event.setProp('title', event.title)
					}
					break;
				case 'DELETE':
					calendar.getEventById(result.id).remove()
					break;
			}

			customProps.revert = null
			customProps.action.hide()
		} else {
			// console.log('%c failed! ', 'color:white;background-color:red;');
			document.body.classList.remove('sending-email')

			if (result.error && result?.code === 302) { // handled by utils.fetch
				customProps.action.hide()
				return
			} else {
				error = true
				message = window.laravel.messages.databaseError
			}
		}
	} catch (err) {
		console.error('err', err);
		error = true
		message = window.laravel.messages.unexpectedError
	} finally {
		document.body.classList.remove('sending-email')
	}

	utils.showAlert({ message, type: error ? 'error' : 'success' })
}

// set/delete event's location (006) and return validation status
const setEventLocation = event => {
	if (modal.classList.contains('location-visible')) {
		if (!validateLocation()) {
			return false
		}

		event.extendedProps.location = {
			name: modal.props.location.name.value,
			address: modal.props.location.address.value,
			code: modal.props.location.code.value,
			city: modal.props.location.city.value,
			country_id: modal.props.location.country_id.value,
		}
	} else {
		delete event.extendedProps.location
	}

	return true
}

// Apply the modifications
const applyAction = () => {
	const modalClass = [...modal.classList].find(cls => cls.startsWith(modal.props.actionClass))
	const action = modalClass.substring(modal.props.actionClass.length)
	const event = JSON.parse(JSON.stringify(customProps.event))

	switch (action) {
		case EVENT_ACTION_ADD:
			event.title = modal.props.patientName.textContent
			event.extendedProps = {
				patient: {
					id: modal.props.patientName.getAttribute('data-patient-id'),
					name: event.title,
					locale: modal.props.patientName.getAttribute('data-patient-locale'),
				}
			}

			if (modal.props.patientEmail.textContent.length) {
				event.extendedProps.patient.email = modal.props.patientEmail.textContent
			}

			if (modal.props.patientPhone.textContent.length) {
				event.extendedProps.patient.phone = modal.props.patientPhone.textContent
				event.extendedProps.patient.phoneCountryId = modal.props.patientPhoneCountryId
				event.extendedProps.patient.phoneCountryCode = modal.props.patientPhoneCountryCode
			}

			event.localStart = event.startStr
			event.localEnd = event.endStr

			if (setEventLocation(event)) storeEvent(action, event)
			break;
		case EVENT_ACTION_LOCK:
			event.title = modal.props.title.value.trim()
			// if (modal.props.title.value.trim().length) {
			// 	event.title = modal.props.title.value
			// }

			// Recurring event
			if (modal.props.recurrCheck.checked && modal.props.recurrFrequency.value != window.laravel.agenda.freq.NONE.title) {
				event.display = 'background'
				event.editable = false
				event.startEditable = false
				event.rrule = {
					freq: modal.props.recurrFrequency.value,
					dtstart: event.startStr,
				}

				if (!event.allDay) {
					const start = toMoment(event.start, calendar)
					const end = toMoment(event.end, calendar)
					const duration = toMomentDuration(end.diff(start), calendar)

					event.duration = `${('00' + duration.hours()).slice(-2)}:${('00' + duration.minutes()).slice(-2)}`
				}

				if (modal.props.recurrFrequency.value == window.laravel.agenda.freq.WEEKLY.title) {
					const checked = modal.props.recurrDays.filter(day => day.checked)

					if (checked.length && checked.length < 7) {
						event.rrule.byweekday = checked.map(day => day.getAttribute('id').slice(-2))
					}
				}

				if (modal.props.recurrLimit.value) {
					event.rrule.until = new Date(modal.props.recurrLimit.value).toISOString().substring(0, 10)
				}
			}

			storeEvent(action, event)
			break;

		case EVENT_ACTION_UPDATE:
		case EVENT_ACTION_UPDATE_LOCK:
			// if (!event.allDay) {
			event.localStart = event.start
			event.localEnd = event.end
			event.start = new Date(event.start).toISOString()
			event.end = new Date(event.end).toISOString()
			// }

			if (action === EVENT_ACTION_UPDATE) {
				const oldEvent = JSON.parse(JSON.stringify(customProps.oldEvent))

				if (oldEvent && !oldEvent.allDay) {
					oldEvent.localStart = oldEvent.start
					oldEvent.localEnd = oldEvent.end
					oldEvent.start = new Date(oldEvent.start).toISOString()
					oldEvent.end = new Date(oldEvent.end).toISOString()
				}

				if (setEventLocation(event)) storeEvent(action, event, oldEvent)
			} else {
				event.title = modal.props.title.value.trim()
				storeEvent(action, event)
			}
			break;

		case EVENT_ACTION_CANCEL:
			utils.showConfirmation(window.laravel.messages.irreversibleAction, () => {
				event.localStart = event.start
				event.localEnd = event.end

				storeEvent(action, event)
			}, () => {
				customProps.action.hide();
			})
			break;

		case EVENT_ACTION_UNLOCK:
			storeEvent(action, event)
			break;
	}
}

// Set modal elements according to the action 
const showModal = action => {
	const format = 'DD/MM/YYYY'
	const event = customProps.event
	const oldEvent = customProps.oldEvent
	const rrule = event._def?.recurringDef ?? false
	const privateEvent = event.extendedProps?.private ?? false
	const sameDay = event.allDay
		? toMoment(event.start, calendar).add(1, 'd').isSame(event.end)
		: event.startStr.substring(0, 10) === event.endStr.substring(0, 10)

	removeClassStartsWith(modal, 'modal-event-')
	modal.classList.add(`${modal.props.actionClass}${action}`)

	modal.props.header.innerHTML = window.laravel.agenda.actions[action].header
	modal.props.actionButton.innerHTML = window.laravel.agenda.actions[action].btn
	modal.props.actionButton.classList.remove('btn-primary', 'btn-danger', 'btn-secondary')

	modal.props.title.value = event.title ?? ''
	modal.props.patient.value = event.title
	modal.props.startDate.textContent = toMoment(event.start, calendar).format(format)
	modal.props.startTime.textContent = event.start.toTimeString().substring(0, 5)
	modal.props.endDate.textContent = toMoment(event.end, calendar).format(format)
	modal.props.endTime.textContent = event.end.toTimeString().substring(0, 5)

	if (oldEvent) {
		modal.props.oldStartDate.textContent = toMoment(oldEvent.start, calendar).format(format)
		modal.props.oldStartTime.textContent = oldEvent.start.toTimeString().substring(0, 5)
		modal.props.oldEndDate.textContent = toMoment(oldEvent.end, calendar).format(format)
		modal.props.oldEndTime.textContent = oldEvent.end.toTimeString().substring(0, 5)
	}

	modal.querySelector('.event-recurr-form').reset()
	modal.props.recurrDays.forEach(day => {
		day.disabled = false
	})

	switch (action) {
		case EVENT_ACTION_ADD:
		case EVENT_ACTION_UPDATE:
			modal.props.location_id.disabled = false
			modal.props.actionButton.classList.add('btn-primary')
			break
		case EVENT_ACTION_CANCEL:
			modal.props.location_id.disabled = true
			modal.props.actionButton.classList.add('btn-danger')
			break
		case EVENT_ACTION_LOCK:
		case EVENT_ACTION_UNLOCK:
		case EVENT_ACTION_UPDATE_LOCK:
			modal.props.actionButton.classList.add('btn-secondary')
			break
	}

	if (action === EVENT_ACTION_ADD) {
		modal.props.location_id.value = window.laravel.settings.location
		modal.props.location_id.dispatchEvent(new Event('change', {}))
		modal.props.patient.value = ''
		modal.props.rdvInfo.classList.add('d-none')
		modal.props.actionButton.classList.add('d-none')
	} else {
		modal.props.actionButton.classList.remove('d-none')
	}

	if (action !== EVENT_ACTION_UPDATE && action !== EVENT_ACTION_UPDATE_LOCK) {
		customProps.revert = null
	}

	if (action === EVENT_ACTION_CANCEL || action === EVENT_ACTION_UPDATE) {
		const patientInfo = {
			id: event.extendedProps.patient.id,
			name: event.extendedProps.patient.name,
			locale: event.extendedProps.patient.locale,
			email: event.extendedProps.patient.email ?? null,
			phone: event.extendedProps.patient.phone ?? null,
			phoneCountryId: event.extendedProps.patient.phoneCountryId ?? null,
			phoneCountryCode: event.extendedProps.patient.phoneCountryCode ?? null,
		}

		setRdvInfo(patientInfo, event.extendedProps)
	}

	if (oldEvent && (
		event.start.getTime() !== oldEvent.start.getTime() ||
		event.end.getTime() !== oldEvent.end.getTime()
	)) {
		modal.classList.add('modal-event-old-event')
	}

	if (event.allDay) {
		modal.classList.add('modal-event-all-day')
	}

	if (sameDay) {
		modal.classList.add('modal-event-same-day')
	}

	if (privateEvent) {
		modal.classList.add('modal-event-private')
	}

	if (rrule || action === EVENT_ACTION_LOCK) {
		const limitContainer = modal.querySelector('.event-recurr-limit-container')

		modal.classList.add('modal-event-recurr')
		modal.props.recurrCheck.checked = false
		modal.querySelector('.event-recurr-days').classList.remove('event-recurr-days-visible')

		if (rrule) {
			const options = rrule.typeData.rruleSet._rrule[0].options
			const freq = Object.values(window.laravel.agenda.freq)

			modal.props.recurrCheck.checked = true
			modal.props.recurrCheck.disabled = true
			modal.props.recurrLimit.disabled = true

			modal.props.recurrFrequency.value = freq.find(f => f.value === options.freq).title

			if (options.until) {
				modal.props.recurrLimit.value = options.until.toISOString().substring(0, 10)
				limitContainer.classList.remove('invisible')
			} else {
				limitContainer.classList.add('invisible')
			}

			if (options.byweekday) {
				modal.querySelector('.event-recurr-days').classList.add('event-recurr-days-visible')
				modal.props.recurrDays.forEach(day => {
					day.disabled = true
				})
				options.byweekday.forEach(day => {
					// modal.querySelector(`input.event-recurr-day-${day}`).checked = true
					modal.props.recurrDays[day].checked = true
				})
			}

			modal.classList.add('modal-event-recurr-open')
		} else {
			modal.props.recurrCheck.disabled = false
			modal.props.recurrLimit.disabled = false

			if (!event.allDay && !sameDay) {
				modal.classList.remove('modal-event-recurr')
			}
		}
	}

	customProps.action.show()
}

modal.props.location_id.addEventListener('change', () => {
	if (modal.props.location_id.value == 2) {
		modal.classList.add('location-visible')
	} else {
		modal.classList.remove('location-visible')
	}
})

modal.props.recurrCheck.addEventListener('change', () => {
	if (modal.props.recurrCheck.checked) {
		modal.querySelector('.event-recurr-form').reset()
		modal.querySelector('.event-recurr-limit-container').classList.add('invisible')
		modal.querySelector('.event-recurr-days').classList.add('no-animation')

		if (customProps.event.allDay) {
			modal.props.recurrFrequency.disabled = true;
			modal.props.recurrFrequency.value = window.laravel.agenda.freq.WEEKLY.title
			modal.querySelector('.event-recurr-days').classList.add('event-recurr-days-visible')
			modal.querySelector('.event-recurr-limit-container').classList.remove('invisible')
		} else {
			modal.props.recurrFrequency.disabled = false;
			modal.querySelector('.event-recurr-days').classList.remove('event-recurr-days-visible')
		}

		// Mark selected days and disable all the days until the event's end
		if (modal.classList.contains(`${modal.props.actionClass}${EVENT_ACTION_LOCK}`)) {
			// moment.js isoWeekday returns 1..7 with Monday being the start
			const startDOW = toMoment(customProps.event.start, calendar).isoWeekday() - 1
			let endDOW = toMoment(customProps.event.end, calendar).isoWeekday() - (
				customProps.event.allDay ? 2 : 1
			)

			if (endDOW < 0) endDOW = 6 // Sunday

			let day = 0
			while (day <= endDOW) {
				modal.props.recurrDays[day].disabled = true

				if (day >= startDOW) {
					// modal.querySelector(`input.event-recurr-day-${day}`).checked = true
					modal.props.recurrDays[day].checked = true
				}

				day++
			}
		}

		modal.classList.add('modal-event-recurr-open')
	} else {
		modal.querySelector('.event-recurr-days').classList.remove('no-animation')
		modal.classList.remove('modal-event-recurr-open')
	}
})

modal.props.recurrFrequency.addEventListener('change', () => {
	// const selected = modal.props.recurrFrequency.options[
	// 	modal.props.recurrFrequency.selectedIndex
	// ]

	// modal.props.recurrDays.forEach(day => {
	// 	day.checked = false
	// })

	modal.querySelector('.event-recurr-days').classList.remove('no-animation')
	modal.querySelector('.event-recurr-limit-container').classList.remove('invisible')

	if (modal.props.recurrFrequency.value == window.laravel.agenda.freq.WEEKLY.title) {
		modal.querySelector('.event-recurr-days').classList.add('event-recurr-days-visible')
	} else {
		modal.querySelector('.event-recurr-days').classList.remove('event-recurr-days-visible')
	}
})

modal.addEventListener('keydown', e => {
	if (e.key === 'Escape' && modal.getAttribute('data-bs-keyboard') !== 'false') {
		modal.props.dismissed = 'escape'
	}
})
modal.addEventListener('click', e => {
	modal.props.dismissed = e.target.getAttribute('data-bs-dismiss') === 'modal'
		? 'close'
		: (e.target.classList.contains('modal')
			? 'overlay'
			: false
		)
})
modal.addEventListener('show.bs.modal', () => {
	modal.props.dismissed = false
})
modal.addEventListener('shown.bs.modal', () => {
	if (modal.classList.contains(`${modal.props.actionClass}${EVENT_ACTION_ADD}`)) {
		if (modal.props.location_id.disabled || modal.props.location_id.options.length < 2) {
			modal.props.patient.focus()
		} else {
			modal.props.location_id.focus()
		}
	}
	if (modal.classList.contains(`${modal.props.actionClass}${EVENT_ACTION_UPDATE}`)) {
		if (!modal.props.location_id.disabled && modal.props.location_id.options.length > 1) {
			modal.props.location_id.focus()
		}
	}
	if (modal.matches(`.${modal.props.actionClass}${EVENT_ACTION_LOCK}, .${modal.props.actionClass}${EVENT_ACTION_UPDATE_LOCK}`)) {
		modal.props.title.focus()
	}
})
modal.addEventListener('hide.bs.modal', () => {
	modal.props.recurrCheck.removeAttribute('opened')

	if (customProps.revert) {
		customProps.revert()
	}

	customProps.event = null
	customProps.oldEvent = null
	customProps.revert = null
})
modal.addEventListener('hidden.bs.modal', () => {
	modal.classList.remove('location-visible')
	modal.props.location.country_id.value = window.laravel.defaultCountryId
	modal.props.locationContainer.querySelectorAll('input').forEach(el => {
		el.value = null
	})
	modal.querySelectorAll('.is-invalid').forEach(el => {
		el.classList.remove('is-invalid')
	})
	modal.querySelectorAll('.invalid-feedback').forEach(el => {
		el.classList.add('d-none')
	})
})
modal.props.actionButton.addEventListener('click', applyAction)


const showUpdateModal = (arg) => {
	customProps.event = arg.event
	customProps.oldEvent = arg.oldEvent
	customProps.revert = arg.revert

	if (!arg.event.extendedProps.patient) {
		// console.log('%c save locked ', 'color:#fff;background-color:#999;');
		showModal(EVENT_ACTION_UPDATE_LOCK)
	} else {
		if (arg.event.extendedProps.private) {
			// console.log('%c save private ', 'color:#fff;background-color:#999;');
		} else {
			// console.log('%c save and email ', 'color:#fff;background-color:#999;');
		}
		showModal(EVENT_ACTION_UPDATE)
	}
}

const calendarElement = document.getElementById('app-calendar')
const calendar = new Calendar(calendarElement, {
	plugins: [
		rrulePlugin,
		interactionPlugin,
		dayGridPlugin,
		timeGridPlugin,
		listPlugin,
		bootstrap5Plugin,
		momentTimezonePlugin,
	],
	// height: 'auto',
	contentHeight: 550,
	allDaySlot: window.laravel.agenda.lock,
	timeZone: window.laravel.agenda.timezone,
	locales: allLocales,
	locale: window.laravel.locale,
	themeSystem: 'bootstrap5',
	initialView: 'timeGridWeek',
	firstDay: 1, // Monday
	slotMinTime: window.laravel.settings.cal_min_time,
	slotMaxTime: window.laravel.settings.cal_max_time,
	slotDuration: `00:${window.laravel.settings.cal_slot}:00`,
	defaultTimedEventDuration: '01:00:00',
	forceEventDuration: true,
	dayMaxEvents: true, // allow "more" link when too many events
	navLinks: true, // can click day/week names to navigate views
	// editable: true,
	selectable: true,
	eventStartEditable: true,
	eventOverlap: false,
	// selectOverlap: false,
	eventOrderStrict: true,
	displayEventEnd: false,
	expandRows: true,
	headerToolbar: {
		left: 'prev,next today',
		center: 'title',
		// right: 'dayGridMonth,timeGridWeek,timeGridDay,listWeek'
		right: 'timeGridWeek,timeGridDay,listWeek'
	},
	slotLabelFormat: {
		hour: '2-digit',
		minute: '2-digit',
		hour12: false,
	},
	eventTimeFormat: {
		hour: '2-digit',
		minute: '2-digit',
		hour12: false,
		// timeZoneName: 'short',
	},
	events: async (info, successCallback, failureCallback) => {
		// console.log('%c*** events', 'color:#c00;');
		const result = await utils.fetch({
			method: 'GET',
			url: `${window.laravel.agenda.url}?start=${info.start.toISOString()}&end=${info.end.toISOString()}`,
		})

		if (result.error) {
			successCallback([])

			if (code !== 302) { // handled by utils.fetch
				utils.showMessage(window.laravel.messages.unexpectedError)
			}
		} else {
			calendar.removeAllEvents()
			successCallback(result)
		}
	},
	loading: isLoading => {
		// console.log('%c*** loading', 'color:#c00;');
		if (isLoading) {
			document.body.classList.add('busy-agenda')
		} else {
			document.body.classList.add('busy-agenda-loaded')
			setTimeout(() => {
				document.body.classList.remove('busy-agenda', 'busy-agenda-loaded')
			}, 250);
		}
	},
	viewDidMount: arg => {
		// console.log('%c*** viewDidMount', 'color:#c00;');
		hideLockedEvents(arg.view)

		customProps.lastSelectOverlap = null
	},
	viewClassNames: arg => {
		// console.log('%c*** viewClassNames', 'color:#c00;');
		hideLockedEvents(arg.view)

		customProps.lastSelectOverlap = null
	},
	// eventClassNames: arg => {
	// 	console.log('%c*** eventClassNames', 'color:#c00;');
	// 	if (arg.event.id == 318) console.log(arg.event);
	// },
	selectOverlap: event => {
		// console.log('%c*** selectOverlap', 'color:#0c0;');
		// To allow all day locking if there is only background LOCK events
		// +++ ATT: Recurring background LOCK events must be at the end of the events' list
		customProps.lastSelectOverlap = event
		return false
	},
	select: arg => {
		// console.log('%c*** select', 'color:#c00;');
		customProps.event = arg

		customProps.hidePopover()

		if (arg.allDay) {
			// console.log('%c lock ALLDAY ', 'color:#fff;background-color:#999;');
			showModal(EVENT_ACTION_LOCK)
		} else if (window.laravel.agenda.lock) {
			customProps.popover = new Popover(arg.jsEvent.target, {
				trigger: 'manual',
				html: true,
				sanitize: false,
				content: `
					<div class="d-flex flex-column">
						<button id="${popoverKeyIds[0]}" class="btn btn-sm btn-primary btn-calendar-popover mb-2">${window.laravel.messages.createAppointment}</button>
						<button id="${popoverKeyIds[1]}" class="btn btn-sm btn-secondary btn-calendar-popover mb-2">${window.laravel.messages.lockSlot}</button>
						<button class="btn btn-sm btn-light btn-close-popover">${window.laravel.messages.close}</button>
					</div>
				`,
			})
			customProps.popover.show()
		} else {
			// console.log('%c add and email ', 'color:#fff;background-color:#999;')
			showModal(EVENT_ACTION_ADD)
		}

		customProps.lastSelectOverlap = null
	},
	unselect: arg => {
		// console.log('%c*** unselect', 'color:#c00;');
		// if (customProps.popover) {
		// 	setTimeout(customProps.hidePopover, 0);
		// }

		customProps.lastSelectOverlap = null
	},
	eventClick: arg => {
		// console.log('%c*** eventClick', 'color:#c00;');
		const btnTitles = {
			edit: window.laravel.messages.edit,
			delete: window.laravel.messages.deleteAppointment,
		}

		let buttons = `
			<button id="${popoverKeyIds[2]}" class="btn btn-sm btn-primary btn-calendar-popover mb-2">${window.laravel.messages.edit}</button>
			<button id="${popoverKeyIds[4]}" class="btn btn-sm btn-danger btn-calendar-popover mb-2">${window.laravel.messages.deleteAppointment}</button>
		`

		arg.jsEvent.stopPropagation()
		customProps.hidePopover()
		customProps.event = arg.event

		if (!arg.event.extendedProps.patient) {
			const recurring = arg.event._def.recurringDef !== null

			buttons = `<button id="${popoverKeyIds[5]}" class="btn btn-sm btn-secondary btn-calendar-popover mb-2">${window.laravel.messages.unlockSlot}</button>`

			if (!recurring) {
				buttons = `<button id="${popoverKeyIds[3]}" class="btn btn-sm btn-secondary btn-calendar-popover mb-2">${window.laravel.messages.edit}</button>${buttons}`
			}

			if (arg.event.allDay) {
				// console.log(`%c unlock${recurring ? ' RECURRING' : ''} ALLDAY `, 'color:#fff;background-color:#999;');
				if (!arg.event.end) { // same day
					const end = toMoment(arg.date, calendar).add(1, 'd')
					arg.event.setEnd(end.toDate())
				}
			} else {
				// console.log(`%c unlock${recurring ? ' RECURRING' : ''} `, 'color:#fff;background-color:#999;');
			}
		} else {
			if (arg.event.extendedProps.private) {
				// console.log('%c cancel private ', 'color:#fff;background-color:#999;');
			} else {
				// console.log('%c cancel ', 'color:#fff;background-color:#999;');
			}
		}

		customProps.popover = new Popover(arg.jsEvent.target, {
			trigger: 'manual',
			html: true,
			sanitize: false,
			content: `
				<div class="d-flex flex-column">
					${buttons}
					<button class="btn btn-sm btn-light btn-close-popover">${window.laravel.messages.close}</button>
				</div>
			`,
		})
		customProps.popover.show()
		customProps.lastSelectOverlap = null
	},
	dateClick: arg => {
		// console.log('%c*** dateClick', 'color:#c00;');
		// console.log('lastSelectOverlap', customProps.lastSelectOverlap?._def);
		// To allow all day locking if there is only background LOCK events
		// +++ ATT: Recurring background LOCK events must be at the end of the events' list
		const allowed = arg.allDay
			&& customProps.lastSelectOverlap?._def.ui.display === 'background'
			&& !customProps.lastSelectOverlap?._def.extendedProps.patient
		if (allowed) {
			// console.log('%c lock ALLDAY ', 'color:#fff;background-color:#999;');
			const end = toMoment(arg.date, calendar).add(1, 'd')
			customProps.event = {
				allDay: arg.allDay,
				start: arg.date,
				startStr: arg.dateStr,
				end: end.toDate(),
				endStr: end.format('YYYY-MM-DD'),
				// jsEvent: arg.jsEvent,
				// view: arg.view,
			}
			showModal(EVENT_ACTION_LOCK)
		}

		customProps.lastSelectOverlap = null
	},
	eventDrop: arg => {
		// console.log('%c*** eventDrop', 'color:#c00;');
		showUpdateModal(arg)
	},
	eventResize: arg => {
		// console.log('%c*** eventResize', 'color:#c00;');
		showUpdateModal(arg)
	},

	// eventAdd: arg => {
	// 	// console.log('%c*** eventAdd', 'color:#c00;');
	// },
	// eventChange: arg => {
	// 	// console.log('%c*** eventChange', 'color:#c00;');
	// 	// Bypass background event which the end has been set manually in "eventClick" callback
	// 	if (arg.event._def.ui.display === 'background') return

	// 	if (arg.event.start.getTime() === arg.oldEvent.start.getTime() && arg.event.end.getTime() === arg.oldEvent.end.getTime()) return

	// 	customProps.event = arg.event
	// 	customProps.oldEvent = arg.oldEvent
	// 	customProps.revert = arg.revert

	// 	if (!arg.event.extendedProps.patient) {
	// 		// console.log('%c save locked ', 'color:#fff;background-color:#999;');
	// 		showModal(EVENT_ACTION_UPDATE_LOCK)
	// 	} else {
	// 		if (arg.event.extendedProps.private) {
	// 			// console.log('%c save private ', 'color:#fff;background-color:#999;');
	// 		} else {
	// 			// console.log('%c save and email ', 'color:#fff;background-color:#999;');
	// 		}
	// 		showModal(EVENT_ACTION_UPDATE)
	// 	}
	// },
	// eventMouseEnter: arg => {
	// 	// console.log('%c*** eventMouseEnter', 'color:#c00;');
	// 	if (!arg.el.classList.contains('fc-bg-event')) {
	// 		// console.log(arg);
	// 	}
	// },
	// eventDisplay: arg => {
	// 	console.log('%c*** eventDisplay', 'color:#c00;');
	// 	console.log(arg)
	// },
})

calendar.render()


Pickers.forEach(picker => {
	picker.patients.setItem = item => `${item.code} - ${item.lastname}, ${item.firstname}`

	picker.patients.onGotItems = items => {
		modal.props.patients = items
	}

	picker.patients.onSelected = ({ patient, id }) => {
		const name = patient.split(' - ')[1]
		const patientInfo = modal.props.patients.find(patient => patient.id == id)
		const prefixObj = window.laravel.agenda.countries.find(prefix => prefix.id == patientInfo.phone_country_id)

		patientInfo.id = id
		patientInfo.name = name
		patientInfo.phone = prefixObj ? `${prefixObj.prefix} ${patientInfo.phone_number.replace(/^0+/, '')}` : null
		patientInfo.phoneCountryId = prefixObj ? prefixObj.id : null
		patientInfo.phoneCountryCode = prefixObj ? prefixObj.code : null

		delete patientInfo.phone_number
		delete patientInfo.phone_country_id

		setRdvInfo(patientInfo)
	}
})


document.body.addEventListener('keyup', e => {
	if (e.key === 'Escape' && customProps.popover) {
		customProps.hidePopover()
	}
})

document.body.addEventListener('mousedown', e => {
	if (customProps.popover && !e.target.classList.contains('btn-calendar-popover')) {
		customProps.hidePopover()
	}
})

document.body.addEventListener('click', e => {
	if (customProps.popover) {
		const id = e.target.getAttribute('id')

		if (!calendarElement.querySelector('.fc-highlight')) {
			customProps.hidePopover()
		}

		if (popoverKeyIds.indexOf(id) > -1) {
			switch (id) {
				case popoverKeyIds[0]: // Add event
					// console.log('%c add and email ', 'color:#fff;background-color:#999;')
					showModal(EVENT_ACTION_ADD)
					break;
				case popoverKeyIds[1]: // Lock slot
					// console.log('%c lock ', 'color:#fff;background-color:#999;')
					showModal(EVENT_ACTION_LOCK)
					break;
				case popoverKeyIds[2]: // Update event
					// console.log('%c update and email ', 'color:#fff;background-color:#999;')
					showModal(EVENT_ACTION_UPDATE)
					break;
				case popoverKeyIds[3]: // Update slot
					// console.log('%c update slot ', 'color:#fff;background-color:#999;')
					showModal(EVENT_ACTION_UPDATE_LOCK)
					break;
				case popoverKeyIds[4]: // Delete event
					// console.log('%c delete and email ', 'color:#fff;background-color:#999;')
					showModal(EVENT_ACTION_CANCEL)
					break;
				case popoverKeyIds[5]: // Unlock slot
					// console.log('%c unlock ', 'color:#fff;background-color:#999;')
					showModal(EVENT_ACTION_UNLOCK)
					break;
			}
		}
	}
})




