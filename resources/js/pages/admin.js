import { utils } from '../utils/utils'

document.querySelector('#monitoring').addEventListener('click', async () => {
	document.querySelector('#monitoring-result').innerHTML = ''

	const response = await utils.fetch({ url: '/admin/monitoring' })
	let result = ''

	if (response) {
		for (const type in response) {
			result += `${type}<br>`

			for (const provider in response[type]) {
				const data = response[type][provider]
				result += `    ${provider}: <span class="p-1 ${data.success ? '' : 'text-bg-danger'}">${JSON.stringify(data.data)}</span><br>`
			}

			result += `<br>`
		}
	}

	document.querySelector('#monitoring-result').innerHTML = result
})

document.querySelectorAll('[id$=-log]').forEach(btn => {
	btn.addEventListener('click', e => {
		const log = e.target.getAttribute('id').split('-')[0]

		document.querySelector('#log-truncate').setAttribute('data-log', log)
		document.querySelector('#log-result').innerHTML = ''
		document.querySelector('.btn-log.btn-success')?.classList.remove('btn-success')

		setTimeout(async () => {
			const response = await utils.fetch({ url: `/admin/log/${log}` })

			e.target.classList.add('btn-success')
			document.querySelector('#log-result').innerHTML = response.data
			document.querySelector('#log-truncate').disabled = false
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
