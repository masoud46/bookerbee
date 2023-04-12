const Phones = []

document.querySelectorAll('.phone-fax-number-component').forEach((element, index) => {
	const id = element.getAttribute('id')

	const number = { element }

	const input = number.element.querySelector('.phone-number-input')
	const toggle = number.element.querySelector('.dropdown-toggle')
	const menu = number.element.querySelector('.dropdown-items')
	const search = number.element.querySelector('.dropdown-search')


	// For the first instance, add a hidden div with country flags
	if (index === 0) {
		const codes = document.getElementById(`${id}-country-codes`).value.split(',')
		const preloadFlags = document.createElement('div')

		// Must have "d-none" class to prevent loading,
		// otherwise will slow down the component's displayed prefix-flag
		preloadFlags.classList.add('phone-number-flag-icons', 'd-none')
		codes.map(code => {
			const flag = document.createElement('span')

			flag.classList.add('fib', `fi-${code}`)
			preloadFlags.appendChild(flag)
		})
		number.element.appendChild(preloadFlags)

		// Remove the "d-none" class after rendering (setTimeout(0))
		// to start loading the flag icons. Then remove it after a timeout
		setTimeout(() => { preloadFlags.classList.remove('d-none') }, 0);
		setTimeout(() => { preloadFlags.remove() }, 5000);
	}

	if (number.element.classList.contains('is-invalid')) {
		// number.element.classList.remove('is-invalid')
		// input.classList.add('is-invalid')
	}

	toggle.addEventListener('show.bs.dropdown', () => {
		menu.querySelectorAll('li.d-none').forEach(item => {
			item.classList.remove('d-none')
		})
		search.value = ''
	})
	toggle.addEventListener('shown.bs.dropdown', () => {
		setTimeout(() => {
			menu.querySelector('.dropdown-item.active').scrollIntoView()
		}, 0);
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

			item.classList.add('d-none')

			if (name.startsWith(text)) {
				item.classList.remove('d-none')
			}

			menu.scrollTop = 0
		})
	})

	search.addEventListener('keydown', e => {
		if (e.which === 40) { // arrow down
			let item = menu.querySelector('.dropdown-item:not(.d-none).active')

			if (!item) item = menu.querySelector('.dropdown-item:not(.d-none)')

			if (item) {
				menu.classList.add('no-scroll')
				setTimeout(() => {
					item.focus()
					menu.classList.remove('no-scroll')
				}, 0);
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


export { Phones }

