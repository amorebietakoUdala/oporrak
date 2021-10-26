import {
    Controller
} from 'stimulus';
import $ from 'jquery';
import 'bootstrap-datepicker';
import 'bootstrap-datepicker/js/locales/bootstrap-datepicker.es.js';
import 'bootstrap-datepicker/js/locales/bootstrap-datepicker.eu.js';

export default class extends Controller {
    static values = {
        locale: String,
    }

    connect() {
        const options = {
            format: "yyyy-mm-dd",
            language: this.localeValue,
            weekStart: 1
        }
        $('#event_form_startDate').datepicker(options);
        $('#event_form_endDate').datepicker(options);
    }
}