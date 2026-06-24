(function () {
  const settings = window.wc && window.wc.wcSettings ? window.wc.wcSettings : null;
  const registry = window.wc && window.wc.wcBlocksRegistry ? window.wc.wcBlocksRegistry : null;
  const element = window.wp && window.wp.element ? window.wp.element : null;
  const htmlEntities = window.wp && window.wp.htmlEntities ? window.wp.htmlEntities : null;
  const i18n = window.wp && window.wp.i18n ? window.wp.i18n : null;

  if (!settings || !registry || !element) return;

  const { registerPaymentMethod } = registry;
  const { createElement: el, useEffect, useMemo, useState } = element;
  const decodeEntities = htmlEntities && htmlEntities.decodeEntities ? htmlEntities.decodeEntities : function (value) { return value; };
  const __ = i18n && i18n.__ ? i18n.__ : function (value) { return value; };
  const data = settings.getSetting("atozpay_direct_data", {});

  const onlyDigits = function (value) { return (value || "").replace(/\D+/g, ""); };
  const formatCardNumber = function (value) { return onlyDigits(value).replace(/(.{4})/g, "$1 ").trim().slice(0, 23); };
  const detectBrand = function (value) {
    const n = onlyDigits(value);
    if (/^4/.test(n)) return "Visa";
    if (/^(5[1-5]|2[2-7])/.test(n)) return "Mastercard";
    if (/^3[47]/.test(n)) return "Amex";
    if (/^6(?:011|5)/.test(n)) return "Discover";
    return "Card";
  };
  const maskPreview = function (value) {
    const n = onlyDigits(value).slice(0, 16);
    return (n + "••••••••••••••••").slice(0, 16).replace(/(.{4})/g, "$1 ").trim();
  };

  const enabledMethods = function () {
    const methods = [];
    if (data.cardEnabled) methods.push("card");
    if (data.fpxEnabled) methods.push("fpx");
    if (data.tngEnabled) methods.push("tng");
    return methods.length ? methods : ["card"];
  };

  const Field = function ({ id, label, type, inputMode, autoComplete, placeholder, maxLength, value, onChange, className, children }) {
    return el(
      "p",
      { className: "atozpay-direct-field " + (className || "") },
      el("label", { htmlFor: id }, label),
      el("input", {
        id,
        name: id,
        type: type || "text",
        inputMode: inputMode || undefined,
        autoComplete: autoComplete || undefined,
        placeholder: placeholder || undefined,
        maxLength: maxLength || undefined,
        value,
        onChange: function (event) { onChange(event.target.value); },
      }),
      children || null,
    );
  };

  const MethodOption = function ({ value, icon, title, subtitle, selected, onChange }) {
    return el(
      "label",
      { className: "atozpay-direct-method " + (selected ? "is-active" : "") },
      el("input", { type: "radio", name: "atozpay_direct_method_block", value, checked: selected, onChange: function () { onChange(value); } }),
      el("span", { className: "atozpay-direct-method-icon", "aria-hidden": true }, icon),
      el("span", {}, el("strong", {}, title), el("small", {}, subtitle)),
    );
  };

  const Content = function (props) {
    const { eventRegistration, emitResponse } = props;
    const methods = useMemo(enabledMethods, []);
    const [method, setMethod] = useState(methods[0] || "card");
    const [holder, setHolder] = useState("");
    const [number, setNumber] = useState("");
    const [month, setMonth] = useState("");
    const [year, setYear] = useState("");
    const [cvv, setCvv] = useState("");
    const [bankCode, setBankCode] = useState("");
    const brand = detectBrand(number);

    useEffect(function () {
      const unsubscribe = eventRegistration.onPaymentSetup(function () {
        if (method === "card" && (!holder || !number || !month || !year || !cvv)) {
          return { type: emitResponse.responseTypes.ERROR, message: __("Please complete your AtozPay card details.", "atozpay-direct") };
        }

        if (method === "fpx" && !bankCode) {
          return { type: emitResponse.responseTypes.ERROR, message: __("Please select your FPX bank.", "atozpay-direct") };
        }

        return {
          type: emitResponse.responseTypes.SUCCESS,
          meta: {
            paymentMethodData: {
              atozpay_direct_method: method,
              atozpay_direct_card_holder_name: holder,
              atozpay_direct_card_number: number,
              atozpay_direct_card_exp_mn: month,
              atozpay_direct_card_exp_yy: year,
              atozpay_direct_card_cvv: cvv,
              atozpay_direct_bank_code: bankCode,
            },
          },
        };
      });

      return unsubscribe;
    }, [eventRegistration, emitResponse, method, holder, number, month, year, cvv, bankCode]);

    return el(
      "div",
      { className: "atozpay-direct-box atozpay-direct-blocks" },
      data.description ? el("div", { className: "atozpay-direct-description", dangerouslySetInnerHTML: { __html: data.description } }) : null,
      el(
        "div",
        { className: "atozpay-direct-header" },
        el("div", { className: "atozpay-direct-title" }, el("span", { className: "atozpay-direct-mark", "aria-hidden": true }, "⌁"), el("div", {}, el("strong", {}, decodeEntities(data.title || "AtozPay")), el("span", {}, __("Secure payments powered by AtozPay", "atozpay-direct")))),
        el("em", { className: "atozpay-direct-badge" }, __("Secure", "atozpay-direct")),
      ),
      el(
        "div",
        { className: "atozpay-direct-methods", role: "radiogroup", "aria-label": __("AtozPay payment method", "atozpay-direct") },
        methods.indexOf("card") !== -1 ? el(MethodOption, { value: "card", icon: "💳", title: __("Card", "atozpay-direct"), subtitle: __("Visa, Mastercard and debit cards", "atozpay-direct"), selected: method === "card", onChange: setMethod }) : null,
        methods.indexOf("fpx") !== -1 ? el(MethodOption, { value: "fpx", icon: "🏦", title: __("FPX Online Banking", "atozpay-direct"), subtitle: __("Pay with Malaysian online banking", "atozpay-direct"), selected: method === "fpx", onChange: setMethod }) : null,
        methods.indexOf("tng") !== -1 ? el(MethodOption, { value: "tng", icon: "📱", title: __("TNG eWallet", "atozpay-direct"), subtitle: __("Pay using Touch 'n Go eWallet", "atozpay-direct"), selected: method === "tng", onChange: setMethod }) : null,
      ),
      method === "card" ? el(
        "div",
        { className: "atozpay-direct-card-panel atozpay-direct-payment-panel", "data-atozpay-panel": "card" },
        el(
          "div",
          { className: "atozpay-direct-card-preview", "aria-hidden": true },
          el("div", { className: "atozpay-direct-card-preview-top" }, el("span", { className: "atozpay-direct-card-chip" }), el("span", { className: "atozpay-direct-card-brand-preview" }, brand)),
          el("div", { className: "atozpay-direct-card-number-preview" }, number ? maskPreview(number) : "•••• •••• •••• ••••"),
          el("div", { className: "atozpay-direct-card-preview-bottom" }, el("span", {}, el("span", { className: "atozpay-direct-card-label" }, __("Cardholder", "atozpay-direct")), el("span", { className: "atozpay-direct-card-holder-preview" }, holder || __("Name on card", "atozpay-direct"))), el("span", {}, el("span", { className: "atozpay-direct-card-label" }, __("Expires", "atozpay-direct")), el("span", { className: "atozpay-direct-card-exp-preview" }, (month || "MM") + "/" + (year ? year.slice(-2) : "YY")))),
        ),
        el("div", { className: "atozpay-direct-grid" },
          el(Field, { id: "atozpay_direct_card_holder_name", label: __("Cardholder Name", "atozpay-direct"), autoComplete: "cc-name", placeholder: "Name on card", value: holder, onChange: setHolder, className: "full" }),
          el(Field, { id: "atozpay_direct_card_number", label: __("Card Number", "atozpay-direct"), inputMode: "numeric", autoComplete: "cc-number", placeholder: "1234 1234 1234 1234", maxLength: 23, value: number, onChange: function (value) { setNumber(formatCardNumber(value)); }, className: "full has-card-brand" }, brand !== "Card" ? el("span", { className: "atozpay-direct-brand", "aria-live": "polite" }, brand) : null),
          el(Field, { id: "atozpay_direct_card_exp_mn", label: __("Month", "atozpay-direct"), inputMode: "numeric", autoComplete: "cc-exp-month", placeholder: "MM", maxLength: 2, value: month, onChange: function (value) { setMonth(onlyDigits(value).slice(0, 2)); } }),
          el(Field, { id: "atozpay_direct_card_exp_yy", label: __("Year", "atozpay-direct"), inputMode: "numeric", autoComplete: "cc-exp-year", placeholder: "YY", maxLength: 4, value: year, onChange: function (value) { setYear(onlyDigits(value).slice(0, 4)); } }),
          el(Field, { id: "atozpay_direct_card_cvv", label: __("CVV", "atozpay-direct"), type: "password", inputMode: "numeric", autoComplete: "cc-csc", placeholder: "123", maxLength: 4, value: cvv, onChange: function (value) { setCvv(onlyDigits(value).slice(0, 4)); } }),
        ),
      ) : null,
      method === "fpx" ? el(
        "div",
        { className: "atozpay-direct-alt-panel atozpay-direct-payment-panel", "data-atozpay-panel": "fpx" },
        el("div", { className: "atozpay-direct-alt-header" }, el("span", { className: "atozpay-direct-alt-icon", "aria-hidden": true }, "🏦"), el("div", {}, el("strong", {}, __("Choose your bank", "atozpay-direct")), el("span", {}, __("You will be redirected to complete payment securely via FPX.", "atozpay-direct")))),
        el("p", { className: "atozpay-direct-field full" }, el("label", { htmlFor: "atozpay_direct_bank_code" }, __("Bank", "atozpay-direct")), el("select", { id: "atozpay_direct_bank_code", name: "atozpay_direct_bank_code", value: bankCode, onChange: function (event) { setBankCode(event.target.value); } }, [el("option", { value: "", key: "" }, __("Select bank", "atozpay-direct"))].concat((data.fpxBanks || []).map(function (bank) { return el("option", { value: bank.code, key: bank.code }, bank.name); }))))
      ) : null,
      method === "tng" ? el(
        "div",
        { className: "atozpay-direct-alt-panel atozpay-direct-payment-panel", "data-atozpay-panel": "tng" },
        el("div", { className: "atozpay-direct-alt-header" }, el("span", { className: "atozpay-direct-alt-icon", "aria-hidden": true }, "📱"), el("div", {}, el("strong", {}, __("Pay with TNG eWallet", "atozpay-direct")), el("span", {}, __("After placing your order, you will be redirected to complete your eWallet payment.", "atozpay-direct"))))
      ) : null,
      el("div", { className: "atozpay-direct-trust-row" }, el("span", { className: "atozpay-direct-trust-pill" }, __("Encrypted payment", "atozpay-direct")), el("span", { className: "atozpay-direct-trust-pill" }, __("No card data stored", "atozpay-direct")), el("span", { className: "atozpay-direct-trust-pill" }, __("Order status sync", "atozpay-direct"))),
    );
  };

  registerPaymentMethod({
    name: "atozpay_direct",
    label: decodeEntities(data.title || "AtozPay"),
    ariaLabel: decodeEntities(data.title || "AtozPay"),
    content: el(Content, null),
    edit: el(Content, null),
    canMakePayment: function () { return !!(data.cardEnabled || data.fpxEnabled || data.tngEnabled); },
    supports: { features: data.supports || ["products"] },
  });
})();
