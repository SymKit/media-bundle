import { Controller } from '@hotwired/stimulus';
import { getComponent } from '@symfony/ux-live-component';

export default class extends Controller {
    static targets = ['dropZone', 'input'];

    async connect() {
        this.component = await getComponent(this.element);

        this.element.addEventListener('dragover', this.onDragOver.bind(this));
        this.element.addEventListener('dragleave', this.onDragLeave.bind(this));
        this.element.addEventListener('drop', this.onDrop.bind(this));
    }

    disconnect() {
        this.element.removeEventListener('dragover', this.onDragOver.bind(this));
        this.element.removeEventListener('dragleave', this.onDragLeave.bind(this));
        this.element.removeEventListener('drop', this.onDrop.bind(this));
    }

    onDragOver(event) {
        event.preventDefault();
        this.dropZoneTarget.classList.remove('hidden');
    }

    onDragLeave(event) {
        event.preventDefault();
        // Only hide if we are leaving the main element, not entering a child
        if (!this.element.contains(event.relatedTarget)) {
            this.dropZoneTarget.classList.add('hidden');
        }
    }

    onDrop(event) {
        event.preventDefault();
        this.dropZoneTarget.classList.add('hidden');

        const files = event.dataTransfer.files;
        if (files.length > 0) {
            this.uploadFile(files[0]);
        }
    }

    async uploadFile(file) {
        const formData = new FormData();
        formData.append('file', file);

        // Show loading state if possible
        // this.dispatch('upload-start');

        try {
            const response = await fetch('/api/media/upload', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                }
            });

            if (!response.ok) {
                const err = await response.json();
                throw new Error(err.error || 'Upload failed');
            }

            const data = await response.json();

            if (this.component) {
                this.component.emit('media:uploaded', data);
            } else {
                console.error('LiveComponent not found');
            }

            window.dispatchEvent(new CustomEvent('media:uploaded', { detail: data }));
        } catch (error) {
            console.error('Upload error:', error);
            alert('Upload failed: ' + error.message);
        }
    }

    triggerInput(event) {
        if (this.hasInputTarget) {
            this.inputTarget.click();
        } else {
            console.error('Input target not found!');
        }
    }

    onInputChange(event) {
        if (event.target.files.length > 0) {
            this.uploadFile(event.target.files[0]);
        }
    }
}
