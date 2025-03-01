import { Controller } from '@hotwired/stimulus';
import { Tooltip } from 'bootstrap';

export default class extends Controller {
    connect() {
        const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]')
        const tooltipList = [...tooltipTriggerList].map(tooltipTriggerEl => new Tooltip(tooltipTriggerEl))

        $(document).on('change', 'tr th input, tr th select', function (e) {
            $('#formFilter').trigger('submit');
        });

        $('#addItem').on('input', function (e) {
            if ($(this).val().length >= 3) {
                let url = $(this).attr('data-url');
                url += '&search=' + $(this).val();

                fetch(url, {
                    method: 'GET',
                }).then(response => {
                    if (response.status === 200) {
                        response.json().then(json => {
                            if (json.result === true) {
                                $('#itemsList').html(json.searchResult);
                                $('#itemsList').show();
                            }
                        });
                    }
                });
            } else {
                $('#itemsList').hide();
            }
        });
    }
}
