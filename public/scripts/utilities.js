/**
 * Sanitize string
 * @param {string} str 
 * @returns Sanitized string
 */
 function sanitize(str) {
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#x27;',
        "/": '&#x2F;',
    };
    const reg = /[&<>"'/]/ig;
    return str.replace(reg, (match) => (map[match]));
}

/**
 * Return the self path without query string
 * @returns The self path
 */
function selfPath()
{
    return window.location.origin + window.location.pathname
}

/**
 * Enable or Disable DOM elements
 * @param {Array} elements DOM elements list
 * @param {*} state State for all elements
 */
function enableElements(elements, state) {
    for(const elm of elements) {
        if(elm === null) {
            continue
        }
        
        if (state === true) {
            if(elm.classList.contains('disabled')) {
                elm.classList.remove('disabled')
            }
            if(elm.hasAttribute('disabled')) {
                elm.removeAttribute('disabled')
            }
        } else {
            if(!elm.classList.contains('disabled')) {
                elm.classList.add('disabled')
            }
            if(!elm.hasAttribute('disabled')) {
                elm.setAttribute('disabled', 'disabled')
            }
        }
    }
}

/**
 * Show or Hide DOM elements
 * @param {Array} elements DOM elements list
 * @param {*} state State for all elements
 */
 function showElements(elements, state) {
    for(const elm of elements) {
        if(elm === null) {
            continue
        }
        
        if (state === true) {
            if (elm.classList.contains('d-none')) {
                elm.classList.replace('d-none', 'd-block')
            } else {
                elm.classList.add('d-block')
            }
        } else {
            if (elm.classList.contains('d-block')) {
                elm.classList.replace('d-block', 'd-none')
            } else {
                elm.classList.add('d-none')
            }
        }
    }
}

/**
 * Get the Barear Token
 * @returns The Barear Token
 */
function getBarearToken(headers) {
    if (headers.has('authorization') !== true) {
        return '';
    }
    
    return headers.get('authorization').substr(7).trim();
}