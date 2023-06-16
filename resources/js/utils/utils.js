import { Modal, Toast } from 'bootstrap';


export const utils = {
	editableElements: ['input', 'select', 'textarea'],
	toast: {
		container: document.querySelector('body'),
		prefix: 'toast-', // seconds
		timeout: 2.5, // seconds
		errorTimeout: 5, // seconds
	},
	flash: {
		element: document.getElementById('flash-message'),
		timeout: 2.5, // seconds
		errorTimeout: 5, // seconds
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
	if (method.toUpperCase() !== 'GET' && !csrf) {
		const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content')

		if (csrf) {
			options.headers['X-CSRF-TOKEN'] = csrf
		} else {
			console.log('%cNo CSRF is provided!', 'color: #c00')
			return null
		}
	}

	const response = await fetch(url, options)

	if (response.headers.get('content-type') !== 'application/json') { // error
		console.log('%c non-json response ', 'color:#fff;background-color:#c00;');
		const code = response.redirected
			? 302 // session has expired -> redirected to login pages
			: 400

		if (code === 302) {
			const message = window.laravel?.messages?.sessionError
				?? 'Your session has been expired!\nPlease sign back in.'

			if (document.getElementById('message-modal')) {
				utils.showMessage(message, () => {
					window.location.assign('/')
				})
			} else {
				utils.showAlert({ message, timeout: 0, type: 'error' })
			}
		}

		return { error: true, code }
	}

	return await response.json()
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
	delay = delay ?? (error ? utils.toast.errorTimeout : utils.toast.timeout)
	delay *= 1000

	const id = document.querySelectorAll('.toast').length
	const element = utils.toast.template
		.replace('%id', id)
		.replace('%color', error ? 'danger' : 'success')
		.replace('%message', message)

	utils.toast.container.insertAdjacentHTML('beforeend', element)

	setTimeout(() => {
		const element = document.getElementById(`${utils.toast.prefix}${id}`)
		const toast = new Toast(element, { delay, autohide: false })
		const onHidden = () => {
			element.removeEventListener('hidden.bs.toast', onHidden)
			element.remove()
		}

		element.addEventListener('hidden.bs.toast', onHidden)
		toast.show()
	}, 0);
}

utils.showAlert = ({ message, timeout, type = 'success' }) => {
	const flash = utils.flash

	timeout = timeout ?? (type === 'success' ? flash.timeout : flash.errorTimeout)

	flash.element.classList.remove('flash-message-visible')
	flash.element.querySelector('.flash-message-text').innerHTML = message
	flash.element.style.top = `-${flash.element.offsetHeight + 30}px`

	flash.element.classList.remove('flash-message-success', 'flash-message-warning', 'flash-message-error')
	flash.element.classList.add(`flash-message-${type}`)

	flash.element.classList.add('flash-message-visible')
	clearTimeout(flash.timeoutId)

	if (timeout > 0) {
		flash.timeoutId = setTimeout(() => {
			clearTimeout(flash.timeoutId)
			flash.element.classList.remove('flash-message-visible')
		}, timeout * 1000)
	}
}

utils.showMessage = (message, cbClose = null) => {
	const modalObj = new Modal('#message-modal')
	const modal = document.getElementById('message-modal')

	const onShown = () => {
		modal.removeEventListener('shown.bs.modal', onShown)
		modal.querySelector('.btn[data-bs-dismiss="modal"]').focus()
	}

	modal.querySelector('.modal-body').innerHTML = message
	modal.addEventListener('shown.bs.modal', onShown)

	if (typeof cbClose === 'function') {
		modal.addEventListener('hidden.bs.modal', cbClose)
	}

	modalObj.show()
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

