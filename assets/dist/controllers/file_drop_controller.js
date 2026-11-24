import { Controller } from '@hotwired/stimulus';

/*
 * File of the form theme (block file_widget, template form/_file.html.twig). Same as `FileUpload` of the crudit-v2
 * mockup.
 *
 * - The true file input covers the drop zone: the browser opens the picker on a click and takes a file dropped on it.
 *   The controller only marks the zone while a file is dragged over it (crudit-ui-file--dragging).
 * - The files chosen are listed with their size (and a preview for an image, its alt "Preview of <name>"), each with a
 *   button Remove that empties the input (several files: the others are kept), then puts the focus back on the input.
 * - The file already sent (target current), with a button Delete: it checks the hidden checkbox that asks the deletion,
 *   the line says the file will be deleted, a button Cancel puts it back. The focus goes from one button to the other.
 * - One file per field (not multiple): a file chosen replaces the file already sent when saved (VichUploaderBundle
 *   sends it even when the deletion is asked). While a file is chosen, the line of the file already sent says it will be
 *   replaced (target replaceNote). The two actions exclude each other: choosing a file cancels the deletion asked,
 *   Delete removes the file chosen.
 * - Each change is told in a live region (status). A reset of the form empties the list of the files chosen and keeps
 *   the file already sent.
 * The texts come from the theme, translated by the server (%name%, %size%); the sizes are written in the locale of the
 * request (value locale, else the lang of the page), in Latin digits.
 */
export default class extends Controller {
    static targets = ['zone', 'list', 'current', 'chosen', 'note', 'replaceNote', 'delete', 'keep', 'checkbox', 'status'];

    static values = {
        removeText: String,
        previewText: String,
        addedText: String,
        removedText: String,
        deletedText: String,
        keptText: String,
        replacedText: String,
        kbText: String,
        mbText: String,
        locale: String,
    };

    connect() {
        this.input = this.element.querySelector('input[type="file"]');
        this.urls = [];
        if (!this.input) {
            return;
        }
        this.onChange = () => {
            // One file per field: a file chosen replaces the file already sent (VichUploaderBundle sends it even when
            // the deletion is asked), so it cancels the deletion asked
            if (this.hasReplaceNoteTarget && this.input.files.length > 0) {
                this.keep(null, false);
            }
            this.render(true);
        };
        this.onEnter = () => this.element.classList.add('crudit-ui-file--dragging');
        this.onLeave = () => this.element.classList.remove('crudit-ui-file--dragging');
        this.input.addEventListener('change', this.onChange);
        this.input.addEventListener('dragenter', this.onEnter);
        this.input.addEventListener('dragleave', this.onLeave);
        this.input.addEventListener('drop', this.onLeave);
        this.form = this.input.form;
        this.onReset = () => setTimeout(() => {
            this.render(false);
            this.keep(null, false);
        });
        this.form?.addEventListener('reset', this.onReset);
        // A file kept by the browser (back to the page) is shown
        if (this.input.files.length > 0) {
            this.render(false);
        }
    }

    disconnect() {
        this.input?.removeEventListener('change', this.onChange);
        this.input?.removeEventListener('dragenter', this.onEnter);
        this.input?.removeEventListener('dragleave', this.onLeave);
        this.input?.removeEventListener('drop', this.onLeave);
        this.form?.removeEventListener('reset', this.onReset);
        this.revoke();
    }

    // The files of the input, listed after the file already sent
    render(announce) {
        this.revoke();
        for (const line of this.listTarget.querySelectorAll('[data-chosen]')) {
            line.remove();
        }
        const files = [...this.input.files];
        files.forEach((file, index) => {
            const line = this.chosenTarget.content.firstElementChild.cloneNode(true);
            line.dataset.chosen = String(index);
            line.querySelector('[data-name]').textContent = file.name;
            line.querySelector('[data-size]').textContent = this.size(file.size);
            line.querySelector('[data-remove]').textContent = this.removeTextValue.replace('%name%', file.name);
            if (file.type.startsWith('image/')) {
                const url = URL.createObjectURL(file);
                this.urls.push(url);
                const image = document.createElement('img');
                image.className = 'crudit-ui-file__preview';
                image.src = url;
                image.alt = this.previewTextValue.replace('%name%', file.name);
                line.querySelector('.crudit-ui-file__file-icon')?.replaceWith(image);
            }
            this.listTarget.append(line);
        });
        this.listTarget.hidden = files.length === 0 && !this.hasCurrentTarget;
        const replaced = this.markReplaced();
        if (announce && files.length > 0) {
            const messages = files.map((file) => this.addedTextValue.replace('%name%', file.name));
            if (replaced) {
                messages.push(this.replacedTextValue.replace('%name%', this.currentName()));
            }
            this.announce(messages.join(' '));
        }
    }

