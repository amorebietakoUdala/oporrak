import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = [];
    static values = {
        locale: String,
    };

    changeLocale(event) {
        if (this.localeValue === event.currentTarget.innerHTML) {
            return;
        } else {
            this.locale = event.currentTarget.innerHTML;
            let location = window.location.href;
            let location_new = location.replace("/" + this.localeValue + "/", "/" + event.currentTarget.innerHTML + "/");
            window.location.href = location_new;
        }
    }
}