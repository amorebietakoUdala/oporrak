import {
    Controller
} from 'stimulus';
import $ from 'jquery';
import Calendar from 'js-year-calendar';
import 'js-year-calendar/locales/js-year-calendar.es';
import 'js-year-calendar/locales/js-year-calendar.eu';

import {
    useDispatch
} from 'stimulus-use';

import Translator, { defaultDomain } from 'bazinga-translator';
const translations = require('../../public/translations/' + Translator.locale + '.json');
import '@fortawesome/fontawesome-free/js/all.js';
const routes = require('../../public/js/fos_js_routes.json');
import Routing from '../../public/js/router.min.js';

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
        department: String,
        colorPalette: Array,
        type: String,
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
                        content += '<div class="event-name" style="color:' + e.events[i].color + '">' + e.events[i].type;
                        if ( e.events[i].startHalfDay ) {
                            content += " (" + e.events[i].hours + "h.)";
                        }
                        content += '</div>';
                        if (typeof(e.events[i].status) != "undefined") {
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
                let department = this.departmentValue;
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
        if ( user.length > 0) {
            params.user = user;
        }
        if ( null !== department && '' !== department ) {
            params.department = department;
        }
        if (null !== status && '' !== status) {
            params.status = status;
        } 
        urlParams = new URLSearchParams(params);
        let colorArray = null;
        let dates = await fetch(`${this.departmentDatesUrlValue}?${urlParams.toString()}`)
            .then(result => result.json())
            .then(result => {
                if (result.items) {
                    colorArray = this.assignColor(result.items);
                    return result.items.map(r => ({
                        id: r.id,
                        startDate: new Date(r.startDate),
                        endDate: new Date(r.endDate),
//                        name: this.localeValue == 'es' ? r.type.descriptionEs : r.type.descriptionEu,
                        statusId: r.status.id,
                        status: Translator.trans(r.status.description, {}, 'messages'),
                        color: this.typeValue == 'department' ? colorArray[r.user.username] : r.status.color,
                        startHalfDay: r.halfDay,
                        hours: r.hours,
                        type: this.localeValue == 'es' ? r.type.descriptionEs : r.type.descriptionEu,
                        user: r.user.username,
                        usePreviousYearDays: r.usePreviousYearDays,
                    }));
                }
            }).then(dates => {
                this.dates = dates;
                this.addDates(this.holidays);
                this.calendar.setDataSource(this.dates);
                this.dispatch('loaded', { colorArray, year });
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
    
    assignColor(items) {
        let colorArray = [];
        let i = 0;
        let totalColors = this.colorPaletteValue.length;
        items.forEach(item => {
            if (!colorArray.hasOwnProperty(item.user.username)) {
                colorArray[item.user.username] = this.colorPaletteValue[i % totalColors];
                i++;
            }
        });
        return colorArray;
    }

    async deleteEvent(event) {
        event.preventDefault();
        const id = event.currentTarget.dataset.eventid;
        import ('sweetalert2').then(async(Swal) => {
          Swal.default.fire({
              template: '#my-template'
          }).then(async(result) => {
              if (result.value) {
                  let url = app_base + Routing.generate('event_delete', { _locale: global.locale, event: id });
                  await $.ajax({
                      url: url,
                      method: 'GET'
                  }).then(() => {
                      var dataSource = this.calendar.getDataSource();
                      this.calendar.setDataSource(dataSource.filter(item => item.id != id));
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
}