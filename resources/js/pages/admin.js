import { utils } from '../utils/utils'

import '../../scss/pages/admin.scss'


const body = document.querySelector('body')

document.querySelector('#monitoring').addEventListener('click', async e => {
	const btn = e.target
	
	btn.classList.add('btn-spinner')
	body.classList.add('busy')

	document.querySelector('#monitoring-result').innerHTML = ''

	const response = await utils.fetch({ url: '/admin/monitoring' })
	let result = ''

	if (response) {
		for (const type in response) {
			result += `${type}<br>`

			for (const provider in response[type]) {
				const data = response[type][provider]
				const limit = typeof data.limit === 'number' ? ` / ${JSON.stringify(data.limit)}` : ''
				const dataClass = data.critical ? 'text-bg-danger' : (!data.success ? 'text-bg-warning' : '')

				result += `    ${provider}: <span class="p-1 ${dataClass}">${data.success ? JSON.stringify(data.data) : data.message}${limit}</span><br>`
			}

			result += `<br>`
		}
	}

	document.querySelector('#monitoring-result').innerHTML = result

	body.classList.remove('busy')
	btn.classList.remove('btn-spinner')
})

document.querySelectorAll('[id$=-log]').forEach(btn => {
	btn.addEventListener('click', e => {
		const btn = e.target
		const log = btn.getAttribute('id').split('-')[0]
		
		btn.classList.add('btn-spinner')
		body.classList.add('busy')

		document.querySelector('#log-truncate').setAttribute('data-log', log)
		document.querySelector('#log-result').innerHTML = ''
		document.querySelector('.btn-log.btn-success')?.classList.remove('btn-success')
		document.querySelector('#log-truncate').disabled = true

		setTimeout(async () => {
			const content = document.querySelector('#log-result');
			const response = await utils.fetch({ url: `/admin/log/${log}` })

			if (response.success) {
				document.querySelector('#log-truncate').disabled = false
			}

			btn.classList.add('btn-success')
			content.innerHTML = response.data
			setTimeout(async () => {
				content.scrollTop = content.scrollHeight;

				body.classList.remove('busy')
				btn.classList.remove('btn-spinner')
			}, 0);
		}, 0);
	})
})

document.querySelector('#log-truncate').addEventListener('click', async e => {
	const btn = e.target
	const log = btn.getAttribute('data-log')

	btn.disabled = true
	document.querySelector('#log-result').innerHTML = ''

	const response = await utils.fetch({ url: `/admin/truncate/log/${log}` })

	document.querySelector('#log-result').innerHTML = response.data
})


document.querySelector('#buy-sms-credits').addEventListener('click', e => {
	const btn = e.target
	const input = e.target.parentElement.querySelector('input')
	const amount = parseInt(input.value)

	if (isNaN(amount) || amount < 100) {
		input.classList.add('is-invalid')
		input.focus()

		return;
	}

	input.classList.remove('is-invalid')

	const url = `/admin/sms/buy/${amount}`
	const data = {
		credit: 1000,
	}

	utils.showConfirmation(`Buy ${amount} SMS credits from OVH?`, async () => {
		btn.disabled = true;
		input.disabled = true;

		const response = await utils.fetch({ url, data })

		if (response.success) {
			console.log(response);
		} else {
			console.log('%c operation failed! ', 'color: #fff; background-color: #c00;');
			console.log(response);
		}

		btn.disabled = false;
		input.disabled = false;
		input.value = '';
	})
})


document.body.addEventListener('click', e => {
	if (e.target.getAttribute('id') !== 'buy-sms-credits') {
		document.querySelectorAll('.is-invalid').forEach(element => {
			element.classList.remove('is-invalid')
		})
	}
})
