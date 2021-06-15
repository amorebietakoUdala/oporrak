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
        useDispatch(this);
        $('#event_form_startDate').datepicker({
            format: "yyyy-mm-dd",
            language: this.localeValue
        });
        $('#event_form_endDate').datepicker({
            format: "yyyy-mm-dd",
            language: this.localeValue
        });
    }
}