/**
 * @param {unknown} error
 * @returns {string}
 */
function resolveTelegramConnectErrorMessage(error) {
    const fallbackMessage = 'Произошла ошибка, напишите нам в поддержку или попробуйте позже.';
    if (!(error instanceof Error)) {
        return fallbackMessage;
    }

    const message = error.message.trim();
    if (
        message === ''
        || message === 'Failed to fetch'
        || message === 'Load failed'
        || message === 'NetworkError when attempting to fetch resource.'
        || message === 'Network request failed'
    ) {
        return fallbackMessage;
    }

    return message;
}

/**
 * @param {string} value
 * @returns {string}
 */
function extractTelegramBotToken(value) {
    const match = String(value).match(/\d+:[A-Za-z0-9_-]+/);

    return match !== null ? match[0] : String(value).trim();
}

/**
 * @param {HTMLInputElement} tokenInputElement
 */
function bindTelegramBotTokenInput(tokenInputElement) {
    tokenInputElement.addEventListener('paste', (event) => {
        const clipboardData = event.clipboardData;
        if (clipboardData === null) {
            return;
        }

        const pastedText = clipboardData.getData('text');
        const extractedToken = extractTelegramBotToken(pastedText);
        if (extractedToken === pastedText.trim()) {
            return;
        }

        event.preventDefault();
        tokenInputElement.value = extractedToken;
    });
}

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

        if (this.#tokenInputElement instanceof HTMLInputElement) {
            bindTelegramBotTokenInput(this.#tokenInputElement);
        }

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

        const botToken = extractTelegramBotToken(this.#tokenInputElement?.value ?? '');
        if (botToken === '') {
            this.#showMessage(this.#errorElement, 'Укажите токен бота.');
            return;
        }

        if (this.#tokenInputElement !== null) {
            this.#tokenInputElement.value = botToken;
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
            this.#showMessage(this.#errorElement, resolveTelegramConnectErrorMessage(error));
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

        this.#enableKnowledgeAssignSection();
    }

    #enableKnowledgeAssignSection() {
        const needBotElement = document.querySelector('[data-assign-kb-need-bot]');
        if (needBotElement instanceof HTMLElement) {
            needBotElement.hidden = true;
        }

        const emptyElement = document.querySelector('[data-assign-kb-empty]');
        if (emptyElement instanceof HTMLElement) {
            emptyElement.hidden = false;
        }

        const formElement = document.querySelector('[data-assign-kb-form]');
        if (formElement instanceof HTMLElement) {
            formElement.hidden = false;
        }
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

class AssignTelegramKnowledgeForm {
    /** @type {HTMLFormElement} */
    #formElement;

    /** @type {HTMLElement|null} */
    #errorElement;

    /** @type {HTMLElement|null} */
    #successElement;

    /** @type {HTMLElement|null} */
    #counterElement;

    /** @type {FormPreloader|null} */
    #formPreloader;

    /** @type {number} */
    #maxKnowledgeBases;

    /**
     * @param {HTMLFormElement} formElement
     */
    constructor(formElement) {
        this.#formElement = formElement;
        this.#errorElement = formElement.querySelector('[data-assign-kb-error]');
        this.#successElement = document.querySelector('[data-telegram-success]');
        this.#counterElement = formElement.querySelector('[data-assign-kb-counter]');
        this.#formPreloader = typeof FormPreloader !== 'undefined'
            ? FormPreloader.create(formElement)
            : null;
        this.#maxKnowledgeBases = Math.max(0, Number.parseInt(formElement.getAttribute('data-assign-kb-max') ?? '0', 10) || 0);

        formElement.querySelectorAll('[data-assign-kb-checkbox]').forEach((checkboxElement) => {
            if (checkboxElement instanceof HTMLInputElement) {
                checkboxElement.addEventListener('change', () => {
                    this.#enforceSelectionLimit(checkboxElement);
                    this.#updateCounter();
                });
            }
        });

        formElement.addEventListener('submit', (event) => {
            this.#handleSubmit(event);
        });

        this.#updateCounter();
    }

    static initAll() {
        document.querySelectorAll('[data-assign-kb-form]').forEach((formElement) => {
            if (formElement instanceof HTMLFormElement) {
                new AssignTelegramKnowledgeForm(formElement);
            }
        });
    }

    /**
     * @param {Event} event
     */
    async #handleSubmit(event) {
        event.preventDefault();
        this.#clearMessages();

        const payload = this.#formToPayload();
        const selectedCount = payload.knowledge_base_ids.length;

        if (this.#maxKnowledgeBases > 0 && selectedCount > this.#maxKnowledgeBases) {
            this.#showMessage(
                this.#errorElement,
                `По тарифу можно выбрать не больше ${this.#maxKnowledgeBases} баз знаний.`,
            );
            return;
        }

        this.#formPreloader?.setLoading(true);

        try {
            const data = await this.#postJson(this.#formElement.action, payload);
            this.#showMessage(this.#successElement, data.message || 'Базы знаний сохранены.');
            this.#successElement?.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        } catch (error) {
            const message = error instanceof Error ? error.message : 'Не удалось сохранить базы знаний.';
            this.#showMessage(this.#errorElement, message);
        } finally {
            this.#formPreloader?.setLoading(false);
        }
    }

    /**
     * @returns {{form_action: string, knowledge_base_ids: string[]}}
     */
    #formToPayload() {
        /** @type {string[]} */
        const knowledgeBaseIds = [];

        this.#formElement.querySelectorAll('[data-assign-kb-checkbox]').forEach((checkboxElement) => {
            if (checkboxElement instanceof HTMLInputElement && checkboxElement.checked && !checkboxElement.disabled) {
                knowledgeBaseIds.push(checkboxElement.value);
            }
        });

        return {
            form_action: 'assign_telegram',
            knowledge_base_ids: knowledgeBaseIds,
        };
    }

    /**
     * @param {HTMLInputElement} changedCheckbox
     */
    #enforceSelectionLimit(changedCheckbox) {
        if (this.#maxKnowledgeBases <= 0 || !changedCheckbox.checked) {
            return;
        }

        const checkedCount = this.#formElement.querySelectorAll('[data-assign-kb-checkbox]:checked').length;
        if (checkedCount <= this.#maxKnowledgeBases) {
            return;
        }

        changedCheckbox.checked = false;
        this.#showMessage(
            this.#errorElement,
            `По тарифу можно выбрать не больше ${this.#maxKnowledgeBases} баз знаний.`,
        );
    }

    #updateCounter() {
        if (this.#counterElement === null) {
            return;
        }

        const checkedCount = this.#formElement.querySelectorAll('[data-assign-kb-checkbox]:checked').length;
        this.#counterElement.textContent = `Выбрано: ${checkedCount} из ${this.#maxKnowledgeBases}`;
    }

    /**
     * @param {string} url
     * @param {Record<string, unknown>} payload
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
}

AssignTelegramKnowledgeForm.initAll();
