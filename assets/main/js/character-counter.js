class CharacterCounter {
    /** @type {HTMLElement} */
    #rootElement;

    /** @type {HTMLInputElement|HTMLTextAreaElement|null} */
    #inputElement;

    /** @type {HTMLElement|null} */
    #outputElement;

    /** @type {number} */
    #maxLength = 0;

    /**
     * @param {HTMLElement} rootElement
     */
    constructor(rootElement) {
        this.#rootElement = rootElement;
        const inputElement = rootElement.querySelector('[data-character-counter-input]');
        this.#inputElement = inputElement instanceof HTMLInputElement || inputElement instanceof HTMLTextAreaElement
            ? inputElement
            : null;
        this.#outputElement = rootElement.querySelector('[data-character-counter-output]');
        this.#maxLength = this.#resolveMaxLength();

        this.#inputElement?.addEventListener('input', () => {
            this.#update();
        });

        this.#update();
    }

    static initAll() {
        document.querySelectorAll('[data-character-counter]').forEach((rootElement) => {
            if (rootElement instanceof HTMLElement) {
                new CharacterCounter(rootElement);
            }
        });
    }

    /**
     * @returns {number}
     */
    #resolveMaxLength() {
        if (this.#inputElement === null) {
            return 0;
        }

        const maxLengthAttribute = this.#inputElement.getAttribute('maxlength');
        if (maxLengthAttribute !== null && maxLengthAttribute !== '') {
            const parsedMaxLength = Number.parseInt(maxLengthAttribute, 10);
            if (Number.isFinite(parsedMaxLength) && parsedMaxLength > 0) {
                return parsedMaxLength;
            }
        }

        return this.#inputElement.maxLength > 0 ? this.#inputElement.maxLength : 0;
    }

    #update() {
        if (this.#inputElement === null || this.#outputElement === null || this.#maxLength <= 0) {
            return;
        }

        const currentLength = this.#inputElement.value.length;
        const remainingLength = Math.max(this.#maxLength - currentLength, 0);
        const remainingLabel = remainingLength.toLocaleString('ru-RU');

        this.#outputElement.textContent = `Осталось ${remainingLabel} символов`;
        this.#outputElement.classList.toggle('is-limit-reached', remainingLength === 0);
    }
}

CharacterCounter.initAll();
