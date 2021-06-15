/*
 * Welcome to your app's main JavaScript file!
 *
 * We recommend including the built version of this JavaScript file
 * (and its CSS file) in your base layout (base.html.twig).
 */

// any CSS you import will output into a single css file (app.css in this case)
import './styles/app.css';

// start the Stimulus application
import './bootstrap';

import 'bootstrap';
import 'popper.js';

global.app_base = '/oporrak';

$(function() {
    global.locale = $('html').attr("lang");
    $('.js-back').on('click', function(e) {
        e.preventDefault();
        var url = e.currentTarget.dataset.url;
        document.location.href = url;
    });
});