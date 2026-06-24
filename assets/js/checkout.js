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

  function setPanelInputsDisabled(panel, disabled) {
    var fields = panel.querySelectorAll("input, select, textarea");

    fields.forEach(function (field) {
      if (field.name === "atozpay_direct_method") {
        return;
      }

      field.disabled = disabled;
    });
  }

  function updateAtozpayDirectBox(box) {
    var selected = box.querySelector('input[name="atozpay_direct_method"]:checked');
    var value = selected ? selected.value : "card";
    var panels = box.querySelectorAll("[data-atozpay-panel]");
    var methods = box.querySelectorAll(".atozpay-direct-method");

    panels.forEach(function (panel) {
      var isActive = panel.getAttribute("data-atozpay-panel") === value;
      panel.style.display = isActive ? "" : "none";
      panel.hidden = !isActive;
      panel.setAttribute("aria-hidden", isActive ? "false" : "true");
      setPanelInputsDisabled(panel, !isActive);
    });

    methods.forEach(function (method) {
      var input = method.querySelector('input[name="atozpay_direct_method"]');
      method.classList.toggle("is-active", !!input && input.checked);
    });
  }

  function initAtozpayDirectMethodToggle() {
    document.querySelectorAll(".atozpay-direct-box").forEach(function (box) {
      updateAtozpayDirectBox(box);
    });
  }

  function initAtozpayDirectFields() {
    document.querySelectorAll(".atozpay-direct-box").forEach(function (box) {
      var holder = box.querySelector("#atozpay_direct_card_holder_name");
      var cardNumber = box.querySelector("#atozpay_direct_card_number");
      var brand = box.querySelector(".atozpay-direct-brand");
      var brandPreview = box.querySelector(".atozpay-direct-card-brand-preview");
      var numberPreview = box.querySelector(".atozpay-direct-card-number-preview");
      var holderPreview = box.querySelector(".atozpay-direct-card-holder-preview");
      var expPreview = box.querySelector(".atozpay-direct-card-exp-preview");
      var month = box.querySelector("#atozpay_direct_card_exp_mn");
      var year = box.querySelector("#atozpay_direct_card_exp_yy");
      var cvv = box.querySelector("#atozpay_direct_card_cvv");

      function updatePreview() {
        var cardBrand = detectBrand(cardNumber ? cardNumber.value : "");
        if (brand) brand.textContent = cardBrand === "Card" ? "" : cardBrand;
        if (brandPreview) brandPreview.textContent = cardBrand;
        if (numberPreview) {
          numberPreview.textContent =
            cardNumber && cardNumber.value
              ? maskPreview(cardNumber.value)
              : "•••• •••• •••• ••••";
        }
        if (holderPreview) {
          holderPreview.textContent =
            holder && holder.value ? holder.value : "Name on card";
        }
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
    });
  }

  function bootAtozpayDirect() {
    initAtozpayDirectMethodToggle();
    initAtozpayDirectFields();
  }

  document.addEventListener("change", function (event) {
    if (!event.target.matches('input[name="atozpay_direct_method"]')) {
      return;
    }

    var box = event.target.closest(".atozpay-direct-box");
    if (box) {
      updateAtozpayDirectBox(box);
    }
  });

  document.addEventListener("click", function (event) {
    var method = event.target.closest(".atozpay-direct-method");
    if (!method) return;

    var input = method.querySelector('input[name="atozpay_direct_method"]');
    if (!input || input.disabled) return;

    input.checked = true;
    input.dispatchEvent(new Event("change", { bubbles: true }));
  });

  document.addEventListener("DOMContentLoaded", bootAtozpayDirect);

  if (document.body) {
    document.body.addEventListener("updated_checkout", bootAtozpayDirect);
  }

  setTimeout(bootAtozpayDirect, 100);
})();
