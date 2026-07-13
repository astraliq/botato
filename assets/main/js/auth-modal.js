class AuthModal {
    /** @type {HTMLElement} */
    #modalElement;

    /** @type {HTMLElement|null} */
    #emailStepElement;

    /** @type {HTMLElement|null} */
    #codeStepElement;

    /** @type {HTMLFormElement|null} */
    #emailFormElement;

    /** @type {HTMLFormElement|null} */
    #codeFormElement;

    /** @type {HTMLInputElement|null} */
    #emailInputElement;

    /** @type {HTMLInputElement|null} */
    #codeInputElement;

    /** @type {HTMLElement|null} */
    #errorElement;

    /** @type {HTMLElement|null} */
    #successElement;

    /** @type {string} */
    #currentEmail = '';

    /**
     * @param {HTMLElement} modalElement
     */
    constructor(modalElement) {
        this.#modalElement = modalElement;
        this.#emailStepElement = modalElement.querySelector('[data-auth-step="email"]');
        this.#codeStepElement = modalElement.querySelector('[data-auth-step="code"]');
        this.#emailFormElement = modalElement.querySelector('[data-auth-email-form]');
        this.#codeFormElement = modalElement.querySelector('[data-auth-code-form]');
        this.#emailInputElement = modalElement.querySelector('[data-auth-email-input]');
        this.#codeInputElement = modalElement.querySelector('[data-auth-code-input]');
        this.#errorElement = modalElement.querySelector('[data-auth-error]');
        this.#successElement = modalElement.querySelector('[data-auth-success]');

        this.#bindEvents();
    }

    static initAll() {
        const modalElement = document.getElementById('auth-modal');
        if (modalElement === null) {
            return;
        }

        new AuthModal(modalElement);
    }

    #bindEvents() {
        document.querySelectorAll('[data-auth-open]').forEach((buttonElement) => {
            buttonElement.addEventListener('click', () => this.#openModal());
        });

        this.#modalElement.querySelectorAll('[data-auth-close]').forEach((buttonElement) => {
            buttonElement.addEventListener('click', () => this.#closeModal());
        });

        this.#modalElement.addEventListener('click', (event) => {
            if (event.target === this.#modalElement) {
                this.#closeModal();
            }
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && this.#modalElement.classList.contains('is-open')) {
                this.#closeModal();
            }
        });

        this.#modalElement.querySelector('[data-auth-back]')?.addEventListener('click', () => {
            this.#clearMessages();
            this.#showEmailStep();
        });

        this.#emailFormElement?.addEventListener('submit', (event) => {
            void this.#handleEmailSubmit(event);
        });

        this.#codeFormElement?.addEventListener('submit', (event) => {
            void this.#handleCodeSubmit(event);
        });
    }

    /**
     * @param {HTMLElement|null} messageElement
     * @param {string} message
     */
    #showMessage(messageElement, message) {
        if (messageElement === null) {
            return;
        }

        if (message === '') {
            messageElement.hidden = true;
            messageElement.textContent = '';
            return;
        }

        messageElement.hidden = false;
        messageElement.textContent = message;
    }

    #clearMessages() {
        this.#showMessage(this.#errorElement, '');
        this.#showMessage(this.#successElement, '');
    }

    #showEmailStep() {
        if (this.#emailStepElement !== null) {
            this.#emailStepElement.hidden = false;
        }
        if (this.#codeStepElement !== null) {
            this.#codeStepElement.hidden = true;
        }
        if (this.#codeInputElement !== null) {
            this.#codeInputElement.value = '';
        }
    }

    #showCodeStep() {
        if (this.#emailStepElement !== null) {
            this.#emailStepElement.hidden = true;
        }
        if (this.#codeStepElement !== null) {
            this.#codeStepElement.hidden = false;
        }
        this.#codeInputElement?.focus();
    }

    #openModal() {
        this.#clearMessages();
        this.#showEmailStep();
        this.#modalElement.classList.add('is-open');
        this.#modalElement.setAttribute('aria-hidden', 'false');
        this.#emailInputElement?.focus();
    }

    #closeModal() {
        this.#modalElement.classList.remove('is-open');
        this.#modalElement.setAttribute('aria-hidden', 'true');
    }

    /**
     * @param {string} url
     * @param {Record<string, string>} payload
     */
    async #postJson(url, payload) {
        const response = await fetch(url, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(payload),
        });

        const data = await response.json();
        if (!response.ok || data.success !== true) {
            throw new Error(data.message || 'Произошла ошибка. Попробуйте ещё раз.');
        }

        return data;
    }

    /**
     * @param {Event} event
     */
    async #handleEmailSubmit(event) {
        event.preventDefault();
        this.#clearMessages();

        if (this.#emailInputElement === null || this.#emailFormElement === null) {
            return;
        }

        this.#currentEmail = this.#emailInputElement.value.trim();
        if (this.#currentEmail === '') {
            this.#showMessage(this.#errorElement, 'Введите email.');
            return;
        }

        const submitButtonElement = this.#emailFormElement.querySelector('button[type="submit"]');
        if (submitButtonElement instanceof HTMLButtonElement) {
            submitButtonElement.disabled = true;
        }

        try {
            const data = await this.#postJson('/api/auth/request-code', { email: this.#currentEmail });
            this.#showMessage(this.#successElement, data.message || 'Код отправлен на email.');
            this.#showCodeStep();
        } catch (error) {
            const message = error instanceof Error ? error.message : 'Не удалось отправить код.';
            this.#showMessage(this.#errorElement, message);
        } finally {
            if (submitButtonElement instanceof HTMLButtonElement) {
                submitButtonElement.disabled = false;
            }
        }
    }

    /**
     * @param {Event} event
     */
    async #handleCodeSubmit(event) {
        event.preventDefault();
        this.#clearMessages();

        if (this.#codeInputElement === null || this.#codeFormElement === null) {
            return;
        }

        const code = this.#codeInputElement.value.trim();
        if (code === '') {
            this.#showMessage(this.#errorElement, 'Введите код подтверждения.');
            return;
        }

        const submitButtonElement = this.#codeFormElement.querySelector('button[type="submit"]');
        if (submitButtonElement instanceof HTMLButtonElement) {
            submitButtonElement.disabled = true;
        }

        try {
            const data = await this.#postJson('/api/auth/verify-code', {
                email: this.#currentEmail,
                code: code,
            });
            window.location.href = data.redirect || '/cabinet';
        } catch (error) {
            const message = error instanceof Error ? error.message : 'Не удалось подтвердить код.';
            this.#showMessage(this.#errorElement, message);
        } finally {
            if (submitButtonElement instanceof HTMLButtonElement) {
                submitButtonElement.disabled = false;
            }
        }
    }
}

AuthModal.initAll();
