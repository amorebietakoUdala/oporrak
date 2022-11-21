import $ from 'jquery';
import { Controller } from '@hotwired/stimulus';
import { Modal } from 'bootstrap';
import { useDispatch } from 'stimulus-use';

export default class extends Controller {
    static targets = ['modal', 'modalTitle', 'modalBody', 'modalSaveButton'];
    static values = {
        locale: String,
        entitySaveUrl: String
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
        let allowEdit = event.currentTarget.dataset.allowedit;
        try {
            await $.ajax({
                url: url,
                method: 'GET',
            }).then((response) => {
                this.modal = new Modal(this.modalTarget);
                this.modalBodyTarget.innerHTML = response;
                if (allowEdit == "false") {
                    $(this.modalSaveButtonTarget).hide();
                } else {
                    $(this.modalSaveButtonTarget).show(); 
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
        import ('sweetalert2').then(async(Swal) => {
            Swal.default.fire({
                template: '#my-template'
            }).then(async(result) => {
                if (result.value) {
                    await $.ajax({
                        url: url,
                        method: 'DELETE'
                    }).then((response) => {
                        this.dispatch('success');
                    }).catch((err) => {
                        Swal.default.fire('There was an error!!!');
                    });
                }
            });
        });
    }
}