import { Controller } from '@hotwired/stimulus';
import $ from 'jquery';
import { Modal } from 'bootstrap';
import Calendar from 'js-year-calendar';
import 'js-year-calendar/locales/js-year-calendar.es';
import 'js-year-calendar/locales/js-year-calendar.eu';

import Translator, { defaultDomain } from 'bazinga-translator';
const translations = require('../../public/translations/' + Translator.locale + '.json');
import '@fortawesome/fontawesome-free/js/all.js';
const routes = require('../../public/js/fos_js_routes.json');
import Routing from '../../public/js/router.min.js';

export default class extends Controller {
    static targets = ['events', 'holidays', 'workdays', 'holidaysLegend', 'approved', 'userSelect', 'departmentSelect','modal', 'modalBody', 'modalTitle'];
    static values = {
        locale: String,
        holidaysUrl: String,
        holidaysColor: String,
        departmentDatesUrl: String,
        departmentUsersUrl: String,
        formUrl: String,
        year: String,
        status: String,
        department: String,
        colorPalette: Array,
        type: String,
        enableRangeSelection: Boolean,
    };

    calendar = null;
    calendarDates = null;
    holidays = null;
    counters = null;
    approved = 0;

    connect() {
        Routing.setRoutingData(routes);
        Translator.fromJSON(translations);
        Translator.locale = this.localeValue;
        if ( this.hasModalTarget) {
            this.modal = new Modal(this.modalTarget);
        }
        this.calendar = new Calendar('#calendar', {
            enableRangeSelection: this.enableRangeSelectionValue == true,
            language: this.localeValue,
            startYear: this.yearValue,
            disabledWeekDays: [0, 6],
            style: 'border',
            selectRange: (event) => {
                //this.openModal(event);
                this.addEvent({
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
                this.dispatch('clickDay', { detail:{ date: e.date, events: e.events }});
            },
            dayContextMenu: function(e) {
                $(e.element).popover('hide');
            },
            yearChanged: (event) => {        
                this.refreshYear(event.currentYear);
            }
        });
    }

    refreshYear(year) {
        // It makes a year changed on init, so it doesn't need another load after this.
        let user = $(this.userSelectTarget).val();
        let department = this.departmentValue;
        if ( this.hasDepartmentSelectTarget ) {
            department = $(this.departmentSelectTarget).val();
        }
        this.load(year, user, department, this.statusValue);
        this.dispatch('yearChanged', { detail: { year }});
    }

    hasHoliday(events) {
        for (var [key, value] of Object.entries(events)) {
            if (value.type === 'holiday') {
                return true;
            }
        }
        return false;
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
            params.users = user;
        }
        if ( null !== department && '' !== department ) {
            params.department = department;
        }
        if (null !== status && '' !== status) {
            params.status = status;
        }
        if (null !== this.typeValue && this.typeValue === 'city-hall') {
            params.calendar = this.typeValue;
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
                        status: this.localeValue == 'es' ? r.status.descriptionEs : r.status.descriptionEu,
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
                this.dispatch('loaded', { detail: { colorArray, year }});
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
            import ('sweetalert2').then(async(Swal) => {
                Swal.default.fire('There was an error!!!');
            });
        });

    }

    addEvent(event) {
        this.fillEvent(event);
        this.openModal(event);
    }

    fillEvent(event) {
        $('#event_form_id').val(event ? event.id : '');
        $('#event_form_name').val(event ? event.name : '');
        $('#event_form_startDate').datepicker('update', event ? event.startDate : '');
        $('#event_form_endDate').datepicker('update', event ? event.endDate : '');
        $('#event_form_status').val(event ? event.statusId : '');
        $('#event_form_halfDay').val(event ? event.halfDay : '');
        $('#event_form_hours').val(event ? event.hours : '');
    }

    async editEvent(event) {
        try {
            event.preventDefault();
            this.modalTitleTarget.innerHTML = Translator.trans('modal.title.event.edit');
            const id = event.currentTarget.dataset.eventid;
            let url = app_base + Routing.generate('event_edit', { _locale: global.locale, event: id });
            await $.ajax({
                url: url,
            }).then((response) => {
                this.modalBodyTarget.innerHTML = response;
                this.modal.show();
                });
            } catch (e) {
                this.modalBodyTarget.innerHTML = e.responseText;
            }
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
                this.dispatch('update', { detail: { year }});
                this.refreshYear(year);
                this.modal.hide();
            });
        } catch (e) {
            this.modalBodyTarget.innerHTML = e.responseText;
        }
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
                      this.dispatch('update', { detail: { year }});
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