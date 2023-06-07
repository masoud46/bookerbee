
import axios from 'axios';

import { Modal, Popover, Tooltip } from 'bootstrap';

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

const popoverKeyIds = ['btn-popover-add-event', 'btn-popover-lock-slot']
const modal = document.getElementById('calendar-modal')
modal.props = {
	patients: [],
	actionClass: 'modal-event-action-',
	header: modal.querySelector('.modal-title'),
	title: modal.querySelector('.calendar-event-title'),
	patient: modal.querySelector('.patient-picker-input'),
	rdvInfo: modal.querySelector('.calendar-event-rdv-info'),
	patientName: modal.querySelector('.event-patient-name'),
	patientEmail: modal.querySelector('.event-patient-email'),
	patientPhone: modal.querySelector('.event-patient-phone'),
	rdvHasEmail: modal.querySelector('.calendar-event-has-email'),
	rdvNoEmail: modal.querySelector('.calendar-event-no-email'),
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
	lockedClass: 'fc-locked-event',
	privateClass: 'fc-private-event',
	event: null,
	oldEvent: null,
	revert: null,
	popover: null,
	action: new Modal(modal)
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

// Show/hide appointment specific elements
const setRdvInfo = patientInfo => {
	modal.props.patientName.textContent = patientInfo.name
	modal.props.patientName.setAttribute('data-patient-id', patientInfo.id)
	modal.props.patientEmail.textContent = patientInfo.email ?? ''
	modal.props.patientPhone.textContent = patientInfo.phone ?? ''

	if (patientInfo.email) {
		modal.props.patientEmail.parentNode.classList.remove('d-none')
		modal.props.rdvHasEmail.classList.remove('d-none')
		modal.props.rdvNoEmail.classList.add('d-none')
	} else {
		modal.props.patientEmail.parentNode.classList.add('d-none')
		modal.props.rdvHasEmail.classList.add('d-none')
		modal.props.rdvNoEmail.classList.remove('d-none')
	}

	if (patientInfo.phone) {
		modal.props.patientPhone.parentNode.classList.remove('d-none')
	} else {
		modal.props.patientPhone.parentNode.classList.add('d-none')
	}

	modal.props.rdvInfo.classList.remove('d-none')
	modal.props.actionButton.classList.remove('d-none')
}

// Add/Update event
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
	console.log(method, url);

	let message = window.laravel.agenda.actions[action].message
	let error = false

	event = JSON.parse(JSON.stringify(event))

	if (event.allDay) {
		event.start = event.startStr
		event.end = event.endStr
	}

	delete event.startStr
	delete event.endStr
	delete event.jsEvent
	delete event.view
	console.log(action, event, oldEvent);

	if (event.extendedProps?.patient?.email) document.body.classList.add('sending-email')

	try {
		console.log(method, url, { event, oldEvent });
		const response = await utils.fetch({
			method,
			url,
			data: {
				event,
				oldEvent,
			}
		})
		console.log(event);
		console.log(response);

		if (response.success) {
			if (method === 'POST') {
				event.id = response.id
				calendar.addEvent(event)
			} else if (method === 'DELETE') {
				calendar.getEventById(response.id).remove()
			}

			customProps.action.hide()
		} else {
			console.log('%c failed! ', 'color:white;background-color:red;');
			if (customProps.revert) {
				customProps.revert()
				customProps.revert = null
			}

			error = true
			message = window.laravel.messages.databaseError
		}
	} catch (err) {
		console.error('err', err);
		error = true
		message = window.laravel.messages.unexpectedError
	} finally {
		document.body.classList.remove('sending-email')
	}

	utils.showAlert({ message, error })
}

// Apply the modifications
const applyAction = () => {
	const modalClass = [...modal.classList].find(cls => cls.startsWith(modal.props.actionClass))
	const action = modalClass.substring(modal.props.actionClass.length)
	const event = customProps.event
	console.log(event);

	switch (action) {
		case EVENT_ACTION_ADD:
			event.title = modal.props.patientName.textContent
			event.extendedProps = {
				patient: {
					id: modal.props.patientName.getAttribute('data-patient-id'),
					name: event.title,
				}
			}

			if (modal.props.patientEmail.textContent.length) {
				event.extendedProps.patient.email = modal.props.patientEmail.textContent
			}

			if (modal.props.patientPhone.textContent.length) {
				event.extendedProps.patient.phone = modal.props.patientPhone.textContent
			}

			storeEvent(action, event)
			break;
		case EVENT_ACTION_LOCK:
			event.className = customProps.lockedClass
			event.title = modal.props.title.value
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
			const newEvent = JSON.parse(JSON.stringify(customProps.event))
			let oldEvent = null

			if (!newEvent.allDay) {
				newEvent.localStart = newEvent.start
				newEvent.localEnd = newEvent.end
				newEvent.start = new Date(newEvent.start).toISOString()
				newEvent.end = new Date(newEvent.end).toISOString()
			}

			if (action === EVENT_ACTION_UPDATE) {
				oldEvent = JSON.parse(JSON.stringify(customProps.oldEvent))
				if (!oldEvent.allDay) {
					oldEvent.localStart = oldEvent.start
					oldEvent.localEnd = oldEvent.end
					oldEvent.start = new Date(oldEvent.start).toISOString()
					oldEvent.end = new Date(oldEvent.end).toISOString()
				}
			}

			storeEvent(action, newEvent, oldEvent)
			break;

		default:
			storeEvent(action, event)
			break;
	}
}

