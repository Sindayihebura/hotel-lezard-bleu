/**
 * admin.js — Scripts administration
 * Hôtel Le Lézard Bleu & Spa, Bujumbura, Burundi
 */
'use strict';

document.addEventListener('DOMContentLoaded', function () {

    // ── Sidebar mobile toggle ─────────────────────────────────────────
    const sidebar = document.querySelector('.admin-sidebar');
    const toggleBtn = document.getElementById('adminSidebarToggle');
    if (toggleBtn && sidebar) {
        toggleBtn.addEventListener('click', () => sidebar.classList.toggle('open'));
    }
    // Fermer sidebar en cliquant à l'extérieur (mobile)
    document.addEventListener('click', function (e) {
        if (sidebar && sidebar.classList.contains('open') && !sidebar.contains(e.target) && e.target !== toggleBtn) {
            sidebar.classList.remove('open');
        }
    });

    // ── Auto-dismiss alertes après 4s ────────────────────────────────
    document.querySelectorAll('[data-dismiss="auto"]').forEach(function (el) {
        setTimeout(() => el.style.display = 'none', 4000);
    });

    // ── Confirmer les actions destructives ───────────────────────────
    document.querySelectorAll('[data-confirm]').forEach(function (el) {
        el.addEventListener('click', function (e) {
            if (!confirm(this.dataset.confirm || 'Confirmer cette action ?')) {
                e.preventDefault();
                return false;
            }
        });
    });

    // ── Table de recherche instantanée (filtre côté client) ──────────
    const liveSearch = document.getElementById('liveSearch');
    if (liveSearch) {
        liveSearch.addEventListener('input', function () {
            const q = this.value.toLowerCase();
            document.querySelectorAll('.admin-table tbody tr').forEach(function (row) {
                row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
            });
        });
    }

    // ── KPI counter animation ─────────────────────────────────────────
    document.querySelectorAll('.kpi-value[data-count]').forEach(function (el) {
        const target = parseInt(el.dataset.count, 10);
        if (isNaN(target)) return;
        let current = 0;
        const step = Math.ceil(target / 30);
        const timer = setInterval(function () {
            current = Math.min(current + step, target);
            el.textContent = current.toLocaleString('fr-BI');
            if (current >= target) clearInterval(timer);
        }, 30);
    });

    // ── Copier référence au clipboard ────────────────────────────────
    document.querySelectorAll('[data-copy]').forEach(function (el) {
        el.style.cursor = 'pointer';
        el.title = 'Cliquer pour copier';
        el.addEventListener('click', function () {
            navigator.clipboard.writeText(this.dataset.copy).then(() => {
                const original = this.textContent;
                this.textContent = '✓ Copié !';
                setTimeout(() => this.textContent = original, 1500);
            });
        });
    });

    // ── Indiquer les liens actifs dans la sidebar ─────────────────────
    const currentPath = window.location.pathname;
    document.querySelectorAll('.sidebar-link').forEach(function (link) {
        if (link.getAttribute('href') && currentPath.startsWith(link.getAttribute('href').replace(/\/$/, ''))) {
            if (link.getAttribute('href') !== '/admin/') {
                link.classList.add('active');
            }
        }
    });
});
