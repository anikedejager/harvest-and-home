/*
 * Harvest & Home client-side scripts
 * - mobile menu toggle
 * - form validation before submit (the server validates again)
 * - cart quantity auto-update
 * - confirm prompts on delete buttons
 * - card number formatting and payment "processing" screen
 * - product image preview in the admin site
 */
document.addEventListener('DOMContentLoaded', function () {

    // ---------- Mobile menu ----------
    document.querySelectorAll('.menu-btn').forEach(function (btn) {
        var nav = document.getElementById(btn.getAttribute('aria-controls'));
        btn.addEventListener('click', function () {
            var open = nav.classList.toggle('open');
            btn.setAttribute('aria-expanded', open ? 'true' : 'false');
            btn.setAttribute('aria-label', open ? 'Close menu' : 'Open menu');
        });
    });

    // ---------- Form validation ----------
    var rules = {
        email: function (v) {
            return /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(v) || 'Enter a valid email address.';
        },
        phone: function (v) {
            return /^[0-9 +()-]{10,20}$/.test(v) || 'Enter a valid phone number (at least 10 digits).';
        },
        password: function (v) {
            return (v.length >= 8 && /[A-Za-z]/.test(v) && /[0-9]/.test(v)) ||
                'Use at least 8 characters with letters and numbers.';
        },
        match: function (v, input) {
            var other = document.getElementById(input.dataset.match);
            return (other && v === other.value) || 'Passwords do not match.';
        },
        money: function (v) {
            return (/^\d+(\.\d{1,2})?$/.test(v) && parseFloat(v) > 0) || 'Enter a price greater than 0, for example 45.00.';
        },
        whole: function (v) {
            return /^\d+$/.test(v) || 'Enter a whole number (0 or more).';
        },
        postal: function (v) {
            return /^\d{4}$/.test(v) || 'Enter a 4-digit postal code.';
        },
        card: function (v) {
            return luhn(v.replace(/\s/g, '')) || 'Enter a valid 16-digit card number.';
        },
        expiry: function (v) {
            var m = /^(0[1-9]|1[0-2])\/(\d{2})$/.exec(v);
            if (!m) { return 'Use the format MM/YY.'; }
            var end = new Date(2000 + parseInt(m[2], 10), parseInt(m[1], 10), 1);
            return end > new Date() || 'This card has expired.';
        },
        cvv: function (v) {
            return /^\d{3}$/.test(v) || 'Enter the 3-digit CVV.';
        }
    };

    function luhn(num) {
        if (!/^\d{16}$/.test(num)) { return false; }
        var sum = 0;
        for (var i = 0; i < 16; i++) {
            var d = parseInt(num.charAt(15 - i), 10);
            if (i % 2 === 1) { d *= 2; if (d > 9) { d -= 9; } }
            sum += d;
        }
        return sum % 10 === 0;
    }

    function showError(input, message) {
        clearError(input);
        input.classList.add('bad');
        input.setAttribute('aria-invalid', 'true');
        var span = document.createElement('span');
        span.className = 'err js-err';
        span.textContent = message;
        input.insertAdjacentElement('afterend', span);
    }

    function clearError(input) {
        input.classList.remove('bad');
        input.removeAttribute('aria-invalid');
        var next = input.nextElementSibling;
        if (next && next.classList.contains('js-err')) { next.remove(); }
    }

    function checkField(input) {
        var value = input.value.trim();
        if (input.type === 'checkbox') {
            if (input.required && !input.checked) { showError(input.parentElement, 'Please tick this box to continue.'); return false; }
            clearError(input.parentElement);
            return true;
        }
        if (input.required && value === '') {
            showError(input, 'This field is required.');
            return false;
        }
        if (value !== '' && input.dataset.rule && rules[input.dataset.rule]) {
            var result = rules[input.dataset.rule](value, input);
            if (result !== true) { showError(input, result); return false; }
        }
        clearError(input);
        return true;
    }

    document.querySelectorAll('form[data-validate]').forEach(function (form) {
        form.setAttribute('novalidate', 'novalidate');
        var fields = form.querySelectorAll('input, select, textarea');

        fields.forEach(function (f) {
            f.addEventListener('blur', function () { if (!f.disabled && f.offsetParent !== null) { checkField(f); } });
        });

        form.addEventListener('submit', function (e) {
            var ok = true, first = null;
            fields.forEach(function (f) {
                if (f.disabled || f.type === 'hidden' || f.offsetParent === null) { return; }
                if (!checkField(f)) { ok = false; first = first || f; }
            });
            if (!ok) {
                e.preventDefault();
                first.focus();
                return;
            }
            // Payment form: show the processing screen while the server works
            if (form.id === 'payment-form') {
                document.getElementById('processing').classList.add('show');
                form.querySelector('button[type=submit]').disabled = true;
            }
        });
    });

    // ---------- Card number formatting: 4111111111111111 -> 4111 1111 1111 1111 ----------
    var cardInput = document.getElementById('card_number');
    if (cardInput) {
        cardInput.addEventListener('input', function () {
            var digits = cardInput.value.replace(/\D/g, '').slice(0, 16);
            cardInput.value = digits.replace(/(\d{4})(?=\d)/g, '$1 ');
        });
    }
    var expInput = document.getElementById('expiry');
    if (expInput) {
        expInput.addEventListener('input', function () {
            var d = expInput.value.replace(/\D/g, '').slice(0, 4);
            expInput.value = d.length > 2 ? d.slice(0, 2) + '/' + d.slice(2) : d;
        });
    }

    // ---------- Fulfilment choice: show the address only for delivery ----------
    var fulfil = document.querySelectorAll('input[name=fulfilment_method]');
    var addressBox = document.getElementById('address-box');
    function updateFulfilment() {
        var chosen = document.querySelector('input[name=fulfilment_method]:checked');
        var delivery = chosen && chosen.value === 'delivery';
        if (addressBox) {
            addressBox.hidden = !delivery;
            addressBox.querySelectorAll('input[data-req]').forEach(function (i) {
                i.required = delivery;
            });
        }
        var feeRow = document.getElementById('fee-row');
        var totalEl = document.getElementById('grand-total');
        if (feeRow && totalEl) {
            var goods = parseFloat(totalEl.dataset.goods);
            var fee = delivery ? parseFloat(totalEl.dataset.fee) : 0;
            feeRow.querySelector('span:last-child').textContent = delivery ? formatRand(fee) : 'Free';
            totalEl.textContent = formatRand(goods + fee);
        }
    }
    function formatRand(n) {
        return 'R' + n.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ' ');
    }
    fulfil.forEach(function (r) { r.addEventListener('change', updateFulfilment); });
    if (fulfil.length) { updateFulfilment(); }

    // ---------- Cart: update the quantity as soon as it changes ----------
    document.querySelectorAll('.qty-form input[name=qty]').forEach(function (input) {
        input.addEventListener('change', function () {
            if (parseInt(input.value, 10) < 1 || isNaN(parseInt(input.value, 10))) { input.value = 1; }
            input.form.submit();
        });
    });
    document.querySelectorAll('.qty-form .js-update').forEach(function (b) { b.hidden = true; });

    // ---------- Confirm before destructive actions ----------
    document.querySelectorAll('[data-confirm]').forEach(function (el) {
        el.addEventListener('click', function (e) {
            if (!window.confirm(el.dataset.confirm)) { e.preventDefault(); }
        });
    });

    // ---------- Admin: preview a product image before uploading ----------
    var imgInput = document.getElementById('product_image');
    var preview = document.getElementById('image-preview');
    if (imgInput && preview) {
        imgInput.addEventListener('change', function () {
            var file = imgInput.files[0];
            if (!file) { return; }
            if (!/^image\/(jpeg|png|webp)$/.test(file.type) || file.size > 2 * 1024 * 1024) {
                showError(imgInput, 'Choose a JPG, PNG or WEBP image under 2 MB.');
                imgInput.value = '';
                return;
            }
            clearError(imgInput);
            preview.src = URL.createObjectURL(file);
            preview.hidden = false;
        });
    }
});
