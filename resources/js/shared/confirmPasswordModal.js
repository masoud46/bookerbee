const modal = document.getElementById('confirm-password-modal')
const input = modal.querySelector('input[type="password"][name="password"]')
const btn = modal.querySelector('form')

export const password = {
	get: null
}

modal.addEventListener('show.bs.modal', () => {
	input.value = ''
})

modal.addEventListener('shown.bs.modal', () => {
	input.focus()
})

btn.addEventListener('submit', e => {
	e.preventDefault()
	if (typeof password.get === 'function') {
		password.get(input.value)
	}
})

