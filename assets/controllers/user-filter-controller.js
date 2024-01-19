import { Controller } from '@hotwired/stimulus';
import $ from 'jquery';

const routes = require('../../public/js/fos_js_routes.json');
import Routing from '../../public/bundles/fosjsrouting/js/router.js';

import '../js/common/select2';

export default class extends Controller {
   static targets = ['userSelect', 'departmentSelect']
   static values = {
      department: String,
   };

   connect() {
      Routing.setRoutingData(routes);
      let options = {
         theme: "bootstrap-5",
         language: this.localeValue,
         maximumSelectionLength: 10,
         placeholder: ""
      };
      $(this.userSelectTarget).select2(options);
      if ( this.hasDepartmentSelectTarget ) {
         $(this.departmentSelectTarget).append($('<option>', {
            value: '',
            text: ''
        }));
      options = {
         theme: "bootstrap-5",
         language: this.localeValue,
         placeholder: "",
       };
         $(this.departmentSelectTarget).select2(options);
         $(this.departmentSelectTarget).on('select2:select', function(e) {
            let event = new Event('change', { bubbles: true })
            e.currentTarget.dispatchEvent(event);
         });
      }
      // Workaround to dispatch change event on select2 input
   }

   async refreshUsers(event) {
      let department = $(event.currentTarget).val();
      if ( department !== "") {
         let url = app_base + Routing.generate('api_get_department_users', { id: department });
         await fetch(url)
              .then( result => result.json() )
              .then( users => {
                  $(this.userSelectTarget).find('option').remove().end().append($('<option>', { value : '' }).text(''));
                  for ( let user of users ) {
                      $(this.userSelectTarget)
                          .append($('<option>', { value : user.id }).text(user.username));
                  }
               });
      }
   }

  search(event) {
      let users = $(this.userSelectTarget).val();
      let department = this.departmentValue;
      if ( this.hasDepartmentSelectTarget ) {
         department = $(this.departmentSelectTarget).val();
      }
      this.dispatch('search',{ detail: {
         user : users,
         department: department
      }});
   }

   clean(event) {
      if (this.hasUserSelectTarget) {
         $(this.userSelectTarget).val('');
         $(this.userSelectTarget).trigger('change');
      }
      if (this.hasDepartmentSelectTarget) {
         $(this.departmentSelectTarget).val('');
         $(this.departmentSelectTarget).trigger('change');
      }
      this.search(event);
   }


}