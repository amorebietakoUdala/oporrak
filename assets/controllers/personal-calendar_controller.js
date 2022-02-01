import {
    Controller
} from 'stimulus';
import $, { type } from 'jquery';
import {
    Modal
} from 'bootstrap';
import Calendar from 'js-year-calendar';
import 'js-year-calendar/locales/js-year-calendar.es';
import 'js-year-calendar/locales/js-year-calendar.eu';

import {
    useDispatch
} from 'stimulus-use';

import 'bootstrap-datepicker';
const routes = require('../../public/js/fos_js_routes.json');
import Routing from '../../public/js/router.min.js';

import Translator from 'bazinga-translator';
const translations = require('../../public/translations/' + Translator.locale + '.json');

export default class extends Controller {
    static targets = ['modal', 'modalBody'];
    static values = {
        locale: String,
        holidaysUrl: String,
        holidaysColor: String,
        myDatesUrl: String,
        datesServiceUrl: String,
        // To save or get eventForm
        formUrl: String,
        year: String,
        // How many days before can we edit calendar
        days: String,
    };

    calendar = null;
    holidays = null;

    connect() {
        Routing.setRoutingData(routes);
        Translator.fromJSON(translations);
        Translator.locale = this.localeValue;
        useDispatch(this);
        this.modal = new Modal(this.modalTarget);
        this.calendar = new Calendar('#calendar', {
            enableContextMenu: true,
            enableRangeSelection: true,
            language: this.localeValue,
            style: 'background',
            startYear: this.yearValue,
            disabledWeekDays: [0, 6],
            contextMenuItems: [
                {
                    text: Translator.trans('btn.delete'),
                    click: (event) => {
                        this.deleteEvent(event);
                    },
                    /* Show context menu only when it's not holiday */
                    visible: (event) => {
                        let events = [];
                        events.push(event);
                        if (this.hasHoliday(events)) {
                            return false;
                        }
                        return true;
                    }
                }
            ],
            selectRange: (event) => {
                this.openModal(event);
                this.editEvent({
                    startDate: event.startDate,
                    endDate: event.endDate,
                });
            },
            mouseOnDay: function(e) {
                if (e.events.length > 0) {
                    var content = '';

                    for (var i in e.events) {
                        content += '<div class="event-tooltip-content">';
                        if (typeof(e.events[i].id) != "undefined") {
                            content += '<div class="event-id">Id: ' + e.events[i].id + '</div>';
                        } 
                        content += '<div class="event-name" style="color:' + e.events[i].color + '">';
                        if (typeof(e.events[i].name) != "undefined" && e.events[i].type == 'holiday') {
                            content += e.events[i].type + ': ' + e.events[i].name
                        } else {
                            if ( !e.events[i].startHalfDay ) {
                                content += e.events[i].type;
                            } else {
                                content += e.events[i].type + " (" + e.events[i].hours + "h.)";
                            }
                        }
                        content += '</div>'
                        if (typeof(e.events[i].status) != "undefined") {
                            content += '<div class="event-status">' + Translator.trans('label.status')+ ': ' + Translator.trans(e.events[i].status, {}, 'messages') + '</div>';
                        }
                        content += '</div>';
                    }

                    $(e.element).popover({
                        trigger: 'manual',
                        container: 'body',
                        html: true,
                        content: content
                    });

                    $(e.element).popover('show');
                }
            },
            mouseOutDay: function(e) {
                if (e.events.length > 0) {
                    $(e.element).popover('hide');
                }
            },
            dayContextMenu: (e) => {
                $(e.element).popover('hide');
            },
            yearChanged: (event) => {
                // It makes a year changed on init, so it doesn't need another load after this.
                let year = event.currentYear;
                this.load(event.currentYear);
                this.dispatch('yearChanged', { year });
            },
        });
    }

    hasHoliday(events) {
        for (var [key, value] of Object.entries(events)) {
            if (value.type === 'holiday') {
                return true;
            }
        }
        return false;
    }
    
