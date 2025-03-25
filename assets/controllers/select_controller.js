import { Controller } from '@hotwired/stimulus';
import { select2 } from "../../node_modules/select2/dist/js/select2";
import { fr } from "../../node_modules/select2/dist/js/i18n/fr";

export default class extends Controller {
    connect() {
        $(document).ready(function() {
            $('.select2').select2({
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
                            results: data.items
                        };
                    }
                },
                minimumInputLength: 3
            });
        });
    }
}