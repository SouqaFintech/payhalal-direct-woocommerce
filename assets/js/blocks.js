(function () {
    const settings = window.wc && window.wc.wcSettings ? window.wc.wcSettings : null;
    const registry = window.wc && window.wc.wcBlocksRegistry ? window.wc.wcBlocksRegistry : null;
    const element = window.wp && window.wp.element ? window.wp.element : null;
    const htmlEntities = window.wp && window.wp.htmlEntities ? window.wp.htmlEntities : null;
    const i18n = window.wp && window.wp.i18n ? window.wp.i18n : null;

    if (!settings || !registry || !element) {
        return;
    }

    const { registerPaymentMethod } = registry;
    const { createElement: el, useEffect, useState } = element;
    const decodeEntities = htmlEntities && htmlEntities.decodeEntities ? htmlEntities.decodeEntities : function (value) { return value; };
    const __ = i18n && i18n.__ ? i18n.__ : function (value) { return value; };
    const data = settings.getSetting('payhalal_direct_data', {});

    const onlyDigits = function (value) {
        return (value || '').replace(/\D+/g, '');
    };

    const formatCardNumber = function (value) {
        return onlyDigits(value).replace(/(.{4})/g, '$1 ').trim().slice(0, 23);
    };

    const detectBrand = function (value) {
        const n = onlyDigits(value);
        if (/^4/.test(n)) return 'Visa';
        if (/^(5[1-5]|2[2-7])/.test(n)) return 'Mastercard';
        if (/^3[47]/.test(n)) return 'Amex';
        if (/^6(?:011|5)/.test(n)) return 'Discover';
        return 'Card';
    };

    const maskPreview = function (value) {
        const n = onlyDigits(value).slice(0, 16);
        return (n + '••••••••••••••••').slice(0, 16).replace(/(.{4})/g, '$1 ').trim();
    };

    const Field = function ({ id, label, type, inputMode, autoComplete, placeholder, maxLength, value, onChange, className, children }) {
        return el('p', { className: 'payhalal-direct-field ' + (className || '') },
            el('label', { htmlFor: id }, label),
            el('input', {
                id,
                name: id,
                type: type || 'text',
                inputMode: inputMode || undefined,
                autoComplete: autoComplete || undefined,
                placeholder: placeholder || undefined,
                maxLength: maxLength || undefined,
                value,
                onChange: function (event) { onChange(event.target.value); }
            }),
            children || null
        );
    };

    const Content = function (props) {
        const { eventRegistration, emitResponse } = props;
        const [holder, setHolder] = useState('');
        const [number, setNumber] = useState('');
        const [month, setMonth] = useState('');
        const [year, setYear] = useState('');
        const [cvv, setCvv] = useState('');
        const brand = detectBrand(number);

        useEffect(function () {
            const unsubscribe = eventRegistration.onPaymentSetup(function () {
                if (!holder || !number || !month || !year || !cvv) {
                    return {
                        type: emitResponse.responseTypes.ERROR,
                        message: __('Please complete your PayHalal Direct card details.', 'payhalal-direct'),
                    };
                }

                return {
                    type: emitResponse.responseTypes.SUCCESS,
                    meta: {
                        paymentMethodData: {
                            payhalal_direct_method: 'card',
                            payhalal_direct_card_holder_name: holder,
                            payhalal_direct_card_number: number,
                            payhalal_direct_card_exp_mn: month,
                            payhalal_direct_card_exp_yy: year,
                            payhalal_direct_card_cvv: cvv,
                        },
                    },
                };
            });

            return unsubscribe;
        }, [eventRegistration, emitResponse, holder, number, month, year, cvv]);

        return el('div', { className: 'payhalal-direct-box payhalal-direct-blocks' },
            data.description ? el('div', { className: 'payhalal-direct-description', dangerouslySetInnerHTML: { __html: data.description } }) : null,
            el('div', { className: 'payhalal-direct-header' },
                el('div', { className: 'payhalal-direct-title' },
                    el('span', { className: 'payhalal-direct-mark', 'aria-hidden': true }, '⌁'),
                    el('div', {},
                        el('strong', {}, decodeEntities(data.title || 'PayHalal Direct')),
                        el('span', {}, __('Secure card payment powered by PayHalal', 'payhalal-direct'))
                    )
                ),
                el('em', { className: 'payhalal-direct-badge' }, __('Secure', 'payhalal-direct'))
            ),
            el('div', { className: 'payhalal-direct-methods', role: 'radiogroup', 'aria-label': __('PayHalal Direct payment method', 'payhalal-direct') },
                el('label', { className: 'payhalal-direct-method is-active' },
                    el('input', { type: 'radio', name: 'payhalal_direct_method_block', value: 'card', checked: true, readOnly: true }),
                    el('span', { className: 'payhalal-direct-method-icon', 'aria-hidden': true }, '💳'),
                    el('span', {},
                        el('strong', {}, __('Card', 'payhalal-direct')),
                        el('small', {}, __('Visa, Mastercard and supported debit cards', 'payhalal-direct'))
                    )
                ),
                data.showFutureMethods ? el('label', { className: 'payhalal-direct-method is-disabled' },
                    el('input', { type: 'radio', disabled: true }),
                    el('span', { className: 'payhalal-direct-method-icon', 'aria-hidden': true }, '🏦'),
                    el('span', {}, el('strong', {}, __('FPX Online Banking', 'payhalal-direct')), el('small', {}, __('Coming soon', 'payhalal-direct')))
                ) : null,
                data.showFutureMethods ? el('label', { className: 'payhalal-direct-method is-disabled' },
                    el('input', { type: 'radio', disabled: true }),
                    el('span', { className: 'payhalal-direct-method-icon', 'aria-hidden': true }, '📱'),
                    el('span', {}, el('strong', {}, __('TNG eWallet', 'payhalal-direct')), el('small', {}, __('Coming soon', 'payhalal-direct')))
                ) : null
            ),
            el('div', { className: 'payhalal-direct-card-panel' },
                el('div', { className: 'payhalal-direct-card-preview', 'aria-hidden': true },
                    el('div', { className: 'payhalal-direct-card-preview-top' },
                        el('span', { className: 'payhalal-direct-card-chip' }),
                        el('span', { className: 'payhalal-direct-card-brand-preview' }, brand)
                    ),
                    el('div', { className: 'payhalal-direct-card-number-preview' }, number ? maskPreview(number) : '•••• •••• •••• ••••'),
                    el('div', { className: 'payhalal-direct-card-preview-bottom' },
                        el('span', {},
                            el('span', { className: 'payhalal-direct-card-label' }, __('Cardholder', 'payhalal-direct')),
                            el('span', { className: 'payhalal-direct-card-holder-preview' }, holder || __('Name on card', 'payhalal-direct'))
                        ),
                        el('span', {},
                            el('span', { className: 'payhalal-direct-card-label' }, __('Expires', 'payhalal-direct')),
                            el('span', { className: 'payhalal-direct-card-exp-preview' }, (month || 'MM') + '/' + (year ? year.slice(-2) : 'YY'))
                        )
                    )
                ),
                el('div', { className: 'payhalal-direct-grid' },
                    el(Field, { id: 'payhalal_direct_card_holder_name', label: __('Cardholder Name', 'payhalal-direct'), autoComplete: 'cc-name', placeholder: 'Name on card', value: holder, onChange: setHolder, className: 'full' }),
                    el(Field, { id: 'payhalal_direct_card_number', label: __('Card Number', 'payhalal-direct'), inputMode: 'numeric', autoComplete: 'cc-number', placeholder: '1234 1234 1234 1234', maxLength: 23, value: number, onChange: function (value) { setNumber(formatCardNumber(value)); }, className: 'full has-card-brand' },
                        brand !== 'Card' ? el('span', { className: 'payhalal-direct-brand', 'aria-live': 'polite' }, brand) : null
                    ),
                    el(Field, { id: 'payhalal_direct_card_exp_mn', label: __('Month', 'payhalal-direct'), inputMode: 'numeric', autoComplete: 'cc-exp-month', placeholder: 'MM', maxLength: 2, value: month, onChange: function (value) { setMonth(onlyDigits(value).slice(0, 2)); } }),
                    el(Field, { id: 'payhalal_direct_card_exp_yy', label: __('Year', 'payhalal-direct'), inputMode: 'numeric', autoComplete: 'cc-exp-year', placeholder: 'YY', maxLength: 4, value: year, onChange: function (value) { setYear(onlyDigits(value).slice(0, 4)); } }),
                    el(Field, { id: 'payhalal_direct_card_cvv', label: __('CVV', 'payhalal-direct'), type: 'password', inputMode: 'numeric', autoComplete: 'cc-csc', placeholder: '123', maxLength: 4, value: cvv, onChange: function (value) { setCvv(onlyDigits(value).slice(0, 4)); } })
                ),
                el('div', { className: 'payhalal-direct-trust-row' },
                    el('span', { className: 'payhalal-direct-trust-pill' }, __('Encrypted payment', 'payhalal-direct')),
                    el('span', { className: 'payhalal-direct-trust-pill' }, __('No card data stored', 'payhalal-direct')),
                    el('span', { className: 'payhalal-direct-trust-pill' }, __('Order status sync', 'payhalal-direct'))
                )
            )
        );
    };

    registerPaymentMethod({
        name: 'payhalal_direct',
        label: decodeEntities(data.title || 'PayHalal Direct'),
        ariaLabel: decodeEntities(data.title || 'PayHalal Direct'),
        content: el(Content, null),
        edit: el(Content, null),
        canMakePayment: function () { return !!data.cardEnabled; },
        supports: {
            features: data.supports || ['products'],
        },
    });
})();
