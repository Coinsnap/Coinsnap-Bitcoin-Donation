/**
 * Coinsnap Bitcoin Donation - Shared Utilities
 */

const addNumSeparators = (amount) => {
    var tmp = removeThousandSeparator(amount);
    var val = Number(tmp).toLocaleString("en-GB");
    return (tmp === '') ? '' : val;
};

const getThousandSeparator = () => {
    return (1000).toLocaleString("en-GB").replace(/\d/g, '')[0];
};

const removeThousandSeparator = (amount) => {
    const sep = getThousandSeparator();
    return amount?.replace(new RegExp(`\\${sep}`, 'g'), '');
};

const cleanDonationAmount = (amount) => {
    return parseFloat(removeThousandSeparator(String(amount)));
};

const formatNumericInput = (target) => {
    var tmp = removeThousandSeparator(target.value);
    var original = tmp;
    tmp = parseFloat(tmp);
    original = original.replace(tmp, "");
    var val = Number(tmp).toLocaleString("en-GB");
    if (isNaN(tmp) || tmp === '') {
        target.value = '';
    } else {
        target.value = `${val}${original}`;
    }
};

const unformatNumericInput = (target) => {
    var val = removeThousandSeparator(target.value);
    target.value = val;
};

const NumericInput = (inputFieldName) => {
    const inp = document.getElementById(inputFieldName);
    if (inp) {
        const sep = (getThousandSeparator() === ".") ? "," : ".";
        var numericKeys = `0123456789${sep}`;

        inp.addEventListener('keypress', function (e) {
            var event = e || window.event;
            if (event.charCode === 0) return;
            if (`${inp.value}`.includes(sep) && event.key === sep) {
                event.preventDefault();
                return;
            }
            if (-1 === numericKeys.indexOf(event.key)) {
                event.preventDefault();
                return;
            }
        });

        inp.addEventListener('blur', function (e) {
            formatNumericInput(e.target);
        });

        inp.addEventListener('focus', function (e) {
            unformatNumericInput(e.target);
        });
        unformatNumericInput(inp);
        formatNumericInput(inp);
    }
};

const addErrorDonationField = (field, message) => {
    field.classList.add('error');
    if (message !== '') {
        if (jQuery(field).next('.donation-field-error').length) {
            jQuery(field).next('.donation-field-error').text(message);
        } else {
            jQuery('<span>').addClass('donation-field-error').text(message).insertAfter(jQuery(field));
        }
    }
    removeDonationBorderOnFocus(field, field);
};

const removeDonationBorderOnFocus = (field1, field2) => {
    field1.addEventListener('focus', function () {
        field2.classList.remove('error');
        jQuery(field1).next('.donation-field-error').remove();
        jQuery(field2).next('.donation-field-error').remove();
    });
};

const hideDonationElementById = (id, prefix, sufix) => {
    prefix = prefix || '';
    sufix = sufix || '';
    var el = document.getElementById(`${prefix}${id}${sufix}`);
    if (el) el.style.display = 'none';
};

const showDonationElementById = (id, display, prefix, sufix) => {
    prefix = prefix || '';
    sufix = sufix || '';
    var el = document.getElementById(`${prefix}${id}${sufix}`);
    if (el) el.style.display = display;
};

const hideDonationElementsById = (ids, prefix, sufix) => {
    ids.forEach(id => hideDonationElementById(id, prefix, sufix));
};

const showDonationElementsById = (ids, display, prefix, sufix) => {
    ids.forEach(id => showDonationElementById(id, display, prefix, sufix));
};
