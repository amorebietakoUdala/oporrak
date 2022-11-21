import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
   static targets = ['year','overtimeDays', 'vacationDays', 'particularBusinessLeave', 'antiquityDays'];
   static values = {
       remainingDaysUrl: String,
   };

   year = null;
   remainingDays = null;

   async refresh(event) {
      this.year = event.detail.year;
      let params = new URLSearchParams({
          year: this.year
      });
      await fetch(`${this.remainingDaysUrlValue}?${params.toString()}`)
          .then(result => result.json())
          .then(result => {
              if (result !== null) {
                  this.remainingDays = result;
                  this.updateTargets();
              }
          });
      return;
  }

  updateTargets() {
    if (this.hasYearTarget) {
        this.yearTarget.innerHTML = this.year
    };
    if (this.hasVacationDaysTarget) {
        this.vacationDaysTarget.innerHTML = this.remainingDays[1] !== null ? this.remainingDays[1] : 0;
    }
    if (this.hasParticularBusinessLeaveTarget) {
        this.particularBusinessLeaveTarget.innerHTML = this.remainingDays[2] !== null ? this.remainingDays[2] : 0;    
    }
    if (this.hasOvertimeDaysTarget) {
        this.overtimeDaysTarget.innerHTML = this.remainingDays[3] !== null ? this.remainingDays[3] : 0;
    }
    if (this.hasAntiquityDaysTarget) {
        this.antiquityDaysTarget.innerHTML = this.remainingDays[4] !== null ? this.remainingDays[4] : 0;
    }
    return this;
  }

}