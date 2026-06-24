(function () {
  function digits(value) {
    return (value || "").replace(/\D+/g, "");
  }

  function formatCardNumber(value) {
    return digits(value)
      .replace(/(.{4})/g, "$1 ")
      .trim()
      .slice(0, 23);
  }

  function detectBrand(number) {
    var n = digits(number);
    if (/^4/.test(n)) return "Visa";
    if (/^(5[1-5]|2[2-7])/.test(n)) return "Mastercard";
    if (/^3[47]/.test(n)) return "Amex";
    if (/^6(?:011|5)/.test(n)) return "Discover";
    return "Card";
  }

  function maskPreview(value) {
    var n = digits(value).slice(0, 16);
    var padded = (n + "••••••••••••••••").slice(0, 16);
    return padded.replace(/(.{4})/g, "$1 ").trim();
  }


  function initAtozpayDirectMethodToggle() {
    var boxes = document.querySelectorAll('.atozpay-direct-box');

    boxes.forEach(function (box) {
      if (box.dataset.atozpayMethodBound) return;
      box.dataset.atozpayMethodBound = '1';

      var radios = box.querySelectorAll('input[name="atozpay_direct_method"]');
      var panels = box.querySelectorAll('[data-atozpay-panel]');
      var methods = box.querySelectorAll('.atozpay-direct-method');

      function update() {
        var selected = box.querySelector('input[name="atozpay_direct_method"]:checked');
        var value = selected ? selected.value : 'card';

        panels.forEach(function (panel) {
          panel.style.display = panel.getAttribute('data-atozpay-panel') === value ? '' : 'none';
        });

        methods.forEach(function (method) {
          var input = method.querySelector('input[name="atozpay_direct_method"]');
          method.classList.toggle('is-active', !!input && input.checked);
        });
      }

      radios.forEach(function (radio) {
        radio.addEventListener('change', update);
      });

      update();
    });
  }

  function initAtozpayDirectFields() {
    var holder = document.getElementById("atozpay_direct_card_holder_name");
    var cardNumber = document.getElementById("atozpay_direct_card_number");
    var brand = document.querySelector(".atozpay-direct-brand");
    var brandPreview = document.querySelector(
      ".atozpay-direct-card-brand-preview",
    );
    var numberPreview = document.querySelector(
      ".atozpay-direct-card-number-preview",
    );
    var holderPreview = document.querySelector(
      ".atozpay-direct-card-holder-preview",
    );
    var expPreview = document.querySelector(".atozpay-direct-card-exp-preview");
    var month = document.getElementById("atozpay_direct_card_exp_mn");
    var year = document.getElementById("atozpay_direct_card_exp_yy");
    var cvv = document.getElementById("atozpay_direct_card_cvv");

    function updatePreview() {
      var cardBrand = detectBrand(cardNumber ? cardNumber.value : "");
      if (brand) brand.textContent = cardBrand === "Card" ? "" : cardBrand;
      if (brandPreview) brandPreview.textContent = cardBrand;
      if (numberPreview)
        numberPreview.textContent =
          cardNumber && cardNumber.value
            ? maskPreview(cardNumber.value)
            : "•••• •••• •••• ••••";
      if (holderPreview)
        holderPreview.textContent =
          holder && holder.value ? holder.value : "Name on card";
      if (expPreview) {
        var mm = month && month.value ? month.value.padStart(2, "0") : "MM";
        var yy = year && year.value ? year.value.slice(-2) : "YY";
        expPreview.textContent = mm + "/" + yy;
      }
    }

    if (holder && !holder.dataset.atozpayBound) {
      holder.dataset.atozpayBound = "1";
      holder.addEventListener("input", updatePreview);
    }

    if (cardNumber && !cardNumber.dataset.atozpayBound) {
      cardNumber.dataset.atozpayBound = "1";
      cardNumber.addEventListener("input", function () {
        cardNumber.value = formatCardNumber(cardNumber.value);
        updatePreview();
      });
    }

    [month, year, cvv].forEach(function (field) {
      if (!field || field.dataset.atozpayBound) return;
      field.dataset.atozpayBound = "1";
      field.addEventListener("input", function () {
        field.value = digits(field.value);
        updatePreview();
      });
    });

    updatePreview();
  }

  document.addEventListener("DOMContentLoaded", function () {
    initAtozpayDirectMethodToggle();
    initAtozpayDirectFields();
  });
  if (document.body) {
    document.body.addEventListener("updated_checkout", function () {
      initAtozpayDirectMethodToggle();
      initAtozpayDirectFields();
    });
  }
})();
