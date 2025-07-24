import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    connect() {
        $(document).on('change', 'tr th input, tr th select', function (e) {
            $('#formFilter').trigger('submit');
        });
    }
}
