import { utils } from '../utils/utils'


const settingsForm = document.getElementById('settings-form')
const settingsSaved = document.getElementById('settings-saved')

// set settings saved state
function setSettingsSaved(value) {
	settingsSaved.value = value ? 'true' : 'false'
	settingsSaved.dispatchEvent(new Event('change'))
}

settingsForm.addEventListener('submit', () => {
	document.querySelector('body').classList.add('busy')
})

// when any of the settings elements changes, set saved state to false
settingsForm.querySelectorAll(utils.editableElements).forEach(element => {
	element.addEventListener('input', () => {
		setSettingsSaved(false)
	})
})

settingsSaved.addEventListener('change', () => {
	const message = document.getElementById('settings-not-saved-message')

	window.laravel.modified = settingsSaved.value.toLowerCase() !== "true";

	if (settingsSaved.value.toLowerCase() === 'true') {
		message.classList.add('invisible')
	} else {
		message.classList.remove('invisible')
	}
})

