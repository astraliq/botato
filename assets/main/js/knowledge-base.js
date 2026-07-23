class KnowledgeBaseForms {
    /** @type {HTMLElement|null} */
    #pageElement;

    /** @type {HTMLElement|null} */
    #successElement;

    /**
     * @param {HTMLElement} pageElement
     */
    constructor(pageElement) {
        this.#pageElement = pageElement;
        this.#successElement = pageElement.querySelector('[data-kb-success]');

        pageElement.querySelectorAll('[data-kb-form]').forEach((formElement) => {
            if (!(formElement instanceof HTMLFormElement)) {
                return;
            }

            formElement.addEventListener('submit', (event) => {
                this.#handleSubmit(event, formElement);
            });
        });
    }

    static initAll() {
        document.querySelectorAll('[data-kb-page]').forEach((pageElement) => {
            if (pageElement instanceof HTMLElement) {
                new KnowledgeBaseForms(pageElement);
            }
        });
    }

    /**
     * @param {Event} event
     * @param {HTMLFormElement} formElement
     */
    async #handleSubmit(event, formElement) {
        event.preventDefault();
        this.#clearMessages(formElement);

        const confirmMessage = formElement.getAttribute('data-kb-confirm');
        if (confirmMessage !== null && confirmMessage !== '' && !window.confirm(confirmMessage)) {
            return;
        }

        // Payload must be collected before FormPreloader disables inputs —
        // FormData omits disabled fields (including hidden form_action).
        const payload = this.#formToPayload(formElement);

        const formPreloader = typeof FormPreloader !== 'undefined'
            ? FormPreloader.create(formElement)
            : null;

        formPreloader?.setLoading(true);

        try {
            const data = await this.#postJson(formElement.action, payload);

            if (formElement.hasAttribute('data-kb-no-reload')) {
                this.#showMessage(this.#successElement, data.message || 'Сохранено.');
                this.#applyLocalUpdates(formElement, payload);
                return;
            }

            this.#reloadPage(data.knowledgeBaseId ?? null);
        } catch (error) {
            const message = error instanceof Error ? error.message : 'Не удалось выполнить действие.';
            const formErrorElement = formElement.querySelector('[data-kb-form-error]');
            this.#showMessage(
                formErrorElement instanceof HTMLElement ? formErrorElement : null,
                message,
            );
        } finally {
            formPreloader?.setLoading(false);
        }
    }

    /**
     * @param {HTMLFormElement} formElement
     * @returns {Record<string, string|boolean>}
     */
    #formToPayload(formElement) {
        const formData = new FormData(formElement);
        /** @type {Record<string, string|boolean>} */
        const payload = {};

        formData.forEach((value, key) => {
            if (typeof value === 'string') {
                payload[key] = value;
            }
        });

        const activeCheckbox = formElement.querySelector('input[name="is_active"][type="checkbox"]');
        if (activeCheckbox instanceof HTMLInputElement) {
            payload.is_active = activeCheckbox.checked;
        }

        return payload;
    }

    /**
     * @param {string} url
     * @param {Record<string, string|boolean>} payload
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
     * @param {HTMLFormElement} formElement
     * @param {Record<string, string|boolean>} payload
     */
    #applyLocalUpdates(formElement, payload) {
        if (payload.form_action !== 'update_base' || typeof payload.title !== 'string') {
            return;
        }

        const title = payload.title.trim();
        if (title === '') {
            return;
        }

        this.#pageElement?.querySelectorAll('[data-kb-panel-title]').forEach((element) => {
            element.textContent = title;
        });

        const knowledgeBaseId = typeof payload.knowledge_base_id === 'string'
            ? payload.knowledge_base_id
            : null;

        if (knowledgeBaseId === null) {
            return;
        }

        this.#pageElement?.querySelectorAll('[data-kb-pill]').forEach((pillElement) => {
            if (!(pillElement instanceof HTMLElement) || pillElement.dataset.kbId !== knowledgeBaseId) {
                return;
            }

            const titleElement = pillElement.querySelector('[data-kb-pill-title]');
            if (titleElement !== null) {
                titleElement.textContent = title;
            }
        });
    }

    /**
     * @param {string|null} knowledgeBaseId
     */
    #reloadPage(knowledgeBaseId) {
        const pageUrl = this.#pageElement?.getAttribute('data-kb-page-url') ?? window.location.pathname;
        if (knowledgeBaseId !== null && knowledgeBaseId !== '') {
            window.location.assign(`${pageUrl}?kb=${encodeURIComponent(knowledgeBaseId)}`);
            return;
        }

        window.location.assign(pageUrl);
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
        element.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    /**
     * @param {HTMLFormElement} formElement
     */
    #clearMessages(formElement) {
        if (this.#successElement !== null) {
            this.#successElement.hidden = true;
            this.#successElement.textContent = '';
        }

        this.#pageElement?.querySelectorAll('[data-kb-form-error]').forEach((element) => {
            if (element instanceof HTMLElement) {
                element.hidden = true;
                element.textContent = '';
            }
        });

        const formErrorElement = formElement.querySelector('[data-kb-form-error]');
        if (formErrorElement instanceof HTMLElement) {
            formErrorElement.hidden = true;
            formErrorElement.textContent = '';
        }
    }
}

KnowledgeBaseForms.initAll();
