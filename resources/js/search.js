// Search overlay: live suggestions, typo correction notice, recent + trending searches, keyboard navigation.
import * as Turbo from '@hotwired/turbo';
import { addToCart } from './cart';
import { toast, fadeInImages } from './ui';

const RECENT_KEY = 'nm:recent-searches';
const cache = new Map();
let controller = null;
let debounce = null;
let active = -1;

const $ = selector => document.querySelector(selector);
const root = () => $('[data-search]');
const input = () => $('[data-search-input]');
const results = () => $('[data-search-results]');

const escapeHtml = text => String(text).replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c]);
const escapeRe = text => text.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');

function highlight(text, query) {
    const words = query.trim().split(/\s+/).filter(w => w.length > 0).map(escapeRe);
    const safe = escapeHtml(text);
    if (!words.length) return safe;
    return safe.replace(new RegExp(`(${words.join('|')})`, 'gi'), '<mark>$1</mark>');
}

function readRecent() {
    try { return JSON.parse(localStorage.getItem(RECENT_KEY)) || []; } catch { return []; }
}
function saveRecent(term) {
    term = term.trim();
    if (!term) return;
    const list = [term, ...readRecent().filter(t => t.toLowerCase() !== term.toLowerCase())].slice(0, 6);
    try { localStorage.setItem(RECENT_KEY, JSON.stringify(list)); } catch { /* private mode */ }
}

const icon = (paths, cls = 'size-4') =>
    `<svg class="${cls} shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">${paths}</svg>`;
const ICON = {
    history: '<path d="M3 12a9 9 0 1 0 3-6.7L3 8"/><path d="M3 3v5h5"/><path d="M12 7v5l3 2"/>',
    trending: '<path d="m22 7-8.5 8.5-5-5L2 17"/><path d="M16 7h6v6"/>',
    search: '<circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>',
    arrow: '<path d="M17 17 7 7M7 15V7h8"/>',
    grid: '<rect x="3" y="3" width="7" height="7" rx="2"/><rect x="14" y="3" width="7" height="7" rx="2"/><rect x="3" y="14" width="7" height="7" rx="2"/><rect x="14" y="14" width="7" height="7" rx="2"/>',
};

const chip = (label, attrs, iconPaths) =>
    `<button type="button" ${attrs} class="chip gap-1.5 text-[13px]">${iconPaths ? icon(iconPaths, 'size-3.5 text-gray-400') : ''}${escapeHtml(label)}</button>`;

function renderIdle() {
    const el = root();
    const recent = readRecent();
    const trending = JSON.parse(el.dataset.trending || '[]');
    const categories = JSON.parse(el.dataset.categories || '[]');

    results().innerHTML = `
        ${recent.length ? `
            <div class="mb-5">
                <div class="mb-2 flex items-center justify-between px-1">
                    <h3 class="text-xs font-extrabold tracking-wide text-gray-500 uppercase">Recent searches</h3>
                    <button type="button" data-search-clear class="text-xs font-bold text-brand-700">Clear</button>
                </div>
                <div class="flex flex-wrap gap-2">${recent.map(t => chip(t, `data-search-term="${escapeHtml(t)}"`, ICON.history)).join('')}</div>
            </div>` : ''}
        ${trending.length ? `
            <div class="mb-5">
                <h3 class="mb-2 px-1 text-xs font-extrabold tracking-wide text-gray-500 uppercase">Trending near you</h3>
                <div class="flex flex-wrap gap-2">${trending.map(t => chip(t, `data-search-term="${escapeHtml(t)}"`, ICON.trending)).join('')}</div>
            </div>` : ''}
        <div>
            <h3 class="mb-2 px-1 text-xs font-extrabold tracking-wide text-gray-500 uppercase">Browse categories</h3>
            <div class="grid grid-cols-2 gap-2 sm:grid-cols-3">
                ${categories.map(c => `<a href="${escapeHtml(c.url)}" data-search-link class="flex items-center gap-2 rounded-xl bg-gray-50 px-3 py-2.5 text-sm font-bold text-gray-700 hover:bg-brand-50 hover:text-brand-700">${icon(ICON.grid, 'size-4 text-gray-400')}${escapeHtml(c.name)}</a>`).join('')}
            </div>
        </div>`;
    active = -1;
}

