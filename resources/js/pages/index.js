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
})


const invoiceFilter = document.getElementById('invoice-filter')
const invoices = document.querySelector('#invoices-container tbody')

if (invoiceFilter && invoices) {
	const input = invoiceFilter.querySelector('.invoice-filter-input')
	const count = invoiceFilter.querySelector('.invoice-count')

	input.addEventListener('input', () => {
		const text = utils.removeDiacritics(input.value).toLowerCase()
		const items = [...invoices.querySelectorAll('.invoice-item')]

		items.forEach(item => {
			const content = utils.removeDiacritics(item.textContent).toLowerCase()

			if (content.includes(text)) {
				item.classList.remove('d-none')
			} else {
				item.classList.add('d-none')
			}
		})

		setTimeout(() => {
			count.textContent = invoices.querySelectorAll('.invoice-item:not(.d-none)').length
		}, 0);
	})

	invoices.addEventListener('click', e => {
		const element = e.target
		const id = element.parentElement.getAttribute('data-id')

		if (!id) return

		window.location.assign(document.getElementById('invoice-show-url').value.replace('?id', id))
	})

	input.value = ''	
}


if (document.querySelector('.patient-picker-input')) {
	document.querySelector('.patient-picker-input').focus()
}

