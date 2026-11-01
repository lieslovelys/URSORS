document.documentElement.classList.add('js');
document.addEventListener('DOMContentLoaded', () => {
  const menu = document.querySelector('#site-nav');
  const toggle = document.querySelector('[data-menu]');
  toggle?.addEventListener('click', () => {
    const open = toggle.getAttribute('aria-expanded') !== 'true';
    toggle.setAttribute('aria-expanded', String(open));
    menu?.classList.toggle('is-open', open);
    toggle.textContent = open ? 'Close menu' : 'Menu';
  });
  document.addEventListener('keydown', event => {
    if (event.key === 'Escape' && menu?.classList.contains('is-open')) {
      menu.classList.remove('is-open'); toggle?.setAttribute('aria-expanded', 'false');
      toggle.textContent = 'Menu'; toggle.focus();
    }
  });
  document.querySelectorAll('[data-password]').forEach(button => button.addEventListener('click', () => {
    const input = document.getElementById(button.dataset.password);
    const show = input.type === 'password'; input.type = show ? 'text' : 'password';
    button.textContent = show ? 'Hide' : 'Show'; button.setAttribute('aria-pressed', String(show));
  }));
  document.querySelectorAll('[data-preview]').forEach(input => input.addEventListener('change', () => {
    const preview = document.getElementById(input.dataset.preview);
    const file = input.files[0];
    if (preview.dataset.url) URL.revokeObjectURL(preview.dataset.url);
    preview.hidden = true;
    if (!file) return;
    if (file.size > 10 * 1024 * 1024) { input.setCustomValidity('Choose a file smaller than 10 MB.'); input.reportValidity(); return; }
    input.setCustomValidity('');
    if (!file.type.startsWith('image/')) return;
    preview.src = URL.createObjectURL(file); preview.dataset.url = preview.src; preview.hidden = false;
  }));
  document.querySelectorAll('form[data-validate] input').forEach(input => {
    const message = document.getElementById(input.id + '-error');
    const validate = () => {
      if (!message) return;
      let text = '';
      if (input.validity.valueMissing) text = 'Please fill in this field.';
      else if (input.validity.typeMismatch) text = 'Enter a complete email address, like name@example.com.';
      else if (input.validity.tooShort) text = 'Use at least ' + input.minLength + ' characters.';
      message.textContent = text; input.setAttribute('aria-invalid', String(!!text));
    };
    input.addEventListener('blur', validate); input.addEventListener('input', () => { if (input.getAttribute('aria-invalid') === 'true') validate(); });
    input.addEventListener('invalid', validate);
  });
  const dialog = document.querySelector('#confirm-dialog');
  let pending = null;
  document.querySelectorAll('form[data-confirm]').forEach(form => form.addEventListener('submit', event => {
    if (form.dataset.confirmed === 'yes') return;
    if (!dialog?.showModal) { if (!window.confirm(form.dataset.confirm)) event.preventDefault(); return; }
    event.preventDefault(); pending = {form, submitter: event.submitter};
    dialog.querySelector('[data-confirm-text]').textContent = form.dataset.confirm;
    dialog.showModal(); dialog.querySelector('[data-cancel]').focus();
  }));
  dialog?.querySelector('[data-cancel]')?.addEventListener('click', () => { dialog.close(); pending = null; });
  dialog?.querySelector('[data-yes]')?.addEventListener('click', () => {
    if (!pending) return;
    const {form, submitter} = pending; form.dataset.confirmed = 'yes'; dialog.close();
    if (submitter) form.requestSubmit(submitter); else form.requestSubmit();
  });
});
