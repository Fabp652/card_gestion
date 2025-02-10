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
            $('.msg').remove();
            let formData = new FormData(document.querySelector('#modalForm'));
            let requiredData = $('input[required="required"]');
            let valid = true;
            
            requiredData.each(function (index) {
                if (!$(this).val()) {
                    $(this).parent().append(
                        '<span class="msg pt-0 fw-bold text-danger">Cet élément est requis</span>'
                    );
                    valid = false;
                }
            });
            if (valid) {
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
                            } else {
                                $('#modalBody').prepend(
                                    '<b class="text-danger">' + json.message + '</b>'
                                )
                            }
                        })
                    }
                })
            }
        });
    }
}