const startInput = document.getElementById('report-start')
const endInput = document.getElementById('report-end')
const exportReport = document.getElementById('export-report')
const printReport = document.getElementById('print-report')
const printOnly = document.getElementById('print-only')

const csrfTag = document.querySelector('meta[name="csrf-token"]')

if ((startInput, endInput)) {
  if (exportReport) {
    document.getElementById('export-report').addEventListener('click', async e => {
      const start = startInput.value
      const end = endInput.value
      const url = e.target.getAttribute('data-url').replace('?start', start).replace('?end', end)

      e.preventDefault()
      window.location.assign(url)
    })
  }

  if ((printReport, printOnly, csrfTag)) {
    document.getElementById('print-report').addEventListener('click', async e => {
      const start = startInput.value
      const end = endInput.value
      const url = e.target.getAttribute('data-url')

      e.preventDefault()

      try {
        const response = await fetch(url, {
          method: 'post',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfTag.getAttribute('content'),
          },
          body: JSON.stringify({ start, end }),
        })
        const result = await response.text()

        printOnly.setAttribute('class', null)
        printOnly.classList.add('report-print')
        printOnly.innerHTML = result
        setTimeout(() => {
          window.print()
        }, 0)
      } catch (error) {
        alert(window.laravel.messages.unexpectedError.replace(/<br>/g, '\n'))
      }
    })
  }
}
