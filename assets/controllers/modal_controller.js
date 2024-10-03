import { Controller } from '@hotwired/stimulus';
import { Modal } from 'bootstrap';

export default class extends Controller {
    connect() {
        const modal = new Modal('#modal');
        $('.showModal').on('click', function (e) {
            let url = $(this).data('url');
            let title = $(this).data('title');

            fetch(url, {
                method: 'get'
            }).then(response => {
                if (response.status === 200) {
                    response.json().then(json => {
                        if (json.result === true) {
                            $('#modalTitle').text(title);
                            $('#modalBody').html(json.content);

                            modal.show();
                        }
                    });
                }
            });
        });

        $('#modalSubmit').on('click', function (e) {
            let formData = new FormData(document.querySelector('#modalForm'));
            let url = $('#modalForm').attr('action');

            fetch(url, {
                method: $('#modalForm').attr('method'),
                body: formData
            }).then(response => {
                if (response.status === 200) {
                    response.json().then(json => {
                        if (json.result === true) {
                            modal.hide();
                            window.location.reload();
                        }
                    })
                }
            })
        });

        $(document).on('#modalBody form', 'submit', function (e) {
            e.preventDefault();
            console.log(1)
            let formData = new FormData($(this));
            let url = $(this).attr('action');

            fetch(url, {
                method: $(this).attr('method'),
                body: formData
            }).then(response => {
                if (response.status === 200) {
                    response.json().then(json => {
                        if (json.result === true) {
                            modal.hide();
                        }
                    })
                }
            })
        });
    }
}