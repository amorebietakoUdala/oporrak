import {
    Controller
} from 'stimulus';
import $, { type } from 'jquery';
import Calendar from 'js-year-calendar';
import 'js-year-calendar/locales/js-year-calendar.es';
import 'js-year-calendar/locales/js-year-calendar.eu';

import {
    useDispatch
} from 'stimulus-use';

import Translator from 'bazinga-translator';
const translations = require('../../public/translations/' + Translator.locale + '.json');
import '@fortawesome/fontawesome-free/js/all.js';
const routes = require('../../public/js/fos_js_routes.json');
import Routing from '../../vendor/friendsofsymfony/jsrouting-bundle/Resources/public/js/router.min.js';

export default class extends Controller {
    static targets = ['events', 'holidays', 'workdays', 'holidaysLegend', 'approved', 'userSelect', 'departmentSelect'];
    static values = {
        locale: String,
        holidaysUrl: String,
        holidaysColor: String,
        departmentDatesUrl: String,
        departmentUsersUrl: String,
        year: String,
        status: String,
    };

    calendar = null;
    calendarDates = null;
    holidays = null;
    counters = null;
    approved = 0;

    connect() {
        Routing.setRoutingData(routes);
        useDispatch(this);
        Translator.fromJSON(translations);
        Translator.locale = this.localeValue;
        this.calendar = new Calendar('#calendar', {
            enableContextMenu: true,
            enableRangeSelection: false,
            language: this.localeValue,
            startYear: this.yearValue,
            disabledWeekDays: [0, 6],
            style: 'border',
            selectRange: (event) => {
                this.openModal();
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
                        content += '<div class="event-name" style="color:' + e.events[i].color + '">' + e.events[i].name + '</div>';
                        if (typeof(e.events[i].type) == "undefined" && typeof(e.events[i].status) != "undefined") {
                            content += '<div class="event-status">'+ Translator.trans('label.status') + ': ' + Translator.trans(e.events[i].status, {}, 'messages') + '</div>';
                            content += '<div class="event-user">' + Translator.trans('label.user') + ': ' + e.events[i].user + '</div>';
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
            clickDay: (e) => {
                this.dispatch('clickDay', { date: e.date, events: e.events });
            },
            dayContextMenu: function(e) {
                $(e.element).popover('hide');
            },
            yearChanged: (event) => {
                // It makes a year changed on init, so it doesn't need another load after this.
                let year = event.currentYear;
                let user = $(this.userSelectTarget).val();
                let department = null;
                if ( this.hasDepartmentSelectTarget ) {
                    department = $(this.departmentSelectTarget).val();
                }
                this.load(event.currentYear, user, department, this.statusValue);
                this.dispatch('yearChanged', { year });
            },
        });
    }

    async load(year, user, department, status) {
        let params = {
            year: year
        }
        let urlParams = new URLSearchParams(params);
        this.holidays = await fetch(`${this.holidaysUrlValue}?${urlParams.toString()}`)
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
        if ( '' !== user) {
            params.user = user;
        }
        if ( null !== department && '' !== department ) {
            params.department = department;
        }
        if (null !== status && '' !== status) {
            params.status = status;
        } 
        urlParams = new URLSearchParams(params);
        let dates = await fetch(`${this.departmentDatesUrlValue}?${urlParams.toString()}`)
            .then(result => result.json())
            .then(result => {
                if (result.items) {
                    return result.items.map(r => ({
                        id: r.id,
                        startDate: new Date(r.startDate),
                        endDate: new Date(r.endDate),
                        name: r.name,
                        statusId: r.status.id,
                        status: Translator.trans(r.status.description, {}, 'messages'),
                        user: r.user.username,
                        color: r.status.color,
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

    refreshCalendar(event) {
        let user = event.detail.user;
        let department = event.detail.department === "" ? null : event.detail.department;
        this.load(this.calendar.getYear(), user, department, this.statusValue);
    }
}