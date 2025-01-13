import { Controller } from '@hotwired/stimulus';

import $ from 'jquery';

import '../js/common/datepicker';
import '../js/common/list';

export default class extends Controller {
   static targets = ['list'];

    static values = {
        locale: String,
    }

   connect() {
      if (this.hasListTarget) {
         $(this.listTarget).bootstrapTable({
             cache: false,
             showExport: true,
             exportTypes: ['excel'],
             exportDataType: 'all',
             showColumns: false,
             showExport: true,
             iconsPrefix: 'fa',
             icons: {
                 export: 'fa-download',
             },
             exportTypes: ['excel'],
             pagination: true,
             search: true,
             striped: true,
             sortStable: true,
             pageSize: 100,
             pageList: [100, 200],
             sortable: true,
             locale: this.localeValue + '-' + this.localeValue.toUpperCase(),
         });        
      }
   }
}