    async openModal(event) {
        let $alert = $(this.modalBodyTarget).find('.alert');
        $alert.remove();
        await $.ajax({
            url: this.formUrlValue,
            method: 'GET'
        }).then((response) => {
            this.modalBodyTarget.innerHTML = response;
            const options = {
                format: "yyyy-mm-dd",
                language: this.localeValue,
                weekStart: 1
            }
            $('#event_form_startDate').datepicker(options);
            $('#event_form_endDate').datepicker(options);
            $('#event_form_startDate').datepicker('update', event ? event.startDate : '');
            $('#event_form_endDate').datepicker('update', event ? event.endDate : '');
            this.modal.show();
        }).catch((err) => {
            Swal.default.fire('There was an error!!!');
        });

    }

    editEvent(event) {
        $('#event_form_id').val(event ? event.id : '');
        $('#event_form_name').val(event ? event.name : '');
        $('#event_form_startDate').datepicker('update', event ? event.startDate : '');
        $('#event_form_endDate').datepicker('update', event ? event.endDate : '');
        $('#event_form_status').val(event ? event.statusId : '');
        $('#event_form_halfDay').val(event ? event.halfDay : '');
        $('#event_form_hours').val(event ? event.hours : '');
        this.openModal(event);
    }

    async deleteEvent(event) {
          import ('sweetalert2').then(async(Swal) => {
            Swal.default.fire({
                template: '#my-template'
            }).then(async(result) => {
                if (result.value) {
                    let url = app_base + Routing.generate('event_delete', { _locale: global.locale, event: event.id });
                    await $.ajax({
                        url: url,
                        method: 'GET'
                    }).then(() => {
                        var dataSource = this.calendar.getDataSource();
                        this.calendar.setDataSource(dataSource.filter(item => item.id != event.id));
                        let year = this.calendar.getYear();
                        this.dispatch('update', { year });
                    }).catch((err) => {
                        Swal.default.fire({
                            template: '#error',
                            html: err.responseText
                        });
                    });
                }
            });
        });
    }

    async submitForm(event) {
        const $form = $(this.modalBodyTarget).find('form');
        try {
            await $.ajax({
                url: this.formUrlValue,
                method: $form.prop('method'),
                data: $form.serialize()
            }).then(() => {
                let year = this.calendar.getYear();
                this.dispatch('update', { year });
                this.refreshCalendar();
                this.modal.hide();
            });
        } catch (e) {
            this.modalBodyTarget.innerHTML = e.responseText;
        }
    }

    async load(year) {
        let params = new URLSearchParams({
            year: year
        });
        this.holidays = await fetch(`${this.holidaysUrlValue}?${params.toString()}`)
            .then(result => result.json())
            .then(result => {
                if (result) {
                    return result.map(r => ({
                        startDate: new Date(r.date),
                        endDate: new Date(r.date),
                        name: this.localeValue == 'es' ? r.descriptionEs : r.descriptionEu,
                        type: 'holiday',
                        color: this.holidaysColorValue,
                    }));
                }
            });
        let dates = await fetch(`${this.myDatesUrlValue}?${params.toString()}`)
            .then(result => result.json())
            .then(result => {
                if (result.items) {
                    return result.items.map(r => ({
                        id: r.id,
                        startDate: new Date(r.startDate),
                        endDate: new Date(r.endDate),
                        name: r.name == null ? r.type.descriptionEs : r.type.descriptionEu,
                        statusId: r.status.id,
                        status: r.status.description,
                        color: r.status.color,
                        startHalfDay: r.halfDay,
                        hours: r.hours,
                        type: this.localeValue == 'es' ? r.type.descriptionEs : r.type.descriptionEu,
                    }));
                }
            }).then(dates => {
                this.dates = dates;
                this.addDates(this.holidays);
                this.calendar.setDataSource(this.dates);
            });
    }

    addDates(dates) {
        dates.forEach(element => {
            this.dates.push(element);
        });
    }

    refreshCalendar() {
        this.load(this.calendar.getYear());
    }
}