// Set modal elements according to the action 
const showModal = action => {
	// console.log('event', event);
	// console.log('old event', customProps.oldEvent);
	const format = 'DD/MM/YYYY'
	const event = customProps.event
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

	modal.querySelector('.event-recurr-form').reset()
	modal.props.recurrDays.forEach(day => {
		day.disabled = false
	})

	switch (action) {
		case EVENT_ACTION_ADD:
		case EVENT_ACTION_UPDATE:
			modal.props.actionButton.classList.add('btn-primary')
			break
		case EVENT_ACTION_CANCEL:
			modal.props.actionButton.classList.add('btn-danger')
			break
		case EVENT_ACTION_LOCK:
		case EVENT_ACTION_UNLOCK:
		case EVENT_ACTION_UPDATE_LOCK:
			modal.props.actionButton.classList.add('btn-secondary')
			break
	}

	if (action === EVENT_ACTION_ADD) {
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
		// console.log(event.extendedProps);
		const patientInfo = {
			name: event.extendedProps.patient.name,
			email: event.extendedProps.patient.email ?? null,
			phone: event.extendedProps.patient.phone ?? null,
		}

		setRdvInfo(patientInfo)
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

			console.log(startDOW, endDOW);
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
modal.addEventListener('hide.bs.modal', () => {
	modal.props.recurrCheck.removeAttribute('opened')
})
modal.addEventListener('shown.bs.modal', () => {
	if (modal.classList.contains(`${modal.props.actionClass}${EVENT_ACTION_ADD}`)) modal.props.patient.focus()
	if (modal.classList.contains(`${modal.props.actionClass}${EVENT_ACTION_LOCK}`)) modal.props.title.focus()
})
modal.addEventListener('show.bs.modal', () => {
	modal.props.dismissed = false
})
modal.addEventListener('hidden.bs.modal', () => {
	console.log('dismissed:', modal.props.dismissed);
	if (modal.props.dismissed) {
		if (customProps.revert) {
			customProps.revert()
			customProps.revert = null
		}
	}
})
modal.props.actionButton.addEventListener('click', applyAction)
// showModal('lock')


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
	allDaySlot: window.laravel.agenda.lock,
	timeZone: window.laravel.agenda.timezone,
	locales: allLocales,
	locale: window.laravel.locale,
	themeSystem: 'bootstrap5',
	initialView: 'timeGridWeek',
	firstDay: 1, // Monday
	// slotMinTime: '07:00:00',
	// slotMaxTime: '21:00:00',
	defaultTimedEventDuration: '01:00:00',
	forceEventDuration: true,
	dayMaxEvents: true, // allow "more" link when too many events
	navLinks: true, // can click day/week names to navigate views
	editable: true,
	selectable: true,
	eventStartEditable: true,
	eventOverlap: false,
	// selectOverlap: false,
	eventOrderStrict: true,
	headerToolbar: {
		left: 'prev,next today',
		center: 'title',
		right: 'timeGridWeek,timeGridDay,listWeek'
	},
	eventTimeFormat: {
		hour: '2-digit',
		minute: '2-digit',
		// timeZoneName: 'short',
	},
	events: async (info, successCallback, failureCallback) => {
		console.log('%c*** events', 'color:#c00;');
		const events = await utils.fetch({
			method: 'GET',
			url: `/events?start=${info.start.toISOString()}&end=${info.end.toISOString()}`,
		})
		console.log(events);

		if (events !== null) {
			calendar.removeAllEvents()
			successCallback(events.events)
		}
	},
	loading: isLoading => {
		console.log('%c*** loading', 'color:#c00;');
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
		console.log('%c*** viewDidMount', 'color:#c00;');
		hideLockedEvents(arg.view)

		customProps.lastSelectOverlap = null
	},
	viewClassNames: arg => {
		console.log('%c*** viewClassNames', 'color:#c00;');
		hideLockedEvents(arg.view)

		customProps.lastSelectOverlap = null
	},
	selectOverlap: event => {
		console.log('%c*** selectOverlap', 'color:#0c0;');
		// To allow all day locking if there is only background LOCK events
		// +++ ATT: Recurring background LOCK events must be at the end of the events' list
		customProps.lastSelectOverlap = event
		return false
	},
	select: arg => {
		console.log('%c*** select', 'color:#c00;');
		customProps.event = arg

		if (arg.allDay) {
			console.log('%c lock ALLDAY ', 'color:#fff;background-color:#999;');
			showModal(EVENT_ACTION_LOCK)
		} else if (window.laravel.agenda.lock) {
			customProps.popover = new Popover(arg.jsEvent.target, {
				trigger: 'manual',
				html: true,
				sanitize: false,
				content: `
					<div class="d-flex flex-column">
						<button id="${popoverKeyIds[0]}" class="btn btn-primary mb-3">${window.laravel.messages.createAppointment}</button>
						<button id="${popoverKeyIds[1]}" class="btn btn-secondary">${window.laravel.messages.lockSlot}</button>
					</div>
				`,
			})
			customProps.popover.show()
		} else {
			console.log('%c add and email ', 'color:#fff;background-color:#999;')
			showModal(EVENT_ACTION_ADD)
		}

		customProps.lastSelectOverlap = null
	},
	unselect: arg => {
		console.log('%c*** unselect', 'color:#c00;');
		if (customProps.popover && customProps.popover.tip !== null) {
			setTimeout(() => {
				customProps.popover.hide()
			}, 0);
		}

		customProps.lastSelectOverlap = null
	},
	eventClick: arg => {
		console.log('%c*** eventClick', 'color:#c00;');
		customProps.event = arg.event

		if (!arg.event.extendedProps.patient) {
			const recurring = arg.event._def.recurringDef !== null

			if (arg.event.allDay) {
				console.log(`%c unlock${recurring ? ' RECURRING' : ''} ALLDAY `, 'color:#fff;background-color:#999;');
				if (!arg.event.end) { // same day
					const end = toMoment(arg.date, calendar).add(1, 'd')
					arg.event.setEnd(end.toDate())
				}
			} else {
				console.log(`%c unlock${recurring ? ' RECURRING' : ''} `, 'color:#fff;background-color:#999;');
			}
			showModal(EVENT_ACTION_UNLOCK)
		} else {
			if (arg.event.extendedProps.private) {
				console.log('%c cancel private ', 'color:#fff;background-color:#999;');
			} else {
				console.log('%c cancel ', 'color:#fff;background-color:#999;');
			}
			showModal(EVENT_ACTION_CANCEL)
		}

		customProps.lastSelectOverlap = null
	},
	dateClick: arg => {
		console.log('%c*** dateClick', 'color:#c00;');
		// console.log('lastSelectOverlap', customProps.lastSelectOverlap?._def);
		// To allow all day locking if there is only background LOCK events
		// +++ ATT: Recurring background LOCK events must be at the end of the events' list
		const allowed = arg.allDay
			&& customProps.lastSelectOverlap?._def.ui.display === 'background'
			&& !customProps.lastSelectOverlap?._def.extendedProps.patient
		if (allowed) {
			console.log('%c lock ALLDAY ', 'color:#fff;background-color:#999;');
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
	eventAdd: arg => {
		console.log('%c*** eventAdd', 'color:#c00;');
	},
	eventChange: arg => {
		console.log('%c*** eventChange', 'color:#c00;');
		// Bypass background event which the end has been set manually in "eventClick" callback
		if (arg.event._def.ui.display === 'background') return

		console.log(arg.oldEvent.start.toISOString());
		console.log(arg.event.start.toISOString());
		customProps.event = arg.event
		customProps.oldEvent = arg.oldEvent
		customProps.revert = arg.revert

		if (!arg.event.extendedProps.patient) {
			console.log('%c save locked ', 'color:#fff;background-color:#999;');
			showModal(EVENT_ACTION_UPDATE_LOCK)
		} else {
			if (arg.event.extendedProps.private) {
				console.log('%c save private ', 'color:#fff;background-color:#999;');
			} else {
				console.log('%c save and email ', 'color:#fff;background-color:#999;');
			}
			showModal(EVENT_ACTION_UPDATE)
		}
	},

	// eventDrop: arg => {
	// 	console.log('%c*** eventDrop', 'color:#c00;');
	// 	// console.log(arg.oldEvent.start.toISOString());
	// 	// console.log(arg.event.start.toISOString());
	// },
	// eventResize: arg => {
	// 	console.log('%c*** eventResize', 'color:#c00;');
	// 	// console.log(arg.oldEvent.end.toISOString());
	// 	// console.log(arg.event.end.toISOString());
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
		const prefixObj = window.laravel.agenda.prefixes.find(prefix => prefix.id == patientInfo.phone_country_id)

		patientInfo.name = name
		patientInfo.phone = prefixObj ? `${prefixObj.prefix} ${patientInfo.phone_number}` : null
		console.log(patientInfo);
		setRdvInfo(patientInfo)
	}
})


document.body.addEventListener('click', e => {
	const id = e.target.getAttribute('id')

	if (popoverKeyIds.indexOf(id) > -1) {
		switch (id) {
			case popoverKeyIds[0]: // Add event
				console.log('%c add and email ', 'color:#fff;background-color:#999;')
				showModal(EVENT_ACTION_ADD)
				break;
			case popoverKeyIds[1]: // Lock slot
				console.log('%c lock ', 'color:#fff;background-color:#999;')
				showModal(EVENT_ACTION_LOCK)
				break;
		}
	}
})




