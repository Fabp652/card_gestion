import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    connect() {
        $('#newCategoryBtn').on('click', function (e) {
            $('#newCategoryBtn').hide();
            $('#newCategory').removeClass('d-none');
            $('#newCategory').addClass('d-flex');
        });

        $('#newCategoryCancel').on('click', function (e) {
            $('#newCategory').removeClass('d-flex');
            $('#newCategory').addClass('d-none');
            $('#newCategoryBtn').show();
        });
    }
}