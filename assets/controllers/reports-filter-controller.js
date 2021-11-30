import {
   Controller
} from 'stimulus';

import $ from 'jquery';

import '../js/common/datepicker';
import '../js/common/list';

export default class extends Controller {
   static targets = ['list'];

    static values = {
        locale: String,
    }

   connect() {
      const options = {
         format: "yyyy-mm-dd",
         language: this.localeValue,
         weekStart: 1
     }
     $('.js-datepicker').datepicker(options);
   }

   clean(event) {
      event.preventDefault();
      $('.js-datepicker').val('');
   }
   
}