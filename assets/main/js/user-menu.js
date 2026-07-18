class UserMenu {
    /** @type {HTMLElement} */
    #rootElement;

    /** @type {HTMLButtonElement|null} */
    #toggleButtonElement;

    /** @type {HTMLElement|null} */
    #dropdownElement;

    /**
     * @param {HTMLElement} rootElement
     */
    constructor(rootElement) {
        this.#rootElement = rootElement;
        this.#toggleButtonElement = rootElement.querySelector('[data-user-menu-toggle]');
        this.#dropdownElement = rootElement.querySelector('[data-user-menu-dropdown]');

        this.#bindEvents();
    }

    static initAll() {
        document.querySelectorAll('[data-user-menu]').forEach((rootElement) => {
            if (rootElement instanceof HTMLElement) {
                new UserMenu(rootElement);
            }
        });
    }

    #bindEvents() {
        this.#toggleButtonElement?.addEventListener('click', (event) => {
            event.stopPropagation();
            this.#toggle();
        });

        this.#rootElement.querySelector('#logout-button')?.addEventListener('click', () => {
            this.#logout();
        });

        document.addEventListener('click', (event) => {
            if (!(event.target instanceof Node) || !this.#rootElement.contains(event.target)) {
                this.#close();
            }
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                this.#close();
            }
        });
    }

    async #logout() {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        const response = await fetch('/api/auth/logout', {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-Token': csrfToken,
            },
        });
        const data = await response.json();
        window.location.href = data.redirect || '/';
    }

    #toggle() {
        if (this.#dropdownElement?.hidden) {
            this.#open();
            return;
        }

        this.#close();
    }

    #open() {
        if (this.#dropdownElement === null || this.#toggleButtonElement === null) {
            return;
        }

        this.#dropdownElement.hidden = false;
        this.#rootElement.classList.add('is-open');
        this.#toggleButtonElement.setAttribute('aria-expanded', 'true');
    }

    #close() {
        if (this.#dropdownElement === null || this.#toggleButtonElement === null) {
            return;
        }

        this.#dropdownElement.hidden = true;
        this.#rootElement.classList.remove('is-open');
        this.#toggleButtonElement.setAttribute('aria-expanded', 'false');
    }
}

UserMenu.initAll();
