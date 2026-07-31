class CopyButton {
    /** @type {HTMLButtonElement} */
    #buttonElement;

    /** @type {string} */
    #defaultLabel;

    /** @type {number|null} */
    #resetTimeoutId = null;

    /**
     * @param {HTMLButtonElement} buttonElement
     */
    constructor(buttonElement) {
        this.#buttonElement = buttonElement;
        this.#defaultLabel = buttonElement.textContent?.trim() || 'Скопировать';

        buttonElement.addEventListener('click', (event) => {
            event.preventDefault();
            this.#handleClick();
        });
    }

    static initAll() {
        document.querySelectorAll('[data-copy-button]').forEach((buttonElement) => {
            if (buttonElement instanceof HTMLButtonElement) {
                new CopyButton(buttonElement);
            }
        });
    }

    async #handleClick() {
        const text = this.#buttonElement.dataset.copyText ?? '';
        if (text === '') {
            return;
        }

        try {
            await this.#copyText(text);
            this.#showCopied();
        } catch (error) {
            this.#showError();
        }
    }

    /**
     * @param {string} text
     * @returns {Promise<void>}
     */
    async #copyText(text) {
        if (navigator.clipboard && typeof navigator.clipboard.writeText === 'function') {
            await navigator.clipboard.writeText(text);
            return;
        }

        this.#copyTextFallback(text);
    }

    /**
     * Fallback для старых мобильных браузеров без Clipboard API.
     *
     * @param {string} text
     */
    #copyTextFallback(text) {
        const textAreaElement = document.createElement('textarea');
        textAreaElement.value = text;
        textAreaElement.setAttribute('readonly', '');
        textAreaElement.style.position = 'fixed';
        textAreaElement.style.top = '0';
        textAreaElement.style.left = '0';
        textAreaElement.style.width = '1px';
        textAreaElement.style.height = '1px';
        textAreaElement.style.padding = '0';
        textAreaElement.style.border = 'none';
        textAreaElement.style.outline = 'none';
        textAreaElement.style.boxShadow = 'none';
        textAreaElement.style.background = 'transparent';
        textAreaElement.style.opacity = '0';

        document.body.appendChild(textAreaElement);

        const selection = document.getSelection();
        const selectedRange = selection !== null && selection.rangeCount > 0
            ? selection.getRangeAt(0)
            : null;

        textAreaElement.focus();
        textAreaElement.select();
        textAreaElement.setSelectionRange(0, text.length);

        let copied = false;
        try {
            copied = document.execCommand('copy');
        } finally {
            document.body.removeChild(textAreaElement);

            if (selectedRange !== null && selection !== null) {
                selection.removeAllRanges();
                selection.addRange(selectedRange);
            }
        }

        if (!copied) {
            throw new Error('Не удалось скопировать текст.');
        }
    }

    #showCopied() {
        this.#setLabel('Скопировано');
        this.#buttonElement.classList.add('is-copied');
        this.#buttonElement.classList.remove('is-error');
        this.#scheduleReset();
    }

    #showError() {
        this.#setLabel('Не удалось');
        this.#buttonElement.classList.add('is-error');
        this.#buttonElement.classList.remove('is-copied');
        this.#scheduleReset();
    }

    /**
     * @param {string} label
     */
    #setLabel(label) {
        this.#buttonElement.textContent = label;
    }

    #scheduleReset() {
        if (this.#resetTimeoutId !== null) {
            window.clearTimeout(this.#resetTimeoutId);
        }

        this.#resetTimeoutId = window.setTimeout(() => {
            this.#setLabel(this.#defaultLabel);
            this.#buttonElement.classList.remove('is-copied', 'is-error');
            this.#resetTimeoutId = null;
        }, 2000);
    }
}

document.addEventListener('DOMContentLoaded', () => {
    CopyButton.initAll();
});
