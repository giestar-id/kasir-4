// Browser environment bootstrap
window.addEventListener('unhandledrejection', (event) => {
    console.warn('Unhandled rejection:', event.reason);
});
