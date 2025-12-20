/**
 * Main JavaScript File
 * Handles global logic, state management, and utilitarian functions.
 */

document.addEventListener('DOMContentLoaded', () => {
    console.log('App initialized');
    initApp();
});

function initApp() {
    // Add active class to current nav item
    highlightCurrentNav();
}

function highlightCurrentNav() {
    try {
        const path = window.location.pathname;
        const navLinks = document.querySelectorAll('.nav-item');

        navLinks.forEach(link => {
            const href = link.getAttribute('href');
            if (href && path.includes(href)) {
                link.classList.add('active');
            }
        });
    } catch (e) {
        console.error('Nav highlighting error:', e);
    }
}

// Global utils
const utils = {
    // Format date: "Senin, 12 Des 2024"
    formatDate: (dateStr) => {
        const options = { weekday: 'long', year: 'numeric', month: 'short', day: 'numeric' };
        return new Date(dateStr).toLocaleDateString('id-ID', options);
    },

    // Format currency if needed
    formatCurrency: (amount) => {
        return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' }).format(amount);
    }
};

/* --- Global Modal Logic --- */
// Run immediately to avoid race conditions with other scripts
injectGlobalModal();

let modalCallback = null;
let confirmCallback = null;
let cancelCallback = null;

function injectGlobalModal() {
    if (document.getElementById('globalModal')) return;

    const modalHTML = `
    <!-- Global Modal -->
    <div id="globalModal" class="modal-overlay" style="z-index: 2147483647 !important; align-items: center !important; justify-content: center !important;">
        <div class="global-modal-content">
            <div class="modal-icon" id="globalModalIcon"></div>
            <div class="modal-title" id="globalModalTitle"></div>
            <div class="modal-message" id="globalModalMessage"></div>
            <div class="modal-actions" id="globalModalActions">
                <button class="modal-btn" onclick="closeGlobalModal()">OK</button>
            </div>
        </div>
    </div>
    <!-- Global Loading -->
    <div id="globalLoading" class="loading-overlay" style="z-index: 2147483647 !important;">
        <div class="loading-spinner"></div>
        <div class="loading-text">Memproses...</div>
    </div>
    `;
    document.body.insertAdjacentHTML('beforeend', modalHTML);

    // Click outside to close (optional, maybe not for confirm)
    /*
    document.getElementById('globalModal').addEventListener('click', (e) => {
        if (e.target.id === 'globalModal') closeGlobalModal();
    });
    */
}

// Show Alert-like Modal
function showModal(type, title, message, callback = null) {
    const modal = document.getElementById('globalModal');
    if (!modal) return; // Should not happen

    const icon = document.getElementById('globalModalIcon');
    const titleEl = document.getElementById('globalModalTitle');
    const msgEl = document.getElementById('globalModalMessage');
    const actions = document.getElementById('globalModalActions');

    // Reset Classes
    icon.className = 'modal-icon';
    icon.innerHTML = '✓'; // Default success

    if (type === 'success') {
        icon.classList.remove('error', 'warning');
        icon.innerHTML = '<span class="material-icons-round" style="font-size:36px">check</span>';
    } else if (type === 'error') {
        icon.classList.add('error');
        icon.classList.remove('warning');
        icon.innerHTML = '<span class="material-icons-round" style="font-size:36px">close</span>';
    } else if (type === 'warning') {
        icon.classList.add('warning');
        icon.classList.remove('error');
        icon.innerHTML = '<span class="material-icons-round" style="font-size:36px">priority_high</span>';
    }

    titleEl.textContent = title;
    msgEl.innerHTML = message.replace(/\n/g, '<br>');

    actions.innerHTML = `<button class="modal-btn" onclick="closeGlobalModal()">OK</button>`;

    modal.style.display = 'flex';
    modalCallback = callback;
}

// Show Confirm-like Modal
function showConfirm(title, message, onYes, onNo = null) {
    const modal = document.getElementById('globalModal');
    if (!modal) return;

    const icon = document.getElementById('globalModalIcon');
    const titleEl = document.getElementById('globalModalTitle');
    const msgEl = document.getElementById('globalModalMessage');
    const actions = document.getElementById('globalModalActions');

    // Warning style for confirm usually
    icon.className = 'modal-icon warning';
    icon.innerHTML = '<span class="material-icons-round" style="font-size:36px">question_mark</span>';

    titleEl.textContent = title;
    msgEl.innerHTML = message.replace(/\n/g, '<br>');

    actions.innerHTML = `
        <button class="modal-btn secondary" onclick="handleConfirm(false)">Batal</button>
        <button class="modal-btn" onclick="handleConfirm(true)">Ya</button>
    `;

    modal.style.display = 'flex';
    confirmCallback = onYes;
    cancelCallback = onNo;
}

function handleConfirm(isYes) {
    document.getElementById('globalModal').style.display = 'none';
    if (isYes && confirmCallback) confirmCallback();
    else if (!isYes && cancelCallback) cancelCallback();

    confirmCallback = null;
    cancelCallback = null;
}

function closeGlobalModal() {
    document.getElementById('globalModal').style.display = 'none';
    if (modalCallback) {
        modalCallback();
        modalCallback = null;
    }
}

function toggleLoading(show) {
    const loader = document.getElementById('globalLoading');
    if (loader) loader.style.display = show ? 'flex' : 'none';
}
