import '../js/common/list.js';

import $ from 'jquery';
import {
    Controller
} from 'stimulus';

import {
    Modal
} from 'bootstrap';

import {
    useDispatch
} from 'stimulus-use';

export default class extends Controller {
    static targets = ['modal', 'modalTitle', 'modalBody', 'modalSaveButton'];
    static values = {
        locale: String,
        entity: String,
        entitySaveUrl: String,
    };
    modal = null;

    connect() {
        useDispatch(this);
        this.dispatch('init');
    }

    async openModal(event) {
        this.modalBodyTarget.innerHTML = 'Loading...';
        this.modal = new Modal(this.modalTarget);
        this.modal.show();
        this.modalBodyTarget.innerHTML = await $.ajax(this.entitySaveUrlValue);
    }

    async submitForm(event) {
        let $form = $(this.modalBodyTarget).find('form');
        try {
            await $.ajax({
                url: this.entitySaveUrlValue,
                method: $form.prop('method'),
                data: $form.serialize()
            });
            this.modal.hide();
            this.dispatch('success');
        } catch (e) {
            this.modalBodyTarget.innerHTML = e.responseText;
        }
    }

    async edit(event) {
        event.preventDefault();
        let url = event.currentTarget.dataset.url;
        let allowEdit = event.currentTarget.dataset.allowEdit;
        try {
            await $.ajax({
                url: url,
                method: 'GET',
            }).then((response) => {
                this.modal = new Modal(this.modalTarget);
                this.modalBodyTarget.innerHTML = response;
                this.modalTitleTarget.innerHTML = this.entityValue;
                if (allowEdit == "false") {
                    $(this.modalSaveButtonTarget).hide();
                }
                this.modal.show();
            });
        } catch (e) {
            this.modalBodyTarget.innerHTML = e.responseText;
        }
    }

    async delete(event) {
        event.preventDefault();
        let url = event.currentTarget.dataset.url;
        console.log(url);
        import ('sweetalert2').then(async(Swal) => {
            Swal.default.fire({
                template: '#my-template'
            }).then(async(result) => {
                console.log(result);
                if (result.value) {
                    await $.ajax({
                        url: url,
                        method: 'DELETE'
                    }).then((response) => {
                        console.log(response);
                        this.dispatch('success');
                    }).catch((err) => {
                        console.log(err);
                        Swal.default.fire('There was an error!!!');
                    });
                }
            });
        });
    }

    modalHidden() {
        console.log('it was hidden!');
    }
}