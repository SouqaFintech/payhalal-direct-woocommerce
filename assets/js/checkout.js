(function () {
  function digits(value) {
    return (value || '').replace(/\D+/g, '');
  }

  function formatCardNumber(value) {
    return digits(value).replace(/(.{4})/g, '$1 ').trim().slice(0, 23);
  }

  function detectBrand(number) {
    var n = digits(number);
    if (/^4/.test(n)) return 'Visa';
    if (/^(5[1-5]|2[2-7])/.test(n)) return 'Mastercard';
    if (/^3[47]/.test(n)) return 'Amex';
    if (/^6(?:011|5)/.test(n)) return 'Discover';
    return 'Card';
  }

  function maskPreview(value) {
    var n = digits(value).slice(0, 16);
    var padded = (n + '••••••••••••••••').slice(0, 16);
    return padded.replace(/(.{4})/g, '$1 ').trim();
  }

  function initPayHalalDirectFields() {
    var holder = document.getElementById('payhalal_direct_card_holder_name');
    var cardNumber = document.getElementById('payhalal_direct_card_number');
    var brand = document.querySelector('.payhalal-direct-brand');
    var brandPreview = document.querySelector('.payhalal-direct-card-brand-preview');
    var numberPreview = document.querySelector('.payhalal-direct-card-number-preview');
    var holderPreview = document.querySelector('.payhalal-direct-card-holder-preview');
    var expPreview = document.querySelector('.payhalal-direct-card-exp-preview');
    var month = document.getElementById('payhalal_direct_card_exp_mn');
    var year = document.getElementById('payhalal_direct_card_exp_yy');
    var cvv = document.getElementById('payhalal_direct_card_cvv');

    function updatePreview() {
      var cardBrand = detectBrand(cardNumber ? cardNumber.value : '');
      if (brand) brand.textContent = cardBrand === 'Card' ? '' : cardBrand;
      if (brandPreview) brandPreview.textContent = cardBrand;
      if (numberPreview) numberPreview.textContent = cardNumber && cardNumber.value ? maskPreview(cardNumber.value) : '•••• •••• •••• ••••';
      if (holderPreview) holderPreview.textContent = holder && holder.value ? holder.value : 'Name on card';
      if (expPreview) {
        var mm = month && month.value ? month.value.padStart(2, '0') : 'MM';
        var yy = year && year.value ? year.value.slice(-2) : 'YY';
        expPreview.textContent = mm + '/' + yy;
      }
    }

    if (holder && !holder.dataset.payhalalBound) {
      holder.dataset.payhalalBound = '1';
      holder.addEventListener('input', updatePreview);
    }

    if (cardNumber && !cardNumber.dataset.payhalalBound) {
      cardNumber.dataset.payhalalBound = '1';
      cardNumber.addEventListener('input', function () {
        cardNumber.value = formatCardNumber(cardNumber.value);
        updatePreview();
      });
    }

    [month, year, cvv].forEach(function (field) {
      if (!field || field.dataset.payhalalBound) return;
      field.dataset.payhalalBound = '1';
      field.addEventListener('input', function () {
        field.value = digits(field.value);
        updatePreview();
      });
    });

    updatePreview();
  }

  document.addEventListener('DOMContentLoaded', initPayHalalDirectFields);
  if (document.body) {
    document.body.addEventListener('updated_checkout', initPayHalalDirectFields);
  }
})();
