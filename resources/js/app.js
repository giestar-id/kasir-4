import './bootstrap';
import { createIcons, icons } from 'lucide';

// Global Lucide Icon helper
window.refreshIcons = () => {
    try {
        createIcons({ icons });
    } catch (e) {
        console.error('Lucide render error', e);
    }
};

document.addEventListener('DOMContentLoaded', () => {
    window.refreshIcons();
});

// Re-render icons on Livewire navigation and component morphs
document.addEventListener('livewire:navigated', () => {
    document.body.classList.remove('overflow-hidden');
    window.refreshIcons();
});

// Custom reactive Modal dispatch helpers
window.openModal = (name) => {
    if (!name) return;
    window.dispatchEvent(new CustomEvent('open-modal', { detail: name }));
    window.dispatchEvent(new CustomEvent(`open-modal-${name}`));
};

window.closeModal = (name) => {
    if (!name) return;
    window.dispatchEvent(new CustomEvent('close-modal', { detail: name }));
    window.dispatchEvent(new CustomEvent(`close-modal-${name}`));
};

// Custom reactive Toast dispatch helper
window.showToast = (message, type = 'success', title = null) => {
    const detail = {
        id: Date.now() + Math.random(),
        message: typeof message === 'object' ? (message.message || JSON.stringify(message)) : message,
        type: typeof message === 'object' ? (message.type || type) : type,
        title: typeof message === 'object' ? (message.title || title) : title,
    };
    window.dispatchEvent(new CustomEvent('toast-message', { detail }));
    window.dispatchEvent(new CustomEvent('toast', { detail }));
};

// Livewire 3 Global Event Bridge
document.addEventListener('livewire:init', () => {
    Livewire.hook('morph.updated', () => {
        window.refreshIcons();
    });
    Livewire.hook('commit', () => {
        setTimeout(window.refreshIcons, 50);
    });

    // Bridge Livewire event dispatches to Alpine toast and modal
    Livewire.on('toast', (eventData) => {
        const data = Array.isArray(eventData) ? (eventData[0] || {}) : eventData;
        if (typeof data === 'string') {
            window.showToast(data, 'success');
        } else if (data && typeof data === 'object') {
            window.showToast(data.message || '', data.type || 'success', data.title || null);
        }
    });

    Livewire.on('open-modal', (name) => {
        const modalName = Array.isArray(name) ? name[0] : (typeof name === 'object' ? (name.name || name.id) : name);
        if (modalName) window.openModal(modalName);
    });

    Livewire.on('close-modal', (name) => {
        const modalName = Array.isArray(name) ? name[0] : (typeof name === 'object' ? (name.name || name.id) : name);
        if (modalName) window.closeModal(modalName);
    });
});

window.addEventListener('render-icons', () => {
    setTimeout(window.refreshIcons, 50);
});
