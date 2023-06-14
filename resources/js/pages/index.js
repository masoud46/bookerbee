import { Pickers } from '../components/patientPicker'
import { utils } from '../utils/utils'

import '../../scss/pages/index.scss'


const patientCount = document.querySelector('.patient-count')

Pickers.forEach(picker => {
	picker.patients.setItem = item => {
		return `${item.code} - ${item.lastname}, ${item.firstname}`
	}

	picker.patients.onChange = count => {
		if (patientCount) {
			patientCount.textContent = count
		}
	}

	picker.patients.onError = code => {
		if (code !== 302) { // handled in utils.fetch
			utils.showMessage(window.laravel.messages.unexpectedError)
		}
	}
})


const listFilter = document.getElementById('items-table-filter')
const input = listFilter.querySelector('.items-table-filter-input')
const button = listFilter.querySelector('.btn-search-filter')
const total = listFilter.querySelector('.items-table-total')
const count = listFilter.querySelector('.items-table-count')

const invoices = document.querySelector('#invoices-container tbody')
const patients = document.querySelector('#patients-container tbody')

if (listFilter && input && count) {
	const parent = invoices ?? (patients ?? null)

	if (parent) {
		input.addEventListener('input', () => {
			const text = utils.removeDiacritics(input.value).toLowerCase()
			const items = [...parent.querySelectorAll('.items-table-item')]

			items.forEach(item => {
				const content = utils.removeDiacritics(item.textContent).toLowerCase()

				if (content.includes(text)) {
					item.classList.remove('d-none')
				} else {
					item.classList.add('d-none')
				}
			})

			setTimeout(() => {
				count.textContent = parent.querySelectorAll('.items-table-item:not(.d-none)').length
			}, 0);

			if (input.value.length) {
				button.classList.add('filter-active')
			} else {
				button.classList.remove('filter-active')
			}
		})

		button.addEventListener('click', () => {
			input.value = ''
			input.focus()
			input.dispatchEvent(new Event('input'))
		})
	}
}


if (invoices) {
	invoices.addEventListener('click', e => {
		const id = e.target.parentElement.getAttribute('data-id')

		if (id) {
			window.location.assign(document.getElementById('invoice-show-url').value.replace('?id', id))
		}
	})

	input.value = ''

}


if (patients) {
	const container = document.getElementById('patients-container')
	const accordion = document.querySelector('.accordion-button')

	async function fetchPatients() {
		container.classList.add('loading')

		while (patients.firstChild) patients.removeChild(patients.firstChild)

		const result = await utils.fetch({ url: '/patient/list' })

		if (result.error) {
			container.classList.remove('loading')

			if (result.code !== 302) { // handled by utils.fetch
				utils.showAlert({ message: window.laravel.messages.unexpectedError, type: 'error' })
			}

			return
		}

		total.textContent = result.length
		count.textContent = result.length
		input.value = ''

		result.forEach(patient => {
			const row = document.createElement('tr')
			let cells = [...Array(5)] // equals to Array(5).fill()

			cells = cells.map(cell => {
				cell = document.createElement('td')
				cell.setAttribute('scope', 'col')
				row.appendChild(cell)

				return cell
			})

			row.classList.add('items-table-item', 'user-select-none')
			row.setAttribute('role', 'button')
			row.setAttribute('data-id', patient.id)
			patients.appendChild(row)

			cells[0].textContent = patient.code
			cells[1].textContent = patient.lastname
			cells[2].textContent = patient.firstname
			cells[3].textContent = patient.email
			cells[4].textContent = patient.phone_number ? `${patient.phone_prefix} ${patient.phone_number}` : ''
		})

		container.classList.remove('loading')
	}

	listFilter.querySelector('button').addEventListener('click', fetchPatients)

	patients.addEventListener('click', e => {
		const id = e.target.parentElement.getAttribute('data-id')

		if (id) {
			window.location.assign(document.getElementById('patient-show-url').value.replace('?id', id))
		}
	})

	accordion.addEventListener('click', async () => {
		const expanded = !accordion.classList.contains('collapsed')

		if (expanded && patients.children.length === 0) {
			fetchPatients()
		}
	})

	input.value = ''
}


if (document.querySelector('.patient-picker-input')) {
	document.querySelector('.patient-picker-input').focus()
}

