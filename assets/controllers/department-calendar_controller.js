import {
    Controller
} from 'stimulus';
import $, { type } from 'jquery';
import Calendar from 'js-year-calendar';
import 'js-year-calendar/locales/js-year-calendar.es';
import 'js-year-calendar/locales/js-year-calendar.eu';
import { workingDaysBetween } from '../js/dateUtils';

import {
    useDispatch
} from 'stimulus-use';

//import 'bootstrap-datepicker';
const routes = require('../../public/js/fos_js_routes.json');
import Routing from '../../vendor/friendsofsymfony/jsrouting-bundle/Resources/public/js/router.min.js';
export default class extends Controller {
    static targets = ['events', 'holidays', 'workdays', 'holidaysLegend', 'approved'];
    static values = {
        locale: String,
        holidaysUrl: String,
        holidaysColor: String,
        departmentDatesUrl: String,
        year: String,
        colorPalette: Array,
        roles: Array,
        //   // How many days before can we edit calendar
        //   days: String,
    };

    calendar = null;
    calendarDates = null;
    holidays = null;
    counters = null;
    approved = 0;

    connect() {
        Routing.setRoutingData(routes);
        useDispatch(this, {
            debug: true
        });
        //useDispatch(this);
        console.log(this.rolesValue);
        this.calendar = new Calendar('#calendar', {
            enableContextMenu: true,
            enableRangeSelection: false,
            language: this.localeValue,
            //            style: 'background',
            startYear: this.yearValue,
            disabledWeekDays: [0, 6],
            style: 'border',
            // contextMenuItems: (this.rolesValue) => {
            //         return false;
            //     }
            // contextMenuItems: [
            //    {
            //         text: 'Update',
            //         click: (event) => {
            //             console.log('event');
            //             this.editEvent(event);
            //         }
            //     },
            //  {
            //      text: 'Delete',
            //      click: (event) => {
            //          this.deleteEvent(event);
            //      }
            //  }
            // ],
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
                        console.log(e.events[i]);
                        content += '<div class="event-tooltip-content">';
                        if (typeof(e.events[i].id) != "undefined") {
                            content += '<div class="event-id">Id: ' + e.events[i].id + '</div>';
                        }
                        content += '<div class="event-name" style="color:' + e.events[i].color + '">' + e.events[i].name + '</div>';
                        if (typeof(e.events[i].type) == "undefined" && typeof(e.events[i].status) != "undefined") {
                            content += '<div class="event-status">' + e.events[i].status + '</div>';
                            content += '<div class="event-user">' + 'User: ' + e.events[i].user + '</div>';
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
                this.load(event.currentYear);
                this.dispatch('yearChanged', { year });
            },
        });
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
        let dates = await fetch(`${this.departmentDatesUrlValue}?${params.toString()}`)
            .then(result => result.json())
            .then(result => {
                if (result.items) {
                    //   this.counters = this.createCounters(result.items, this.holidays);
                    //   this.updateCounters();
                    //   let workdays = this.calculateWorkDays(result.items);
                    // this.holidaysLegendTarget.innerHTML = this.holidays.length;
                    // this.workdaysTarget.innerHTML = workdays;
                    let colorArray = this.assignColor(result.items);

                    return result.items.map(r => ({
                        id: r.id,
                        startDate: new Date(r.startDate),
                        endDate: new Date(r.endDate),
                        name: r.name,
                        statusId: r.status.id,
                        status: r.status.description,
                        user: r.user.username,
                        color: colorArray[r.user.username],
                    }));
                }
            }).then(dates => {
                this.dates = dates;
                this.addDates(this.holidays);
                // console.log(this.dates);
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

    assignColor(items) {
        let colorArray = [];
        let i = 0;
        let totalColors = this.colorPaletteValue.length;
        //        console.log(this.colorPaletteValue, this.colorPaletteValue.length);
        items.forEach(item => {
            //            console.log(item.type, item.user.username);
            if (typeof(item.type) === 'undefined' && typeof(item.user.username) !== 'undefined') {
                if (!colorArray.hasOwnProperty(item.user.username)) {
                    colorArray[item.user.username] = this.colorPaletteValue[i % totalColors];
                    i++;
                }
                //                console.log(colorArray, i);
            }
        });
        return colorArray;
    }
}