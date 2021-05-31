import {
    Controller
} from 'stimulus';

export default class extends Controller {
    static targets = ['year',
        'annualMaximumWorkingHours',
        'annualMaximumWorkingDays',
        'dailyWorkingHours',
        'dailyWorkingMinutes',
        'break',
        'annualTotalWorkHours',
        'overtimeHours',
        'vacationDays',
        'particularBusinessLeave',
        'overtimeDays'
    ];
    static values = {
        summaryUrl: String,
    };

    year = 0;
    annualMaximumWorkingHours = 0;
    annualMaximumWorkingDays = 0;
    dailyWorkingHours = 0;
    dailyWorkingMinutes = 0;
    break = 0;
    annualTotalWorkHours = 0;
    overtimeHours = 0;
    vacationDays = 0;
    particularBusinessLeave = 0;
    overtimeDays = 0;

    connect() {
        console.log('Summary connected');
    }

    async refreshSummary(event) {
        console.log('refreshing ', event);
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
                    this.annualMaximumWorkingHours = 0;
                    this.annualMaximumWorkingDays = 0;
                    this.dailyWorkingHours = 0;
                    this.dailyWorkingMinutes = 0;
                    this.break = 0;
                    this.annualTotalWorkHours = 0;
                    this.overtimeHours = 0;
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
        this.annualMaximumWorkingHours = json['annualMaximumWorkingHours'];
        this.annualMaximumWorkingDays = json['annualMaximumWorkingDays'];
        this.dailyWorkingHours = json['dailyWorkingHours'];
        this.dailyWorkingMinutes = json['dailyWorkingMinutes'];
        this.break = json['break'];
        this.annualTotalWorkHours = json['annualTotalWorkHours'];
        this.overtimeHours = json['overtimeHours'];
        this.vacationDays = json['vacationDays'];
        this.particularBusinessLeave = json['particularBusinessLeave'];
        this.overtimeDays = json['overtimeDays'];
        return this;
    }

    updateTargets() {
        this.yearTarget.innerHTML = this.year;
        this.annualMaximumWorkingHoursTarget.innerHTML = this.annualMaximumWorkingHours;
        this.annualMaximumWorkingDaysTarget.innerHTML = this.annualMaximumWorkingDays;
        this.dailyWorkingHoursTarget.innerHTML = this.dailyWorkingHours;
        this.dailyWorkingMinutesTarget.innerHTML = this.dailyWorkingMinutes;
        this.breakTarget.innerHTML = this.break;
        this.annualTotalWorkHoursTarget.innerHTML = this.annualTotalWorkHours;
        this.overtimeHoursTarget.innerHTML = this.overtimeHours;
        this.vacationDaysTarget.innerHTML = this.vacationDays;
        this.particularBusinessLeaveTarget.innerHTML = this.particularBusinessLeave;
        this.overtimeDaysTarget.innerHTML = this.overtimeDays;
        return this;
    }


}