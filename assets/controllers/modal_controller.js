import { Controller } from '@hotwired/stimulus';
import { Modal, Tooltip } from 'bootstrap';

export default class extends Controller {
    connect() {
        const modal = new Modal('#modal');

        $('.showModal').on('click', function (e) {
            let url = $(this).data('url');
            let title = $(this).data('title');
            $('.msg').remove();
            $('input').removeClass('is-invalid');
            $('select').removeClass('is-invalid');

            if (url) {
                fetch(url, {
                method: 'get'
                }).then(response => {
                    if (response.status === 200) {
                        response.json().then(json => {
                            if (json.result === true) {
                                $('#modalTitle').text(title);
                                $('#modalBody').html(json.content);

                                modal.show();

                                const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
                                if (tooltipTriggerList.length > 0) {
                                    const tooltipList = [...tooltipTriggerList].map(tooltipTriggerEl => new Tooltip(tooltipTriggerEl));
                                }
                            }
                        });
                    }
                });
            } else {
                let submitUrl = $(this).data('submit-url');
                let className = '.' + $(this).data('class');
                let state = $(this).data('state');
                $('#modalForm').children().addClass('d-none');
                $('.modalInput').val('');

                $('#modalTitle').text(title);
                $('#modalForm').attr('action', submitUrl);
                if (state) {
                    $('#state').val(state);
                }
                $(className).removeClass('d-none');

                modal.show();
            }
        });

        $('#modalSubmit').on('click', function (e) {
            $('#modalForm').trigger('submit')
        });

        $('#modalBody').on('submit', function (e) {
            e.preventDefault();

            $('.msg').remove();
            $('input').removeClass('is-invalid');
            $('select').removeClass('is-invalid');
            
            let formData = new FormData(document.querySelector('#modalForm'));
            let requiredData = $('input[required="required"]');
            let valid = true;

            requiredData.each(function (index) {
                if (!$(this).val()) {
                    $(this).parent().append(
                        '<div class="msg invalid-feedback">Cet élément est requis</div>'
                    );
                    $(this).addClass('is-invalid');
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
                                if (json.redirect != undefined) {
                                    window.location.assign(json.redirect);
                                } else {
                                    window.location.reload();
                                }
                            } else {
                                if (json.message) {
                                    $('#modalBody').prepend(
                                        '<b class="msg text-danger">' + json.message + '</b>'
                                    )
                                } else {
                                    for (const [key, value] of Object.entries(json.messages)) {
                                        let input = $('input[id$="' + key + '"]');
                                        if (input.length == 0) {
                                            input = $('select[id$="' + key + '"]')
                                            if (input.length == 0) {
                                                $('#modalBody').prepend(
                                                    '<b class="msg text-danger">' + value + '</b>'
                                                )
                                            }
                                        }
                                        
                                        let msg = '<div class="msg invalid-feedback">' + value + '</div>';
                                        input.parent().append(msg);
                                        input.addClass('is-invalid');
                                    }
                                }
                            }
                        })
                    }
                })
            }
        });
    }
}