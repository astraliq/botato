class OnboardingWizard {
    /** @type {HTMLElement} */
    #rootElement;

    /** @type {number} */
    #currentStep = 1;

    /** @type {boolean} */
    #hasKnowledgeBase = false;

    /** @type {boolean} */
    #telegramConnected = false;

    /** @type {HTMLElement|null} */
    #errorElement;

    /** @type {HTMLElement|null} */
    #successElement;

    /**
     * @param {HTMLElement} rootElement
     */
    constructor(rootElement) {
        this.#rootElement = rootElement;
        this.#errorElement = rootElement.querySelector('[data-onboarding-error]');
        this.#successElement = rootElement.querySelector('[data-onboarding-success]');
        this.#hasKnowledgeBase = rootElement.dataset.hasKnowledgeBase === '1';
        this.#telegramConnected = rootElement.dataset.telegramConnected === '1';

        const initialStep = Number.parseInt(rootElement.dataset.initialStep ?? '1', 10);
        this.#showStep(Number.isFinite(initialStep) && initialStep >= 1 ? initialStep : 1);
        this.#syncFinishAvailability();

        const profileForm = rootElement.querySelector('[data-onboarding-profile-form]');
        if (profileForm instanceof HTMLFormElement) {
            profileForm.addEventListener('submit', (event) => {
                this.#handleProfileSubmit(event, profileForm);
            });
        }

        const knowledgeForm = rootElement.querySelector('[data-onboarding-knowledge-form]');
        if (knowledgeForm instanceof HTMLFormElement) {
            knowledgeForm.addEventListener('submit', (event) => {
                this.#handleKnowledgeSubmit(event, knowledgeForm);
            });
        }

        const telegramForm = rootElement.querySelector('[data-onboarding-telegram-form]');
        if (telegramForm instanceof HTMLFormElement) {
            telegramForm.addEventListener('submit', (event) => {
                this.#handleTelegramConnect(event, telegramForm);
            });
        }

        const finishButton = rootElement.querySelector('[data-onboarding-finish]');
        if (finishButton instanceof HTMLButtonElement) {
            finishButton.addEventListener('click', () => {
                this.#handleFinish();
            });
        }

        rootElement.querySelectorAll('[data-onboarding-back]').forEach((buttonElement) => {
            buttonElement.addEventListener('click', () => {
                this.#showStep(this.#currentStep - 1);
            });
        });
    }

    static initAll() {
        document.querySelectorAll('[data-onboarding]').forEach((rootElement) => {
            if (rootElement instanceof HTMLElement) {
                new OnboardingWizard(rootElement);
            }
        });
    }

    /**
     * @param {number} step
     */
    #showStep(step) {
        if (step < 1 || step > 3) {
            return;
        }

        this.#currentStep = step;
        this.#clearMessages();

        this.#rootElement.querySelectorAll('[data-onboarding-step]').forEach((stepElement) => {
            if (!(stepElement instanceof HTMLElement)) {
                return;
            }

            const stepNumber = Number.parseInt(stepElement.dataset.onboardingStep ?? '0', 10);
            const isActive = stepNumber === step;
            stepElement.classList.toggle('is-active', isActive);
            stepElement.hidden = !isActive;
        });

        this.#rootElement.querySelectorAll('[data-onboarding-progress-item]').forEach((itemElement) => {
            if (!(itemElement instanceof HTMLElement)) {
                return;
            }

            const itemStep = Number.parseInt(itemElement.dataset.onboardingProgressItem ?? '0', 10);
            itemElement.classList.toggle('is-active', itemStep === step);
            itemElement.classList.toggle('is-done', itemStep < step);
        });
    }

    /**
     * @param {Event} event
     * @param {HTMLFormElement} formElement
     */
    async #handleProfileSubmit(event, formElement) {
        event.preventDefault();
        this.#clearMessages();

        const formData = new FormData(formElement);
        const payload = {
            surname: String(formData.get('surname') ?? '').trim(),
            name: String(formData.get('name') ?? '').trim(),
            patronymic: String(formData.get('patronymic') ?? '').trim(),
            company_name: String(formData.get('company_name') ?? '').trim(),
            description: String(formData.get('description') ?? '').trim(),
            phone: String(formData.get('phone') ?? '').trim(),
            gender: String(formData.get('gender') ?? '').trim(),
        };

        if (payload.surname === '' || payload.name === '' || payload.company_name === '' || payload.gender === '') {
            this.#showError('Заполните обязательные поля профиля.');
            return;
        }

        const formPreloader = typeof FormPreloader !== 'undefined' ? FormPreloader.create(formElement) : null;
        formPreloader?.setLoading(true);

        try {
            await this.#postJson(this.#rootElement.dataset.saveProfileUrl ?? '', payload);
            this.#showSuccess('Профиль сохранён.');
            this.#showStep(2);
        } catch (error) {
            this.#showError(error instanceof Error ? error.message : 'Не удалось сохранить профиль.');
        } finally {
            formPreloader?.setLoading(false);
        }
    }

    /**
     * @param {Event} event
     * @param {HTMLFormElement} formElement
     */
    async #handleKnowledgeSubmit(event, formElement) {
        event.preventDefault();
        this.#clearMessages();

        if (this.#hasKnowledgeBase) {
            this.#showStep(3);
            return;
        }

        const titleInput = formElement.querySelector('[name="title"]');
        const title = titleInput instanceof HTMLInputElement ? titleInput.value.trim() : '';

        if (title === '') {
            this.#showError('Укажите название базы знаний.');
            return;
        }

        const formPreloader = typeof FormPreloader !== 'undefined' ? FormPreloader.create(formElement) : null;
        formPreloader?.setLoading(true);

        try {
            await this.#postJson(this.#rootElement.dataset.createKnowledgeUrl ?? '', {
                form_action: 'create_base',
                title,
                summary: '',
            });
            this.#hasKnowledgeBase = true;
            this.#rootElement.dataset.hasKnowledgeBase = '1';
            this.#showSuccess('База знаний создана.');
            this.#showStep(3);
        } catch (error) {
            this.#showError(error instanceof Error ? error.message : 'Не удалось создать базу знаний.');
        } finally {
            formPreloader?.setLoading(false);
        }
    }

    /**
     * @param {Event} event
     * @param {HTMLFormElement} formElement
     */
    async #handleTelegramConnect(event, formElement) {
        event.preventDefault();
        this.#clearMessages();

        const tokenInput = formElement.querySelector('[name="bot_token"]');
        const botToken = tokenInput instanceof HTMLInputElement ? tokenInput.value.trim() : '';

        if (botToken === '') {
            this.#showError('Укажите токен бота.');
            return;
        }

        const formPreloader = typeof FormPreloader !== 'undefined' ? FormPreloader.create(formElement) : null;
        formPreloader?.setLoading(true);

        try {
            const connectData = await this.#postJson(this.#rootElement.dataset.connectTelegramUrl ?? '', {
                bot_token: botToken,
            });
            this.#telegramConnected = true;
            this.#rootElement.dataset.telegramConnected = '1';
            this.#markTelegramConnected(connectData.displayName ?? null);
            this.#enableKnowledgeAssignment();
            this.#syncFinishAvailability();
            this.#showSuccess(connectData.message || 'Telegram-бот подключён. Продолжите шаги 5–6 ниже.');

            if (tokenInput instanceof HTMLInputElement) {
                tokenInput.value = '';
            }
        } catch (error) {
            this.#showError(error instanceof Error ? error.message : 'Не удалось подключить бота.');
        } finally {
            formPreloader?.setLoading(false);
        }
    }

    async #handleFinish() {
        this.#clearMessages();

        if (!this.#telegramConnected) {
            this.#showError('Сначала подключите и проверьте токен бота.');
            return;
        }

        const knowledgeBaseIds = this.#selectedKnowledgeBaseIds();
        if (knowledgeBaseIds.length === 0) {
            this.#showError('Выберите хотя бы одну базу знаний.');
            return;
        }

        const finishButton = this.#rootElement.querySelector('[data-onboarding-finish]');
        if (finishButton instanceof HTMLButtonElement) {
            finishButton.disabled = true;
        }

        try {
            const completeData = await this.#postJson(this.#rootElement.dataset.completeUrl ?? '', {
                knowledge_base_ids: knowledgeBaseIds,
            });
            window.location.href = completeData.redirect || this.#rootElement.dataset.settingsUrl || '/cabinet/settings';
        } catch (error) {
            this.#showError(error instanceof Error ? error.message : 'Не удалось завершить настройку.');
            this.#syncFinishAvailability();
        }
    }

    /**
     * @returns {string[]}
     */
    #selectedKnowledgeBaseIds() {
        /** @type {string[]} */
        const selectedIds = [];

        this.#rootElement.querySelectorAll('[data-onboarding-kb-checkbox]').forEach((checkboxElement) => {
            if (checkboxElement instanceof HTMLInputElement && checkboxElement.checked) {
                selectedIds.push(checkboxElement.value);
            }
        });

        return selectedIds;
    }

    #enableKnowledgeAssignment() {
        const fieldsetElement = this.#rootElement.querySelector('[data-onboarding-kb-fieldset]');
        if (fieldsetElement instanceof HTMLFieldSetElement) {
            fieldsetElement.disabled = false;
        }

        this.#rootElement.querySelectorAll('[data-onboarding-kb-checkbox]').forEach((checkboxElement) => {
            if (checkboxElement instanceof HTMLInputElement && !checkboxElement.classList.contains('is-disabled')) {
                const labelElement = checkboxElement.closest('label');
                const isInactive = labelElement?.classList.contains('is-disabled') === true;
                checkboxElement.disabled = isInactive;
            }
        });

        const needBotNote = this.#rootElement.querySelector('[data-onboarding-kb-need-bot]');
        if (needBotNote instanceof HTMLElement) {
            needBotNote.hidden = true;
        }
    }

    #syncFinishAvailability() {
        const finishButton = this.#rootElement.querySelector('[data-onboarding-finish]');
        if (finishButton instanceof HTMLButtonElement) {
            finishButton.disabled = !this.#telegramConnected;
        }
    }

    /**
     * @param {string|null} displayName
     */
    #markTelegramConnected(displayName) {
        if (typeof displayName === 'string' && displayName !== '') {
            this.#rootElement.querySelectorAll('[data-telegram-bot-username]').forEach((usernameElement) => {
                usernameElement.textContent = displayName;
            });
        }

        let statusElement = this.#rootElement.querySelector('[data-onboarding-telegram-status]');
        if (!(statusElement instanceof HTMLElement)) {
            const formElement = this.#rootElement.querySelector('[data-onboarding-telegram-form]');
            if (!(formElement instanceof HTMLFormElement)) {
                return;
            }

            statusElement = document.createElement('div');
            statusElement.className = 'channel-status';
            statusElement.dataset.onboardingTelegramStatus = '';
            formElement.prepend(statusElement);
        }

        statusElement.innerHTML = '';
        const badgeElement = document.createElement('div');
        badgeElement.className = 'channel-status__badge';
        badgeElement.textContent = 'Подключено';
        statusElement.appendChild(badgeElement);

        if (typeof displayName === 'string' && displayName !== '') {
            const metaElement = document.createElement('div');
            metaElement.className = 'channel-status__meta';
            const strongElement = document.createElement('strong');
            strongElement.textContent = displayName;
            metaElement.appendChild(strongElement);
            statusElement.appendChild(metaElement);
        }

        const connectButton = this.#rootElement.querySelector('[data-onboarding-connect]');
        if (connectButton instanceof HTMLButtonElement) {
            connectButton.textContent = 'Обновить токен';
        }

        const tokenInput = this.#rootElement.querySelector('#onboarding_bot_token');
        if (tokenInput instanceof HTMLInputElement) {
            tokenInput.required = false;
        }
    }

    /**
     * @param {string} url
     * @param {Record<string, unknown>} payload
     * @returns {Promise<Record<string, any>>}
     */
    async #postJson(url, payload) {
        if (url === '') {
            throw new Error('Не задан URL запроса.');
        }

        const response = await fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
            },
            credentials: 'same-origin',
            body: JSON.stringify(payload),
        });

        let data = {};
        try {
            data = await response.json();
        } catch (error) {
            throw new Error('Сервер вернул некорректный ответ.');
        }

        if (!response.ok || data.success !== true) {
            throw new Error(typeof data.message === 'string' && data.message !== ''
                ? data.message
                : 'Запрос не выполнен.');
        }

        return data;
    }

    /**
     * @param {string} message
     */
    #showError(message) {
        if (this.#errorElement === null) {
            return;
        }

        this.#errorElement.hidden = false;
        this.#errorElement.textContent = message;

        if (this.#successElement !== null) {
            this.#successElement.hidden = true;
            this.#successElement.textContent = '';
        }
    }

    /**
     * @param {string} message
     */
    #showSuccess(message) {
        if (this.#successElement === null) {
            return;
        }

        this.#successElement.hidden = false;
        this.#successElement.textContent = message;

        if (this.#errorElement !== null) {
            this.#errorElement.hidden = true;
            this.#errorElement.textContent = '';
        }
    }

    #clearMessages() {
        if (this.#errorElement !== null) {
            this.#errorElement.hidden = true;
            this.#errorElement.textContent = '';
        }

        if (this.#successElement !== null) {
            this.#successElement.hidden = true;
            this.#successElement.textContent = '';
        }
    }
}

document.addEventListener('DOMContentLoaded', () => {
    OnboardingWizard.initAll();
});
