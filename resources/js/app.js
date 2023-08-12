// import.meta.glob([
// 	'../images/**',
// 	'../fonts/**',
// ])

import './bootstrap';

import { Tooltip } from 'bootstrap';
import { utils } from './utils/utils';


document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(element =>
	element = new Tooltip(element)
)

document.querySelectorAll('a.nav-link:not(.dropdown-toggle), a.dropdown-item').forEach(element => {
	element.addEventListener('click', e => {
		console.log(e.target.getAttribute('href'));
		if (window.laravel.modified) {
			e.preventDefault();
			utils.showConfirmation(window.laravel.messages.saveModification, () => {
				window.location.assign(e.target.getAttribute("href"));
			});
		}
	})
})

const invalidElement = document.querySelector('.is-invalid:not(.is-invalid-parent)')
if (invalidElement) {
	invalidElement.focus()
}

if (typeof window.laravel.flash === 'object') {
	utils.showAlert(window.laravel.flash)
}


/*********************************************************/
/**************	 stacked modals management  **************/
let modalBodyStyle = null;

document.addEventListener('show.bs.modal', e => {
	const zIndex = 1050 + (20 * document.querySelectorAll('.modal.show').length)

	e.target.style.zIndex = zIndex
	setTimeout(() => {
		document.querySelectorAll('.modal-backdrop:not(.modal-stack)').forEach(modal => {
			modal.style.zIndex = zIndex - 10
			modal.classList.add('modal-stack')
		})
	}, 0)
})

document.addEventListener('hide.bs.modal', e => {
	if (document.querySelectorAll('.modal.show').length > 1) {
		modalBodyStyle = document.body.getAttribute('style')
	} else {
		modalBodyStyle = null
	}
})

document.addEventListener('hidden.bs.modal', e => {
	if (document.querySelectorAll('.modal.show').length > 0) {
		document.body.classList.add('modal-open')
		if (modalBodyStyle) {
			document.body.setAttribute('style', modalBodyStyle)
		}
	}
})
/*********************************************************/


