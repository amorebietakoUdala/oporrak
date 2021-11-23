import {
   Controller
} from 'stimulus';

export default class extends Controller {
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
}