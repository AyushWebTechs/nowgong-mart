if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js')
            .catch(error => console.error('SW registration failed:', error));
    });
}

document.addEventListener('submit', event => {
    const message = event.target.dataset.confirm;
    if (message && !window.confirm(message)) {
        event.preventDefault();
    }
});

document.addEventListener('click', event => {
    const button = event.target.closest('[data-step]');
    if (!button) return;
    const input = button.parentElement.querySelector('input[type=number]');
    if (!input) return;
    const step = parseFloat(input.step || '1');
    const min = parseFloat(input.min || step);
    const next = Math.max(min, (parseFloat(input.value || '0') + step * parseInt(button.dataset.step, 10)));
    input.value = Number.isInteger(step) ? next : next.toFixed(2);
});

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
