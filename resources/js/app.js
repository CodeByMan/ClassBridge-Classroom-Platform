import './content-filter';

const body = document.body;
const drawerButton = document.querySelector('[data-drawer-toggle]');
const drawerBackdrop = document.querySelector('[data-drawer-backdrop]');
const drawer = document.getElementById('application-drawer');
let activeDialog = null;
let dialogReturnFocus = null;

const isMobileDrawer = () => window.matchMedia('(max-width: 840px)').matches;

const setDrawerState = (open) => {
    if (!drawer) return;

    if (isMobileDrawer()) {
        body.classList.toggle('gc-drawer-open', open);
        body.classList.remove('gc-drawer-collapsed');
    } else {
        body.classList.remove('gc-drawer-open');
        body.classList.toggle('gc-drawer-collapsed', !open);
    }

    drawerButton?.setAttribute('aria-expanded', String(open));
};

const closeDropdowns = (except = null) => {
    document.querySelectorAll('[data-dropdown]').forEach((dropdown) => {
        if (dropdown === except) return;

        const menu = dropdown.querySelector('[data-dropdown-menu]');
        const trigger = dropdown.querySelector('[data-dropdown-trigger]');
        if (menu) menu.hidden = true;
        dropdown.classList.remove('is-open');
        trigger?.setAttribute('aria-expanded', 'false');
    });
};

const toggleDropdown = (dropdown) => {
    const menu = dropdown?.querySelector('[data-dropdown-menu]');
    const trigger = dropdown?.querySelector('[data-dropdown-trigger]');
    if (!menu) return;

    const willOpen = menu.hidden;
    closeDropdowns(dropdown);
    menu.hidden = !willOpen;
    dropdown?.classList.toggle('is-open', willOpen);
    trigger?.setAttribute('aria-expanded', String(willOpen));

    if (willOpen) {
        menu.querySelector('a, button, input, select, textarea')?.focus();
    }
};

const focusableElements = (container) => Array.from(container.querySelectorAll(
    'a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])',
)).filter((element) => !element.hidden && element.offsetParent !== null);

const openDialog = (dialog, trigger = document.activeElement) => {
    if (!dialog) return;

    if (activeDialog && activeDialog !== dialog) {
        activeDialog.hidden = true;
    }

    dialogReturnFocus = trigger instanceof HTMLElement ? trigger : null;
    activeDialog = dialog;
    dialog.hidden = false;
    body.classList.add('gc-dialog-open');
    closeDropdowns();
    if (isMobileDrawer()) setDrawerState(false);

    window.requestAnimationFrame(() => {
        dialog.querySelector('[autofocus], input, textarea, select, button:not([data-dialog-close])')?.focus();
    });
};

const closeDialog = (dialog = activeDialog) => {
    if (!dialog) return;

    dialog.hidden = true;
    body.classList.remove('gc-dialog-open');
    activeDialog = null;
    dialogReturnFocus?.focus();
    dialogReturnFocus = null;
};

const toggleInvite = (visible) => {
    const toast = document.getElementById('toast-bottom-right');
    if (!toast) return;

    toast.style.visibility = visible ? 'visible' : 'hidden';
    toast.style.opacity = visible ? '1' : '0';
};

const toggleCollapse = (trigger) => {
    const targetId = trigger.dataset.collapseTrigger;
    const panel = targetId ? document.getElementById(targetId) : null;
    if (!panel) return;

    const willOpen = panel.hidden;
    panel.hidden = !willOpen;
    trigger.setAttribute('aria-expanded', String(willOpen));
    trigger.querySelector('[data-collapse-icon]')?.classList.toggle('fa-chevron-up', willOpen);
    trigger.querySelector('[data-collapse-icon]')?.classList.toggle('fa-chevron-down', !willOpen);

    if (willOpen) {
        panel.querySelector('input, textarea, select, button')?.focus();
    }
};


const togglePasswordVisibility = (button) => {
    const inputId = button.dataset.passwordToggle;
    const input = inputId ? document.getElementById(inputId) : null;

    if (!(input instanceof HTMLInputElement)) return;

    const willShow = input.type === 'password';
    input.type = willShow ? 'text' : 'password';

    const label = willShow ? 'Hide password' : 'Show password';
    button.setAttribute('aria-label', label);
    button.setAttribute('aria-pressed', String(willShow));
    button.dataset.passwordVisible = String(willShow);
    button.classList.toggle('is-visible', willShow);

    const screenReaderLabel = button.querySelector('[data-password-toggle-label]');
    if (screenReaderLabel) screenReaderLabel.textContent = label;

    input.focus({ preventScroll: true });
    const inputLength = input.value.length;
    input.setSelectionRange?.(inputLength, inputLength);
};

const dispatchLivewireModal = (trigger) => {
    const component = trigger.dataset.livewireModal;
    const argumentName = trigger.dataset.argumentName;
    const argumentId = Number(trigger.dataset.argumentId);

    if (!component || !argumentName || !Number.isInteger(argumentId)) return;

    if (window.Livewire?.dispatch) {
        window.Livewire.dispatch('openModal', {
            component,
            arguments: { [argumentName]: argumentId },
        });
        return;
    }

    if (window.Livewire?.emit) {
        window.Livewire.emit('openModal', component, { [argumentName]: argumentId });
        return;
    }

    window.alert('This action is temporarily unavailable. Refresh the page and try again.');
};

drawerButton?.addEventListener('click', () => {
    const open = isMobileDrawer()
        ? !body.classList.contains('gc-drawer-open')
        : body.classList.contains('gc-drawer-collapsed');
    setDrawerState(open);
});

