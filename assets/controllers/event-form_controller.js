import { Controller } from '@hotwired/stimulus';
import $ from 'jquery';
import 'bootstrap-datepicker';
import 'bootstrap-datepicker/js/locales/bootstrap-datepicker.es.js';
import 'bootstrap-datepicker/js/locales/bootstrap-datepicker.eu.js';
import 'select2';

export default class extends Controller {
    static targets = [ 'userInput', 'halfDay' ];
    static values = {
        locale: String,
        unionHoursType: Number,
    }

    connect() {
        const select2Options = {
            theme: "bootstrap-5",
            language: this.localeValue,
            allowClear: false,
            // We add this to Avoid problems on modals. Do not remove.
            dropdownParent: $('#modal'),
        }
        if ( this.hasUserInputTarget ) {
            $(this.userInputTarget).select2(select2Options);
         }
        const options = {
            format: "yyyy-mm-dd",
            language: this.localeValue,
            weekStart: 1
        }
        $('#event_form_startDate').datepicker(options);
        $('#event_form_endDate').datepicker(options);
        // To set halfDay check on init when editing an event
        this.onHalfDayClick(null);
    }

    onHalfDayClick(e) {
        if ( $('.js-halfDay').is(':checked') ) {
            $('#event_form_hours').removeClass('d-none');
            $('label[for="event_form_hours"]').removeClass('d-none');
            $('#event_form_minutes').removeClass('d-none');
            $('label[for="event_form_minutes"]').removeClass('d-none');
        } else {          
            $('#event_form_hours').addClass('d-none');
            $('label[for="event_form_hours"]').addClass('d-none');
            $('#event_form_minutes').addClass('d-none');
            $('label[for="event_form_minutes"]').addClass('d-none');
        }
    }

    onTypeChange (e) {
        // If selected option y union hours type 
        const unionHours = this.unionHoursTypeValue;
        if ( e.target.value == unionHours ) { 
            $(this.halfDayTarget).prop('checked', true);
            this.onHalfDayClick(e);
        }
    }
}