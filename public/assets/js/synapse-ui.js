/* ============================================================================
   SYNAPSE UI Library — JavaScript
   ----------------------------------------------------------------------------
   Provides global helpers: synapse.toast, synapse.dialog, synapse.spinner,
   synapse.skeleton, plus auto-wiring for declarative data-* attributes.
   ============================================================================ */
(function (global) {
    'use strict';

    // ==========================================================================
    // TOAST
    // ==========================================================================
    function ensureToastStack() {
        let stack = document.getElementById('syn-toast-stack');
        if (!stack) {
            stack = document.createElement('div');
            stack.id = 'syn-toast-stack';
            stack.className = 'syn-toast-stack';
            stack.setAttribute('role', 'region');
            stack.setAttribute('aria-label', 'Notifications');
            document.body.appendChild(stack);
        }
        return stack;
    }

    /**
     * Show a toast notification.
     *
     * synapse.toast('Saved successfully', 'success');
     * synapse.toast({
     *     type: 'error',
     *     title: 'Could not save',
     *     message: 'Network connection lost',
     *     duration: 0     // 0 = sticky (no auto-dismiss)
     * });
     */
    function toast(message, type, options) {
        let opts = options || {};
        if (typeof message === 'object' && message !== null) {
            opts = message;
            message = null;
        }

        const config = {
            type: opts.type || (typeof message === 'string' ? type : 'info') || 'info',
            title: opts.title || null,
            message: opts.message || (typeof message === 'string' ? message : ''),
            duration: opts.duration != null ? opts.duration : 4000
        };

        const stack = ensureToastStack();
        const el = document.createElement('div');
        el.className = 'syn-toast syn-toast--' + config.type;
        el.setAttribute('role', config.type === 'error' ? 'alert' : 'status');

        const iconMap = {
            success: 'fa-check',
            error:   'fa-xmark',
            warning: 'fa-exclamation',
            info:    'fa-info'
        };

        const icon = iconMap[config.type] || iconMap.info;

        let html = '<div class="syn-toast-icon"><i class="fas ' + icon + '"></i></div>';
        html += '<div class="syn-toast-content">';
        if (config.title) {
            html += '<div class="syn-toast-title">' + escapeHtml(config.title) + '</div>';
        }
        html += '<div class="syn-toast-message">' + escapeHtml(config.message) + '</div>';
        html += '</div>';
        html += '<button class="syn-toast-close" aria-label="Dismiss"><i class="fas fa-xmark"></i></button>';

        if (config.duration > 0) {
            html += '<div class="syn-toast-progress"></div>';
        }

        el.innerHTML = html;
        stack.appendChild(el);

        // Trigger entrance animation
        requestAnimationFrame(() => {
            requestAnimationFrame(() => el.classList.add('is-visible'));
        });

        const close = () => {
            el.classList.remove('is-visible');
            setTimeout(() => el.remove(), 300);
        };

        el.querySelector('.syn-toast-close').addEventListener('click', close);

        if (config.duration > 0) {
            setTimeout(close, config.duration);
        }

        return { close: close };
    }

    // ==========================================================================
    // DIALOG / MODAL
    // ==========================================================================
    const openDialogs = new Set();

    /**
     * Open a dialog by element id, or by HTML content.
     *
     * synapse.dialog.open('myDialogId');
     * synapse.dialog.open({
     *     title: 'Confirm delete',
     *     body: 'Are you sure? This cannot be undone.',
     *     danger: true,
     *     confirmText: 'Delete',
     *     cancelText: 'Cancel',
     *     onConfirm: () => { ... },
     *     onCancel: () => { ... }
     * });
     */
    function openDialog(target) {
        if (typeof target === 'string') {
            // target is a DOM id
            const el = document.getElementById(target);
            if (!el) {
                console.warn('[synapse.dialog] No element found with id:', target);
                return null;
            }
            return showDialogElement(el, { mode: 'inline' });
        }

        // target is an existing DOM element (e.g. a dialog built by
        // formToDialog()). Pass it through directly with 'inline' mode so
        // we don't try to build a new dialog from its (non-existent)
        // properties.
        if (target instanceof HTMLElement) {
            return showDialogElement(target, { mode: 'inline' });
        }

        // target is an options object
        return showDialogElement(buildDialogFromOptions(target), { mode: 'generated' });
    }

    function closeDialog(dialogEl) {
        if (!dialogEl) return;
        const backdrop = dialogEl.parentElement;
        if (!backdrop || !backdrop.classList.contains('syn-dialog-backdrop')) return;

        backdrop.classList.remove('is-open');
        openDialogs.delete(dialogEl);

        setTimeout(() => {
            backdrop.remove();
            if (openDialogs.size === 0) {
                document.body.style.overflow = '';
            }
        }, 200);
    }

    function showDialogElement(dialogEl, meta) {
        // If already in DOM, just re-open
        if (dialogEl.parentElement && dialogEl.parentElement.classList.contains('syn-dialog-backdrop')) {
            const existingBackdrop = dialogEl.parentElement;
            existingBackdrop.classList.add('is-open');
            return dialogEl;
        }

        const backdrop = document.createElement('div');
        backdrop.className = 'syn-dialog-backdrop';
        backdrop.setAttribute('role', 'dialog');
        backdrop.setAttribute('aria-modal', 'true');

        // Wire the dialog's title (h1/h2/.syn-dialog-title) to the dialog
        // landmark via aria-labelledby so screen readers announce it.
        const titleEl = dialogEl.querySelector('.syn-dialog-title, h1, h2');
        if (titleEl && !titleEl.id) {
            titleEl.id = 'syn-dialog-title-' + Math.random().toString(36).slice(2, 9);
        }
        if (titleEl && titleEl.id) {
            backdrop.setAttribute('aria-labelledby', titleEl.id);
        }

        if (meta.mode === 'inline') {
            dialogEl.removeAttribute('hidden');
        }
        backdrop.appendChild(dialogEl);
        document.body.appendChild(backdrop);
        document.body.style.overflow = 'hidden';
        openDialogs.add(dialogEl);

        requestAnimationFrame(() => {
            requestAnimationFrame(() => {
                backdrop.classList.add('is-open');
                // Move keyboard focus into the dialog on open so screen-reader
                // users land on the first interactive control. Skip if focus
                // is already inside the dialog (e.g. an input that was just
                // clicked to open it).
                const alreadyInside = document.activeElement && dialogEl.contains(document.activeElement);
                if (!alreadyInside) {
                    const focusable = dialogEl.querySelector(
                        'input:not([type=hidden]):not([disabled]),' +
                        'select:not([disabled]),' +
                        'textarea:not([disabled]),' +
                        'button:not([disabled]),' +
                        'a[href]'
                    );
                    if (focusable) {
                        // Use setTimeout to defer until after the open transition
                        setTimeout(() => { try { focusable.focus(); } catch (_) {} }, 60);
                    }
                }
            });
        });

        // Wire up close buttons
        backdrop.querySelectorAll('[data-synapse-dialog-close]').forEach(btn => {
            btn.addEventListener('click', () => closeDialog(dialogEl));
        });

        // Wire up confirm button (only for generated dialogs)
        if (meta.mode === 'generated') {
            const confirmBtn = backdrop.querySelector('[data-synapse-dialog-confirm]');
            const cancelBtn = backdrop.querySelector('[data-synapse-dialog-cancel]');
            if (confirmBtn) {
                confirmBtn.addEventListener('click', () => {
                    if (typeof dialogEl._onConfirm === 'function') {
                        const result = dialogEl._onConfirm();
                        if (result === false) return; // prevent close
                    }
                    closeDialog(dialogEl);
                });
            }
            if (cancelBtn) {
                cancelBtn.addEventListener('click', () => {
                    if (typeof dialogEl._onCancel === 'function') {
                        dialogEl._onCancel();
                    }
                    closeDialog(dialogEl);
                });
            }
        }

        // Click on backdrop (not dialog) closes
        backdrop.addEventListener('click', (e) => {
            if (e.target === backdrop) {
                closeDialog(dialogEl);
            }
        });

        // Escape key closes
        const escHandler = (e) => {
            if (e.key === 'Escape' && openDialogs.has(dialogEl)) {
                closeDialog(dialogEl);
                document.removeEventListener('keydown', escHandler);
            }
        };
        document.addEventListener('keydown', escHandler);

        return dialogEl;
    }

    function buildDialogFromOptions(opts) {
        const dialog = document.createElement('div');
        dialog.className = 'syn-dialog' + (opts.wide ? ' syn-dialog--wide' : '') + (opts.danger ? ' syn-dialog--danger' : '');

        let html = '';
        if (opts.title || opts.subtitle) {
            html += '<div class="syn-dialog-header">';
            if (opts.title) {
                html += '<h2 class="syn-dialog-title">' + escapeHtml(opts.title) + '</h2>';
            }
            if (opts.subtitle) {
                html += '<p class="syn-dialog-desc">' + escapeHtml(opts.subtitle) + '</p>';
            }
            html += '</div>';
        }

        if (opts.body) {
            html += '<div class="syn-dialog-body">' + (typeof opts.body === 'string' ? opts.body : '') + '</div>';
        }

        if (opts.alert) {
            html += '<div class="syn-dialog-alert">';
            html += '<i class="fas fa-triangle-exclamation"></i>';
            html += '<div>';
            if (opts.alertTitle) {
                html += '<strong>' + escapeHtml(opts.alertTitle) + '</strong>';
            }
            html += escapeHtml(opts.alert);
            html += '</div></div>';
        }

        if (opts.input) {
            html += '<div style="padding: 1.25rem 1.75rem 0;">';
            if (opts.inputLabel) {
                html += '<label class="syn-dialog-label">' + escapeHtml(opts.inputLabel) + '</label>';
            }
            html += '<div class="syn-dialog-input-row">';
            html += '<input type="text" value="' + escapeHtml(opts.input || '') + '" readonly>';
            html += '<button data-copy-input aria-label="Copy"><i class="fas fa-copy"></i></button>';
            html += '</div></div>';
        }

        html += '<div class="syn-dialog-actions">';
        html += '<button type="button" class="syn-btn syn-btn--secondary" data-synapse-dialog-cancel>' + escapeHtml(opts.cancelText || 'Cancel') + '</button>';
        const confirmClass = opts.danger ? 'syn-btn--danger' : 'syn-btn--primary';
        html += '<button type="button" class="syn-btn ' + confirmClass + '" data-synapse-dialog-confirm>' + escapeHtml(opts.confirmText || 'Confirm') + '</button>';
        html += '</div>';

        dialog.innerHTML = html;
        dialog._onConfirm = opts.onConfirm;
        dialog._onCancel = opts.onCancel;

        // Wire up copy button if present
        const copyBtn = dialog.querySelector('[data-copy-input]');
        if (copyBtn) {
            copyBtn.addEventListener('click', () => {
                const input = dialog.querySelector('.syn-dialog-input-row input');
                if (input) {
                    input.select();
                    navigator.clipboard.writeText(input.value).then(() => {
                        const orig = copyBtn.innerHTML;
                        copyBtn.innerHTML = '<i class="fas fa-check"></i>';
                        setTimeout(() => copyBtn.innerHTML = orig, 1500);
                    });
                }
            });
        }

        return dialog;
    }

    // Convenience: confirm() replacement
    /**
     * synapse.dialog.confirm({
     *     title: 'Delete user?',
     *     body: 'This will permanently remove the account.',
     *     danger: true,
     *     confirmText: 'Delete',
     *     onConfirm: () => doDelete()
     * });
     */
    function confirm(opts) {
        return openDialog(Object.assign({ danger: true, confirmText: 'Confirm' }, opts));
    }

    // Convenience: alert() replacement
    function alert(opts) {
        const o = typeof opts === 'string' ? { body: opts } : opts;
        return openDialog(Object.assign({
            title: o.title || 'Notice',
            confirmText: 'OK',
            cancelText: 'Close',
            onConfirm: o.onConfirm
        }, o));
    }

    // ==========================================================================
    // SPINNER HELPERS
    // ==========================================================================
    /**
     * Show an inline spinner in a button (replaces text, disables button).
     * Returns a stop() function to restore the original state.
     */
    function buttonLoading(button) {
        if (!button) return () => {};
        const originalHtml = button.innerHTML;
        const originalDisabled = button.disabled;
        button.classList.add('syn-btn-loading');
        if (!button.classList.contains('syn-btn')) {
            button.classList.add('syn-btn-loading--dark');
        }
        button.disabled = true;
        return function stop() {
            button.classList.remove('syn-btn-loading', 'syn-btn-loading--dark');
            button.innerHTML = originalHtml;
            button.disabled = originalDisabled;
        };
    }

    /**
     * Show a full-page loader overlay. Returns a hide() function.
     */
    function pageLoader(message) {
        const overlay = document.createElement('div');
        overlay.className = 'syn-page-loader';
        const html = '<div style="text-align: center;">';
        html += '<div class="syn-spinner syn-spinner--lg" style="margin: 0 auto 0.85rem;"></div>';
        if (message) {
            html += '<div style="font-size: 0.875rem; color: var(--gray-700); font-weight: 500;">' + escapeHtml(message) + '</div>';
        }
        html += '</div>';
        overlay.innerHTML = html;
        document.body.appendChild(overlay);
        return function hide() {
            overlay.style.opacity = '0';
            setTimeout(() => overlay.remove(), 200);
        };
    }

    // ==========================================================================
    // CUSTOM DROPDOWN (button + popover)  --  .syn-dropdown
    // ==========================================================================
    /**
     * Build a custom dropdown from a plain <select> element.
     * Replaces the native control with a styled button + popover.
     * Original <select> stays in the DOM (hidden) for form submission.
     *
     * <select data-synapse-dropdown name="role">
     *   <option value="">Select role…</option>
     *   <option value="1">Admin</option>
     *   <option value="2">Staff</option>
     * </select>
     */
    function buildDropdown(selectEl) {
        if (selectEl._synDropdown) return selectEl._synDropdown;

        const isMulti = selectEl.multiple || selectEl.hasAttribute('multiple');
        const isSearchable = selectEl.hasAttribute('data-synapse-searchable')
            || selectEl.options.length > 8;

        // Wrap the <select> in a .syn-dropdown container
        const wrap = document.createElement('div');
        wrap.className = 'syn-dropdown';
        if (selectEl.disabled) wrap.classList.add('is-disabled');
        selectEl.parentNode.insertBefore(wrap, selectEl);
        wrap.appendChild(selectEl);
        selectEl.style.display = 'none';
        selectEl.setAttribute('data-synapse-dropdown-original', '1');

        // Build trigger button
        const trigger = document.createElement('button');
        trigger.type = 'button';
        trigger.className = 'syn-dropdown-trigger';
        trigger.setAttribute('aria-haspopup', 'listbox');
        trigger.setAttribute('aria-expanded', 'false');

        const label = document.createElement('span');
        label.className = 'syn-dropdown-label';

        const icon = document.createElement('i');
        icon.className = 'fas fa-chevron-down syn-dropdown-icon';

        trigger.appendChild(label);
        trigger.appendChild(icon);
        wrap.appendChild(trigger);

        // Build menu
        const menu = document.createElement('div');
        menu.className = 'syn-dropdown-menu';
        menu.setAttribute('role', 'listbox');
        if (isMulti) menu.setAttribute('aria-multiselectable', 'true');

        // Optional search input
        let searchInput = null;
        if (isSearchable) {
            searchInput = document.createElement('input');
            searchInput.type = 'text';
            searchInput.className = 'syn-dropdown-search';
            searchInput.placeholder = 'Search…';
            searchInput.setAttribute('autocomplete', 'off');
            menu.appendChild(searchInput);
        }

        const optionsList = document.createElement('ul');
        optionsList.className = 'syn-dropdown-options';
        menu.appendChild(optionsList);
        wrap.appendChild(menu);
        // Track the menu on the wrap so openMenu/closeMenu can find it
        // even after it's been portaled to <body>.
        wrap._synMenu = menu;

        const state = {
            isOpen: false,
            highlighted: -1,
            selectedValues: Array.from(selectEl.selectedOptions).map(o => o.value)
        };

        function getOptions() {
            return Array.from(selectEl.options).filter(o => o.value !== '' || !o.disabled);
        }

        function renderLabel() {
            const selected = Array.from(selectEl.selectedOptions);

            if (isMulti) {
                if (selected.length === 0) {
                    label.textContent = selectEl.getAttribute('data-placeholder') || 'Select…';
                    label.classList.add('is-placeholder');
                    wrap.querySelector('.syn-dropdown-chips')?.remove();
                    return;
                }
                label.classList.remove('is-placeholder');
                label.innerHTML = '';
                const chips = document.createElement('div');
                chips.className = 'syn-dropdown-chips';
                selected.forEach(opt => {
                    const chip = document.createElement('span');
                    chip.className = 'syn-dropdown-chip';
                    chip.textContent = opt.textContent;
                    if (!selectEl.hasAttribute('data-no-chip-remove')) {
                        const x = document.createElement('button');
                        x.type = 'button';
                        x.className = 'syn-dropdown-chip-remove';
                        x.innerHTML = '<i class="fas fa-xmark"></i>';
                        x.setAttribute('aria-label', 'Remove ' + opt.textContent);
                        x.addEventListener('click', (e) => {
                            e.stopPropagation();
                            opt.selected = false;
                            selectEl.dispatchEvent(new Event('change', { bubbles: true }));
                            renderAll();
                        });
                        chip.appendChild(x);
                    }
                    chips.appendChild(chip);
                });
                label.appendChild(chips);
            } else {
                if (selected.length === 0) {
                    label.textContent = selectEl.getAttribute('data-placeholder') || 'Select…';
                    label.classList.add('is-placeholder');
                } else {
                    label.textContent = selected[0].textContent;
                    label.classList.remove('is-placeholder');
                }
            }
        }

        function renderOptions(filter) {
            optionsList.innerHTML = '';
            const filterFn = (o) => {
                if (!filter) return true;
                return o.textContent.toLowerCase().includes(filter.toLowerCase());
            };

            // Walk through both <option> and <optgroup><option>...</optgroup>
            const groups = [];
            Array.from(selectEl.children).forEach(child => {
                if (child.tagName === 'OPTGROUP') {
                    const groupOptions = Array.from(child.children).filter(o => o.tagName === 'OPTION' && filterFn(o));
                    if (groupOptions.length > 0) {
                        groups.push({ label: child.label || '', options: groupOptions });
                    }
                } else if (child.tagName === 'OPTION' && filterFn(child)) {
                    groups.push({ label: null, options: [child] });
                }
            });

            const allOpts = groups.flatMap(g => g.options);
            if (allOpts.length === 0) {
                const empty = document.createElement('li');
                empty.className = 'syn-dropdown-empty';
                empty.textContent = 'No matches';
                optionsList.appendChild(empty);
                return;
            }

            let globalIdx = 0;
            groups.forEach(group => {
                if (group.label) {
                    const header = document.createElement('li');
                    header.className = 'syn-dropdown-header';
                    header.textContent = group.label;
                    optionsList.appendChild(header);
                }
                group.options.forEach((opt) => {
                    const li = document.createElement('li');
                    li.className = 'syn-dropdown-option';
                    li.setAttribute('role', 'option');
                    li.dataset.value = opt.value;
                    li.dataset.index = globalIdx;

                    if (opt.selected) li.classList.add('is-selected');
                    if (opt.disabled) li.classList.add('is-disabled');

                    if (opt.dataset.icon) {
                        const i = document.createElement('i');
                        i.className = opt.dataset.icon + ' syn-dropdown-option-icon';
                        li.appendChild(i);
                    }

                    const text = document.createElement('span');
                    text.textContent = opt.textContent;
                    li.appendChild(text);

                    const check = document.createElement('i');
                    check.className = 'fas fa-check syn-dropdown-check';
                    li.appendChild(check);

                    li.addEventListener('click', (e) => {
                        e.preventDefault();
                        if (opt.disabled) return;
                        if (isMulti) {
                            opt.selected = !opt.selected;
                        } else {
                            Array.from(selectEl.options).forEach(o => o.selected = false);
                            opt.selected = true;
                            closeMenu();
                        }
                        selectEl.dispatchEvent(new Event('change', { bubbles: true }));
                        renderAll();
                    });

                    li.addEventListener('mouseenter', () => {
                        state.highlighted = globalIdx;
                        updateHighlight();
                    });

                    optionsList.appendChild(li);
                    globalIdx++;
                });
                if (group.label) {
                    const divider = document.createElement('li');
                    divider.className = 'syn-dropdown-divider';
                    optionsList.appendChild(divider);
                }
            });
        }

        function updateHighlight() {
            const items = optionsList.querySelectorAll('.syn-dropdown-option');
            items.forEach((el, i) => {
                el.classList.toggle('is-highlighted', i === state.highlighted);
            });
        }

        function openMenu() {
            if (wrap.classList.contains('is-open')) return;
            wrap.classList.add('is-open');
            trigger.setAttribute('aria-expanded', 'true');
            state.isOpen = true;
            renderOptions(searchInput ? searchInput.value : '');
            state.highlighted = Array.from(selectEl.selectedOptions)[0]
                ? Array.from(selectEl.options).indexOf(Array.from(selectEl.selectedOptions)[0])
                : 0;
            updateHighlight();
            if (searchInput) setTimeout(() => searchInput.focus(), 50);

            // Portal the menu to <body> so it can never be clipped by an
            // intermediate container with overflow:hidden/auto (table cells,
            // cards, sidebars, etc.). We position it absolutely using the
            // trigger's viewport rect, with smart flip+shift logic so it
            // stays inside the viewport on all sides.
            requestAnimationFrame(() => {
                portalMenuToBody();
            });
        }

        /**
         * Move the menu out of the wrap (and out of any clipping container)
         * and attach it to document.body with absolute coordinates. Compute
         * the best position so it never overlaps the trigger, never escapes
         * the viewport, and always sits inside visible space.
         */
        function portalMenuToBody() {
            const localMenu = wrap._synMenu;
            if (!localMenu || localMenu._isPortaled) return;

            // Detach the menu from the wrap and attach to body so it's
            // outside any scrollable / overflow:hidden ancestor.
            document.body.appendChild(localMenu);
            localMenu._isPortaled = true;
            localMenu.classList.add('is-portaled');

            positionPortaledMenu();

            // Keep it positioned correctly if the page scrolls or resizes
            // while the menu is open.
            localMenu._repositionHandler = () => positionPortaledMenu();
            window.addEventListener('scroll', localMenu._repositionHandler, true);
            window.addEventListener('resize', localMenu._repositionHandler);
        }

        function positionPortaledMenu() {
            const portaled = wrap._synMenu;
            if (!portaled || !portaled._isPortaled) return;

            // Reset any previously computed inline positioning so we can
            // measure natural size.
            portaled.style.top = '';
            portaled.style.left = '';
            portaled.style.right = '';
            portaled.style.bottom = '';
            portaled.style.position = 'fixed';
            portaled.style.visibility = 'hidden';

            const triggerRect = trigger.getBoundingClientRect();
            const menuRect = portaled.getBoundingClientRect();
            const vw = window.innerWidth;
            const vh = window.innerHeight;
            const gap = 6;       // gap between trigger and menu
            const margin = 8;    // viewport edge margin
            let top, left;

            // Decide vertical placement: prefer below the trigger, flip
            // above if there's not enough room below AND there IS room above.
            const spaceBelow = vh - triggerRect.bottom - margin;
            const spaceAbove = triggerRect.top - margin;
            const menuH = menuRect.height;

            if (spaceBelow >= menuH + gap) {
                top = triggerRect.bottom + gap;
            } else if (spaceAbove >= menuH + gap) {
                top = triggerRect.top - menuH - gap;
            } else {
                // neither side fits fully — pick the side with more room
                top = spaceBelow >= spaceAbove
                    ? Math.max(margin, triggerRect.bottom + gap)
                    : Math.max(margin, triggerRect.top - menuH - gap);
            }

            // Decide horizontal placement: align with trigger left edge,
            // then shift left if it would overflow the right edge.
            const menuW = menuRect.width;
            left = triggerRect.left;
            if (left + menuW > vw - margin) {
                left = Math.max(margin, vw - menuW - margin);
            }
            if (left < margin) {
                left = margin;
            }

            portaled.style.top = top + 'px';
            portaled.style.left = left + 'px';
            portaled.style.visibility = '';
        }

        function closeMenu() {
            wrap.classList.remove('is-open');
            trigger.setAttribute('aria-expanded', 'false');
            state.isOpen = false;
            if (searchInput) searchInput.value = '';

            // Un-portal: move the menu back into its wrap and clean up
            // listeners so we don't leak handlers across open/close cycles.
            const portaled = wrap._synMenu;
            if (portaled && portaled._isPortaled) {
                if (portaled._repositionHandler) {
                    window.removeEventListener('scroll', portaled._repositionHandler, true);
                    window.removeEventListener('resize', portaled._repositionHandler);
                    portaled._repositionHandler = null;
                }
                portaled.style.position = '';
                portaled.style.top = '';
                portaled.style.left = '';
                portaled.style.right = '';
                portaled.style.bottom = '';
                portaled.style.visibility = '';
                portaled.classList.remove('is-portaled');
                portaled._isPortaled = false;
                wrap.appendChild(portaled);
            }
            trigger.focus();
        }

        function renderAll() {
            renderLabel();
            renderOptions(searchInput ? searchInput.value : '');
        }

        // Wire up trigger
        trigger.addEventListener('click', (e) => {
            e.preventDefault();
            if (wrap.classList.contains('is-disabled')) return;
            if (state.isOpen) closeMenu();
            else openMenu();
        });

        // Keyboard navigation
        trigger.addEventListener('keydown', (e) => {
            if (e.key === 'ArrowDown' || e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                openMenu();
            }
        });

        if (searchInput) {
            searchInput.addEventListener('input', () => {
                renderOptions(searchInput.value);
            });
            searchInput.addEventListener('keydown', (e) => {
                if (e.key === 'ArrowDown') {
                    e.preventDefault();
                    state.highlighted = 0;
                    updateHighlight();
                } else if (e.key === 'Enter') {
                    e.preventDefault();
                    const highlighted = optionsList.querySelector('.is-highlighted');
                    if (highlighted) highlighted.click();
                } else if (e.key === 'Escape') {
                    closeMenu();
                }
            });
        }

        // Close on outside click
        document.addEventListener('click', (e) => {
            if (state.isOpen && !wrap.contains(e.target)) closeMenu();
        });

        // Re-render when the original <select> changes programmatically
        selectEl.addEventListener('change', renderAll);

        // Initial render
        renderAll();

        const instance = { wrap, trigger, menu, open: openMenu, close: closeMenu, refresh: renderAll };
        selectEl._synDropdown = instance;
        return instance;
    }

    // ==========================================================================
    // FORM → DIALOG CONVERSION
    // ==========================================================================
    /**
     * Convert any <form data-synapse-form-dialog> into a modal dialog.
     * The form is moved into a syn-dialog with header (title), body (fields),
     * and footer (Cancel + Submit). Submitting submits the form normally;
     * the page reloads with flashdata. Validation errors surface as toasts.
     *
     * Usage:
     *   <form data-synapse-form-dialog
     *         data-dialog-title="New user"
     *         data-dialog-submit-label="Create"
     *         data-dialog-icon="fas fa-user-plus"
     *         data-dialog-width="wide">
     *       ... fields ...
     *       <button type="submit">Create</button>
     *   </form>
     */
    function formToDialog(formEl, opts) {
        if (formEl._synFormDialog) return formEl._synFormDialog;
        opts = opts || {};

        const title       = opts.title       || formEl.getAttribute('data-dialog-title') || 'Form';
        const subtitle    = opts.subtitle    || formEl.getAttribute('data-dialog-subtitle') || null;
        const submitLabel = opts.submitLabel || formEl.getAttribute('data-dialog-submit-label') || formEl.querySelector('button[type=submit]')?.textContent.trim() || 'Save';
        const cancelLabel = opts.cancelLabel || formEl.getAttribute('data-dialog-cancel-label') || 'Cancel';
        const icon        = opts.icon        || formEl.getAttribute('data-dialog-icon') || 'fas fa-pen-to-square';
        const wide        = opts.wide        !== undefined ? opts.wide : formEl.hasAttribute('data-dialog-width');

        // Capture inner fields before we restructure
        const submitBtn = formEl.querySelector('button[type=submit]');
        const cancelBtn = formEl.querySelector('a[href], button[type=button], button:not([type])');
        // Replace the inner submit button with our dialog "Confirm" button
        // to avoid two submit triggers.
        if (submitBtn) submitBtn.remove();
        if (cancelBtn && cancelBtn.tagName === 'A') {
            // convert external "Cancel" anchor into a button that closes the dialog
            cancelBtn.removeAttribute('href');
            cancelBtn.setAttribute('type', 'button');
            cancelBtn.setAttribute('data-synapse-close', '');
        }

        // Wrap form contents in a dialog body
        const dialog = document.createElement('div');
        dialog.className = 'syn-dialog' + (wide ? ' syn-dialog--wide' : '');

        // Reset any inline display:none / visibility that might have been
        // set on the form before conversion (e.g. by bindFormLink() to hide
        // it while restructuring). If left in place, the form would be the
        // parent of the dialog body and would hide all the fields.
        formEl.style.display = '';
        formEl.style.visibility = '';
        // Don't let the form impose its own width — the dialog controls sizing
        formEl.style.width = '100%';
        formEl.style.maxWidth = '100%';
        formEl.style.margin = '0';

        // Header
        const header = document.createElement('div');
        header.className = 'syn-dialog-header';
        const titleEl = document.createElement('h2');
        titleEl.className = 'syn-dialog-title';
        titleEl.innerHTML = '<i class="' + icon + '" style="color: var(--primary-600); margin-right: 0.4rem;"></i>' + escapeHtml(title);
        header.appendChild(titleEl);
        if (subtitle) {
            const sub = document.createElement('p');
            sub.className = 'syn-dialog-desc';
            sub.textContent = subtitle;
            header.appendChild(sub);
        }
        dialog.appendChild(header);

        // Optional validation alert (used when re-opening with server errors)
        if (opts.alert) {
            const alert = document.createElement('div');
            alert.className = 'syn-alert syn-alert--danger';
            alert.style.marginBottom = '1rem';
            alert.innerHTML = '<i class="fas fa-circle-exclamation"></i> '
                + '<strong>' + escapeHtml(opts.alertTitle || 'Error') + ':</strong> '
                + escapeHtml(opts.alert);
            dialog.appendChild(alert);
        }

        // Body — wraps existing form contents
        const body = document.createElement('div');
        body.className = 'syn-dialog-body';
        // Move all form children into body
        while (formEl.firstChild) {
            body.appendChild(formEl.firstChild);
        }
        formEl.appendChild(body);
        dialog.appendChild(formEl);

        // Footer
        const footer = document.createElement('div');
        footer.className = 'syn-dialog-actions';

        const cancel = document.createElement('button');
        cancel.type = 'button';
        cancel.className = 'syn-btn syn-btn--secondary';
        cancel.setAttribute('data-synapse-close', '');
        cancel.innerHTML = '<i class="fas fa-xmark"></i> ' + escapeHtml(cancelLabel);
        cancel.addEventListener('click', () => closeDialog(dialog));
        footer.appendChild(cancel);

        const confirm = document.createElement('button');
        confirm.type = 'button';
        confirm.className = 'syn-btn syn-btn--primary';
        confirm.innerHTML = '<i class="fas fa-check"></i> ' + escapeHtml(submitLabel);
        confirm.addEventListener('click', async () => {
            // Set a hidden marker so the controller can detect this was submitted via dialog
            let marker = formEl.querySelector('input[name="_dialog_submit"]');
            if (!marker) {
                marker = document.createElement('input');
                marker.type = 'hidden';
                marker.name = '_dialog_submit';
                marker.value = '1';
                formEl.appendChild(marker);
            }
            // Disable buttons + show spinner
            const stop = buttonLoading(confirm);
            const cancelBtn = footer.querySelector('[data-synapse-close]');
            if (cancelBtn) cancelBtn.disabled = true;

            // Read the form's CSRF token + meta CSRF (CodeIgniter rotates
            // the token on every successful POST, so the meta tag must
            // be updated from any `csrf_hash` echoed in the response).
            const csrfMeta = document.querySelector('meta[name="csrf-token"]');
            const csrfToken = csrfMeta ? csrfMeta.getAttribute('content') : '';

            // Build FormData so all <input>, <select>, <textarea> values are
            // submitted correctly — including any dynamic fields the
            // dialog injected (e.g. _dialog_submit).
            const formData = new FormData(formEl);

            // Honour an explicit opt-out: if the form carries
            // data-synapse-form-action="native", fall back to the legacy
            // native submit (full page navigation, redirect target).
            if (formEl.dataset.synapseFormAction === 'native') {
                formEl.submit();
                return;
            }

            try {
                const action = formEl.getAttribute('action') || window.location.href;
                const res = await fetch(action, {
                    method: 'POST',
                    credentials: 'same-origin',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    redirect: 'manual' // we'll handle a 302 redirect ourselves
                });

                // CI4 rotates the CSRF token on every POST — adopt the new one
                if (csrfMeta) {
                    const newToken = res.headers.get('X-CSRF-TOKEN')
                                  || res.headers.get('x-csrf-token');
                    if (newToken) csrfMeta.setAttribute('content', newToken);
                }

                // Read the response (CI4's `redirect()` returns a JSON
                // envelope for AJAX requests containing `status`,
                // `message`, `redirect`, and the new `csrf_hash`).
                let payload = null;
                const ct = res.headers.get('content-type') || '';
                if (ct.indexOf('application/json') !== -1) {
                    try { payload = await res.json(); } catch (_) { payload = null; }
                } else if (res.status === 0 || res.type === 'opaqueredirect') {
                    // Browser blocked the redirect; the action succeeded
                    // server-side. Treat as success and reload the page.
                    payload = { status: 'success', reload: true };
                } else {
                    // Plain-text / HTML response — likely a redirect-follow
                    // or unexpected error. Read body for diagnostics.
                    const text = await res.text().catch(() => '');
                    payload = { status: res.ok ? 'success' : 'error', message: text.substring(0, 200) };
                }

                if (payload && payload.csrf_hash && csrfMeta) {
                    csrfMeta.setAttribute('content', payload.csrf_hash);
                }

                if (payload && payload.status === 'success') {
                    // Briefly flash a check icon so the user sees the
                    // success transition before the dialog closes
                    confirm.innerHTML = '<i class="fas fa-check"></i> Saved';
                    // Surface any flash message the controller emitted
                    if (payload.message && window.synapse && synapse.toast) {
                        synapse.toast({
                            type: 'success',
                            title: payload.title || 'Saved',
                            message: payload.message,
                            duration: 3500
                        });
                    }
                    // Close the dialog (deferred so the user sees the
                    // spinner → Saved transition briefly)
                    setTimeout(() => {
                        closeDialog(dialog);
                        stop();
                        // If the server told us to reload or redirect, honour it
                        if (payload.reload) {
                            window.location.reload();
                        } else if (payload.redirect) {
                            window.location.href = payload.redirect;
                        }
                    }, 350);
                } else if (payload && payload.status === 'error') {
                    // Show validation/handler errors inside the dialog
                    stop();
                    if (cancelBtn) cancelBtn.disabled = false;
                    const msg = payload.message || 'Could not save changes.';
                    if (window.synapse && synapse.toast) {
                        synapse.toast({ type: 'error', title: 'Save failed', message: msg, duration: 5000 });
                    }
                    // Re-open the dialog body so user can correct the form
                    const existing = dialog.querySelector('.syn-alert--danger');
                    if (!existing) {
                        const alert = document.createElement('div');
                        alert.className = 'syn-alert syn-alert--danger';
                        alert.style.marginBottom = '1rem';
                        alert.innerHTML = '<i class="fas fa-circle-exclamation"></i> '
                            + '<strong>Error:</strong> ' + escapeHtml(msg);
                        const bodyDiv = dialog.querySelector('.syn-dialog-body');
                        if (bodyDiv) bodyDiv.insertBefore(alert, bodyDiv.firstChild);
                    }
                } else {
                    stop();
                    if (cancelBtn) cancelBtn.disabled = false;
                    if (window.synapse && synapse.toast) {
                        synapse.toast({ type: 'error', title: 'Unexpected response', message: 'Please try again.' });
                    }
                }
            } catch (err) {
                stop.failure();
                if (cancelBtn) cancelBtn.disabled = false;
                if (window.synapse && synapse.toast) {
                    synapse.toast({ type: 'error', title: 'Network error', message: err.message || String(err) });
                }
            }
        });
        footer.appendChild(confirm);

        dialog.appendChild(footer);

        // Re-show inside a backdrop using the existing dialog.open()
        openDialog(dialog);

        // Re-initialize datepickers and custom dropdowns inside the dialog body
        // (the dialog was just inserted into the DOM, so the auto-wirer missed it)
        setTimeout(() => {
            if (typeof flatpickr !== 'undefined') {
                body.querySelectorAll('.syn-datepicker').forEach(input => {
                    if (input._flatpickr) return;
                    const isTime     = input.classList.contains('syn-datepicker--time');
                    const isTimeOnly = input.classList.contains('syn-datepicker--time-only');
                    const isRange    = input.classList.contains('syn-datepicker--range');
                    input._flatpickr = flatpickr(input, {
                        enableTime: isTime || isTimeOnly,
                        enableSeconds: false,
                        time_24hr: true,
                        minuteIncrement: 5,
                        noCalendar: isTimeOnly,
                        dateFormat: isTime ? 'Y-m-d H:i' : isTimeOnly ? 'H:i' : 'Y-m-d',
                        mode: isRange ? 'range' : 'single',
                        allowInput: !isTimeOnly,
                        disableMobile: true,
                        locale: { firstDayOfWeek: 1 },
                        // Native-overlay behaviour: the calendar is an
                        // absolutely-positioned popover appended to
                        // <body> (NOT inline with the input). This
                        // matches how a native <input type="date"> shows
                        // its picker — a system-level overlay that doesn't
                        // disturb the surrounding form layout.
                        //
                        // `appendTo: document.body` is critical: it escapes
                        // any ancestor with `overflow: hidden/auto` (our
                        // .page-content scroll container) and any
                        // stacking-context traps (dialog backdrops).
                        appendTo: document.body,
                        // Auto-close on scroll — native browser date
                        // pickers (Chrome, Safari, Firefox) all close
                        // their popups the moment the user scrolls because
                        // the input has moved out of its anchor position
                        // and repositioning would require recomputing
                        // top/left every frame. Users find this
                        // predictable and muscle-memory friendly: scroll
                        // → picker dismisses, click input again to reopen.
                        // We listen on the nearest scrollable ancestor
                        // (.page-content) AND on the window, so both
                        // inner container scroll and full-page scroll
                        // dismiss the picker.
                        onOpen: function (_, __, instance) {
                            const scrollParent = instance.element.closest('.page-content');
                            const close = () => { if (instance.isOpen) instance.close(); };
                            if (instance._synScrollHandler) {
                                if (instance._synScrollParent) {
                                    instance._synScrollParent.removeEventListener('scroll', instance._synScrollHandler);
                                }
                                window.removeEventListener('scroll', instance._synScrollHandler);
                            }
                            instance._synScrollHandler = close;
                            instance._synScrollParent = scrollParent;
                            if (scrollParent) scrollParent.addEventListener('scroll', close, { passive: true });
                            window.addEventListener('scroll', close, { passive: true });
                        },
                        onClose: function (_, __, instance) {
                            if (instance._synScrollHandler) {
                                if (instance._synScrollParent) {
                                    instance._synScrollParent.removeEventListener('scroll', instance._synScrollHandler);
                                }
                                window.removeEventListener('scroll', instance._synScrollHandler);
                                instance._synScrollParent = null;
                                instance._synScrollHandler = null;
                            }
                        }
                    });
                });
            }
            body.querySelectorAll('select[data-synapse-dropdown]').forEach(buildDropdown);
        }, 50);

        formEl._synFormDialog = { dialog, open: () => openDialog(dialog), close: () => closeDialog(dialog) };
        return formEl._synFormDialog;
    }

    /**
     * Convert a regular link into a "open dialog form" trigger.
     * Useful when the create/edit button is a link, not a form submit.
     *
     * <a href="/admin/users/create" data-synapse-form-link
     *    data-dialog-title="New user"
     *    data-dialog-width="wide">New user</a>
     *
     * The link fetches the URL via fetch(), extracts the form, and renders
     * it in a dialog. This makes the create/edit flow stay on the list page.
     */
    function bindFormLink(linkEl) {
        if (linkEl._synFormLink) return linkEl._synFormLink;
        linkEl._synFormLink = true;
        linkEl.addEventListener('click', async function (e) {
            e.preventDefault();
            // Accept href from <a href="..."> OR data-synapse-form-link="..."
            // OR data-href="..." (for non-<a> elements like <li role="button">).
            const href = linkEl.getAttribute('href')
                      || linkEl.getAttribute('data-synapse-form-link')
                      || linkEl.getAttribute('data-href');
            const title = linkEl.getAttribute('data-dialog-title')
                       || linkEl.getAttribute('data-synapse-form-link-title')
                       || linkEl.textContent.trim()
                       || 'Form';
            const icon  = linkEl.getAttribute('data-dialog-icon')  || 'fas fa-pen-to-square';
            const wide  = linkEl.hasAttribute('data-dialog-width')
                       || linkEl.hasAttribute('data-synapse-form-link-wide');

            try {
                const res = await fetch(href, { credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                const html = await res.text();
                // Parse out the first <form>...</form>
                const match = html.match(/<form[\s\S]*?<\/form>/i);
                if (!match) {
                    synapse.toast({ type: 'error', title: 'Could not open form', message: 'No form found at ' + href });
                    return;
                }
                // Parse the HTML fragment
                const wrapper = document.createElement('div');
                wrapper.innerHTML = match[0];
                const formEl = wrapper.querySelector('form');
                if (!formEl) return;

                // Close any parent dropdown menu that was used to open this
                // dialog. Without this the menu stays open behind the modal
                // because the click that opened the dialog was technically
                // INSIDE the menu (the outside-click handler sees the click
                // target as inside and does nothing).
                const triggerEl = linkEl.closest('.syn-dropdown.is-open, .syn-dropdown-menu, .notification-dropdown');
                if (triggerEl) {
                    const openMenu = triggerEl.closest('.syn-dropdown, .notification-dropdown');
                    if (openMenu) openMenu.classList.remove('is-open');
                }

                // If the response already contains validation errors as
                // server-rendered HTML, surface them as a toast. (The view
                // also renders errors INSIDE the form so they show inside
                // the dialog body — we don't want to duplicate them as a
                // separate syn-dialog-alert banner above the form.)
                let alertHtml = null;
                const hasInlineErrors = formEl.querySelector('.syn-alert--danger, .alert.alert-danger');
                if (!hasInlineErrors) {
                    const errBanner = html.match(/<div[^>]*class="[^"]*alert-danger[^"]*"[^>]*>([\s\S]*?)<\/div>/i);
                    if (errBanner) {
                        const errText = errBanner[1].replace(/<[^>]+>/g, ' ').replace(/\s+/g, ' ').trim().substring(0, 200);
                        if (errText.length > 5) {
                            alertHtml = errText;
                        }
                    }
                }

                // Inject form into the DOM so submit reloads properly.
                // We keep it visible only briefly — formToDialog() will
                // restructure it (move children into a body div) and append
                // the whole form into a backdrop-syn-dialog that's then
                // positioned fixed on screen. The form's display:none is
                // cleared inside formToDialog so the dialog body renders.
                document.body.appendChild(formEl);

                // Then convert it to a dialog
                formToDialog(formEl, {
                    title: title,
                    icon: icon,
                    wide: wide,
                    alert: alertHtml,
                    alertTitle: 'Validation failed'
                });

                // If we have validation errors, surface as a toast too
                if (alertHtml) {
                    synapse.toast({ type: 'error', title: 'Validation failed', message: alertHtml.substring(0, 120) + (alertHtml.length > 120 ? '…' : '') });
                }
            } catch (err) {
                synapse.toast({ type: 'error', title: 'Could not open form', message: err.message || String(err) });
            }
        });

        // Keyboard activation for non-<a> triggers (e.g. <li role="button">).
        // <a href="..."> already activates on Enter natively.
        if (linkEl.tagName !== 'A') {
            linkEl.addEventListener('keydown', function (e) {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    linkEl.click();
                }
            });
        }

        linkEl._synFormLink = true;
    }

    // ==========================================================================
    // SKELETON HELPERS
    // ==========================================================================
    /**
     * Show a skeleton placeholder inside a container, then auto-replace with
     * the result of an async operation.
     *
     * synapse.skeleton(target, fetchData);
     */
    function skeleton(target, asyncFn) {
        const el = typeof target === 'string' ? document.querySelector(target) : target;
        if (!el) return Promise.resolve();

        const originalHtml = el.innerHTML;
        el.innerHTML = '<div class="syn-skel-card">'
            + '<div class="syn-skel syn-skel--title"></div>'
            + '<div class="syn-skel syn-skel--line"></div>'
            + '<div class="syn-skel syn-skel--text"></div>'
            + '<div class="syn-skel syn-skel--text" style="width: 60%;"></div>'
            + '<div style="height: 0.85rem;"></div>'
            + '<div class="syn-skel syn-skel--line"></div>'
            + '<div class="syn-skel syn-skel--text" style="width: 90%;"></div>'
            + '</div>';

        return Promise.resolve(asyncFn()).then(
            (html) => { el.innerHTML = html; },
            (err) => {
                el.innerHTML = originalHtml;
                throw err;
            }
        );
    }

    // ==========================================================================
    // UTILITIES
    // ==========================================================================
    function escapeHtml(str) {
        if (str == null) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    // ==========================================================================
    // AUTO-WIRING (declarative data-* attributes)
    // ==========================================================================
    //
    // `root` is optional. When omitted, scans the whole document (initial
    // page load). When passed (e.g. a swapped-in <main>), only wires that
    // subtree. Nodes that have already been wired carry a sentinel flag
    // (e.g. `_synFormDialog`, `_synDropdown`, `_flatpickr`) so this is
    // safe to call multiple times.
    function autoWire(root) {
        /* Defensive: if root was passed but doesn't have
           querySelectorAll (e.g. a NodeList), fall back to document. */
        var doc = (root && typeof root.querySelectorAll === 'function')
            ? root
            : document;
        var qs  = function (sel) { return doc.querySelectorAll(sel); };

        // [data-synapse-open] opens a dialog by id
        qs('[data-synapse-open]').forEach(btn => {
            /* Skip if a previous wiring captured it. */
            if (btn._synOpenBound) return;
            btn._synOpenBound = true;
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                const id = btn.getAttribute('data-synapse-open');
                openDialog(id);
            });
        });

        // [data-synapse-close] closes nearest dialog
        qs('[data-synapse-close]').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                const dialog = btn.closest('.syn-dialog');
                if (dialog) closeDialog(dialog);
            });
        });

        // [data-synapse-toast] shows a toast on click
        qs('[data-synapse-toast]').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                const type = btn.getAttribute('data-synapse-toast-type') || 'info';
                const message = btn.getAttribute('data-synapse-toast');
                toast(message, type);
            });
        });

        // [data-synapse-confirm] shows a confirm dialog on click
        qs('[data-synapse-confirm]').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                const title = btn.getAttribute('data-synapse-confirm-title') || 'Are you sure?';
                const body = btn.getAttribute('data-synapse-confirm-body') || 'This action cannot be undone.';
                const confirmText = btn.getAttribute('data-synapse-confirm-text') || 'Confirm';
                const form = btn.closest('form');
                confirm({
                    title: title,
                    body: body,
                    danger: btn.hasAttribute('data-synapse-confirm-danger'),
                    confirmText: confirmText,
                    onConfirm: () => {
                        if (form) form.submit();
                    }
                });
            });
        });

        // [data-synapse-per-page-select] is the per-page <select> rendered
        // by the PHP pagination helper. We navigate to the option's URL
        // on change. The <option value="…"> already carries the full URL
        // (with `per_page` set and `page` reset), so navigation is safe.
        qs('[data-synapse-per-page-select]').forEach(sel => {
            if (sel._synPerPageBound) return;
            sel._synPerPageBound = true;
            sel.addEventListener('change', (e) => {
                const opt = sel.options[sel.selectedIndex];
                if (!opt) return;
                const url = opt.getAttribute('value');
                if (!url) return;
                /* Use SPA navigation if available so the user gets the
                   same fluid experience as clicking a sidebar link. */
                if (window.__synapseSpaNav && typeof window.spaNavigate === 'function') {
                    e.preventDefault();
                    window.spaNavigate(url, false, false);
                } else {
                    window.location.href = url;
                }
            });
        });

        // ====================================================================
        // LIVE-SEARCH BAR
        // ====================================================================
        // Wires any <form data-synapse-search> so the user gets results
        // WHILE they type (no Enter required). The form must have:
        //
        //   - `data-synapse-search` attribute (binds the wiring)
        //   - `data-synapse-search-target="<selector>"` (optional, defaults
        //     to the closest <main id="mainContent">). Used when the result
        //     region is somewhere other than the main content area.
        //   - At least one <input name="q"> or `name="q"|"search"|"filter"`
        //
        // Behaviour:
        //   1. Listen for `input` events on the primary search input.
        //   2. Debounce by 280ms (fast enough to feel instant, slow
        //      enough to skip most keystrokes between words).
        //   3. Build a URL from the form's `action` attribute plus the
        //      current values of all form fields.
        //   4. If the user is just adding whitespace, no fetch fires.
        //   5. Show a "Searching…" pill next to the input while fetching.
        //   6. After fetch, swap the result region with the server's HTML.
        //   7. Re-run `autoWire` on the swapped-in content (so any new
        //      data-synapse-* attributes get re-bound).
        //   8. If the server returned no <table>, render an inline empty
        //      state so the user sees "no matches" feedback without
        //      having to look at the URL bar.
        //   9. Show a toast when the search completes (1.5s) — discreet
        //      feedback that "yes, results updated".
        //
        // Pressing Escape clears the input and reverts to the unfiltered
        // list. Pressing Enter submits the form normally (fallback for
        // users who prefer the classic flow).
        //
        // Performance: each fetch hits the same SPA fetch pipeline as
        // sidebar clicks, so it reuses AbortController (rapidly-typed
        // keystrokes cancel stale requests automatically) and uses the
        // HTML cache-buster query param already on the page.
        /* Live search is wired via bindLiveSearch(root) so autoWire
           can re-bind the fresh <form data-synapse-search> after an
           SPA swap (the previous instance is destroyed when
           <main>.innerHTML is replaced). */
        function bindLiveSearch(root) {
            const forms = (root || document).querySelectorAll('form[data-synapse-search]');
            if (!forms.length) return;

            forms.forEach((form) => {
                if (form._synLiveSearchBound) return;
                form._synLiveSearchBound = true;

                /* The "main" search input. If the form has multiple
                   inputs (e.g. filters + search), only the one with
                   name="q" / "search" / "filter" triggers live updates. */
                const triggerInput = form.querySelector(
                    'input[name="q"], input[name="search"], input[name="filter"], input[data-synapse-search-trigger]'
                );
                if (!triggerInput) return;

                /* Visual status pill (Searching… / X results). We add it
                   if the form has a [data-synapse-search-status] element
                   to use, otherwise we create one next to the input. */
                let statusEl = form.querySelector('[data-synapse-search-status]');
                if (!statusEl) {
                    statusEl = document.createElement('span');
                    statusEl.className = 'syn-search-status';
                    statusEl.hidden = true;
                    triggerInput.parentElement.appendChild(statusEl);
                }

                /* Timer + AbortController per form, so rapid typing
                   cancels the previous in-flight request. */
                let timer = null;
                let aborter = null;
                let inflightCount = 0;

                function showStatus(text, isLoading) {
                    if (!text) {
                        statusEl.hidden = true;
                        statusEl.classList.remove('is-loading');
                        return;
                    }
                    statusEl.hidden = false;
                    statusEl.textContent = text;
                    statusEl.classList.toggle('is-loading', !!isLoading);
                }

                function buildUrl() {
                    /* Build the URL from the form's action + all fields.
                       Drop empty values so the URL stays clean. */
                    const action = form.getAttribute('action') || window.location.pathname;
                    const params = new URLSearchParams();
                    const fields = form.querySelectorAll('input, select, textarea');
                    fields.forEach((field) => {
                        if (!field.name) return;
                        if (field.type === 'submit' || field.type === 'button') return;
                        const val = field.value;
                        if (val === '' || val == null) return;
                        params.set(field.name, val);
                    });
                    /* The data-synapse-state attribute holds non-editable
                       context (e.g. sort key/direction) that should ride
                       along on every search query without needing a
                       hidden <input>. JSON-decoded, merged in. */
                    try {
                        const stateAttr = form.getAttribute('data-synapse-state');
                        if (stateAttr) {
                            const state = JSON.parse(stateAttr);
                            if (state && typeof state === 'object') {
                                Object.keys(state).forEach((k) => {
                                    if (state[k] !== '' && state[k] != null) {
                                        params.set(k, state[k]);
                                    }
                                });
                            }
                        }
                    } catch (e) { /* ignore malformed JSON */ }
                    /* Always reset to page 1 when the user changes the
                       query — otherwise they could be on page 5 of an
                       unfiltered query and the new query has no page 5. */
                    params.delete('page');
                    const qs = params.toString();
                    return action + (qs ? '?' + qs : '');
                }

                async function runSearch() {
                    const url = buildUrl();
                    if (!url) return;

                    /* Capture focus + caret BEFORE the swap destroys
                       the original input. The swap replaces <main>'s
                       innerHTML, which obliterates the focused element
                       and breaks rapid typing. We restore focus and the
                       caret position to the fresh input on the next
                       animation frame so the user can keep typing
                       without re-clicking the search bar. */
                    const restoreFocus = {
                        id: triggerInput.id || null,
                        name: triggerInput.name || null,
                        start: triggerInput.selectionStart,
                        end: triggerInput.selectionEnd
                    };
                    const docHasFocus = (document.activeElement === triggerInput);

                    /* Cancel previous request if still in flight. */
                    if (aborter) try { aborter.abort(); } catch (e) {}
                    aborter = (typeof AbortController === 'function') ? new AbortController() : null;

                    inflightCount++;
                    showStatus('Searching…', true);

                    try {
                        const fetchOpts = {
                            credentials: 'same-origin',
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'text/html',
                                'X-Synapse-Search': '1'
                            },
                            redirect: 'follow'
                        };
                        if (aborter) fetchOpts.signal = aborter.signal;

                        const res = await fetch(url, fetchOpts);
                        if (!res.ok) {
                            if (res.status === 401 || res.status === 419) {
                                /* Session expired or CSRF — let the
                                   server's redirect run. */
                                window.location.href = url;
                                return;
                            }
                            throw new Error('HTTP ' + res.status);
                        }
                        if (res.redirected) {
                            window.location.href = res.url;
                            return;
                        }

                        const text = await res.text();
                        const doc = new DOMParser().parseFromString(text, 'text/html');
                        const newMain = doc.getElementById('mainContent');
                        if (!newMain) {
                            /* Server isn't ours (login / 404). Fall
                               back to a full navigation so the user
                               lands on the right page. */
                            window.location.href = url;
                            return;
                        }

                        /* Swap the main content via the same path the
                           SPA module uses for sidebar clicks, so the
                           sidebar, settings, and SPA integration all
                           stay in sync. */
                        const mainEl = document.getElementById('mainContent');
                        if (mainEl) {
                            mainEl.innerHTML = newMain.innerHTML;
                        }

                        /* Update title. */
                        const newTitle = doc.querySelector('title');
                        if (newTitle && newTitle.textContent) {
                            document.title = newTitle.textContent;
                        }

                        /* Re-bind anything data-synapse-* inside the
                           swapped content (dropdowns, dialogs, etc.)
                           This MUST happen before we restore focus so
                           that any focus-stealing init (custom dropdown
                           buttons, etc.) runs first; we then put the
                           caret back where the user expects it.
                           autoWire() calls bindLiveSearch() internally
                           so the freshly swapped <form data-synapse-search>
                           gets re-wired for live search in the same
                           pass. */
                        if (window.synapse && typeof window.synapse.rebind === 'function') {
                            window.synapse.rebind(mainEl);
                        } else {
                            document.dispatchEvent(new CustomEvent('synapse:content-replaced', {
                                detail: { root: mainEl }
                            }));
                            /* Fallback: make sure the new search form
                               gets bound even if synapse.rebind isn't
                               available. */
                            try {
                                if (typeof bindLiveSearch === 'function') {
                                    bindLiveSearch(mainEl);
                                }
                            } catch (e) { /* ignore */ }
                        }

                        /* Restore focus + caret to the freshly swapped
                           search input. The previous triggerInput was
                           destroyed by the innerHTML swap above, so we
                           find the matching one in the new tree by id
                           (preferred) or by name. requestAnimationFrame
                           waits for the browser to commit the new DOM
                           before we hand focus back, otherwise the
                           focus call can race the layout pass. We only
                           steal focus back if the input was focused
                           before — otherwise we'd hijack the user's
                           click on something else. */
                        if (restoreFocus.id || restoreFocus.name) {
                            const sel = restoreFocus.id
                                ? '#' + CSS.escape(restoreFocus.id)
                                : 'form[data-synapse-search] input[name="' + CSS.escape(restoreFocus.name) + '"]';
                            const newInput = document.querySelector(sel);
                            if (newInput && docHasFocus) {
                                /* Two-phase restore: a setTimeout(0) lets
                                   the swap settle before we steal focus
                                   back. We then retry on the next two
                                   ticks in case a later init (custom
                                   dropdown button focus, flatpickr,
                                   etc.) still holds focus. We avoid
                                   requestAnimationFrame here because
                                   it's not guaranteed to fire on hidden
                                   tabs and we don't want typing to break
                                   just because the tab is in the
                                   background. */
                                const restore = (attempt) => {
                                    try {
                                        if (document.activeElement === newInput) return;
                                        newInput.focus({ preventScroll: true });
                                        const len = newInput.value.length;
                                        const start = Math.min(restoreFocus.start ?? len, len);
                                        const end   = Math.min(restoreFocus.end ?? len, len);
                                        try { newInput.setSelectionRange(start, end); }
                                        catch (e) { /* type=email/number etc. — ignore */ }
                                    } catch (e) { /* ignore */ }
                                };
                                setTimeout(() => {
                                    restore(1);
                                    setTimeout(() => {
                                        if (document.activeElement !== newInput) restore(2);
                                    }, 0);
                                }, 0);
                            }
                        }

                        /* Update URL bar (pushState) so the user can
                           bookmark / share the search results. We use
                           replaceState (not pushState) so a series of
                           search keystrokes doesn't pollute the back
                           button history. */
                        try {
                            window.history.replaceState(
                                { spa: true, url: url },
                                '',
                                url
                            );
                        } catch (e) { /* ignore */ }

                        /* Show the result count from the swap. */
                        const countEl = newMain.querySelector('.syn-search-result-count');
                        if (countEl) {
                            const matches = countEl.textContent.trim().replace(/\s+/g, ' ');
                            showStatus(matches, false);
                        } else {
                            showStatus('', false);
                        }
                    } catch (err) {
                        if (err && err.name === 'AbortError') {
                            /* A newer keystroke superseded us. Silently
                               ignore; the newer request will update the
                               status. */
                            return;
                        }
                        /* Network error or 5xx — fall back to a real
                           navigation so the user gets a working page. */
                        showStatus('Error', false);
                        /* Wait a tick, then redirect. */
                        setTimeout(() => { window.location.href = url; }, 400);
                    } finally {
                        inflightCount--;
                        if (inflightCount <= 0) {
                            /* Ensure status pill hides if this was the
                               last in-flight call (AbortError case). */
                        }
                    }
                }

                function schedule() {
                    if (timer) clearTimeout(timer);
                    timer = setTimeout(runSearch, 280);
                }

                /* Main typing event. */
                triggerInput.addEventListener('input', schedule);

                /* Escape clears the input and reverts to the
                   unfiltered list. */
                triggerInput.addEventListener('keydown', (e) => {
                    if (e.key === 'Escape') {
                        e.preventDefault();
                        triggerInput.value = '';
                        runSearch();
                    }
                    /* Enter submits the form normally (server takes
                       over with the current value). The input handler
                       has already done a debounced fetch, so Enter is
                       just a no-op convenience. */
                });

                /* When the user submits via the button, cancel any
                   pending debounced search so we don't double-fire. */
                form.addEventListener('submit', (e) => {
                    if (timer) {
                        clearTimeout(timer);
                        timer = null;
                    }
                    /* If JS is working, prevent the form from doing a
                       full reload — the live search has already swapped
                       the result, or about to. */
                    if (window.__synapseSpaNav) {
                        e.preventDefault();
                        runSearch();
                    }
                });
            });
        }
        bindLiveSearch(doc);

        // Auto-spinner on form submit: any form with the data-synapse-submit
        // attribute (or any form that has a button.syn-btn[type=submit] as
        // its only submit control) will show a loading state on submit.
        qs('form').forEach(form => {
            if (form.hasAttribute('data-synapse-submit') ||
                (form.querySelectorAll('button[type=submit]').length === 1 &&
                 !form.hasAttribute('data-synapse-no-spin'))) {
                form.addEventListener('submit', () => {
                    const submit = form.querySelector('button[type=submit]');
                    if (submit && !submit.disabled) {
                        buttonLoading(submit);
                    }
                });
            }
        });

        // Auto-convert forms tagged with data-synapse-form-dialog into modals.
        // The form keeps all its existing fields; JS wraps it in a dialog shell.
        qs('form[data-synapse-form-dialog]').forEach(formToDialog);

        // Auto-bind links with data-synapse-form-link to fetch + open the
        // referenced URL's form inside a dialog (no full-page navigation).
        // We also match any element with [role="button"][data-synapse-form-link]
        // so non-<a> elements (e.g. <li role="button"> inside a custom
        // dropdown) get the same behaviour.
        qs('a[data-synapse-form-link], [role="button"][data-synapse-form-link]').forEach(bindFormLink);

        // Auto-build custom dropdowns only for explicit opt-in.
        // Plain <select> elements use the browser default.
        // <select class="syn-select"> uses the chevron + brand styling (native).
        // <select data-synapse-dropdown> is fully replaced by the custom dropdown.
        qs('select[data-synapse-dropdown]').forEach(buildDropdown);

        // Auto-attach flatpickr to any input with .syn-datepicker class
        if (typeof flatpickr !== 'undefined') {
            qs('.syn-datepicker').forEach(input => {
                if (input._flatpickr) return; // already attached
                const isTime     = input.classList.contains('syn-datepicker--time');
                const isTimeOnly = input.classList.contains('syn-datepicker--time-only');
                const isRange    = input.classList.contains('syn-datepicker--range');

                const fp = flatpickr(input, {
                    enableTime: isTime || isTimeOnly,
                    enableSeconds: false,
                    time_24hr: true,
                    minuteIncrement: 5,
                    noCalendar: isTimeOnly,
                    dateFormat: isTime ? 'Y-m-d H:i'
                              : isTimeOnly ? 'H:i'
                              : 'Y-m-d',
                    mode: isRange ? 'range' : 'single',
                    // Date inputs: allow manual typing (with format hint)
                    // Time inputs: force picker use so users can't type letters
                    allowInput: !isTimeOnly,
                    disableMobile: true,
                    locale: { firstDayOfWeek: 1 },
                    // Native-overlay behaviour: the calendar is an
                    // absolutely-positioned popover appended to <body>
                    // (NOT inline with the input). This matches how a
                    // native <input type="date"> shows its picker — a
                    // system-level overlay that doesn't disturb the
                    // surrounding form layout.
                    //
                    // `appendTo: document.body` is critical: it escapes
                    // any ancestor with `overflow: hidden/auto` (our
                    // .page-content scroll container) and any
                    // stacking-context traps (dialog backdrops).
                    appendTo: document.body,
                    // Auto-close on scroll — native browser date pickers
                    // (Chrome, Safari, Firefox) all close their popups the
                    // moment the user scrolls because the input has moved
                    // out of its anchor position. Users find this
                    // predictable and muscle-memory friendly: scroll →
                    // picker dismisses, click input again to reopen.
                    // We listen on the nearest scrollable ancestor
                    // (.page-content) AND on the window, so both inner
                    // container scroll and full-page scroll dismiss the
                    // picker.
                    onOpen: function (_, __, instance) {
                        const scrollParent = instance.element.closest('.page-content');
                        const close = () => { if (instance.isOpen) instance.close(); };
                        if (instance._synScrollHandler) {
                            if (instance._synScrollParent) {
                                instance._synScrollParent.removeEventListener('scroll', instance._synScrollHandler);
                            }
                            window.removeEventListener('scroll', instance._synScrollHandler);
                        }
                        instance._synScrollHandler = close;
                        instance._synScrollParent = scrollParent;
                        if (scrollParent) scrollParent.addEventListener('scroll', close, { passive: true });
                        window.addEventListener('scroll', close, { passive: true });
                    },
                    onClose: function (_, __, instance) {
                        if (instance._synScrollHandler) {
                            if (instance._synScrollParent) {
                                instance._synScrollParent.removeEventListener('scroll', instance._synScrollHandler);
                            }
                            window.removeEventListener('scroll', instance._synScrollHandler);
                            instance._synScrollParent = null;
                            instance._synScrollHandler = null;
                        }
                    },
                    // Prevent letter input on time fields by using inputmode=number
                    // on the spinbutton elements that flatpickr creates
                    onReady: function (selectedDates, dateStr, instance) {
                        if (isTimeOnly) {
                            instance.element.setAttribute('inputmode', 'numeric');
                            instance.element.setAttribute('pattern', '[0-9]*');
                            // Lock the input to numeric characters only
                            instance.element.addEventListener('keypress', function (e) {
                                // Only allow digits and colon (auto-completed by picker)
                                if (!/[\d:]/.test(e.key) && !['Backspace','Delete','Tab','ArrowUp','ArrowDown','Enter'].includes(e.key)) {
                                    e.preventDefault();
                                }
                            });
                        } else {
                            instance.element.setAttribute('inputmode', 'numeric');
                        }
                    }
                });
                input._flatpickr = fp;
            });
        }
    }

    // ==========================================================================
    // EXPORTS
    // ==========================================================================
    global.synapse = global.synapse || {};
    global.synapse.toast = toast;
    global.synapse.dialog = {
        open: openDialog,
        close: closeDialog,
        confirm: confirm,
        alert: alert
    };
    global.synapse.spinner = {
        button: buttonLoading,
        page: pageLoader
    };
    global.synapse.skeleton = skeleton;
    global.synapse.dropdown = {
        build: buildDropdown
    };
    global.synapse.formDialog = {
        convert: formToDialog,
        bindLink: bindFormLink
    };
    global.synapse.escapeHtml = escapeHtml;

    // Re-run the auto-wirings on an arbitrary subtree. The SPA
    // navigation module calls this after swapping <main> content, so
    // newly-inserted nodes (data-synapse-* attrs, .syn-datepicker
    // inputs, form dialogs, etc.) get the same handlers their
    // initial-page-load counterparts received.
    global.synapse.rebind = function (root) {
        autoWire(root || document);
    };

    // Listen for the custom event the SPA nav module dispatches as
    // a fallback (in case synapse.rebind isn't available).
    document.addEventListener('synapse:content-replaced', function (e) {
        try { autoWire((e && e.detail && e.detail.root) || document); }
        catch (err) { /* ignore */ }
    });

    // Auto-wire on DOMContentLoaded
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', autoWire);
    } else {
        autoWire();
    }
})(window);
