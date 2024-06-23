import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    connect() {
        $(document).on('change', '#nameFilter, #referenceFilter, #rarityFilter, #priceFilter, #qualityFilter, #numberFilter', function (e) {
            $('#formFilter').trigger('submit');
        });
    }
}
