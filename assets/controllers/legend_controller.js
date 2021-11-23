import {
    Controller
} from 'stimulus';

export default class extends Controller {
    static targets = [ 'stats' ];
    static values = {
        statsUrl: String,
    };

    async refreshStats(event) {
        let params = new URLSearchParams({
            year: event.detail.year,
        });
        const response = await fetch(`${this.statsUrlValue}?${params.toString()}`)
        this.statsTarget.innerHTML = await response.text();
    } 
}