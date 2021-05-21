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
// import {
//    useDispatch
// } from 'stimulus-use';

import 'bootstrap-datepicker';

export default class extends Controller {
   static targets = ['modal', 'modalBody', 'content'];
   static values = {
      locale: String,
      holidaysUrl: String,
      holidaysColor: String,
      myDatesUrl: String,
      datesServiceUrl: String,
      formUrl: String,
   };

   calendar = null;
   calendarDates = null;
   holidays = null;

   connect() {
      // useDispatch(this, {
      //    debug: true
      // });
      this.load();
      this.modal = new Modal(this.modalTarget);
      this.calendar = new Calendar('#calendar', {
         enableContextMenu: true,
         enableRangeSelection: true,
         language: this.localeValue,
         style: 'background',
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
         mouseOnDay: function (e) {
            console.log(e);
            if (e.events.length > 0) {
               var content = '';

               for (var i in e.events) {
                  content += '<div class="event-tooltip-content">';
                  if (typeof (e.events[i].id) != "undefined") {
                     content += '<div class="event-id">Id: ' + e.events[i].id + '</div>';
                  }
                  content += '<div class="event-name" style="color:' + e.events[i].color + '">' + e.events[i].name + '</div>';
                  if (typeof (e.events[i].type) == "undefined" && typeof (e.events[i].status) != "undefined") {
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
         mouseOutDay: function (e) {
            if (e.events.length > 0) {
               $(e.element).popover('hide');
            }
         },
         dayContextMenu: function (e) {
            $(e.element).popover('hide');
         },
      });
   }

   openModal() {
      console.log(this.modalTarget);
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

   deleteEvent(event) {
      console.log('Delete event: ', event);
      var dataSource = this.calendar.getDataSource();
      this.calendar.setDataSource(dataSource.filter(item => item.id == event.id));
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
         // $('#event-modal').modal('hide');
         // this.dispatch('success');
         this.refreshCalendar(event);
         this.modal.hide();
      } catch (e) {
         this.modalBodyTarget.innerHTML = e.responseText;
      }
   }

   async load() {
      const params = new URLSearchParams({
         year: new Date().getFullYear()
      });
      console.log(this.holidaysColorValue);
      let holidays = await fetch(`${this.holidaysUrlValue}?${params.toString()}`)
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
      // const params = new URLSearchParams({
      //    year: new Date().getFullYear()
      // });
      // let holidays = await fetch(`${this.holidaysUrlValue}?${params.toString()}`)
      //    .then(result => result.json())
      //    .then(result => {
      //       if (result.items) {
      //          return result.items.map(r => ({
      //             startDate: new Date(r.startDate),
      //             endDate: new Date(r.endDate),
      //             name: r.name,
      //             color: r.color,
      //          }));
      //       }
      //    });
      await fetch(this.myDatesUrlValue)
         .then(result => result.json())
         .then(result => {
            if (result.items) {
               //               console.log(result.items);
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
            this.addDates(holidays);
            console.log(this.dates);
            this.calendar.setDataSource(this.dates);
         });
   }

   addDates(dates) {
      dates.forEach(element => {
         this.dates.push(element);
      });
   }

   refreshCalendar(event) {
      console.log("RefreshCalendar");
      this.load();
   }


}