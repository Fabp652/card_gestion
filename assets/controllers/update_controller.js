import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    connect() {
        $('#formUpdate').on('submit', function (e) {
            e.preventDefault();
            let formData = new FormData(document.querySelector('#formUpdate'));
            formData.set('sold', $(this).prop('checked'));

            fetch($(this).attr('action'), {
                method: $(this).attr('method'),
                body: formData
            }).then(response => {
                if (response.status == 200) {
                    response.json().then(json => {
                        if (json.result) {
                            window.location.reload();
                        }
                    })
                }
            })
        });

        $('*[id^=update').on('change', function (e) {
            $('#formUpdate').trigger('submit');
        });
    }
}