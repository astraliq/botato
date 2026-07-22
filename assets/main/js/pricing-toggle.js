class PricingToggle {
    /** @type {Record<string, number>} */
    static #periodDiscounts = {
        '1': 0,
        '6': 0.15,
        '12': 0.25,
    };

    /** @type {NodeListOf<HTMLElement>} */
    #periodButtonElements;

    /** @type {NodeListOf<HTMLElement>} */
    #priceElements;

    /**
     * @param {HTMLElement} toggleRootElement
     */
    constructor(toggleRootElement) {
        const pricingSectionElement = toggleRootElement.closest('section');
        if (pricingSectionElement === null) {
            return;
        }

        this.#periodButtonElements = toggleRootElement.querySelectorAll('[data-period]');
        this.#priceElements = pricingSectionElement.querySelectorAll('[data-price][data-base-price]');

        this.#bindEvents();
    }

    static initAll() {
        document.querySelectorAll('[data-pricing-toggle]').forEach((toggleRootElement) => {
            if (toggleRootElement instanceof HTMLElement && toggleRootElement.closest('section') !== null) {
                new PricingToggle(toggleRootElement);
            }
        });
    }

    #bindEvents() {
        this.#periodButtonElements.forEach((buttonElement) => {
            buttonElement.addEventListener('click', () => {
                const periodMonths = buttonElement.dataset.period;
                if (periodMonths !== undefined) {
                    this.#setPeriod(periodMonths);
                }
            });
        });
    }

    /**
     * @param {number} amount
     */
    #formatPrice(amount) {
        return new Intl.NumberFormat('ru-RU').format(Math.round(amount)) + ' ₽';
    }

    /**
     * @param {string} periodMonths
     */
    #setPeriod(periodMonths) {
        const months = Number(periodMonths);
        const discount = PricingToggle.#periodDiscounts[periodMonths] ?? 0;
        const priceMultiplier = 1 - discount;

        this.#periodButtonElements.forEach((buttonElement) => {
            buttonElement.classList.toggle('is-active', buttonElement.dataset.period === periodMonths);
        });

        this.#priceElements.forEach((priceElement) => {
            const basePrice = Number(priceElement.dataset.basePrice);
            const monthlyPrice = basePrice * priceMultiplier;
            let periodLabel = months === 1 || basePrice === 0
                ? '/мес'
                : '/мес при оплате за ' + months + ' мес';

            if (months > 1 && basePrice > 0) {
                const totalPrice = basePrice * months * priceMultiplier;
                periodLabel += ' · ' + this.#formatPrice(totalPrice);
            }

            let priceHtml = '<span class="pricing-card__price-current">'
                + this.#formatPrice(monthlyPrice)
                + '</span>';

            if (discount > 0 && basePrice > 0) {
                priceHtml += '<span class="pricing-card__price-old">'
                    + this.#formatPrice(basePrice)
                    + '</span>';
            }

            priceElement.innerHTML = priceHtml + '<small>' + periodLabel + '</small>';
        });
    }
}

PricingToggle.initAll();
