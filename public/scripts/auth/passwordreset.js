"use strict"

const formPasswordreset = document.querySelector('#form_passwordreset');
const formPasswordresetSubmit = document.querySelector('#form_passwordreset_submit');
const formPasswordresetCsrf = document.querySelector('#csrf_field_passwordreset')
const formPasswordresetPassword1 = document.querySelector('#form_reset_password')
const formPasswordresetPasswordStrength = document.querySelector('#form_reset_password_strength')
const formPasswordresetPassword2 = document.querySelector('#form_reset_passwordbis')
if (formPasswordreset !== null
    && formPasswordresetSubmit !== null
    && formPasswordresetCsrf !== null
    && formPasswordresetPassword1 !== null
    && formPasswordresetPassword2 !== null) {

    formPasswordresetPassword1.focus()

    const passwordValidator = function () {
        if (formPasswordresetPasswordStrength !== null && formPasswordresetPassword1 != null) {
            let result = passwordScore(formPasswordresetPassword1.value);
            formPasswordresetPasswordStrength.style = "width: " + result.percent + "%";
            formPasswordresetPasswordStrength.setAttribute("aria-valuemin", result.min);
            formPasswordresetPasswordStrength.setAttribute("aria-valuemax", result.max);
            formPasswordresetPasswordStrength.setAttribute("aria-valuenow", result.score);
            formPasswordresetPasswordStrength.classList.remove("bg-success");
            formPasswordresetPasswordStrength.classList.remove("bg-danger");
            formPasswordresetPasswordStrength.classList.add(result["bs-class"]);
        }
    };
    formPasswordresetPassword1.addEventListener("keyup", passwordValidator, false);
    formPasswordresetPassword1.addEventListener("change", passwordValidator, false);
    formPasswordresetPassword1.addEventListener("input", passwordValidator, false);
    formPasswordresetPassword1.addEventListener("focus", passwordValidator, false);

    /**
     * Request for new CSRF token
     * @param Headers headers HTTP Headers
     */
    function newPasswordResetCsrfToken(headers) {
        if (headers.has('csrf-token') !== true) {
            showAlert('*', 'danger', true)
            return
        }

        const csrf = headers.get('csrf-token').split(';')

        const name = csrf[0].trim()
        const value = csrf[1].trim()

        formPasswordresetCsrf.name = name
        formPasswordresetCsrf.value = value

        setTimeout(() => {
            formPasswordresetPassword1.value = ''
            formPasswordresetPassword2.value = ''

            enableElements([formPasswordresetPassword1, formPasswordresetPassword2, formPasswordresetSubmit], true)

            formPasswordresetPassword1.focus()
        }, 2000)
    }

    formPasswordreset.addEventListener('submit', (event) => {
        event.preventDefault()
        event.stopPropagation()

        if (!formPasswordreset.checkValidity()) {
            return false
        }

        const password1 = sanitize(formPasswordresetPassword1.value)
        const password2 = sanitize(formPasswordresetPassword2.value)
        if (password1 !== password2) {
            showAlert('passwordNotEquals', 'warning')
            return false
        }

        let result = passwordScore(password1);
        if (result.score !== result.max) {
            showAlert('passwordQuality', 'warning')
            return false
        }

        const url = formPasswordreset.action
        const csrfName = sanitize(formPasswordresetCsrf.name)
        const csrfValue = sanitize(formPasswordresetCsrf.value)

        const fetchParams = new FormData()
        fetchParams.append(csrfName, csrfValue)
        fetchParams.append('password', password1)
        fetchParams.append('passwordbis', password2)
        fetchParams.append('mode', 'api')

        const fetchInit = {
            method: 'POST',
            body: fetchParams,
            mode: 'cors',
            cache: 'default',
        }

        showSpinner('form_passwordreset', true)
        enableElements([formPasswordresetPassword1, formPasswordresetPassword2, formPasswordresetSubmit], false)

        fetch(url, fetchInit)
            .then((response) => {
                showSpinner('form_passwordreset', false)

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
                                    }

                                    showCustomAlert(json.message, 'success', true)

                                    showSpinner('form_passwordreset', true)
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

                newPasswordResetCsrfToken(response.headers)

            })
            .catch((error) => {
                showAlert('*', 'danger', true)
            })

    })

}