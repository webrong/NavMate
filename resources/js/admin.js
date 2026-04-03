// Admin Panel - Shared JavaScript

function openAdminModal(title, bodyHtml) {
    document.getElementById('admin-modal-title').textContent = title;
    document.getElementById('admin-modal-body').innerHTML = bodyHtml;
    document.getElementById('admin-modal').classList.remove('hidden');
}

function closeAdminModal() {
    document.getElementById('admin-modal').classList.add('hidden');
    document.getElementById('admin-modal-body').innerHTML = '';
}

function showToast(message, duration) {
    const container = document.getElementById('admin-toast');
    const inner = container.querySelector('div');
    inner.textContent = message;
    container.classList.remove('hidden');
    clearTimeout(container._timer);
    container._timer = setTimeout(() => {
        container.classList.add('hidden');
    }, duration || 2500);
}

// Close modal on Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeAdminModal();
    }
});
