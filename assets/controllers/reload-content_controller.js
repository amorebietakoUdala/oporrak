import '../js/common/list.js';

import { Controller } from 'stimulus';
import $ from 'jquery';

export default class extends Controller {
    static targets = ['content'];
    static values = {
        url: String,
        locale: String,
    }

    async refreshContent(event) {
        const target = this.hasContentTarget ? this.contentTarget : this.element;
        target.style.opacity = .5;
        console.log(event);
        if (event.type === 'entity:success') {
            const response = await fetch(this.urlValue);
            target.innerHTML = await response.text();
        }
        $('#taula').bootstrapTable({
            cache: false,
            showExport: true,
            exportTypes: ['excel'],
            exportDataType: 'all',
            exportOptions: {
                fileName: this.entityValue,
                ignoreColumn: ['options']
            },
            showColumns: false,
            pagination: true,
            search: true,
            striped: true,
            sortStable: true,
            pageSize: 10,
            pageList: [10, 25, 50, 100],
            sortable: true,
            locale: this.localeValue + '-' + this.localeValue.toUpperCase(),
        });
        target.style.opacity = 1;
    }
}