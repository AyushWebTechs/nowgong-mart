// Infinite scroll for product grids: fetches the next page and appends its cards (no reload).
import { fadeInImages } from './ui';

let observer = null;

async function loadMore(link) {
    if (link.dataset.loading) return;
    link.dataset.loading = '1';
    link.textContent = 'Loading…';

    try {
        const html = await (await fetch(link.href, { headers: { Accept: 'text/html' } })).text();
        const doc = new DOMParser().parseFromString(html, 'text/html');
        const grid = document.querySelector('[data-infinite]');
        [...doc.querySelectorAll('[data-infinite] > *')].forEach((card, i) => {
            card.style.setProperty('--i', String(i));
            grid.appendChild(document.importNode(card, true));
        });
        fadeInImages(grid);
        history.replaceState(history.state, '', link.href); // so refresh/back lands near here

        const next = doc.querySelector('[data-infinite-next]');
        if (next) {
            link.href = next.href;
            delete link.dataset.loading;
            link.textContent = 'Load more';
        } else {
            link.closest('[data-infinite-more]')?.remove();
            observer?.disconnect();
        }
    } catch {
        delete link.dataset.loading;
        link.textContent = 'Retry';
    }
}

document.addEventListener('click', event => {
    const link = event.target.closest('[data-infinite-next]');
    if (!link) return;
    event.preventDefault();
    loadMore(link);
});

document.addEventListener('turbo:load', () => {
    observer?.disconnect();
    const link = document.querySelector('[data-infinite-next]');
    if (!link || !('IntersectionObserver' in window)) return;
    observer = new IntersectionObserver(entries => {
        if (entries.some(e => e.isIntersecting)) loadMore(link);
    }, { rootMargin: '600px 0px' });
    observer.observe(link);
});
