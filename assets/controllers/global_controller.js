import { Controller } from '@hotwired/stimulus';
import Swal from 'sweetalert2'

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

        $('.removeElement').on('click', function (e) {
            e.preventDefault();

            Swal.fire({
                title: $(this).attr('data-sweetAlert-title'),
                text: $(this).attr('data-sweetAlert-text'),
                icon: 'warning',
                showDenyButton: true,
                showCancelButton: true,
                confirmButtonText: 'Oui',
                denyButtonText: 'Non',
                cancelButtonText: 'Annuler',
                customClass: {
                    popup: 'rounded-0',
                    actions: 'mx-5',
                    cancelButton: 'btn btn-secondary ms-auto',
                    confirmButton: 'btn btn-primary',
                    denyButton: 'btn btn-danger'
                }
            }).then(result => {
                if (result.isConfirmed) {
                    fetch($(this).attr('href'), {
                        method: 'GET'
                    }).then(response => {
                        if (response.status === 200) {
                            response.json().then(json => {
                                if (json.result === true) {
                                    window.location.reload();
                                } else {
                                    let toast = Swal.mixin({
                                        toast: true,
                                        position: 'top',
                                        width: '400px',
                                        showConfirmButton: false,
                                        timer: 1500,
                                        timerProgressBar: true,
                                        customClass: {
                                            popup: 'text-bg-danger rounded-0'
                                        },
                                        iconColor: '#fff'
                                    });
                                    toast.fire({
                                        icon: 'error',
                                        title: json.message
                                    });
                                }
                            });
                        }
                    });
                }
            });
        })
    }
}