function renderSkeleton() {
    results().innerHTML = Array.from({ length: 4 }, () => `
        <div class="flex items-center gap-3 p-2">
            <div class="skeleton size-12 rounded-xl"></div>
            <div class="flex-1 space-y-2"><div class="skeleton h-3 w-2/3 rounded"></div><div class="skeleton h-3 w-1/3 rounded"></div></div>
        </div>`).join('');
}

function renderResults(query, data) {
    const shown = data.corrected || query;
    const products = data.products || [];
    const categories = data.categories || [];

    if (!products.length && !categories.length) {
        results().innerHTML = `
            <div class="flex flex-col items-center px-4 py-10 text-center">
                <span class="grid size-14 place-items-center rounded-full bg-gray-100 text-gray-400">${icon(ICON.search, 'size-7')}</span>
                <p class="mt-3 font-bold text-gray-800">No results for “${escapeHtml(query)}”</p>
                <p class="mt-1 text-sm text-gray-500">Check the spelling or try a simpler word.</p>
            </div>`;
        active = -1;
        return;
    }

    results().innerHTML = `
        ${data.corrected ? `<p class="mb-2 px-2 text-sm text-gray-600">Showing results for <span class="font-bold text-brand-700">${escapeHtml(data.corrected)}</span></p>` : ''}
        ${categories.length ? `<div class="mb-2 flex flex-wrap gap-2 px-1">${categories.map(c =>
            `<a href="${escapeHtml(c.url)}" data-search-link data-search-option class="chip text-[13px]">${icon(ICON.grid, 'size-3.5 text-gray-400')}in ${escapeHtml(c.name)}</a>`).join('')}</div>` : ''}
        <ul class="space-y-0.5">
            ${products.map(p => `
                <li class="flex items-center gap-3 rounded-2xl p-2 transition hover:bg-gray-50 data-[active]:bg-brand-50" data-search-option>
                    <a href="${escapeHtml(p.url)}" data-search-link class="flex min-w-0 flex-1 items-center gap-3">
                        <span class="skeleton size-14 shrink-0 overflow-hidden rounded-xl bg-gray-100">
                            ${p.image ? `<img src="${escapeHtml(p.image)}" alt="" class="fade-img size-full object-cover" loading="lazy">` : ''}
                        </span>
                        <span class="min-w-0">
                            <span class="line-clamp-2 text-sm font-bold text-gray-900">${highlight(p.name, shown)}</span>
                            <span class="mt-0.5 block text-xs text-gray-500">${escapeHtml(p.category ?? '')} · per ${escapeHtml(p.unit)}</span>
                            <span class="mt-0.5 block text-sm font-extrabold">${escapeHtml(p.price)} ${p.mrp ? `<span class="text-xs font-medium text-gray-400 line-through">${escapeHtml(p.mrp)}</span>` : ''}</span>
                        </span>
                    </a>
                    ${p.in_stock
                        ? `<button type="button" data-search-add="${p.id}" class="atc-btn w-16 shrink-0 text-xs">ADD</button>`
                        : '<span class="shrink-0 text-xs font-bold text-gray-400">Sold out</span>'}
                </li>`).join('')}
        </ul>
        <button type="button" data-search-all class="mt-2 flex w-full items-center justify-center gap-1 rounded-2xl bg-gray-50 py-3 text-sm font-bold text-brand-700 hover:bg-brand-50">
            See all results for “${escapeHtml(shown)}” ${icon('<path d="m9 18 6-6-6-6"/>')}
        </button>`;
    active = -1;
    fadeInImages(results());
}

async function suggest(query) {
    const key = query.toLowerCase();
    if (cache.has(key)) return renderResults(query, cache.get(key));

    controller?.abort();
    controller = new AbortController();
    if (!results().querySelector('[data-search-option]')) renderSkeleton();

    try {
        const url = `${root().dataset.suggestUrl}?q=${encodeURIComponent(query)}`;
        const response = await fetch(url, { headers: { Accept: 'application/json' }, signal: controller.signal });
        const data = await response.json();
        cache.set(key, data);
        if (input().value.trim().toLowerCase() === key) renderResults(query, data);
    } catch (error) {
        if (error.name !== 'AbortError') results().innerHTML = '<p class="p-4 text-center text-sm text-gray-500">Could not load suggestions. Check your connection.</p>';
    }
}

