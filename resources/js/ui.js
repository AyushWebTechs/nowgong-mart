// Shared UI helpers: toasts, micro-animations, image fade-in, typing placeholder, PWA install, confirms.

const ICONS = {
    success: '<path d="m5 12 5 5 9-10"/>',
    error: '<circle cx="12" cy="12" r="9"/><path d="M12 8v5M12 16h.01"/>',
};

export function toast(message, type = 'success') {
    let stack = document.getElementById('toasts');
    if (!stack) {
        stack = document.createElement('div');
        stack.id = 'toasts';
        stack.setAttribute('aria-live', 'polite');
        stack.className = 'pointer-events-none fixed inset-x-0 top-3 z-[70] flex flex-col items-center gap-2 px-4';
        document.body.appendChild(stack);
    }

    const el = document.createElement('div');
    const tone = type === 'error' ? 'bg-rose-600' : 'bg-gray-900';
    el.className = `pointer-events-auto flex max-w-md items-center gap-2.5 rounded-2xl ${tone} px-4 py-3 text-sm font-semibold text-white shadow-2xl`;
    el.innerHTML = `<svg class="size-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">${ICONS[type] ?? ICONS.success}</svg><span></span>`;
    el.querySelector('span').textContent = message;
    stack.appendChild(el);

    el.animate([{ opacity: 0, transform: 'translateY(-16px) scale(.96)' }, { opacity: 1, transform: 'none' }], { duration: 260, easing: 'cubic-bezier(.16,1,.3,1)' });
    setTimeout(() => {
        el.animate([{ opacity: 1 }, { opacity: 0, transform: 'translateY(-10px)' }], { duration: 220, fill: 'forwards' }).finished.then(() => el.remove());
    }, type === 'error' ? 4000 : 2200);
}

/** Re-trigger a one-shot CSS animation class. */
export function bump(el, className = 'pop') {
    if (!el) return;
    el.classList.remove(className);
    void el.offsetWidth;
    el.classList.add(className);
}

export const haptic = () => navigator.vibrate?.(8);

function fadeInImages(root = document) {
    root.querySelectorAll('img.fade-img:not(.is-loaded)').forEach(img => {
        const done = () => {
            img.classList.add('is-loaded');
            img.parentElement?.classList.remove('skeleton');
        };
        if (img.complete && img.naturalWidth) done();
        else img.addEventListener('load', done, { once: true });
    });
}
export { fadeInImages };

// Rotating "Search for …" hint in the header.
const HINTS = ['"milk"', '"atta"', '"ice cream"', '"chips"', '"eggs"', '"tea"', '"doodh"'];
let hintTimer;
function startTypingHints() {
    clearInterval(hintTimer);
    let i = 0;
    hintTimer = setInterval(() => {
        i = (i + 1) % HINTS.length;
        document.querySelectorAll('[data-typing]').forEach(el => {
            el.animate([{ opacity: 1, transform: 'none' }, { opacity: 0, transform: 'translateY(-6px)' }], { duration: 180 }).finished.then(() => {
                el.textContent = HINTS[i];
                el.animate([{ opacity: 0, transform: 'translateY(6px)' }, { opacity: 1, transform: 'none' }], { duration: 220 });
            });
        });
    }, 2600);
}

// PWA install button.
let deferredInstall = null;
window.addEventListener('beforeinstallprompt', event => {
    event.preventDefault();
    deferredInstall = event;
    document.querySelectorAll('[data-install]').forEach(el => el.classList.remove('hidden'));
});
document.addEventListener('click', async event => {
    if (!event.target.closest('[data-install]') || !deferredInstall) return;
    deferredInstall.prompt();
    await deferredInstall.userChoice;
    deferredInstall = null;
    document.querySelectorAll('[data-install]').forEach(el => el.classList.add('hidden'));
});

// Confirmation prompts on destructive forms (capture phase so it runs before Turbo submits).
document.addEventListener('submit', event => {
    const message = event.target.dataset.confirm;
    if (message && !window.confirm(message)) {
        event.preventDefault();
        event.stopImmediatePropagation();
    }
}, true);

// Number inputs with −/+ buttons (checkout, admin forms).
document.addEventListener('click', event => {
    const button = event.target.closest('[data-step]');
    if (!button) return;
    const input = button.parentElement.querySelector('input[type=number]');
    if (!input) return;
    const stepSize = parseFloat(input.step || '1');
    const min = parseFloat(input.min || stepSize);
    const next = Math.max(min, parseFloat(input.value || '0') + stepSize * parseInt(button.dataset.step, 10));
    input.value = Number.isInteger(stepSize) ? next : next.toFixed(2);
});

// Auto-submit filter/sort forms on change.
document.addEventListener('change', event => {
    const form = event.target.closest('form[data-autosubmit]');
    if (form) form.requestSubmit();
});

// Shared-element transition: the tapped product image morphs into the product page hero.
document.addEventListener('click', event => {
    const link = event.target.closest('[data-product-link]');
    if (!link) return;
    document.querySelectorAll('[style*="product-hero"]').forEach(el => (el.style.viewTransitionName = ''));
    const img = link.closest('[data-product-card]')?.querySelector('img, [aria-hidden]');
    if (img) img.style.viewTransitionName = 'product-hero';
});

document.addEventListener('turbo:load', () => {
    fadeInImages();
    startTypingHints();
    if (deferredInstall) document.querySelectorAll('[data-install]').forEach(el => el.classList.remove('hidden'));
    document.querySelectorAll('[data-toast]').forEach(el => {
        toast(el.textContent.trim(), el.dataset.toast);
        el.remove();
    });
});
document.addEventListener('turbo:frame-load', () => fadeInImages());
document.addEventListener('turbo:morph', () => fadeInImages());
