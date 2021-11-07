"use strict"

const formLogin = document.querySelector('#form_login');
const formSubmit = document.querySelector('#form_submit');
const formCsrf = document.querySelector('#csrf_field')
const formUsername = document.querySelector('#username')
const formPassword = document.querySelector('#password')
if (formLogin !== null 
    && formSubmit !== null 
    && formCsrf !== null
    && formUsername !== null
    && formPassword !== null) {

    /**
     * Show or Hide the form spinner
     * @param {boolean} state Wanted state, true to show, false to hide
     */
    function showSpinner(state) {
        const formSpinner = document.querySelector('#form_spinner')
        if (formSpinner === null) {
            return
        }

        showElements([formSpinner], state)
    }

    /**
     * Hide the alert box
     */
    function hideAlert() {
        const alertBox = document.querySelector('#alert')
        if (alertBox !== null) {
            showElements([alertBox], false)

            alertBox.classList.remove('alert-danger')
            alertBox.classList.remove('alert-warning')
            alertBox.classList.remove('alert-primary')
            alertBox.classList.remove('alert-secondary')
            alertBox.classList.remove('alert-success')
            alertBox.classList.remove('alert-info')
            alertBox.classList.remove('alert-light')
            alertBox.classList.remove('alert-dark')
        }
    }

    /**
     * Show a main alert
     */
    function showAlert(messageKey = '', type = 'danger', block = false) {
        hideAlert()

        const alertBox = document.querySelector('#alert')
        if (alertBox === null) {
            return
        }

        const message = alertMessages[`${messageKey}`]
        if (message) {
            alertBox.innerHTML = (!block ? `<span id="form_spinner" class="spinner-border spinner-border-sm text-${type} me-3" role="status" aria-hidden="true"></span>` : '') + sanitize(message)
            alertBox.classList.add(`alert-${type}`)

            showElements([alertBox], true)

            if (block !== true) {
                setTimeout(hideAlert, 2000)
            }
        }
    }

    /**
     * Request for new CSRF token
     * @param Headers headers HTTP Headers
     */
    function newCsrfToken(headers) {
        if (headers.has('csrf-token') !== true) {
            showAlert('*', 'danger', true)
            return
        }

        const csrf = headers.get('csrf-token').split(';')

        const name = csrf[0].trim()
        const value = csrf[1].trim()

        formCsrf.name = name
        formCsrf.value = value

        setTimeout(() => {
            formUsername.value = ''
            formPassword.value = ''
            
            enableElements([formUsername, formPassword, formSubmit], true)
            
            formUsername.focus()
        }, 2000)
    }

    formLogin.addEventListener('submit', (event) => {
        event.preventDefault()
        event.stopPropagation()
    
        if (!formLogin.checkValidity()) {
            return false
        }

        const url = window.location.origin + '/authentificate'
        const csrfName = sanitize(formCsrf.name)
        const csrfValue = sanitize(formCsrf.value)
        const username = sanitize(formUsername.value)
        const password = sanitize(formPassword.value)

        const fetchParams = new FormData()
        fetchParams.append(csrfName, csrfValue)
        fetchParams.append('username', username)
        fetchParams.append('password', password)
        fetchParams.append('mode', 'api')

        const fetchInit = {
            method: 'POST',
            body: fetchParams,
            mode: 'cors',
            cache: 'default',
        }

        showSpinner(true)
        enableElements([formUsername, formPassword, formSubmit], false)

        fetch(url, fetchInit)
            .then((response) => {
                showSpinner(false)

                if (response.redirected) {
                    window.location.href = response.url
                }

                switch(response.status) {
                    case 200:
                        const token = getBarearToken(response.headers)
                        if (token === '') {
                            showAlert('*', 'danger', true)
                        }

                        window.location.href = '/dashboard';
                        return

                    case 401:
                    case 403:
                        showAlert(`${response.status}`, 'warning')
                        break
                    
                    case 400:
                    case 404:
                    case 500:
                        showAlert(`${response.status}`, 'danger')
                        break
                }

                newCsrfToken(response.headers)

            })
            .catch((error) => {
                console.log(error)
                showAlert('*', 'danger', true)
            })

    })

}
