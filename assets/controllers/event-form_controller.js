import {
   Controller
} from 'stimulus';
import $ from 'jquery';
import 'bootstrap-datepicker';
import {
   useDispatch
} from 'stimulus-use';

export default class extends Controller {
   static values = {
      locale: String,
   }

   connect() {
      useDispatch(this, {
         debug: true
      });
      let $startDate = $('#event_form_startDate').datepicker({
         format: "yyyy-mm-dd",
         language: this.localeValue
      });
      $startDate.datepicker('update', event ? event.startDate : '');
      let $endDate = $('#event_form_endDate').datepicker({
         format: "yyyy-mm-dd",
         language: this.localeValue
      });
      $endDate.datepicker('update', event ? event.endDate : '');
   }

   async submitForm(event) {
      console.log('Event form');
      event.preventDefault();
      const $form = $(this).find('form');

      try {
         await $.ajax({
            url: this.formUrlValue,
            method: $form.prop('method'),
            data: $form.serialize(),
         });
         this.modal.hide();
         this.dispatch('success');
      } catch (e) {
         this.modalBodyTarget.innerHTML = e.responseText;
      }
   }

}