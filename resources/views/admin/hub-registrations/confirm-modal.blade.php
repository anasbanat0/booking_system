<div id="registration-action-modal" class="fixed inset-0 z-[90] hidden items-center justify-center bg-slate-950/60 p-4" data-registration-confirm-modal aria-hidden="true">
    <button type="button" class="absolute inset-0 cursor-default" data-registration-modal-close aria-label="Close confirmation"></button>
    <div class="relative w-full max-w-md rounded-lg bg-white p-6 shadow-2xl" role="dialog" aria-modal="true" aria-labelledby="registration-action-title">
        <div data-registration-modal-icon class="flex h-11 w-11 items-center justify-center rounded-full bg-emerald-100 text-xl font-black text-emerald-700">?</div>
        <h2 id="registration-action-title" data-registration-modal-title class="mt-4 text-xl font-bold text-slate-950">Confirm action</h2>
        <p data-registration-modal-message class="mt-2 text-sm leading-6 text-slate-600"></p>
        <div class="mt-6 flex justify-end gap-2">
            <button type="button" data-registration-modal-close class="h-10 rounded-md border border-slate-300 px-4 text-sm font-bold text-slate-700 hover:bg-slate-50">Cancel</button>
            <button type="button" data-registration-modal-confirm class="h-10 rounded-md bg-emerald-700 px-4 text-sm font-bold text-white hover:bg-emerald-800">Confirm</button>
        </div>
    </div>
</div>

<script>
(() => {
    const modal = document.querySelector('[data-registration-confirm-modal]');
    if (!modal) return;

    const title = modal.querySelector('[data-registration-modal-title]');
    const message = modal.querySelector('[data-registration-modal-message]');
    const icon = modal.querySelector('[data-registration-modal-icon]');
    const confirmButton = modal.querySelector('[data-registration-modal-confirm]');
    let targetForm = null;

    const close = () => {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        modal.setAttribute('aria-hidden', 'true');
        targetForm = null;
    };

    document.querySelectorAll('[data-registration-confirm]').forEach(button => {
        button.addEventListener('click', () => {
            targetForm = document.getElementById(button.dataset.formId);
            const requiresSelection = button.dataset.requiresSelection === 'true';
            const hasSelection = !requiresSelection || targetForm?.querySelector('input[name="registration_ids[]"]:checked');

            title.textContent = hasSelection ? button.dataset.title : 'No applications selected';
            message.textContent = hasSelection ? button.dataset.message : 'Select at least one application, then choose Accept selected.';
            confirmButton.textContent = button.dataset.confirmLabel || 'Confirm';
            const isReject = button.dataset.tone === 'danger';
            confirmButton.className = `h-10 rounded-md px-4 text-sm font-bold text-white ${isReject ? 'bg-rose-700 hover:bg-rose-800' : 'bg-emerald-700 hover:bg-emerald-800'}`;
            confirmButton.classList.toggle('hidden', !hasSelection);
            icon.className = `flex h-11 w-11 items-center justify-center rounded-full text-xl font-black ${isReject ? 'bg-rose-100 text-rose-700' : 'bg-emerald-100 text-emerald-700'}`;
            icon.textContent = isReject ? '!' : '?';
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            modal.setAttribute('aria-hidden', 'false');
            confirmButton.focus();
        });
    });

    modal.querySelectorAll('[data-registration-modal-close]').forEach(button => button.addEventListener('click', close));
    confirmButton.addEventListener('click', () => targetForm?.requestSubmit());
    document.addEventListener('keydown', event => {
        if (event.key === 'Escape' && !modal.classList.contains('hidden')) close();
    });
})();
</script>
