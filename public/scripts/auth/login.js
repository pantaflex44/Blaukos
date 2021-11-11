"use strict"

/**
 * Show or Hide the form spinner
 * @param {boolean} state Wanted state, true to show, false to hide
 */
 function showSpinner(formId, state) {
    const formSpinner = document.querySelector('#' + formId + '_spinner')
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

const formLogin = document.querySelector('#form_login');
const formLoginSubmit = document.querySelector('#form_login_submit');
const formLoginCsrf = document.querySelector('#csrf_field_login')
const formLoginUsername = document.querySelector('#form_login_username')
const formLoginPassword = document.querySelector('#form_login_password')
if (formLogin !== null 
    && formLoginSubmit !== null 
    && formLoginCsrf !== null
    && formLoginUsername !== null
    && formLoginPassword !== null) {

    /**
     * Request for new CSRF token
     * @param Headers headers HTTP Headers
     */
    function newLoginCsrfToken(headers) {
        if (headers.has('csrf-token') !== true) {
            showAlert('*', 'danger', true)
            return
        }

        const csrf = headers.get('csrf-token').split(';')

        const name = csrf[0].trim()
        const value = csrf[1].trim()

        formLoginCsrf.name = name
        formLoginCsrf.value = value

        setTimeout(() => {
            formLoginUsername.value = ''
            formLoginPassword.value = ''
            
            enableElements([formLoginUsername, formLoginPassword, formLoginSubmit], true)
            
            formLoginUsername.focus()
        }, 2000)
    }

    formLogin.addEventListener('submit', (event) => {
        event.preventDefault()
        event.stopPropagation()
    
        if (!formLogin.checkValidity()) {
            return false
        }

        const url = window.location.origin + '/authentificate'
        const csrfName = sanitize(formLoginCsrf.name)
        const csrfValue = sanitize(formLoginCsrf.value)
        const username = sanitize(formLoginUsername.value)
        const password = sanitize(formLoginPassword.value)

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

        showSpinner('form_login', true)
        enableElements([formLoginUsername, formLoginPassword, formLoginSubmit], false)

        fetch(url, fetchInit)
            .then((response) => {
                showSpinner('form_login', false)

                if (response.redirected) {
                    window.location.href = response.url
                }

                switch(response.status) {
                    case 200:
                        const contentType = response.headers.get('content-type')
                        if (!contentType || contentType.indexOf('application/json') === -1) {
                            showAlert('*', 'danger', true)
                            return
                        }

                        response.json()
                            .then((json) => {
                                const userId = json.userId

                                const token = getBarearToken(response.headers)
                                if (token === '') {
                                    showAlert('*', 'danger', true)
                                    return
                                }

                                window.location.href = `/dashboard`
                            })
                            .catch(function(error) {
                                showAlert('*', 'danger', true)
                            })
                      
                        return

                    case 401:
                    case 403:
                    case 429:
                        showAlert(`${response.status}`, 'warning')
                        break
                    
                    case 400:
                    case 404:
                    case 500:
                        showAlert(`${response.status}`, 'danger')
                        break
                }

                newLoginCsrfToken(response.headers)

            })
            .catch((error) => {
                showAlert('*', 'danger', true)
            })

    })

}
