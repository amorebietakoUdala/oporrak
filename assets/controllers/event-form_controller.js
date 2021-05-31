import {
    Controller
} from 'stimulus';
import $ from 'jquery';
import 'bootstrap-datepicker';
import {
    useDispatch
} from 'stimulus-use';

export default class extends Controller {
    static values = {
        locale: String,
    }

    connect() {
        console.log("Event-form connected!!");
        useDispatch(this, {
            debug: true
        });
        $('#event_form_startDate').datepicker({
            format: "yyyy-mm-dd",
            language: this.localeValue
        });
        $('#event_form_endDate').datepicker({
            format: "yyyy-mm-dd",
            language: this.localeValue
        });
    }

    // async submitForm(event) {
    //     //        event.preventDefault();
    //     console.log('Submit form clicked!!!');
    //     const $form = $(this).find('form');

    //     try {
    //         await $.ajax({
    //             url: this.formUrlValue,
    //             method: $form.prop('method'),
    //             data: $form.serialize(),
    //         });
    //         this.modal.hide();
    //         this.dispatch('success');
    //     } catch (e) {
    //         this.modalBodyTarget.innerHTML = e.responseText;
    //     }
    // }

}