import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = [
        'remainingDays'
    ];
    static values = {
        remainingDaysUrl: String,
    };

    async refresh(event) {
        this.year = event.detail.year;
        let params = new URLSearchParams({
            year: this.year
        });

        const response = await fetch(`${this.remainingDaysUrlValue}?${params.toString()}`);
        let remainingDaysContent = await response.text();
        this.remainingDaysTarget.innerHTML = remainingDaysContent;
    }
}