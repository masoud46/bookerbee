// import.meta.glob([
// 	'../images/**',
// 	'../fonts/**',
// ])
// '../../node_modules/flag-icons/flags/4x3/**',

import './bootstrap';

import { Tooltip } from 'bootstrap';
import { utils } from './utils/utils';

import '../scss/app.scss'


document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(element =>
	element = new Tooltip(element)
)

const invalidElement = document.querySelector('.is-invalid:not(.is-invalid-parent)')
if (invalidElement) {
	invalidElement.focus()
}

if (typeof httpFlashMessage === 'object') {
	utils.showMessage(httpFlashMessage)
}


