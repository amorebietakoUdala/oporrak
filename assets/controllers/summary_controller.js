import {
    Controller
} from 'stimulus';

export default class extends Controller {
    static targets = ['year','vacationDays','particularBusinessLeave','overtimeDays'];
    static values = {summaryUrl: String};

    year = 0;
    vacationDays = 0;
    particularBusinessLeave = 0;
    overtimeDays = 0;

    async refreshSummary(event) {
        let params = new URLSearchParams({
            year: event.detail.year
        });
        await fetch(`${this.summaryUrlValue}?${params.toString()}`)
            .then(result => result.json())
            .then(result => {
                if (result !== null) {
                    this.update(result);
                    this.updateTargets();
                } else {
                    this.year = event.detail.year;
                    this.vacationDays = 0;
                    this.particularBusinessLeave = 0;
                    this.overtimeDays = 0;
                    this.updateTargets();
                }
            });
        return;
    }

    update(json) {
        this.year = json['year'];
        this.vacationDays = json['vacationDays'];
        this.particularBusinessLeave = json['particularBusinessLeave'];
        this.overtimeDays = json['overtimeDays'];
        return this;
    }

    updateTargets() {
        this.yearTarget.innerHTML = this.year;
        this.vacationDaysTarget.innerHTML = this.vacationDays;
        this.particularBusinessLeaveTarget.innerHTML = this.particularBusinessLeave;
        this.overtimeDaysTarget.innerHTML = this.overtimeDays;
        return this;
    }
}