const Phones = []

document.querySelectorAll('.phone-fax-number-component').forEach(element => {
	const id = element.getAttribute('id')

	const number = { element }

	const input = number.element.querySelector('.phone-number-input')
	const toggle = number.element.querySelector('.dropdown-toggle')
	const menu = number.element.querySelector('.dropdown-items')
	const search = number.element.querySelector('.dropdown-search')

	if (number.element.classList.contains('is-invalid')) {
		// number.element.classList.remove('is-invalid')
		// input.classList.add('is-invalid')
	}

	toggle.addEventListener('shown.bs.dropdown', () => {
		menu.querySelectorAll('li.d-none').forEach(item => {
			item.classList.remove('d-none')
		})
		menu.querySelector('.dropdown-item.active').scrollIntoView()
		search.value = ''
		search.focus()
	})

	menu.addEventListener('click', e => {
		const element = e.target

		if (element.classList.contains('dropdown-item')) {
			number.setCountry(element.getAttribute('data-id'), true)
			if (typeof number.onChange === 'function') setTimeout(() => {
				number.onChange()
			}, 0);
		} else {
			e.stopPropagation()
		}
	})

	search.addEventListener('input', () => {
		const text = search.value.normalize("NFD").replace(/\p{Diacritic}/gu, "").toLowerCase()

		menu.querySelectorAll('.dropdown-item').forEach(item => {
			const name = item.getAttribute('data-name')
			const parent = item.parentNode

			parent.classList.add('d-none')

			if (name.startsWith(text)) {
				parent.classList.remove('d-none')
			}

			menu.scrollTop = 0
		})
	})

	search.addEventListener('keydown', e => {
		if (e.which === 40) { // arrow down
			let item = menu.querySelector('li:not(.d-none) .dropdown-item.active')

			if (!item) item = menu.querySelector('li:not(.d-none) .dropdown-item')

			if (item) {
				menu.classList.add('no-scroll')
				item.focus()
				menu.classList.remove('no-scroll')
			}
		}
	})


	number.setCountry = (id = null, focus = false) => {
		// if phone number prefix is null (no phone number), use default prefix
		if (!id) id = number.element.getAttribute('data-default-country-id')

		const item = menu.querySelector(`.dropdown-item[data-id="${id}"]`)
		const activeItem = menu.querySelector('.dropdown-item.active')

		if (activeItem) activeItem.classList.remove('active')

		item.classList.add('active')
		number.element.querySelector('.phone-number-country').value = id
		number.element.querySelector('.phone-number-prefix').textContent = item.getAttribute('data-prefix')
		number.element.querySelector('.phone-number-dropdown .fib').setAttribute('class', `fib fi-${item.getAttribute('data-code')} me-1`)

		if (focus) {
			input.focus()
			input.select()
		}
	}

	number.onChange = null

	Phones.push({ id, number })

})

// Remove the "d-none" class right after rendering
// the page, to start loading the flag icons. Then remove it by after a timeout
const flagIconsContainer = document.querySelector('.phone-number-flag-icons')
setTimeout(() => { flagIconsContainer.classList.remove('d-none') }, 0)
setTimeout(() => { flagIconsContainer.remove() }, 5000);


export { Phones }

