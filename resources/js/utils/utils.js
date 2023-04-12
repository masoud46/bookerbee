import { Modal, Toast } from 'bootstrap';


export const utils = {
	editableElements: ['input', 'select', 'textarea'],
	toast: {
		container: document.querySelector('body'),
		prefix: 'toast-', // seconds
		timeout: 2.4, // seconds
		errorTimeout: 5, // seconds
	},
	flash: {
		element: document.getElementById('flash-message'),
		timeout: 2.5, // seconds
		errorTimeout: 4, // seconds
		timeoutId: null,
	},
	removeDiacritics: str => {
		return str.normalize("NFD").replace(/\p{Diacritic}/gu, "")
	},
	resetInvalidFields: () => {
		document.querySelectorAll('.is-invalid').forEach(element => {
			element.classList.remove('is-invalid')
		})
	},
}


utils.fetch = async ({ method = 'POST', url, data = null, csrf = null }) => {
	const options = {
		method,
		headers: {
			'Accept': 'application.json',
			'Content-Type': 'application/json',
		}
	}

	if (data) options.body = JSON.stringify(data)
	else options.method = 'GET'
	if (csrf) options.headers['X-CSRF-TOKEN'] = csrf
	if (method.toUpperCase() === 'POST' && !csrf) {
		const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content')

		if (csrf) {
			options.headers['X-CSRF-TOKEN'] = csrf
		} else {
			console.log('%cNo CSRF is provided!', 'color: #c00')
			return null
		}
	}

	const response = await fetch(url, options)
	const result = await response.json()

	return result
}

utils.toast.template = `
	<div id="${utils.toast.prefix}%id" class="toast position-fixed top-50 start-50 translate-middle align-items-center text-bg-%color border-0">
		<div class="d-flex">
			<div class="toast-body">%message</div>
			<button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
		</div>
	</div>
`

utils.toast.show = ({ message, delay, error = false }) => {
	if (!message) return
	if (!delay) delay = error ? utils.toast.errorTimeout : utils.toast.timeout

	delay *= 1000

	const id = document.querySelectorAll('.toast').length
	console.log('id', id);
	const element = utils.toast.template
		.replace('%id', id)
		.replace('%color', error ? 'danger' : 'success')
		.replace('%message', message)

	utils.toast.container.insertAdjacentHTML('beforeend', element)

	setTimeout(() => {
		console.log(utils.toast.container);
		const element = document.getElementById(`${utils.toast.prefix}${id}`)
		console.log(element);
		const toast = new Toast(element, { delay, autohide: false })
		const onHidden = () => {
			element.removeEventListener('hidden.bs.toast', onHidden)
			element.remove()
		}

		element.addEventListener('hidden.bs.toast', onHidden)
		toast.show()
	}, 0);
}

utils.showMessage = ({ message, timeout, error = false }) => {
	const flash = utils.flash
	if (!timeout) timeout = error ? flash.errorTimeout : flash.timeout

	flash.element.classList.remove('flash-message-visible')
	flash.element.querySelector('.flash-message-text').innerHTML = message
	flash.element.style.top = `-${flash.element.offsetHeight + 30}px`

	if (error) flash.element.classList.add('flash-message-error')
	else flash.element.classList.remove('flash-message-error')

	flash.element.classList.add('flash-message-visible')
	clearTimeout(flash.timeoutId)
	flash.timeoutId = setTimeout(() => {
		clearTimeout(flash.timeoutId)
		flash.element.classList.remove('flash-message-visible')
	}, timeout * 1000)
}

utils.showConfirmation = (message, cbYes = null, cbNo = null) => {
	const modalObj = new Modal('#yes-no-modal')
	const modal = document.getElementById('yes-no-modal')
	const yesBtn = modal.querySelector('.btn-yes')
	const noBtn = modal.querySelector('.btn-no')
	let activeElement = null

	const onShow = () => {
		modal.removeEventListener('show.bs.modal', onShow)
		// retrieve the active element to focus back on it when modal is hidden
		activeElement = document.activeElement
	}

	const onShown = () => {
		modal.removeEventListener('shown.bs.modal', onShown)
		modal.focus()
	}

	const onHide = () => {
		modal.removeEventListener('hide.bs.modal', onHide)
		yesBtn.removeEventListener('click', onYes)
		noBtn.removeEventListener('click', onNo)
	}

	const onHidden = () => {
		modal.removeEventListener('hidden.bs.modal', onHidden)
		// focus back on the element which was active before showing the modal
		if (activeElement) activeElement.focus()
	}

	const onYes = () => {
		if (typeof cbYes === 'function') cbYes()
		modalObj.hide()
	}

	const onNo = () => {
		if (typeof cbNo === 'function') cbNo()
		modalObj.hide()
	}

	modal.addEventListener('show.bs.modal', onShow)
	modal.addEventListener('shown.bs.modal', onShown)
	modal.addEventListener('hide.bs.modal', onHide)
	modal.addEventListener('hidden.bs.modal', onHidden)
	yesBtn.addEventListener('click', onYes)
	noBtn.addEventListener('click', onNo)

	modal.querySelector('.modal-body').innerHTML = message
	modalObj.show()
}

