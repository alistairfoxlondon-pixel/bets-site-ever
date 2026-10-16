import axios from 'axios';

window.axios = axios;
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
window.axios.defaults.withCredentials = true;

const token = document.head.querySelector('meta[name="csrf-token"]');
if (token) {
    window.axios.defaults.headers.common['X-CSRF-TOKEN'] = token.content;
}

/**
 * Small, dependency free helpers used across the product UI.
 */
document.addEventListener('DOMContentLoaded', () => {
    // Dismissable flash messages
    document.querySelectorAll('[data-dismiss]').forEach((el) => {
        el.addEventListener('click', () => el.remove());
    });

    // Toggles: [data-toggle="#id"] opens and closes the target panel
    document.querySelectorAll('[data-toggle]').forEach((el) => {
        el.addEventListener('click', (event) => {
            event.preventDefault();
            const target = document.querySelector(el.dataset.toggle);
            if (!target) return;
            target.classList.toggle('hidden');
            el.setAttribute('aria-expanded', target.classList.contains('hidden') ? 'false' : 'true');
        });
    });

    // Password visibility toggles
    document.querySelectorAll('[data-toggle-password]').forEach((el) => {
        el.addEventListener('click', () => {
            const field = document.querySelector(el.dataset.togglePassword);
            if (!field) return;
            const isPassword = field.getAttribute('type') === 'password';
            field.setAttribute('type', isPassword ? 'text' : 'password');
            el.setAttribute('aria-label', isPassword ? 'Hide password' : 'Show password');
        });
    });

    // Copy to clipboard. The value is either literal text or a selector.
    document.querySelectorAll('[data-copy]').forEach((el) => {
        el.addEventListener('click', async () => {
            const raw = el.dataset.copy || '';
            let value = raw;

            if (raw.startsWith('#')) {
                const source = document.querySelector(raw);
                value = source ? (source.value ?? source.textContent ?? '') : '';
            }

            try {
                await navigator.clipboard.writeText(String(value).trim());
                const original = el.textContent.trim();
                el.textContent = 'Copied';
                el.classList.add('text-pass');
                setTimeout(() => {
                    el.textContent = original;
                    el.classList.remove('text-pass');
                }, 1600);
            } catch (error) {
                /* clipboard unavailable, nothing else to do */
            }
        });
    });

    // Auto dismiss flash messages
    document.querySelectorAll('[data-auto-dismiss]').forEach((el) => {
        setTimeout(() => el.remove(), Number(el.dataset.autoDismiss || 6000));
    });

    // Bulk selection for admin tables
    const toggleAll = document.querySelector('[data-check-all]');
    if (toggleAll) {
        toggleAll.addEventListener('change', () => {
            document.querySelectorAll('[data-check-row]').forEach((row) => {
                row.checked = toggleAll.checked;
            });
        });
    }

    // Slug preview for blog posts
    const titleInput = document.querySelector('[data-slug-source]');
    const slugInput = document.querySelector('[data-slug-target]');
    if (titleInput && slugInput && !slugInput.dataset.locked) {
        titleInput.addEventListener('input', () => {
            slugInput.value = titleInput.value
                .toLowerCase()
                .replace(/[^a-z0-9]+/g, '-')
                .replace(/^-+|-+$/g, '')
                .slice(0, 120);
        });
    }

    // Mobile navigation (marketing drawer + product sidebar)
    document.querySelectorAll('[data-nav-toggle]').forEach((button) => {
        button.addEventListener('click', () => {
            const id = button.getAttribute('aria-controls');
            const target = id ? document.getElementById(id) : null;
            if (!target) return;

            const open = target.classList.toggle('is-open');
            if (target.id === 'mobile-nav') {
                target.hidden = !open;
            }
            button.setAttribute('aria-expanded', open ? 'true' : 'false');
            document.body.classList.toggle('nav-open', open);
        });
    });

    document.addEventListener('click', (event) => {
        if (!document.body.classList.contains('nav-open')) return;
        const sidebar = document.getElementById('app-sidebar');
        const toggle = document.querySelector('[aria-controls="app-sidebar"]');
        if (!sidebar || !sidebar.classList.contains('is-open')) return;
        if (sidebar.contains(event.target) || (toggle && toggle.contains(event.target))) return;
        sidebar.classList.remove('is-open');
        document.body.classList.remove('nav-open');
        if (toggle) toggle.setAttribute('aria-expanded', 'false');
    });

    // Submit the audit form and show a busy state while the crawler works
    document.querySelectorAll('[data-busy-form]').forEach((form) => {
        form.addEventListener('submit', () => {
            const button = form.querySelector('button[type="submit"]');
            if (!button) return;

            button.disabled = true;
            button.dataset.originalLabel = button.textContent;
            button.textContent = form.dataset.busyLabel || 'Working, please wait';
        });
    });
});

/**
 * CKEditor 5 for the long form editors in the control panel. The editor is
 * loaded on demand so visitors never pay for it.
 */
async function mountEditors() {
    const fields = document.querySelectorAll('textarea[data-editor]');

    if (fields.length === 0) {
        return;
    }

    try {
        const { ClassicEditor, Essentials, Paragraph, Heading, Bold, Italic, Link, List, BlockQuote, Autoformat } =
            await import('ckeditor5');
        await import('ckeditor5/ckeditor5.css');

        const editors = [];

        for (const field of fields) {
            const instance = await ClassicEditor.create(field, {
                licenseKey: 'GPL',
                plugins: [Essentials, Paragraph, Heading, Bold, Italic, Link, List, BlockQuote, Autoformat],
                toolbar: {
                    items: ['undo', 'redo', '|', 'heading', '|', 'bold', 'italic', '|', 'link', 'blockQuote', '|', 'bulletedList', 'numberedList'],
                },
            });
            editors.push({ field, instance });
        }

        const forms = new Set(editors.map(({ field }) => field.form).filter(Boolean));
        forms.forEach((form) => {
            form.addEventListener('submit', () => {
                editors.forEach(({ field, instance }) => {
                    field.value = instance.getData();
                });
            });
        });
    } catch (error) {
        /* the plain textarea stays usable when the editor cannot load */
    }
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', mountEditors);
} else {
    mountEditors();
}
