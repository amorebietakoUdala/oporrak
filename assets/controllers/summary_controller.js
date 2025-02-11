import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['summary'];
    static values = {summaryUrl: String};

    async refresh(event) {
        if (this.hasSummaryTarget) {
            let params = new URLSearchParams({
                year: event.detail.year
            });
            const response = await fetch(`${this.summaryUrlValue}?${params.toString()}`);
            let summaryContent = await response.text();
            this.summaryTarget.innerHTML = summaryContent; 
            return;
        }
    }
}