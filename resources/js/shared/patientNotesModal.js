import { utils } from '../utils/utils'


export const options = {
	formKey: null,
	fetchUrl: null,
	storeUrl: null,
	name: null,
	email: null,
	phone: null,
}


const modal = document.getElementById('patient-notes-modal')
const name = modal.querySelector('.modal-title .patient-notes-modal-name')
const email = modal.querySelector('.modal-title .patient-notes-modal-email')
const phone = modal.querySelector('.modal-title .patient-notes-modal-phone')
const body = modal.querySelector('.modal-body')
const content = modal.querySelector('.modal-notes-content')
const saveBtn = modal.querySelector('.btn-save-notes')

// insert loaded notes into the patient's notes modal
function insertNotes(data) {
	content.innerHTML = '<div>' + data + '</div>'
}

// patient notes modal event listeners
async function fetchPatientNotes() {
	if (options.formKey === null) {
		utils.showMessage({ message: 'formKey is missing!', error: true })
		return
	}

	const data = { 'form-key': options.formKey }

	body.classList.add('modal-is-waiting')
	content.classList.add('modal-notes-empty')
	if (options.name) name.textContent = options.name
	if (options.email) {
		email.textContent = options.email
		email.setAttribute('href', `mailto:${options.email}`)
	}
	if (options.phone) phone.textContent = options.phone
	content.innerHTML = ''
	modal.setAttribute('data-saved', "1")

	const result = await utils.fetch({ url: options.fetchUrl, data })

	if (result.success) {
		if (result.data) {
			insertNotes(result.data)
			content.classList.remove('modal-notes-empty')
		}

		setTimeout(() => { body.classList.remove('modal-is-waiting') }, 0);
	} else {
		utils.showMessage({ message: appMessages.unexpectedError, error: true })
	}
}

// patient notes modal event listeners
modal.addEventListener('show.bs.modal', fetchPatientNotes)

modal.addEventListener('hide.bs.modal', e => {
	if (modal.getAttribute('data-saved') === "1") return

	e.preventDefault()
	utils.showConfirmation(appMessages.saveModification,
		() => {
			modal.setAttribute('data-saved', "1")
			modal.querySelector('.btn-close').click()
		}
	)
})

content.addEventListener('input', () => {
	if (content.innerHTML === '<br>' || content.innerHTML === '<div><br></div>') {
		content.innerHTML = ''
	}

	modal.setAttribute('data-saved', "0")

	if (content.innerHTML.length) {
		content.classList.remove('modal-notes-empty')
	} else {
		content.classList.add('modal-notes-empty')
	}
})

saveBtn.addEventListener('click', async () => {
	if (options.formKey === null) {
		utils.showMessage({ message: 'formKey is missing!', error: true })
		return
	}

	if (!content.innerHTML.length
		|| !content.textContent.length
		|| !content.textContent.trim().length) {
		content.focus()
		return
	}

	modal.classList.add('saving-content')
	body.classList.add('modal-is-waiting')

	let notes = []
	content.childNodes.forEach(child => {
		notes.push(child.textContent)
	})

	const data = {
		notes,
		'form-key': options.formKey,
	}
	const result = await utils.fetch({ url: options.storeUrl, data })

	if (result.success) {
		utils.showMessage({ message: appMessages.modificationSaved })
		insertNotes(result.data)
		modal.setAttribute('data-saved', "1")
	} else {
		utils.showMessage({ message: appMessages.unexpectedError, error: true })
	}

	modal.classList.remove('saving-content')
	body.classList.remove('modal-is-waiting')
})


