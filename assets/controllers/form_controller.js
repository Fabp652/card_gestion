import { Controller } from '@hotwired/stimulus';
import Swal from 'sweetalert2';

export default class extends Controller {
    connect() {
        const submitLine = function (e) {
            let requiredInputSetted = true;
            let line = $(this).closest('tr');
            line.find('.input').removeClass('is-invalid');

            let requiredData = line.find('*[required="required"]');
            requiredData.each(function (index) {
                if (!$(this).val()) {
                    $(this).addClass('is-invalid');
                    requiredInputSetted = false;
                }
            });

            if (requiredInputSetted) {
                let mainForm = $('#mainForm');
                let inputs = line.find('.input');
                inputs.each(function (index) {
                    $('#' + mainForm.attr('name') + '_' + $(this).attr('name')).val($(this).val());
                });

                let formData = new FormData(document.querySelector('#mainForm'));
                fetch($(this).attr('data-url'), {
                    method: mainForm.attr('method'),
                    body: formData
                }).then(response => {
                    if (response.status == 200) {
                        response.json().then(json => {
                            if (json.result === true) {
                                if (json.newUrl) {
                                    $(this).attr('data-url', json.newUrl);
                                }

                                if (json.deleteUrl) {
                                    line.find('.lineCancel').attr('data-url', json.deleteUrl);
                                }

                                if (json.dataUrl) {
                                    line.find('.lineRefresh').attr('data-url', json.dataUrl);
                                }

                                let toast = Swal.mixin({
                                    toast: true,
                                    position: 'top-end',
                                    width: '400px',
                                    showConfirmButton: false,
                                    timer: 1500,
                                    timerProgressBar: true,
                                    customClass: {
                                        popup: 'text-bg-success rounded-0'
                                    },
                                    iconColor: '#fff'
                                });
                                toast.fire({
                                    icon: 'success',
                                    title: json.message
                                });
                            } else {
                                for (const [key, value] of Object.entries(json.messages)) {
                                    let msg = '<div class="msg invalid-feedback">' + value + '</div>';
                                    let input = line.find('*[name="' + key + '"]');

                                    input.parent().append(msg);
                                    input.addClass('is-invalid');
                                }

                                let toast = Swal.mixin({
                                    toast: true,
                                    position: 'top-end',
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
                        })
                    }
                });
            }
        }

        $('#formUpdate').on('submit', function (e) {
            e.preventDefault();
            let formData = new FormData(document.querySelector('#formUpdate'));
            if ($('input[role=switch]').length) {
                formData.set($('input[role=switch]').attr('name'), $('input[role=switch]').prop('checked'));
            }

            fetch($(this).attr('action'), {
                method: $(this).attr('method'),
                body: formData
            }).then(response => {
                if (response.status == 200) {
                    response.json().then(json => {
                        if (json.result) {
                            let toast = Swal.mixin({
                                toast: true,
                                position: 'top-end',
                                width: '400px',
                                showConfirmButton: false,
                                timer: 1500,
                                timerProgressBar: true,
                                customClass: {
                                    popup: 'text-bg-success rounded-0'
                                },
                                iconColor: '#fff'
                            });
                            toast.fire({
                                icon: 'success',
                                title: json.message
                            });
                        } else {
                            let toast = Swal.mixin({
                                toast: true,
                                position: 'top-end',
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
        });

        $('*[id^=update').on('change', function (e) {
            $('#formUpdate').trigger('submit');
        });

        $(document).on('click', '.lineSubmit', submitLine);

        $('#newLine').on('click', function (e) {
            let lastLine = $('.line:last');
            let cloneLine = lastLine.clone();

            cloneLine.find('.input').val('');
            cloneLine.find('.select2').children().remove();
            cloneLine.find('.lineSubmit').attr('data-url', $(this).data('url'));
            cloneLine.find('.lineRefresh').removeAttr('data-url');
            cloneLine.find('.lineCancel').removeAttr('data-url');

            let select = cloneLine.find('select.select2');
            select.parent().html(select);
            lastLine.after(cloneLine);
        });

        $(document).on('click', '.lineCancel', function (e) {
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
                    let url = $(this).attr('data-url');
                    let line = $(this).closest('tr');
                    if (url) {
                        fetch(url, {
                            method: 'GET'
                        }).then(response => {
                            if (response.status == 200) {
                                response.json().then(json => {
                                    if (json.result === true) {
                                        if ($('.line').length > 1) {
                                            line.remove();
                                        } else {
                                            line.find('.input').val('');
                                            line.find('select.select2').children().remove();
                                            line.find('.lineSubmit').attr('data-url', $('#newLine').attr('data-url'));
                                            $(this).removeAttr('data-url');
                                            line.find('.lineRefresh').removeAttr('data-url');
                                        }

                                        let linesPrice = $('.price');
                                        let totalPrice = 0;
                                        for (const linePrice of linesPrice) {
                                            if ($(linePrice).val().length > 0) {
                                                totalPrice += parseFloat($(linePrice).val());
                                            }
                                        }

                                        let totalPriceFormat = new Intl.NumberFormat('fr-FR', {style: 'currency', currency: 'EUR'}).format(totalPrice);
                                        $('#totalPrice').text(totalPriceFormat);

                                        let toast = Swal.mixin({
                                            toast: true,
                                            position: 'top-end',
                                            width: '400px',
                                            showConfirmButton: false,
                                            timer: 1500,
                                            timerProgressBar: true,
                                            customClass: {
                                                popup: 'text-bg-success rounded-0'
                                            },
                                            iconColor: '#fff'
                                        });
                                        toast.fire({
                                            icon: 'success',
                                            title: json.message
                                        });
                                    } else {
                                        let toast = Swal.mixin({
                                            toast: true,
                                            position: 'top-end',
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
                                })
                            }
                        })
                    } else {
                         if ($('.line').length > 1) {
                            line.remove();
                        } else {
                            line.find('.input').val('');
                            line.find('select.select2').children().remove();
                        }
                    }
                }
            })
        });

        $(document).on('click', '.lineRefresh', function (e) {
            let url = $(this).attr('data-url');
            let line = $(this).closest('tr');

            if (url) {
                fetch(url, {
                    method: 'GET'
                }).then(response => {
                    if (response.status == 200) {
                        response.json().then(json => {
                            if (json.result === true) {
                                let data = json.data;
                                for (const key in data) {
                                    line.find('*[name="' + key + '"').val(data[key]);
                                }
                                line.find('select.select2').trigger('change');
                                line.find('.price').trigger('change');
                            }
                        });
                    } else {
                        let toast = Swal.mixin({
                            toast: true,
                            position: 'top-end',
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
            } else {
                line.find('.input').val('');
                line.find('select.select2').children().remove();

                line.find('.price').trigger('change');
            }
        });
    }
}