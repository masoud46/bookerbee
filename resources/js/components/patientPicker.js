import { utils } from '../utils/utils'
import { Autocomplete } from "./autocomplete"

import "../../scss/components/patient-picker.scss"


const Pickers = []

document.querySelectorAll('.patient-picker-component').forEach(element => {
	const id = element.getAttribute('id')

	const patients = new Autocomplete(element.querySelector('.patient-picker-input'), {
		threshold: 3,
		maximumItems: 0,
		dropdownClass: 'patient-picker-dropdown',
		data: [],
		onInput: async value => {
			if (patients.searchUrl) {
				const str = value.trim()

				if (str.length >= patients.options.threshold) {
					if (!patients.options.data.length) {
						const items = await utils.fetch({ url: patients.searchUrl, data: { str } })
						const list = []

						if (typeof patients.onGotItems === 'function') {
							patients.onGotItems(items)
						} else if (typeof patients.setItem === 'function') {
							items.map(item => {
								list.push({
									label: patients.setItem(item),
									value: item.id,
								})
							})
							patients.setData(list)
						}
					}
				} else {
					patients.setData([])
				}

				if (typeof patients.onChange === 'function') {
					setTimeout(() => {
						if (patients.options.data.length) {
							patients.onChange(patients.dropdown._menu.querySelectorAll('.dropdown-item').length)
						} else {
							patients.onChange(0)
						}
					}, 0);
				}
			}
		},
		onSelectItem: ({ label, value }) => {
			window.location.assign(patients.pickedUrl.replace('?id', value))
		}
	})

	patients.setThreshold = (threshold) => {
		patients.options.threshold = threshold
	}

	patients.setMaxItems = (maximumItems) => {
		patients.options.maximumItems = maximumItems
	}

	patients.field.value = ''

	patients.searchUrl = element.querySelector('.patient-picker-autocomplete-url').value
	patients.pickedUrl = element.querySelector('.patient-picker-picked-url').value
	patients.setItem = null
	patients.onGotItems = null
	patients.onChange = null

	Pickers.push({ id, patients })

})


export { Pickers }

