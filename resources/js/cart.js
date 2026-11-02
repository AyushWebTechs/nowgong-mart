// Instant cart: ADD → stepper, +/− with debounced sync, remove — all via fetch, no page reloads.
import * as Turbo from '@hotwired/turbo';
import { toast, bump, haptic } from './ui';

const csrf = () => document.querySelector('meta[name="csrf-token"]')?.content ?? '';
const itemsUrl = () => document.body.dataset.cartUrl;
const pending = new Map(); // productId → timeout handle

export async function cartRequest(method, url, body) {
    const response = await fetch(url, {
        method,
        credentials: 'same-origin',
        headers: {
            Accept: 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrf(),
            'X-Requested-With': 'XMLHttpRequest',
        },
        body: body ? JSON.stringify(body) : undefined,
    });

    const data = await response.json().catch(() => ({}));

    if (response.status === 419) {
        toast('Your session expired. Refreshing…', 'error');
        setTimeout(() => Turbo.visit(location.href, { action: 'replace' }), 900);
        throw new Error('Session expired');
    }
    if (!response.ok) {
        const firstError = data.errors ? Object.values(data.errors).flat()[0] : null;
        throw new Error(firstError || data.message || 'Something went wrong. Please try again.');
    }

    return data;
}

export async function addToCart(productId, quantity = 1, sourceEl = null) {
    const data = await cartRequest('POST', itemsUrl(), { product_id: productId, quantity });
    applyCart(data.cart);
    if (sourceEl) flyToCart(sourceEl);
    haptic();
    return data;
}

/** Push server cart state into every badge, bar and ADD/stepper control on the page. */
export function applyCart(cart) {
    const count = cart.count;
    const label = `${count} item${count === 1 ? '' : 's'}`;

    document.querySelectorAll('[data-cart-count]').forEach(el => {
        el.textContent = count;
        el.hidden = count === 0;
        bump(el);
    });
    document.querySelectorAll('[data-cart-count-label]').forEach(el => (el.textContent = label));
    document.querySelectorAll('[data-cart-subtotal]').forEach(el => (el.textContent = cart.subtotal_label));
    document.querySelectorAll('[data-cart-pill]').forEach(el => {
        el.textContent = count ? `${label} · ${cart.subtotal_label}` : 'My Cart';
    });
    document.querySelectorAll('[data-cart-bar]').forEach(el => {
        const wasHidden = el.hidden;
        el.hidden = count === 0;
        if (wasHidden && count) bump(el, 'animate-fade-up');
    });

    document.querySelectorAll('[data-atc]').forEach(control => {
        const item = cart.items[control.dataset.product];
        if (pending.has(control.dataset.product)) return; // the user is still tapping; don't fight them
        renderControl(control, item ? item.id : '', item ? item.qty : 0);
    });

    if (document.querySelector('[data-cart-live]')) {
        Turbo.visit(location.href, { action: 'replace' }); // morph refresh keeps scroll + totals exact
    }
}

function renderControl(control, itemId, qty) {
    control.dataset.item = itemId;
    control.dataset.qty = qty;
    const add = control.querySelector('[data-atc-add]');
    const stepper = control.querySelector('[data-atc-stepper]');
    if (!add || !stepper) return;
    const inCart = Number(qty) > 0;
    if (add.hidden !== inCart) add.hidden = inCart;
    if (stepper.hidden === inCart) stepper.hidden = !inCart;
    stepper.querySelector('[data-atc-qty]').textContent = trimQty(qty);
}

const trimQty = qty => String(Number(Number(qty).toFixed(3)));

/** Same-product controls (card + rail + detail) move together. */
const controlsFor = productId => document.querySelectorAll(`[data-atc][data-product="${productId}"]`);

