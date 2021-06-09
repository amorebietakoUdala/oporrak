import {
    Controller
} from 'stimulus';
import $ from 'jquery';

const routes = require('../../public/js/fos_js_routes.json');
import Routing from '../../vendor/friendsofsymfony/jsrouting-bundle/Resources/public/js/router.min.js';
import Translator from 'bazinga-translator';
const translations = require('../../public/translations/' + Translator.locale + '.json');

export default class extends Controller {
    static targets = ['day', 'body', 'content'];
    static values = {
        roles: Array,
    };

    connect() {
        Routing.setRoutingData(routes);
        Translator.fromJSON(translations);
        Translator.locale = global.locale;
        console.log(global.locale, Translator.trans('Approved', {}, 'messages'));
        $(this.contentTarget).hide();
    }

    showDetails(event) {
        this.dayTarget.innerHTML = this.localizeDate(event.detail.date, global.locale);
        this.bodyTarget.innerHTML = this.renderDetails(event.detail.events, this.rolesValue);
        $(this.contentTarget).show();
    }

    localizeDate(date, locale) {
        if (locale === 'es') {
            return date.toLocaleDateString('es-ES').slice(0, 10).replaceAll('/', '-');
        } else {
            return date.toLocaleDateString('eu-EU').slice(0, 10).replaceAll('/', '-');
        }
    }

    renderDetails(events, roles) {
        let content = '<div id="events-details">';
        events.forEach(element => {
            content += '<div><span style="background-color:' + element.color + '">&nbsp;&nbsp;</span>&nbsp;';
            if (typeof(element.type) === 'undefined') {
                content += element.user + ': <span>(' + this.localizeDate(element.startDate, global.locale) + ' - ' + this.localizeDate(element.endDate, global.locale) +
                    ')</span><span>&nbsp;-&nbsp;'+element.name+'</span>';
                if (element.status !== null) {
                    content += '<span>&nbsp;-&nbsp;' + element.status + '</span>';
                    if (element.statusId === 1 && (roles.includes("ROLE_BOSS") || roles.includes("ROLE_ADMIN"))) {
                        let params = new URLSearchParams({
                            return: document.location.href,
                        });
                        let urlApprove = app_base + Routing.generate('event_approve', { _locale: global.locale, event: element.id }) + '?' + params.toString();
                        let urlDeny = app_base + Routing.generate('event_deny', { _locale: global.locale, event: element.id }) + '?' + params.toString();
                        content += '&nbsp;&nbsp;&nbsp;<span><a href="' + urlApprove + '"><i class="fas fa-check"></i></a></span>&nbsp;' +
                            '<span><a href="' + urlDeny + '"><i class="fas fa-times"></i></a></span>';
                    }
                    content += '</div>';
                }
            } else {
                content += '</span>' + element.name + '</span>';
            }
        });
        content += '</div>';
        return content;
    }
}