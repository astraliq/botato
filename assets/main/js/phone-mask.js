class PhoneMask {
    /** @type {HTMLInputElement} */
    #inputElement;

    /**
     * @param {HTMLInputElement} inputElement
     */
    constructor(inputElement) {
        this.#inputElement = inputElement;
        this.#applyMask();
    }

    static initAll() {
        if (typeof IMask === 'undefined') {
            return;
        }

        document.querySelectorAll('[data-phone-mask]').forEach((inputElement) => {
            if (inputElement instanceof HTMLInputElement) {
                new PhoneMask(inputElement);
            }
        });
    }

    #applyMask() {
        IMask(this.#inputElement, {
            mask: '+{7} (000) 000-00-00',
            lazy: false,
        });
    }
}

document.addEventListener('DOMContentLoaded', () => {
    PhoneMask.initAll();
});
