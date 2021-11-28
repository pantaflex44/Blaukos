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
        alertBox.innerHTML = (!block ? `<span id="form_spinner" class="spinner-border spinner-border-sm text-${type} me-3" role="status" aria-hidden="true"></span>` : '') + message
        alertBox.classList.add(`alert-${type}`)

        showElements([alertBox], true)

        if (block !== true) {
            setTimeout(hideAlert, 2000)
        }
    }
}

/**
 * Show custom alert
 */
function showCustomAlert(message = '', type = 'danger', block = false) {
    hideAlert()

    const alertBox = document.querySelector('#alert')
    if (alertBox === null) {
        return
    }

    alertBox.innerHTML = (!block ? `<span id="form_spinner" class="spinner-border spinner-border-sm text-${type} me-3" role="status" aria-hidden="true"></span>` : '') + message
    alertBox.classList.add(`alert-${type}`)

    showElements([alertBox], true)

    if (block !== true) {
        setTimeout(hideAlert, 2000)
    }
}

/**
 * Password scoring
 * @param {string} password The password
 * @returns object
 */
function passwordScore(password) {
    const conds = [
        (str) => /[a-z]/.test(str), // min 1 lowercase char
        (str) => /[A-Z]/.test(str), // min 1 uppercase char
        (str) => /[0-9]/.test(str), // min 1 number
        (str) => /\W|_/g.test(str), // min 1 special char
        (str) => str.length >= 8 // min 8 chars
    ];
    const score = conds
        .map((x) => (x(password) ? 1 : 0))
        .reduce((a, b) => a + b, 0);
    const min = 0;
    const max = conds.length;

    return {
        password: password,
        score: score,
        percent: Math.round((score * 100) / max),
        min: min,
        max: max,
        "bs-class": score === max ? "bg-success" : "bg-danger"
    };
}