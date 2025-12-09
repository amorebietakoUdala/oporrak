import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
   static targets = ['unionHoursPerMonth', 'unionDelegate'];
   static values = {
      unionHoursPerMonth: Number
   };

   connect () {
      this.unionDelegateTarget.checked ? this.unionHoursPerMonthTarget.disabled = false : this.unionHoursPerMonthTarget.disabled = true;
   }

   onUnionDelegateChange(e) {
      if (this.unionDelegateTarget.checked) {
         this.unionHoursPerMonthTarget.disabled = false;
         this.unionHoursPerMonthTarget.value = this.unionHoursPerMonthValue;
      } else {
         this.unionHoursPerMonthTarget.disabled = true;
         this.unionHoursPerMonthTarget.value = null;
      }
   }

}