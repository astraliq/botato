class ConnectTelegramForm {
    /** @type {HTMLFormElement} */
    #formElement;

    /** @type {HTMLElement|null} */
    #successElement;

    /** @type {HTMLElement|null} */
    #errorElement;

    /** @type {HTMLElement|null} */
    #statusElement;

    /** @type {HTMLElement|null} */
    #tabCheckElement;

    /** @type {HTMLButtonElement|null} */
    #submitButtonElement;

    /** @type {HTMLInputElement|null} */
    #tokenInputElement;

    /** @type {FormPreloader|null} */
    #formPreloader;

    /**
     * @param {HTMLFormElement} formElement
     */
    constructor(formElement) {
        this.#formElement = formElement;
        this.#successElement = document.querySelector('[data-telegram-success]');
        this.#errorElement = formElement.querySelector('[data-telegram-error]');
        this.#statusElement = document.querySelector('[data-telegram-status]');
        this.#tabCheckElement = document.querySelector('[data-telegram-tab-check]');
        this.#submitButtonElement = formElement.querySelector('button[type="submit"]');
        this.#tokenInputElement = formElement.querySelector('[name="bot_token"]');
        this.#formPreloader = FormPreloader.create(formElement);

        this.#formElement.addEventListener('submit', (event) => {
            this.#handleSubmit(event);
        });
    }

    static initAll() {
        document.querySelectorAll('[data-connect-telegram-form]').forEach((formElement) => {
            if (formElement instanceof HTMLFormElement) {
                new ConnectTelegramForm(formElement);
            }
        });
    }

    /**
     * @param {Event} event
     */
    async #handleSubmit(event) {
        event.preventDefault();
        this.#clearMessages();

        const botToken = this.#tokenInputElement?.value.trim() ?? '';
        if (botToken === '') {
            this.#showMessage(this.#errorElement, 'Укажите токен бота.');
            return;
        }

        this.#formPreloader?.setLoading(true);

        try {
            const data = await this.#postJson(this.#formElement.action, { bot_token: botToken });
            this.#showMessage(this.#successElement, data.message || 'Telegram-бот подключён.');
            this.#updateConnectedState(data.displayName ?? null, data.secretHint ?? null);
            if (this.#tokenInputElement !== null) {
                this.#tokenInputElement.value = '';
            }
        } catch (error) {
            const message = error instanceof Error ? error.message : 'Не удалось подключить бота.';
            this.#showMessage(this.#errorElement, message);
        } finally {
            this.#formPreloader?.setLoading(false);
        }
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
     * @param {string|null} displayName
     * @param {string|null} secretHint
     */
    #updateConnectedState(displayName, secretHint) {
        const statusMountElement = document.querySelector('[data-telegram-status-mount]');
        if (this.#statusElement === null && statusMountElement instanceof HTMLElement) {
            this.#statusElement = document.createElement('div');
            this.#statusElement.className = 'channel-status';
            this.#statusElement.setAttribute('data-telegram-status', '');
            statusMountElement.replaceChildren(this.#statusElement);
        }

        if (this.#statusElement !== null) {
            const displayNameHtml = displayName !== null && displayName !== ''
                ? `<strong>${this.#escapeHtml(displayName)}</strong>`
                : '';
            const secretHintHtml = secretHint !== null && secretHint !== ''
                ? `<span class="channel-status__hint">токен ···${this.#escapeHtml(secretHint)}</span>`
                : '';

            this.#statusElement.innerHTML = `
                <div class="channel-status__badge">Подключено</div>
                <div class="channel-status__meta">
                    ${displayNameHtml}
                    ${secretHintHtml}
                </div>
            `;
        }

        if (this.#tabCheckElement === null) {
            const tabElement = document.querySelector('[data-telegram-tab]');
            if (tabElement instanceof HTMLElement) {
                this.#tabCheckElement = document.createElement('span');
                this.#tabCheckElement.className = 'cabinet__tab-check';
                this.#tabCheckElement.title = 'Telegram подключён';
                this.#tabCheckElement.setAttribute('aria-label', 'Telegram подключён');
                this.#tabCheckElement.setAttribute('data-telegram-tab-check', '');
                this.#tabCheckElement.textContent = '✓';
                tabElement.append(this.#tabCheckElement);
            }
        }

        if (this.#submitButtonElement !== null) {
            this.#submitButtonElement.textContent = 'Обновить токен';
        }

        document.querySelectorAll('[data-telegram-bot-username]').forEach((element) => {
            if (displayName !== null && displayName !== '') {
                element.textContent = displayName;
            }
        });
    }

    /**
     * @param {HTMLElement|null} element
     * @param {string} message
     */
    #showMessage(element, message) {
        if (element === null) {
            return;
        }

        element.textContent = message;
        element.hidden = false;
    }

    #clearMessages() {
        if (this.#successElement !== null) {
            this.#successElement.hidden = true;
            this.#successElement.textContent = '';
        }

        if (this.#errorElement !== null) {
            this.#errorElement.hidden = true;
            this.#errorElement.textContent = '';
        }
    }

    /**
     * @param {string} value
     * @returns {string}
     */
    #escapeHtml(value) {
        const templateElement = document.createElement('div');
        templateElement.textContent = value;

        return templateElement.innerHTML;
    }
}

ConnectTelegramForm.initAll();
