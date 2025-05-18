import { Controller } from '@hotwired/stimulus';
import datepicker from "js-datepicker";

export default class extends Controller {
    connect() {
        if ($('.datepicker').length > 0) {
            const picker = datepicker('.datepicker', {
                formatter: (input, date, instance) => {
                    const value = date.toLocaleDateString();
                    input.value = value;
                }
            });
        }

        if ($('.startDate').length > 0) {
            const startDate = datepicker('.startDate', {
                formatter: (input, date, instance) => {
                    const value = date.toLocaleDateString();
                    input.value = value;
                    $('#formFilter').trigger('submit');
                },
                id: 1,
            });
        }
        
        if ($('.endDate').length > 0) {
            const endDate = datepicker('.endDate', {
                formatter: (input, date, instance) => {
                    const value = date.toLocaleDateString();
                    input.value = value;
                    $('#formFilter').trigger('submit');
                },
                id: 1
            });
        }

        $('#modal').on('show.bs.modal', function (e) {
            const pickerModal = datepicker('.pickerModal', {
                formatter: (input, date, instance) => {
                    const value = date.toLocaleDateString();
                    input.value = value;
                },
            });
        });
        
    }
}