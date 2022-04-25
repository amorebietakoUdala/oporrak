import {
   Controller
} from 'stimulus';

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
        this.vacationDaysTarget.innerHTML = this.remainingDays[1];
    }
    if (this.hasParticularBusinessLeaveTarget) {
        this.particularBusinessLeaveTarget.innerHTML = this.remainingDays[2];    
    }
    if (this.hasOvertimeDaysTarget) {
        this.overtimeDaysTarget.innerHTML = this.remainingDays[3];
    }
    if (this.hasAntiquityDaysTarget) {
        this.antiquityDaysTarget.innerHTML = this.remainingDays[4];
    }
    return this;
  }

}