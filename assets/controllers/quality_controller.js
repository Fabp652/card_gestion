import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    connect() {
        $('.criteriaQuality').on('change', function (e) {
            let quality = parseInt($('#item_quality_quality').val());
            let checkboxes = $('input[name="item_quality[criterias][]"]');

            if ($(this).prop('checked')) {
                if ($(this).attr('name') == 'perfect') {
                    quality = 10;
                    checkboxes.prop('checked', false);
                    checkboxes.prop('disabled', true);
                } else {
                    quality -= parseInt($(this).data('point'));
                }
            } else if($(this).attr('name') == 'perfect') {
                checkboxes.prop('disabled', false);
            } else if (quality < 10) {
                quality += parseInt($(this).data('point'));
                for (let index = 0; index < checkboxes.length; index++) {
                    const checkbox = checkboxes[index];
                    quality -= $(checkbox).data('point');
                }
            }

            if (quality < 0) {
                quality = 0;
            }

            $('#item_quality_quality').attr('value', quality);
        })
    }
}