/**
 * shared/validation.js
 * Field-level validation for the lead capture form on the technical-guides page.
 * No DOM dependency beyond reading .value — pure validation logic.
 *
 * Exports:
 *   showErr(el, msg)        — mark a field invalid and display the message
 *   clearErr(el)            — remove the invalid state from a field
 *   validateField(el, iti)  — validate a single field; returns true if valid
 *   validateAll(form, iti)  — validate every .mg-input in the form; returns true if all valid
 */

// ── Validation rules keyed by element id ─────────────────────────────────────

const RULES = {
  first_name:   { required: true, minLength: 2 },
  last_name:    { required: true, minLength: 2 },
  company_name: { required: true },
  work_email:   { required: true, email: true },
  phone_number: { required: true, phone: true },
};

const EMAIL_RE = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

// ── Error display ─────────────────────────────────────────────────────────────

/**
 * Mark a field as invalid and display an error message below it.
 * Creates the .mg-field-error element on first call.
 *
 * @param {HTMLElement} el
 * @param {string}      msg
 */
export function showErr(el, msg) {
  el.classList.add('is-invalid');

  const wrap = el.parentElement;
  let fb     = wrap.querySelector('.mg-field-error');
  if (!fb) {
    fb           = document.createElement('div');
    fb.className = 'mg-field-error';
    wrap.appendChild(fb);
  }
  fb.textContent = msg;
}

/**
 * Remove the invalid state and error message from a field.
 *
 * @param {HTMLElement} el
 */
export function clearErr(el) {
  el.classList.remove('is-invalid');

  // intl-tel-input wraps the <input> inside a .iti div — walk up to find the error
  const wrap = el.closest('.iti') ?? el.parentElement;
  const fb   = (wrap.parentElement ?? wrap).querySelector('.mg-field-error');
  if (fb) fb.remove();
}

// ── Single-field validation ───────────────────────────────────────────────────

/**
 * Validate one field against RULES and call showErr / clearErr accordingly.
 *
 * @param {HTMLElement}  el           The input element to validate.
 * @param {object|null}  itiInstance  intl-tel-input instance (may be null).
 * @returns {boolean}  true if the field is valid.
 */
export function validateField(el, itiInstance) {
  const v    = (el.value ?? '').trim();
  const rule = RULES[el.id];

  // Field has no rule — nothing to validate
  if (!rule) return true;

  let err = '';

  if (rule.required && !v) {
    // Convert snake_case id to "Title Case" for the error message
    err = el.id
      .replace(/_/g, ' ')
      .replace(/\b\w/g, c => c.toUpperCase()) + ' is required.';

  } else if (rule.minLength && v.length < rule.minLength) {
    err = `Minimum ${rule.minLength} characters.`;

  } else if (rule.email && !EMAIL_RE.test(v)) {
    err = 'Please enter a valid email.';

  } else if (rule.phone && itiInstance && !itiInstance.isValidNumber()) {
    err = 'Please enter a valid phone number.';
  }

  if (err) {
    showErr(el, err);
    return false;
  }

  clearErr(el);
  return true;
}

// ── Full-form validation ──────────────────────────────────────────────────────

/**
 * Validate every .mg-input element in the form.
 * Calls validateField on each one — all fields are checked (no early exit)
 * so all errors are shown at once.
 *
 * @param {HTMLFormElement} form
 * @param {object|null}     itiInstance
 * @returns {boolean}  true if every field is valid.
 */
export function validateAll(form, itiInstance) {
  let ok = true;
  form.querySelectorAll('.mg-input').forEach(el => {
    if (!validateField(el, itiInstance)) ok = false;
  });
  return ok;
}
