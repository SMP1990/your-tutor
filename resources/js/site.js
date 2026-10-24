// Small page behaviours. Each one only runs when its elements are on the page.
// Kept in one file (no inline scripts) so the Content-Security-Policy can block inline JavaScript.

function mobileMenu() {
    const btn = document.querySelector('[data-menu-toggle]');
    const nav = document.querySelector('[data-mobile-nav]');
    if (!btn || !nav) return;

    btn.addEventListener('click', () => {
        const opening = nav.hasAttribute('hidden');
        nav.toggleAttribute('hidden', !opening);
        btn.setAttribute('aria-expanded', opening ? 'true' : 'false');
        btn.setAttribute('aria-label', opening ? 'Close menu' : 'Open menu');
    });
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && !nav.hasAttribute('hidden')) {
            btn.click();
            btn.focus();
        }
    });
}

function reviewSlider() {
    const track = document.querySelector('[data-slider]');
    if (!track) return;
    const items = track.children;
    const dots = document.querySelectorAll('[data-slider-dots] .slider-dots__dot');
    const step = () => (items.length > 1 ? items[1].offsetLeft - items[0].offsetLeft : track.clientWidth);

    document.querySelector('[data-slider-prev]').addEventListener('click', () => track.scrollBy({ left: -step(), behavior: 'smooth' }));
    document.querySelector('[data-slider-next]').addEventListener('click', () => track.scrollBy({ left: step(), behavior: 'smooth' }));
    track.addEventListener('scroll', () => {
        const i = Math.round(track.scrollLeft / step());
        dots.forEach((d, n) => d.classList.toggle('is-active', n === i));
    }, { passive: true });
}

function filtersSheet() {
    const panel = document.querySelector('[data-filters]');
    const backdrop = document.querySelector('.sheet-backdrop');
    const openBtn = document.querySelector('[data-filters-open]');
    if (!panel || !openBtn) return;

    const setOpen = (open) => {
        panel.classList.toggle('is-open', open);
        backdrop.hidden = !open;
        document.body.classList.toggle('no-scroll', open);
        openBtn.setAttribute('aria-expanded', open ? 'true' : 'false');
        (open ? panel.querySelector('input, button') : openBtn).focus();
    };

    openBtn.addEventListener('click', () => setOpen(true));
    document.querySelectorAll('[data-filters-close]').forEach((el) => el.addEventListener('click', () => setOpen(false)));
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && panel.classList.contains('is-open')) setOpen(false);
    });
}

function demoFormStudentField() {
    const field = document.querySelector('[data-student-field]');
    const radios = document.querySelectorAll('[data-for-toggle]');
    if (!field || !radios.length) return;

    const sync = () => {
        const child = document.querySelector('[data-for-toggle]:checked').value === 'child';
        field.hidden = !child;
        field.querySelector('input').required = child;
    };
    radios.forEach((r) => r.addEventListener('change', sync));
    sync();
}

function registerRoleFromUrl() {
    // ?role=tutor preselects the role (also works on the static preview).
    const role = new URLSearchParams(location.search).get('role');
    const input = role && document.querySelector(`[data-role][value="${CSS.escape(role)}"]`);
    if (input) input.checked = true;
}

mobileMenu();
reviewSlider();
filtersSheet();
demoFormStudentField();
registerRoleFromUrl();

// Forms with data-confirm="Question?" ask before submitting (e.g. removing a child).
document.querySelectorAll('form[data-confirm]').forEach((form) => {
    form.addEventListener('submit', (e) => {
        if (!window.confirm(form.dataset.confirm)) e.preventDefault();
    });
});

// Submit buttons with data-confirm-button="Question?" ask first (e.g. rejecting a tutor).
document.querySelectorAll('button[data-confirm-button]').forEach((button) => {
    button.addEventListener('click', (e) => {
        if (!window.confirm(button.dataset.confirmButton)) e.preventDefault();
    });
});
