import {
    Controller
} from 'stimulus';
import $ from 'jquery';
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
import Routing from '../../vendor/friendsofsymfony/jsrouting-bundle/Resources/public/js/router.min.js';
export default class extends Controller {
    static targets = ['modal', 'modalBody', 'content', 'events', 'holidays', 'workdays', 'holidaysLegend', 'approved'];
    static values = {
        locale: String,
        holidaysUrl: String,
        holidaysColor: String,
        myDatesUrl: String,
        datesServiceUrl: String,
        formUrl: String,
        year: String,
    };

    calendar = null;
    calendarDates = null;
    holidays = null;
    counters = null;
    approved = 0;

    connect() {
        Routing.setRoutingData(routes);
        // useDispatch(this, {
        //     debug: true
        // });
        useDispatch(this);
        this.modal = new Modal(this.modalTarget);
        this.calendar = new Calendar('#calendar', {
            enableContextMenu: true,
            enableRangeSelection: true,
            language: this.localeValue,
            style: 'background',
            startYear: this.yearValue,
            contextMenuItems: [{
                    text: 'Update',
                    click: (event) => {
                        console.log('event');
                        this.editEvent(event);
                    }
                },
                {
                    text: 'Delete',
                    click: (event) => {
                        this.deleteEvent(event);
                    }
                }
            ],
            selectRange: (event) => {
                this.openModal();
                this.editEvent({
                    startDate: event.startDate,
                    endDate: event.endDate,
                });
            },
            mouseOnDay: function(e) {
                console.log(e);
                if (e.events.length > 0) {
                    var content = '';

                    for (var i in e.events) {
                        content += '<div class="event-tooltip-content">';
                        if (typeof(e.events[i].id) != "undefined") {
                            content += '<div class="event-id">Id: ' + e.events[i].id + '</div>';
                        }
                        content += '<div class="event-name" style="color:' + e.events[i].color + '">' + e.events[i].name + '</div>';
                        if (typeof(e.events[i].type) == "undefined" && typeof(e.events[i].status) != "undefined") {
                            content += '<div class="event-status">' + e.events[i].status + '</div>';
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
            dayContextMenu: function(e) {
                $(e.element).popover('hide');
            },
            yearChanged: (event) => {
                // It makes a year changed on init, so it doesn't need another load after this.
                console.log('yearChanged');
                let year = event.currentYear;
                this.load(event.currentYear);
                this.dispatch('yearChanged', { year });
            },
        });
        //        this.load(this.yearValue);
    }

    openModal() {
        this.modal.show();
    }

    editEvent(event) {
        console.log('editEvent', event);
        $('#event_form_id').val(event ? event.id : '');
        $('#event_form_name').val(event ? event.name : '');
        $('#event_form_startDate').datepicker('update', event ? event.startDate : '');
        $('#event_form_endDate').datepicker('update', event ? event.endDate : '');
        $('#event_form_status').val(event ? event.statusId : '');
        this.openModal();
        //      let respose = await fetch(`${this.holidaysUrlValue}?${params.toString()}`)
    }

    async deleteEvent(event) {
        console.log('Delete event: ', event);
        import ('sweetalert2').then(async(Swal) => {
            Swal.default.fire({
                template: '#my-template'
            }).then(async(result) => {
                console.log(result);
                if (result.value) {
                    let url = app_base + Routing.generate('event_delete', { _locale: global.locale, event: event.id });
                    await $.ajax({
                        url: url,
                        method: 'GET'
                    }).then((response) => {
                        var dataSource = this.calendar.getDataSource();
                        this.calendar.setDataSource(dataSource.filter(item => item.id != event.id));
                    }).catch((err) => {
                        console.log(err);
                        Swal.default.fire('There was an error!!!');
                    });
                }
            });
        });
    }

    async submitForm(event) {
        const $form = $(this.modalBodyTarget).find('form');
        try {
            console.log($form.serialize);
            await $.ajax({
                url: this.formUrlValue,
                method: $form.prop('method'),
                data: $form.serialize()
            });
            console.log(event);
            this.refreshCalendar();

            this.modal.hide();
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
                    this.counters = this.createCounters(result.items);
                    this.updateCounters();
                    let workdays = this.calculateWorkDays(result.items);
                    // this.holidaysTarget.innerHTML = this.holidays.length;
                    this.holidaysLegendTarget.innerHTML = this.holidays.length;
                    // let days = this.calculateDays(result.items);
                    // this.eventsTarget.innerHTML = days;
                    this.workdaysTarget.innerHTML = workdays;

                    return result.items.map(r => ({
                        id: r.id,
                        startDate: new Date(r.startDate),
                        endDate: new Date(r.endDate),
                        name: r.name,
                        statusId: r.status.id,
                        status: r.status.description,
                        color: r.status.color,
                    }));
                }
            }).then(dates => {
                this.dates = dates;
                this.addDates(this.holidays);
                console.log(this.dates);
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

    daysBeetween(startDate, endDate) {
        const date1 = Date.parse(startDate);
        const date2 = Date.parse(endDate);
        const diffTime = Math.abs(date2 - date1);
        const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
        return diffDays + 1;
    }

    calculateDays(events) {
        let days = 0;
        events.forEach(element => {
            days += this.daysBeetween(element.startDate, element.endDate);
        });
        console.log(days);
        return days;
    }

    calculateWorkDays(events) {
        let totalDays = 0;
        let days = 0;
        let holidays = 0;
        let workdays = 0;
        events.forEach(element => {
            days = this.daysBeetween(element.startDate, element.endDate);
            holidays = this.holidaysBeetween(element.startDate, element.endDate);
            workdays = days - holidays;
            totalDays += workdays;
        });
        return totalDays;
    }

    holidaysBeetween(startDate, endDate) {
        let holidays = 0;
        this.holidays.forEach(element => {
            // Holidays has always same startDate and endDate so we only have to check one.
            if (new Date(element.startDate) >= new Date(startDate) && new Date(element.endDate) <= new Date(endDate)) {
                holidays += 1;
            }
        })
        return holidays;
    }

    workdaysBeetween(startDate, endDate) {
        console.log(this.daysBeetween(startDate, endDate), this.holidaysBeetween(startDate, endDate));
        return this.daysBeetween(startDate, endDate) - this.holidaysBeetween(startDate, endDate);
    }

    createCounters(events) {
        let counters = [];
        this.approved = 0;
        events.forEach(element => {
            if (typeof(counters[element.status.id]) === 'undefined') {
                counters[element.status.id] = this.workdaysBeetween(element.startDate, element.endDate);
            } else {
                counters[element.status.id] += this.workdaysBeetween(element.startDate, element.endDate);
            }
            if (element.status.id === 2) {
                this.approved += this.workdaysBeetween(element.startDate, element.endDate);
            }
        });
        return counters;
    }

    updateCounters() {
        $('.color-square').text(0);
        Object.entries(this.counters).forEach(([key, value]) => {
            console.log(`${key}: ${value}`);
            console.log($('#colorSquare' + key));
            $('#colorSquare' + key).text(value);
        });
        console.log(this.approvedTarget);
        this.approvedTarget.innerHTML = this.approved;
    }

}