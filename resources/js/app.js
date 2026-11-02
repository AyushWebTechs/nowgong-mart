// Turbo Drive: every link click and form submit becomes a fetch + page swap (no full reloads),
// with hover prefetching, a progress bar, view transitions and morphing refreshes.
import * as Turbo from '@hotwired/turbo';

import './ui';
import './cart';
import './search';
import './infinite';

Turbo.config.drive.progressBarDelay = 120;

// Keep already-loaded images visible when Turbo morphs the page in place.
document.addEventListener('turbo:before-morph-attribute', event => {
    const { target, detail } = event;
    if (detail.attributeName === 'class' && target.matches?.('img.fade-img.is-loaded, span:has(> img.fade-img.is-loaded)')) {
        event.preventDefault();
    }
});

if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js')
            .catch(error => console.error('SW registration failed:', error));
    });
}
