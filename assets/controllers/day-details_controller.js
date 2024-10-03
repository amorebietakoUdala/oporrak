import { Controller } from '@hotwired/stimulus';
import $ from 'jquery';

const routes = require('../../public/js/fos_js_routes.json');
import Routing from '../../public/bundles/fosjsrouting/js/router.js';
import Translator from 'bazinga-translator';
const translations = require('../../public/translations/' + Translator.locale + '.json');

export default class extends Controller {
    static targets = ['day', 'body', 'content'];
    static values = {
        roles: Array,
        user: String,
        boss: String, 
        previousYearDaysColor: String,
        type: String,
    };

    connect() {
        Routing.setRoutingData(routes);
        Translator.fromJSON(translations);
        Translator.locale = global.locale;
        $(this.contentTarget).hide();
    }

    hideDetails(event) {
        $(this.contentTarget).hide();
    }

    showDetails(event) {
        this.dayTarget.innerHTML = this.localizeDate(event.detail.date, global.locale);
        this.bodyTarget.innerHTML = this.renderDetails(event.detail.events, this.userValue, this.rolesValue);
        $(this.contentTarget).show();
    }

    localizeDate(date, locale) {
        if (locale === 'es') {
            return date.toLocaleDateString('es-ES',{year:"2-digit",month:"2-digit", day:"2-digit"}).slice(0, 10).replaceAll('/', '-');
        } else {
            return date.toLocaleDateString('eu-EU',{year:"2-digit",month:"2-digit", day:"2-digit"}).slice(0, 10).replaceAll('/', '-');
        }
    }

    renderDetails(events, user, roles) {
        let content = '<div id="events-details">';
        events.forEach(element => {
            content += '<div>';
            content += '<span style="background-color:' + element.color + '" title="'+ element.status +'">&nbsp;&nbsp;</span>';
            if ( element.usePreviousYearDays ) {
                content += '<span style="background-color:' + this.previousYearDaysColorValue + '" title="'+ Translator.trans('event.usePreviousYearDays', null, 'messages', global.locale) +'">&nbsp;&nbsp;</span>';
            }
            content += '&nbsp;';
            if (element.type !== 'holiday') {
                content += element.user + ': <span>(' + this.localizeDate(element.startDate, global.locale) + ' - ' + this.localizeDate(element.endDate, global.locale) +
                    ')</span><span>&nbsp;-&nbsp;'+element.type+'</span>';
                if (element.status !== null) {
                    content += '<span>&nbsp;-&nbsp;' + element.status + '</span>';
                    if (element.startHalfDay === true) {
                        content += '<span>&nbsp;(' + element.hours + 'h.)</span>';
                    }
                    let params = new URLSearchParams({
                        return: document.location.href,
                    });
                    if (element.statusId === 1 && (roles.includes("ROLE_BOSS") || roles.includes("ROLE_ADMIN") && element.user !== user )) {
                            let urlApprove = app_base + Routing.generate('event_approve', { _locale: global.locale, id: element.id }) + '?' + params.toString();
                            let urlDeny = app_base + Routing.generate('event_deny', { _locale: global.locale, id: element.id }) + '?' + params.toString();
                            content += '&nbsp;&nbsp;&nbsp;<span><a href="' + urlApprove + '"><i class="fas fa-check"></i></a></span>&nbsp;' +
                                '<span><a href="' + urlDeny + '"><i class="fas fa-times"></i></a></span>';
                    }
                    if ( ( roles.includes("ROLE_HHRR") || roles.includes("ROLE_ADMIN") ) && element.user !== user 
                        && this.typeValue != null && this.typeValue == 'cityHall') {
                        content += '&nbsp;&nbsp;<span><a href="#" data-eventId="'+ element.id +'" title="'+ Translator.trans('btn.delete', null, 'messages', global.locale) +'" data-action="click->department-calendar#deleteEvent"><i class="fas fa-trash"></i></a></span>&nbsp;';
                        content += '&nbsp;&nbsp;<span><a href="#" data-eventId="'+ element.id +'" title="'+ Translator.trans('btn.edit', null, 'messages', global.locale) +'" data-action="click->department-calendar#editEvent"><i class="fas fa-edit"></i></a></span>&nbsp;';
                    }
                }
            } else {
                content += '</span>' + element.name + '</span>';
            }
            content += '</div>';
        });
        content += '</div>';
        return content;
    }
}