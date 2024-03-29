import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
   static targets = [
        'year',
        'overtimeDays', 
        'vacationDays', 
        'particularBusinessLeave', 
        'antiquityDays',
        'additionalVacationDays',
        'additionalVacationDaysLabelSingular',
        'additionalVacationDaysLabelPlural',
        'antiquityDaysLabelSingular',
        'antiquityDaysLabelPlural',
        'overtimeDaysLabelSingular',
        'overtimeDaysLabelPlural',
        'particularBusinessLeaveLabelSingular',
        'particularBusinessLeaveLabelPlural',
        'vacationDaysLabelSingular',
        'vacationDaysLabelPlural',
    ];
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
        if (this.remainingDays[1] === 1) {
            this.changeLabel(this.vacationDaysLabelSingularTarget,this.vacationDaysLabelPluralTarget);
        }
    }
    if (this.hasParticularBusinessLeaveTarget) {
        this.particularBusinessLeaveTarget.innerHTML = this.remainingDays[2] !== null ? this.remainingDays[2] : 0;    
        if (this.remainingDays[2] === 1) {
            this.changeLabel(this.particularBusinessLeaveLabelSingularTarget,this.particularBusinessLeaveLabelPluralTarget);
        }
    }
    if (this.hasOvertimeDaysTarget) {
        this.overtimeDaysTarget.innerHTML = this.remainingDays[3] !== null ? this.remainingDays[3] : 0;
        if (this.remainingDays[3] === 1) {
            this.changeLabel(this.overtimeDaysLabelSingularTarget,this.overtimeDaysLabelPluralTarget);
        }
    }
    if (this.hasAntiquityDaysTarget) {
        this.antiquityDaysTarget.innerHTML = this.remainingDays[4] !== null ? this.remainingDays[4] : 0;
        if (this.remainingDays[4] === 1) {
            this.changeLabel(this.antiquityDaysLabelSingularTarget,this.antiquityDaysLabelPluralTarget);
        }
    }
    if (this.hasAdditionalVacationDaysTarget) {
        this.additionalVacationDaysTarget.innerHTML = this.remainingDays[5] !== null ? this.remainingDays[5] : 0;
        if (this.remainingDays[5] === 1) {
            this.changeLabel(this.additionalVacationDaysLabelSingularTarget,this.additionalVacationDaysLabelPluralTarget);
        }
    }
    return this;
  }

  changeLabel(singularTarget, pluralTarget) {
    singularTarget.classList.remove('d-none');
    pluralTarget.classList.add('d-none');
  }

}