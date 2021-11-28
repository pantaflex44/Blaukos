"use strict"

const formPasswordlost = document.querySelector('#form_passwordlost');
const formPasswordlostSubmit = document.querySelector('#form_passwordlost_submit');
const formPasswordlostCsrf = document.querySelector('#csrf_field_passwordlost')
const formPasswordlostUsername = document.querySelector('#form_passwordlost_username')
if (formPasswordlost !== null
    && formPasswordlostSubmit !== null
    && formPasswordlostCsrf !== null
    && formPasswordlostUsername !== null) {

    formPasswordlostUsername.focus()
    
    /**
     * Request for new CSRF token
     * @param Headers headers HTTP Headers
     */
    function newPasswordLostCsrfToken(headers) {
        if (headers.has('csrf-token') !== true) {
            showAlert('*', 'danger', true)
            return
        }

        const csrf = headers.get('csrf-token').split(';')

        const name = csrf[0].trim()
        const value = csrf[1].trim()

        formPasswordlostCsrf.name = name
        formPasswordlostCsrf.value = value

        setTimeout(() => {
            formPasswordlostUsername.value = ''

            enableElements([formPasswordlostUsername, formPasswordlostSubmit], true)

            formPasswordlostUsername.focus()
        }, 2000)
    }

    formPasswordlost.addEventListener('submit', (event) => {
        event.preventDefault()
        event.stopPropagation()

        if (!formPasswordlost.checkValidity()) {
            return false
        }

        //const url = window.location.origin + '/passwordlost'
        const url = formPasswordlost.action
        const csrfName = sanitize(formPasswordlostCsrf.name)
        const csrfValue = sanitize(formPasswordlostCsrf.value)
        const username = sanitize(formPasswordlostUsername.value)

        const fetchParams = new FormData()
        fetchParams.append(csrfName, csrfValue)
        fetchParams.append('username', username)
        fetchParams.append('mode', 'api')

        const fetchInit = {
            method: 'POST',
            body: fetchParams,
            mode: 'cors',
            cache: 'default',
        }

        showSpinner('form_passwordlost', true)
        enableElements([formPasswordlostUsername, formPasswordlostSubmit], false)

        fetch(url, fetchInit)
            .then((response) => {
                showSpinner('form_passwordlost', false)

                if (response.redirected) {
                    window.location.href = response.url
                }

                switch (response.status) {
                    case 200:
                        const contentType = response.headers.get('content-type')
                        if (!contentType || contentType.indexOf('application/json') === -1) {
                            showAlert('*', 'danger', true)
                            return
                        }

                        response.json()
                            .then((json) => {
                                if (json.hasOwnProperty('error')) {
                                    showCustomAlert(json.error, 'warning')
                                } else {
                                    const userId = json.wantedUserId
                                    const userToken = json.wantedUserToken

                                    const token = getBarearToken(response.headers)
                                    if (token !== userToken) {
                                        showAlert('*', 'danger', true)
                                        return
                                    }

                                    showCustomAlert(json.message, 'success', true)

                                    if (json.hasOwnProperty('redirect')) {
                                        setTimeout(() => {
                                            window.location.href = json.redirect
                                        }, 2000)
                                    }

                                }
                            })
                            .catch(function (error) {
                                showAlert('*', 'danger', true)
                            })

                        break

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

                newPasswordLostCsrfToken(response.headers)

            })
            .catch((error) => {
                showAlert('*', 'danger', true)
            })

    })

}