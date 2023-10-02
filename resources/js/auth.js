import { utils } from "./utils/utils";


if (typeof window.laravel?.flash === 'object') {
	utils.showAlert(window.laravel.flash)
}
