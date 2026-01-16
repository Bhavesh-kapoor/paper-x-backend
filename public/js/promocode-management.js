/**
 * Promo Code Management JavaScript
 * Handles dynamic functionality for promo code forms and listings
 */

document.addEventListener('DOMContentLoaded', function() {
    // Initialize date pickers if available
    initializeDatePickers();

    // Handle event trigger toggle
    initializeEventTriggers();

    // Handle discount type toggle
    initializeDiscountTypeToggle();

    // Initialize filters on index page
    initializeFilters();

    // Generic confirmations (links & buttons with data-confirm attr)
    initializeGenericConfirmations();
});

/** Initialize date pickers for start and end dates */
function initializeDatePickers() {
    const dateInputs = document.querySelectorAll('.datepicker');
    if (dateInputs.length && typeof flatpickr !== 'undefined') {
        dateInputs.forEach(input => {
            flatpickr(input, {
                enableTime: false,
                dateFormat: "Y-m-d",
                // minDate: "today" // keep if you want to prevent past dates
            });
        });
    }
}

/** Initialize event trigger section toggle */
function initializeEventTriggers() {
    const eventTriggerToggle = document.getElementById('has_event_trigger');
    const eventTriggerSection = document.getElementById('event_trigger_section');

    if (eventTriggerToggle && eventTriggerSection) {
        eventTriggerSection.style.display = eventTriggerToggle.checked ? 'block' : 'none';
        eventTriggerToggle.addEventListener('change', function() {
            eventTriggerSection.style.display = this.checked ? 'block' : 'none';
        });
    }
}

/** Initialize discount type toggle and label updates */
function initializeDiscountTypeToggle() {
    const discountTypeRadios = document.querySelectorAll('input[name="discount_type"]');
    const discountValueLabel = document.getElementById('discount_value_label');
    const discountValueInput = document.getElementById('discount_value');

    if (discountTypeRadios.length && discountValueLabel && discountValueInput) {
        updateDiscountLabel();
        discountTypeRadios.forEach(radio => radio.addEventListener('change', updateDiscountLabel));

        function updateDiscountLabel() {
            const checked = document.querySelector('input[name="discount_type"]:checked');
            const selectedType = checked ? checked.value : 'flat';
            if (selectedType === 'percentage') {
                discountValueLabel.textContent = 'Discount Percentage (%)';
                discountValueInput.setAttribute('max', '100');
                discountValueInput.setAttribute('placeholder', 'Enter percentage (e.g. 10 for 10%)');
            } else {
                discountValueLabel.textContent = 'Discount Amount (₹)';
                discountValueInput.removeAttribute('max');
                discountValueInput.setAttribute('placeholder', 'Enter amount in ₹');
            }
        }
    }
}

/** Initialize filters on the index page */
function initializeFilters() {
    const statusFilter = document.getElementById('status_filter');
    if (statusFilter) {
        statusFilter.addEventListener('change', function() {
            const url = new URL(window.location.href);
            url.searchParams.set('status', this.value);
            window.location.href = url.toString();
        });
    }
}

/** Generic confirmations: links & buttons with data-confirm */
function initializeGenericConfirmations() {
    document.body.addEventListener('click', function(e) {
        const target = e.target.closest('[data-confirm]');
        if (!target) return;

        const message = target.getAttribute('data-confirm') || 'Are you sure?';
        const isButton = target.tagName === 'BUTTON' || target.type === 'submit';

        if (!confirm(message)) {
            e.preventDefault();
            e.stopPropagation();
            return false;
        }

        // If it's a link that looks like a button, allow default navigation.
        // If it's a delete <button> inside a form, default submit proceeds.
    }, true);
}

/** Generate a random promo code */
function generateRandomCode() {
    const codeInput = document.getElementById('code');
    if (codeInput) {
        const characters = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        let result = '';
        const length = 8;
        for (let i = 0; i < length; i++) {
            result += characters.charAt(Math.floor(Math.random() * characters.length));
        }
        codeInput.value = result;
    }
}

/** Copy promo code to clipboard with toast */
function copyPromoCode(code) {
    navigator.clipboard.writeText(code).then(() => {
        const toast = document.createElement('div');
        toast.className = 'toast-message success';
        toast.textContent = 'Promo code copied to clipboard!';
        document.body.appendChild(toast);
        setTimeout(() => toast.remove(), 3000);
    });
}