drawerBackdrop?.addEventListener('click', () => setDrawerState(false));

drawer?.addEventListener('click', (event) => {
    if (isMobileDrawer() && event.target.closest('a')) {
        setDrawerState(false);
    }
});

window.addEventListener('resize', () => {
    if (!isMobileDrawer()) body.classList.remove('gc-drawer-open');
});

document.addEventListener('click', (event) => {
    const passwordToggle = event.target.closest('[data-password-toggle]');
    if (passwordToggle) {
        event.preventDefault();
        togglePasswordVisibility(passwordToggle);
        return;
    }

    const dropdownTrigger = event.target.closest('[data-dropdown-trigger]');
    if (dropdownTrigger) {
        event.preventDefault();
        event.stopPropagation();
        toggleDropdown(dropdownTrigger.closest('[data-dropdown]'));
        return;
    }

    const dropdownMenu = event.target.closest('[data-dropdown-menu]');
    if (dropdownMenu && event.target.closest('a, button')) {
        closeDropdowns();
    } else if (!event.target.closest('[data-dropdown]')) {
        closeDropdowns();
    }

    const dialogOpen = event.target.closest('[data-dialog-open]');
    if (dialogOpen) {
        event.preventDefault();
        openDialog(document.getElementById(dialogOpen.dataset.dialogOpen), dialogOpen);
        return;
    }

    const dialogClose = event.target.closest('[data-dialog-close]');
    if (dialogClose) {
        event.preventDefault();
        closeDialog(dialogClose.closest('[role="dialog"]'));
        return;
    }

    const collapseTrigger = event.target.closest('[data-collapse-trigger]');
    if (collapseTrigger) {
        event.preventDefault();
        toggleCollapse(collapseTrigger);
        return;
    }

    const showInvite = event.target.closest('[data-show-invite]');
    if (showInvite) {
        toggleInvite(true);
        return;
    }

    const hideInvite = event.target.closest('[data-hide-invite]');
    if (hideInvite) {
        toggleInvite(false);
        return;
    }

    const dismissTarget = event.target.closest('[data-dismiss-target]');
    if (dismissTarget) {
        document.querySelector(dismissTarget.dataset.dismissTarget)?.remove();
        return;
    }

    const profileBioToggle = event.target.closest('[data-profile-bio-toggle]');
    if (profileBioToggle) {
        const target = document.getElementById(profileBioToggle.dataset.profileBioToggle);
        if (target) {
            target.hidden = !target.hidden;
            profileBioToggle.setAttribute('aria-expanded', String(!target.hidden));
            if (!target.hidden) target.querySelector('textarea')?.focus();
        }
        return;
    }

    const modalTrigger = event.target.closest('[data-livewire-modal]');
    if (modalTrigger) {
        dispatchLivewireModal(modalTrigger);
    }
});

document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') {
        if (activeDialog) {
            closeDialog();
            return;
        }

        if (body.classList.contains('gc-drawer-open')) {
            setDrawerState(false);
            drawerButton?.focus();
        }

        closeDropdowns();
    }

    if (event.key === 'Tab' && activeDialog) {
        const focusable = focusableElements(activeDialog);
        if (!focusable.length) return;

        const first = focusable[0];
        const last = focusable[focusable.length - 1];

        if (event.shiftKey && document.activeElement === first) {
            event.preventDefault();
            last.focus();
        } else if (!event.shiftKey && document.activeElement === last) {
            event.preventDefault();
            first.focus();
        }
    }
});

document.addEventListener('change', (event) => {
    const deadlineToggle = event.target.closest('[data-deadline-toggle]');
    if (deadlineToggle) {
        const deadlineInput = document.getElementById(deadlineToggle.dataset.target);

        if (deadlineInput) {
            deadlineInput.hidden = !deadlineToggle.checked;
            deadlineInput.required = deadlineToggle.checked;
            if (!deadlineToggle.checked) deadlineInput.value = '';
        }
    }

    const imageInput = event.target.closest('[data-image-preview-input]');
    if (imageInput?.files?.length) {
        const preview = document.getElementById(imageInput.dataset.imagePreviewInput);
        const filename = document.querySelector(`[data-file-name-for="${imageInput.id}"]`);
        const file = imageInput.files[0];

        if (filename) filename.textContent = file.name;
        if (preview && file.type.startsWith('image/')) {
            const reader = new FileReader();
            reader.addEventListener('load', () => {
                preview.src = String(reader.result);
            });
            reader.readAsDataURL(file);
        }
    }
});

document.addEventListener('submit', (event) => {
    const confirmationForm = event.target.closest('[data-confirm-form]');
    if (confirmationForm && !window.confirm(confirmationForm.dataset.confirmForm)) {
        event.preventDefault();
        return;
    }

    const form = event.target.closest('[data-working-form]');
    if (!form) return;

    const submitButton = event.submitter || form.querySelector('[type="submit"]');
    if (!submitButton) return;

    submitButton.disabled = true;
    submitButton.setAttribute('aria-busy', 'true');
    submitButton.dataset.originalLabel = submitButton.textContent.trim();
    submitButton.textContent = submitButton.dataset.submitLabel || 'Working…';
});

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-open-on-load]').forEach((dialog) => openDialog(dialog));
    document.querySelectorAll('[data-collapse-open-on-load]').forEach((panel) => {
        panel.hidden = false;
        const trigger = document.querySelector(`[data-collapse-trigger="${panel.id}"]`);
        trigger?.setAttribute('aria-expanded', 'true');
    });
});
