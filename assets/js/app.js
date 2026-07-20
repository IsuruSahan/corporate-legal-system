document.addEventListener("DOMContentLoaded", function () {
    // 1. Dynamic Text Counter constraints tracking
    const textareas = document.querySelectorAll('.form-field-textarea');
    textareas.forEach(textarea => {
        textarea.addEventListener('input', function () {
            const counterId = this.id + '_counter';
            const counterEl = document.getElementById(counterId);
            if (counterEl) {
                const maxLength = this.getAttribute('maxlength') || 500;
                counterEl.textContent = `${this.value.length} / ${maxLength} characters`;
            }
        });
    });

    // 2. Drag & Drop Upload Zone Handler Configuration
    const dropzone = document.querySelector('.file-dropzone-area');
    if (dropzone) {
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            dropzone.addEventListener(eventName, e => {
                e.preventDefault();
                e.stopPropagation();
            }, false);
        });

        ['dragenter', 'dragover'].forEach(eventName => {
            dropzone.addEventListener(eventName, () => dropzone.classList.add('highlight'), false);
        });

        ['dragleave', 'drop'].forEach(eventName => {
            dropzone.addEventListener(eventName, () => dropzone.classList.remove('highlight'), false);
        });

            dropzone.addEventListener('drop', e => {
            const dt = e.dataTransfer;
            const files = dt.files;
            if (files.length) {
                const fileInput = dropzone.querySelector('input[type="file"]') || document.querySelector('input[type="file"]');
                if (fileInput) {
                    fileInput.files = files;
                    fileInput.dispatchEvent(new Event('change', { bubbles: true }));
                }
                console.log("File detected and staged for processing: ", files[0].name);
            }
        });
    }
});

// Utility triggers for global modal state architecture
function showSystemModal(title, text, type = 'success') {
    const modal = document.getElementById('systemModal');
    const icon = document.getElementById('modalIcon');
    const titleEl = document.getElementById('modalTitle');
    const textEl = document.getElementById('modalText');
    
    titleEl.textContent = title;
    textEl.textContent = text;
    
    if (type === 'success') {
        icon.className = 'modal-icon success';
        icon.textContent = '✓';
    } else {
        icon.className = 'modal-icon error';
        icon.textContent = '✕';
    }
    
    modal.classList.add('active');
}

function closeSystemModal() {
    const modal = document.getElementById('systemModal');
    if (modal) modal.classList.remove('active');
}

/**
 * Universal System Endpoint Dispatcher
 * Constructs absolute API paths using BASE_URL dynamically
 */
function getApiEndpoint(action = 'router.php') {
    if (typeof BASE_URL !== 'undefined') {
        return BASE_URL + 'config/' + action;
    }
    return '../config/' + action;
}