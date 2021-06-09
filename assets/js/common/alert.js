import Translator from 'bazinga-translator';
const translations = require('../../../public/translations/' + Translator.locale + '.json');

import Swal from 'sweetalert2';

export function showAlert(title, html, confirmationButtonText, cancelButtonText, confirmURL) {
    Swal.fire({
        title: title,
        html: html,
        type: 'warning',
        showCancelButton: true,
        cancelButtonText: cancelButtonText,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: confirmationButtonText,
    }).then(function(result) {
        if (result.value) {
            document.location.href = confirmURL;
        }
    });
}

export function createConfirmationAlert(confirmURL) {
    Translator.fromJSON(translations);
    showAlert(
        Translator.trans('messages.confirmacion'),
        Translator.trans('messages.confirmationDetail'),
        Translator.trans('messages.si'),
        Translator.trans('messages.no'),
        confirmURL
    );
}