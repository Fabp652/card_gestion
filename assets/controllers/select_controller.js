import { Controller } from '@hotwired/stimulus';
import selectize from '@selectize/selectize';

export default class extends Controller {
    connect() {
        $('.select2').selectize();
    }
}