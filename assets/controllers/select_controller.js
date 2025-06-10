import { Controller } from '@hotwired/stimulus';
import { select2 } from "../../node_modules/select2/dist/js/select2";
import { fr } from "../../node_modules/select2/dist/js/i18n/fr";

export default class extends Controller {
    connect() {
        let totalSelect2 = 0;
        function select2 () {
            let options = {
                language: 'fr',
                ajax: {
                    dataType: 'json',
                    data: function (params) {
                        var query = {
                            search: params.term
                        }
                    return query;
                    },
                    processResults: function (data) {
                        return {
                            results: data.searchResults
                        };
                    }
                }
            };

            if ($('#modalForm').length > 0) {
                options['dropdownParent'] = $('#modalForm');
            }

            let selectParent = $('#selectParent');
            if (selectParent.length > 0) {
                options['ajax']['url'] = selectParent.data('url');
            }
            $('select.select2').select2(options);

            totalSelect2 = $('select.select2').length;
        }

        $(function (e) {
            select2();
        });

        $(document).on('click', function (e) {
            if ($('select.select2').length > totalSelect2 ) {
                select2();
            }
        })
    }
}