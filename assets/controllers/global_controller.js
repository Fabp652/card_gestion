import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    connect() {
        $('#search').on('input', function (e) {
            $('#searchForm').trigger('submit');
        });

        $(window).on('click', function (e) {
            $('#searchResult').hide();
        });

        $('#search, #searchResult').on('click', function(event){
            event.stopPropagation();
        });

        $('#searchForm').on('submit', function (e) {
            e.preventDefault();

            if ($('#search').val().length >= 3) {
                let url = $('#searchForm').attr('action');
                url += '?search=' + $('#search').val();

                fetch(url, {
                    method: $('#searchForm').attr('method'),
                }).then(response => {
                    if (response.status === 200) {
                        response.json().then(json => {
                            if (json.result === true) {
                                $('#searchResult').html(json.searchResult);
                                $('#searchResult').show();
                            }
                        });
                    }
                });
            } else {
                $('#searchResult').hide();
            }
        });
    }
}