    // The file already sent, replaced by the file chosen when saved (not when its deletion is asked): true if it is
    markReplaced() {
        if (!this.hasReplaceNoteTarget) {
            return false;
        }
        const replaced = this.input.files.length > 0 && !(this.hasCheckboxTarget && this.checkboxTarget.checked);
        this.replaceNoteTarget.hidden = !replaced;
        this.currentTarget.classList.toggle('crudit-ui-file__file--replaced', replaced);

        return replaced;
    }

    // Removes a file chosen: the input is emptied, or keeps the other files (several files)
    remove(event) {
        const line = event.currentTarget.closest('[data-chosen]');
        if (!line) {
            return;
        }
        const index = Number(line.dataset.chosen);
        const files = [...this.input.files];
        const name = files[index]?.name ?? '';
        const transfer = new DataTransfer();
        files.forEach((file, position) => {
            if (position !== index) {
                transfer.items.add(file);
            }
        });
        this.input.files = transfer.files;
        this.render(false);
        this.announce(this.removedTextValue.replace('%name%', name));
        this.input.focus();
    }

    // Asks the deletion of the file already sent; one file per field: the file chosen is removed (it would replace it)
    delete() {
        if (!this.hasCheckboxTarget) {
            return;
        }
        const messages = [];
        if (this.hasReplaceNoteTarget && this.input.files.length > 0) {
            messages.push(...[...this.input.files].map((file) => this.removedTextValue.replace('%name%', file.name)));
            this.input.files = new DataTransfer().files;
            this.render(false);
        }
        this.checkboxTarget.checked = true;
        this.currentTarget.classList.add('crudit-ui-file__file--deleted');
        this.noteTarget.hidden = false;
        this.markReplaced();
        this.deleteTarget.hidden = true;
        this.keepTarget.hidden = false;
        this.keepTarget.focus();
        messages.push(this.deletedTextValue.replace('%name%', this.currentName()));
        this.announce(messages.join(' '));
    }

    // Keeps the file already sent
    keep(event, announce = true) {
        if (!this.hasCheckboxTarget) {
            return;
        }
        const asked = this.checkboxTarget.checked;
        this.checkboxTarget.checked = false;
        this.currentTarget.classList.remove('crudit-ui-file__file--deleted');
        this.noteTarget.hidden = true;
        this.keepTarget.hidden = true;
        this.deleteTarget.hidden = false;
        this.markReplaced();
        if (announce && asked) {
            this.deleteTarget.focus();
            this.announce(this.keptTextValue.replace('%name%', this.currentName()));
        }
    }

    currentName() {
        return this.currentTarget.querySelector('.crudit-ui-file__label')?.textContent.trim() ?? '';
    }

    size(bytes) {
        const format = (value, digits) => this.numberFormat(digits).format(value);

        return bytes >= 1048576
            ? this.mbTextValue.replace('%size%', format(bytes / 1048576, 1))
            : this.kbTextValue.replace('%size%', format(Math.max(1, Math.round(bytes / 1024)), 0));
    }

    // Intl only knows BCP 47 tags: a candidate it refuses is skipped; Latin digits whatever the language
    numberFormat(digits) {
        for (const locale of [this.localeValue, document.documentElement.lang].filter(Boolean)) {
            try {
                return new Intl.NumberFormat(locale, { maximumFractionDigits: digits, numberingSystem: 'latn' });
            } catch {
                // Next candidate
            }
        }

        return new Intl.NumberFormat(undefined, { maximumFractionDigits: digits, numberingSystem: 'latn' });
    }

    revoke() {
        for (const url of this.urls ?? []) {
            URL.revokeObjectURL(url);
        }
        this.urls = [];
    }

    announce(message) {
        this.statusTarget.textContent = '';
        requestAnimationFrame(() => {
            this.statusTarget.textContent = message;
        });
    }
}
