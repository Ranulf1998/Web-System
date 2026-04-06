<dialog id="tenant-logout-confirm-modal" class="w-full max-w-md rounded-xl border border-slate-200 p-0 backdrop:bg-slate-900/50">
    <div class="rounded-xl bg-white p-6">
        <h3 class="text-base font-semibold text-slate-900">Confirm Logout</h3>
        <p class="mt-2 text-sm text-slate-600">Are you sure you want to log out?</p>

        <div class="mt-5 flex items-center justify-end gap-2">
            <button type="button" data-tenant-logout-cancel class="rounded-md border border-slate-300 px-3 py-2 text-sm text-slate-700 hover:bg-slate-50">Cancel</button>
            <button type="button" data-tenant-logout-confirm class="rounded-md bg-[color:var(--brand-primary)] px-3 py-2 text-sm font-medium text-white hover:opacity-90">Log Out</button>
        </div>
    </div>
</dialog>

<script>
    (() => {
        const logoutModal = document.getElementById('tenant-logout-confirm-modal');
        const cancelButton = document.querySelector('[data-tenant-logout-cancel]');
        const confirmButton = document.querySelector('[data-tenant-logout-confirm]');

        if (!logoutModal || !cancelButton || !confirmButton) {
            return;
        }

        let pendingForm = null;

        window.BrewCloudTenantLogoutConfirm = {
            open(formElement) {
                if (formElement instanceof HTMLFormElement) {
                    pendingForm = formElement;
                } else if (formElement instanceof HTMLElement) {
                    pendingForm = formElement.closest('form');
                } else {
                    pendingForm = document.querySelector('form[action*="logout"]');
                }

                if (pendingForm) {
                    logoutModal.showModal();
                }
            },
        };

        cancelButton.addEventListener('click', () => {
            pendingForm = null;
            logoutModal.close();
        });

        confirmButton.addEventListener('click', () => {
            if (pendingForm) {
                pendingForm.submit();
            }
        });

        logoutModal.addEventListener('click', (event) => {
            const rect = logoutModal.getBoundingClientRect();
            const inDialog = rect.top <= event.clientY && event.clientY <= rect.top + rect.height && rect.left <= event.clientX && event.clientX <= rect.left + rect.width;

            if (!inDialog) {
                pendingForm = null;
                logoutModal.close();
            }
        });
    })();
</script>
