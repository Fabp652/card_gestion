import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    connect() {
        $('#showHide').on('change', function (e) {
            if ($(this).prop('checked')) {
                $('.showHide').show();
            } else {
                $('.showHide').hide();
            }
        })
    }
}