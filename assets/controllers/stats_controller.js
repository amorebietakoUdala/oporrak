import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = [ 'stats' ];
    static values = {
        statsUrl: String,
    };

    async refresh(event) {
        let params = new URLSearchParams({
            year: event.detail.year,
        });
        const response = await fetch(`${this.statsUrlValue}?${params.toString()}`);
        let statsContent = await response.text();
        this.statsTarget.innerHTML = statsContent;
    }
}