function step(control, direction) {
    const productId = control.dataset.product;
    const stepSize = Number(control.dataset.step || 1);
    const next = Math.max(0, Math.round((Number(control.dataset.qty) + direction * stepSize) * 1000) / 1000);
    if (next > 99) return toast('Maximum 99 per item.', 'error');

    controlsFor(productId).forEach(c => renderControl(c, control.dataset.item, next));
    if (direction > 0) haptic();

    clearTimeout(pending.get(productId));
    pending.set(productId, setTimeout(() => sync(control, next), 350));
}

async function sync(control, qty) {
    const productId = control.dataset.product;
    const itemId = control.dataset.item;
    try {
        const data = qty <= 0
            ? await cartRequest('DELETE', `${itemsUrl()}/${itemId}`)
            : await cartRequest('PATCH', `${itemsUrl()}/${itemId}`, { quantity: qty });
        pending.delete(productId);
        applyCart(data.cart);
        if (qty <= 0) toast('Removed from cart');
    } catch (error) {
        pending.delete(productId);
        toast(error.message, 'error');
        Turbo.visit(location.href, { action: 'replace' }); // resync with the server's truth
    }
}

/** Animate a product thumbnail flying into the visible cart button. */
function flyToCart(sourceEl) {
    if (matchMedia('(prefers-reduced-motion: reduce)').matches) return;
    const img = sourceEl.closest('[data-product-card], main')?.querySelector('img');
    const target = [...document.querySelectorAll('[data-cart-target]')].find(el => el.offsetParent !== null && !el.hidden);
    if (!img || !target) return;

    const from = img.getBoundingClientRect();
    const to = target.getBoundingClientRect();
    const ghost = img.cloneNode();
    Object.assign(ghost.style, {
        position: 'fixed', left: `${from.left}px`, top: `${from.top}px`, width: `${from.width}px`, height: `${from.height}px`,
        borderRadius: '16px', zIndex: 60, pointerEvents: 'none', objectFit: 'cover', viewTransitionName: 'none', opacity: 1,
    });
    document.body.appendChild(ghost);

    const dx = to.left + to.width / 2 - (from.left + from.width / 2);
    const dy = to.top + to.height / 2 - (from.top + from.height / 2);
    ghost.animate(
        [
            { transform: 'translate(0,0) scale(1)', opacity: 1 },
            { transform: `translate(${dx * 0.6}px, ${dy * 0.3 - 60}px) scale(0.45)`, opacity: 0.9, offset: 0.55 },
            { transform: `translate(${dx}px, ${dy}px) scale(0.1)`, opacity: 0.2 },
        ],
        { duration: 650, easing: 'cubic-bezier(.5,0,.75,0)' },
    ).finished.then(() => {
        ghost.remove();
        bump(target);
    });
}

// ---- Event wiring (document-level, so it survives Turbo page swaps) ----

document.addEventListener('submit', async event => {
    const form = event.target;

    if (form.matches('[data-atc-add]')) {
        event.preventDefault();
        const control = form.closest('[data-atc]');
        const button = form.querySelector('button');
        button.disabled = true;
        try {
            await addToCart(control.dataset.product, Number(form.quantity.value || 1), control);
        } catch (error) {
            toast(error.message, 'error');
        } finally {
            button.disabled = false;
        }
        return;
    }

    if (form.matches('[data-cart-remove]')) {
        event.preventDefault();
        form.closest('[id^="line-"]')?.animate([{ opacity: 1 }, { opacity: 0, transform: 'translateX(24px)' }], { duration: 200, fill: 'forwards' });
        try {
            applyCart((await cartRequest('DELETE', form.action)).cart);
            toast('Item removed');
        } catch (error) {
            toast(error.message, 'error');
            Turbo.visit(location.href, { action: 'replace' });
        }
    }
}, true); // capture: run before Turbo's own form handling

document.addEventListener('click', event => {
    const inc = event.target.closest('[data-atc-inc]');
    const dec = event.target.closest('[data-atc-dec]');
    if (!inc && !dec) return;
    event.preventDefault();
    step((inc || dec).closest('[data-atc]'), inc ? 1 : -1);
});
