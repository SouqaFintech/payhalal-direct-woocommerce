(function () {
  function digits(value) {
    return (value || '').replace(/\D+/g, '');
  }

  function formatCardNumber(value) {
    return digits(value).replace(/(.{4})/g, '$1 ').trim();
  }

  function detectBrand(number) {
    var n = digits(number);
    if (/^4/.test(n)) return 'Visa';
    if (/^(5[1-5]|2[2-7])/.test(n)) return 'Mastercard';
    if (/^3[47]/.test(n)) return 'Amex';
    if (/^6(?:011|5)/.test(n)) return 'Discover';
    return '';
  }

  function initPayHalalDirectFields() {
    var cardNumber = document.getElementById('payhalal_direct_card_number');
    var brand = document.querySelector('.payhalal-direct-brand');
    var month = document.getElementById('payhalal_direct_card_exp_mn');
    var year = document.getElementById('payhalal_direct_card_exp_yy');
    var cvv = document.getElementById('payhalal_direct_card_cvv');

    if (cardNumber && !cardNumber.dataset.payhalalBound) {
      cardNumber.dataset.payhalalBound = '1';
      cardNumber.addEventListener('input', function () {
        cardNumber.value = formatCardNumber(cardNumber.value);
        if (brand) brand.textContent = detectBrand(cardNumber.value);
      });
    }

    [month, year, cvv].forEach(function (field) {
      if (!field || field.dataset.payhalalBound) return;
      field.dataset.payhalalBound = '1';
      field.addEventListener('input', function () {
        field.value = digits(field.value);
      });
    });
  }

  document.addEventListener('DOMContentLoaded', initPayHalalDirectFields);
  document.body.addEventListener('updated_checkout', initPayHalalDirectFields);
})();