function open(prefill = '') {
    const el = root();
    if (!el) return;
    el.hidden = false;
    document.documentElement.style.overflow = 'hidden';
    requestAnimationFrame(() => {
        el.querySelector('[data-search-backdrop]').classList.add('opacity-100');
        el.querySelector('[data-search-panel]').classList.add('!opacity-100', '!translate-y-0');
    });
    const field = input();
    field.value = prefill || new URLSearchParams(location.search).get('q') || '';
    field.setAttribute('aria-expanded', 'true');
    field.focus();
    field.value.trim() ? suggest(field.value.trim()) : renderIdle();
}

function close() {
    const el = root();
    if (!el || el.hidden) return;
    el.querySelector('[data-search-backdrop]').classList.remove('opacity-100');
    el.querySelector('[data-search-panel]').classList.remove('!opacity-100', '!translate-y-0');
    input()?.setAttribute('aria-expanded', 'false');
    document.documentElement.style.overflow = '';
    setTimeout(() => (el.hidden = true), 180);
}

function go(term) {
    term = term.trim();
    if (!term) return;
    saveRecent(term);
    close();
    Turbo.visit(`${$('[data-search-form]').action}?q=${encodeURIComponent(term)}`);
}

function move(delta) {
    const options = [...results().querySelectorAll('[data-search-option]')];
    if (!options.length) return;
    options[active]?.removeAttribute('data-active');
    active = (active + delta + options.length) % options.length;
    options[active].setAttribute('data-active', '');
    options[active].scrollIntoView({ block: 'nearest' });
}

// ---- Wiring ----

document.addEventListener('click', async event => {
    if (event.target.closest('[data-search-open]')) return open();
    if (event.target.closest('[data-search-close]')) return close();

    const term = event.target.closest('[data-search-term]');
    if (term) return go(term.dataset.searchTerm);

    if (event.target.closest('[data-search-clear]')) {
        try { localStorage.removeItem(RECENT_KEY); } catch { /* ignore */ }
        return renderIdle();
    }
    if (event.target.closest('[data-search-all]')) return go(input().value);

    const add = event.target.closest('[data-search-add]');
    if (add) {
        add.disabled = true;
        try {
            await addToCart(add.dataset.searchAdd, 1);
            add.textContent = '✓';
            add.classList.add('!bg-brand-600', '!text-white');
            toast('Added to cart');
        } catch (error) {
            toast(error.message, 'error');
            add.disabled = false;
        }
        return;
    }

    if (event.target.closest('[data-search-link]')) {
        saveRecent(input().value);
        close();
    }
});

document.addEventListener('input', event => {
    if (!event.target.matches('[data-search-input]')) return;
    const query = event.target.value.trim();
    clearTimeout(debounce);
    if (!query) return renderIdle();
    debounce = setTimeout(() => suggest(query), 140);
});

document.addEventListener('submit', event => {
    if (!event.target.matches('[data-search-form]')) return;
    event.preventDefault();
    event.stopImmediatePropagation();
    const options = results().querySelectorAll('[data-search-option]');
    const chosen = options[active]?.querySelector('a') ?? (options[active]?.matches('a') ? options[active] : null);
    if (chosen) {
        saveRecent(input().value);
        close();
        return Turbo.visit(chosen.href);
    }
    go(input().value);
}, true);

document.addEventListener('keydown', event => {
    const isOpen = root() && !root().hidden;
    if (!isOpen && event.key === '/' && !event.target.closest('input, textarea, select, [contenteditable]')) {
        event.preventDefault();
        return open();
    }
    if (!isOpen) return;
    if (event.key === 'Escape') close();
    else if (event.key === 'ArrowDown') { event.preventDefault(); move(1); }
    else if (event.key === 'ArrowUp') { event.preventDefault(); move(-1); }
});

document.addEventListener('turbo:before-cache', () => {
    const el = root();
    if (el) el.hidden = true;
    document.documentElement.style.overflow = '';
});
