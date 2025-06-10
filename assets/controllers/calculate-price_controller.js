import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    connect() {
        $(document).on('change', '.price', function (e) {
            let linesPrice = $('.price');
            let totalPrice = 0;
            for (const linePrice of linesPrice) {
                if ($(linePrice).val().length > 0) {
                    totalPrice += parseFloat($(linePrice).val());
                }
            }

            let totalPriceFormat = new Intl.NumberFormat('fr-FR', {style: 'currency', currency: 'EUR'}).format(totalPrice);
            $('#totalPrice').text(totalPriceFormat);
        });
    }
}