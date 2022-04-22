import {
   Controller
} from 'stimulus';
import $ from 'jquery';

import {
   useDispatch
} from 'stimulus-use';

const routes = require('../../public/js/fos_js_routes.json');
import Routing from '../../public/js/router.min.js';

import '../js/common/select2';

export default class extends Controller {
   static targets = ['userSelect', 'departmentSelect'];
   static values = {
//      roles: Array,
      department: String,
   };

   connect() {
      Routing.setRoutingData(routes);
      useDispatch(this);
      $(this.userSelectTarget).append($('<option>', {
         value: '',
         text: ''
     }));
      $(this.userSelectTarget).select2({
         maximumSelectionLength: 10
      });
      if ( this.hasDepartmentSelectTarget ) {
         $(this.departmentSelectTarget).append($('<option>', {
            value: '',
            text: ''
        }));
         $(this.departmentSelectTarget).select2();
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
     let user = $(this.userSelectTarget).val();
     let department = this.departmentValue;
     if ( this.hasDepartmentSelectTarget ) {
         department = $(this.departmentSelectTarget).val();
     }
     this.dispatch('search',{
        user : user,
        department: department
     });
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