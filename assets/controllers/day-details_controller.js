import {
    Controller
} from 'stimulus';
import $, { type } from 'jquery';

export default class extends Controller {
    static targets = ['day', 'body', 'content'];
    static values = {
        //        colorPalette: Array,
    };

    connect() {
        $(this.contentTarget).hide();
    }

    showDetails(event) {
        this.dayTarget.innerHTML = this.localizeDate(event.detail.date, global.locale);
        this.bodyTarget.innerHTML = this.renderDetails(event.detail.events);
        $(this.contentTarget).show();
    }

    localizeDate(date, locale) {
        if (locale === 'es') {
            return date.toLocaleDateString('es-ES').slice(0, 10).replaceAll('/', '-');
        } else {
            return date.toLocaleDateString('eu-EU').slice(0, 10).replaceAll('/', '-');
        }
    }

    renderDetails(events) {
        let content = '<div id="events-details">';
        events.forEach(element => {
            content += '<div><span style="background-color:' + element.color + '">&nbsp;&nbsp;</span>&nbsp;';
            if (typeof(element.type) === 'undefined') {
                content += element.user + ': <span>(' + this.localizeDate(element.startDate, global.locale) + ' - ' + this.localizeDate(element.endDate, global.locale) +
                    ')</span>';
                if (element.status !== null) {
                    content += '<span>&nbsp;-&nbsp;' + element.status + '</span></div>';
                }
            } else {
                content += '</span>' + element.name + '</span>';
            }
        });
        content += '</div>';
        return content;
    }
}