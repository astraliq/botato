class FormPreloader {
    /** @type {HTMLElement} */
    #rootElement;

    /** @type {HTMLElement|null} */
    #preloaderElement;

    /**
     * @param {HTMLElement} rootElement
     */
    constructor(rootElement) {
        this.#rootElement = rootElement;
        this.#preloaderElement = rootElement.querySelector('[data-form-preloader]');
    }

    /**
     * @param {HTMLElement} rootElement
     * @returns {FormPreloader|null}
     */
    static create(rootElement) {
        if (!(rootElement instanceof HTMLElement) || rootElement.querySelector('[data-form-preloader]') === null) {
            return null;
        }

        return new FormPreloader(rootElement);
    }

    /**
     * @param {boolean} isLoading
     */
    setLoading(isLoading) {
        this.#rootElement.classList.toggle('is-loading', isLoading);
        this.#rootElement.setAttribute('aria-busy', isLoading ? 'true' : 'false');

        this.#rootElement.querySelectorAll('input, button, select, textarea').forEach((controlElement) => {
            if (controlElement instanceof HTMLInputElement
                || controlElement instanceof HTMLButtonElement
                || controlElement instanceof HTMLSelectElement
                || controlElement instanceof HTMLTextAreaElement
            ) {
                controlElement.disabled = isLoading;
            }
        });

        if (this.#preloaderElement !== null) {
            this.#preloaderElement.hidden = !isLoading;
            this.#preloaderElement.setAttribute('aria-hidden', isLoading ? 'false' : 'true');
        }
    }
}
