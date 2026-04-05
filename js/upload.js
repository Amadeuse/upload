class UploadSection {
    constructor(container) {
        this.container = container;
        this.uploadUrl = container.dataset.uploadUrl || 'handlers/upload.php';
        this.render();
        this.bindEvents();
    }

    render() {
        this.container.innerHTML = `
            <div class="upload-widget text-center">
                <input type="file" class="upload-input d-none">
                <button type="button" class="upload-square-btn" aria-label="ატვირთვა">
                    <i class="bi bi-cloud-arrow-up-fill"></i>
                    <span>ატვირთვა</span>
                </button>
                <div class="upload-result mt-3 small text-muted">აირჩიე ფაილი ასატვირთად</div>
            </div>
        `;

        this.fileInput = this.container.querySelector('.upload-input');
        this.button = this.container.querySelector('.upload-square-btn');
        this.result = this.container.querySelector('.upload-result');
    }

    bindEvents() {
        this.button.addEventListener('click', () => this.fileInput.click());
        this.fileInput.addEventListener('change', () => {
            const [file] = this.fileInput.files;
            if (file) {
                this.upload(file);
            }
        });
    }

    async upload(file) {
        const formData = new FormData();
        formData.append('file', file);

        this.setLoading(true);
        this.showMessage('იტვირთება...', 'muted');

        try {
            const response = await fetch(this.uploadUrl, {
                method: 'POST',
                body: formData,
            });

            const result = await response.json();

            if (!response.ok || !result.success) {
                throw result;
            }

            this.container.dataset.uploadResult = JSON.stringify(result);
            this.showMessage(`ფაილი: ${result.fileName} | ზომა: ${result.fileSizeFormatted}`, 'success');
            this.container.dispatchEvent(new CustomEvent('upload:success', { detail: result }));

            if (typeof window.onUploadComplete === 'function') {
                window.onUploadComplete(result, this.container);
            }
        } catch (error) {
            const result = {
                success: false,
                errorCode: error.errorCode || 'UPLOAD_ERROR',
                message: error.message || 'ატვირთვა ვერ მოხერხდა.',
            };

            this.showMessage(`${result.errorCode}: ${result.message}`, 'error');
            this.container.dispatchEvent(new CustomEvent('upload:error', { detail: result }));

            if (typeof window.onUploadError === 'function') {
                window.onUploadError(result, this.container);
            }
        } finally {
            this.setLoading(false);
            this.fileInput.value = '';
        }
    }

    setLoading(isLoading) {
        this.button.disabled = isLoading;
        this.button.classList.toggle('is-loading', isLoading);
    }

    showMessage(message, type) {
        this.result.textContent = message;
        this.result.className = 'upload-result mt-3 small';

        if (type === 'success') {
            this.result.classList.add('text-success');
        } else if (type === 'error') {
            this.result.classList.add('text-danger');
        } else {
            this.result.classList.add('text-muted');
        }
    }
}

function initUploadSections(selector = '.upload-section') {
    document.querySelectorAll(selector).forEach((container) => {
        if (!container.dataset.uploadInitialized) {
            container.dataset.uploadInitialized = 'true';
            new UploadSection(container);
        }
    });
}

document.addEventListener('DOMContentLoaded', () => initUploadSections());
window.initUploadSections = initUploadSections;
