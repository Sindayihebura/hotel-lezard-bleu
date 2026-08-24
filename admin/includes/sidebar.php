<?php
/**
 * Sidebar navigation — Administration Le Lézard Bleu
 * Vérifie les permissions pour chaque lien.
 */
if (!isset($adminUser)) {
    $adminUser = [];
}
$currentPage = basename($_SERVER['PHP_SELF']);
$currentDir  = basename(dirname($_SERVER['PHP_SELF']));
?>
<aside class="admin-sidebar">
    <div class="sidebar-brand">
        <div class="sidebar-brand-icon">L</div>
        <div>
            <div class="sidebar-brand-name">LÉZARD BLEU</div>
            <div class="sidebar-brand-sub">Administration</div>
        </div>
    </div>

    <nav class="sidebar-nav">

        <a href="/admin/dashboard.php" class="sidebar-link <?php echo $currentPage === 'dashboard.php' ? 'active' : ''; ?>">
            <span class="sidebar-icon">📊</span> Tableau de Bord
        </a>

        <div class="sidebar-section-label">RÉSERVATIONS</div>
        <a href="/admin/reservations/" class="sidebar-link <?php echo $currentDir === 'reservations' ? 'active' : ''; ?>">
            <span class="sidebar-icon">📋</span> Réservations
        </a>
        <a href="/admin/planning/" class="sidebar-link <?php echo $currentDir === 'planning' ? 'active' : ''; ?>">
            <span class="sidebar-icon">📅</span> Planning Hôtelier
        </a>
        <a href="/admin/reservations/create.php" class="sidebar-link <?php echo $currentPage === 'create.php' ? 'active' : ''; ?>">
            <span class="sidebar-icon">➕</span> Nouvelle Réservation
        </a>
        <a href="/admin/rooms/" class="sidebar-link <?php echo $currentDir === 'rooms' ? 'active' : ''; ?>">
            <span class="sidebar-icon">🛏️</span> Chambres & Suites
        </a>
        <a href="/admin/services/" class="sidebar-link <?php echo $currentDir === 'services' ? 'active' : ''; ?>">
            <span class="sidebar-icon">🍽️</span> Services
        </a>
        <a href="/admin/maintenance/" class="sidebar-link <?php echo $currentDir === 'maintenance' ? 'active' : ''; ?>">
            <span class="sidebar-icon">🔧</span> Maintenance
        </a>

        <div class="sidebar-section-label">CLIENTS & PAIEMENTS</div>
        <a href="/admin/customers/" class="sidebar-link <?php echo $currentDir === 'customers' ? 'active' : ''; ?>">
            <span class="sidebar-icon">👥</span> Clients
        </a>
        <a href="/admin/payments/" class="sidebar-link <?php echo $currentDir === 'payments' ? 'active' : ''; ?>">
            <span class="sidebar-icon">💳</span> Paiements
        </a>
        <a href="/admin/offers/" class="sidebar-link <?php echo $currentDir === 'offers' ? 'active' : ''; ?>">
            <span class="sidebar-icon">🎁</span> Offres & Codes Promo
        </a>

        <div class="sidebar-section-label">CONTENU</div>
        <a href="/admin/reviews/" class="sidebar-link <?php echo $currentDir === 'reviews' ? 'active' : ''; ?>">
            <span class="sidebar-icon">⭐</span> Avis Clients
        </a>

        <div class="sidebar-section-label">ANALYSES</div>
        <a href="/admin/reports/" class="sidebar-link <?php echo $currentDir === 'reports' ? 'active' : ''; ?>">
            <span class="sidebar-icon">📈</span> Rapports
        </a>
        <a href="/admin/audit-logs/" class="sidebar-link <?php echo $currentDir === 'audit-logs' ? 'active' : ''; ?>">
            <span class="sidebar-icon">🔍</span> Logs d'Audit
        </a>

        <div class="sidebar-section-label">SYSTÈME</div>
        <a href="/admin/users/" class="sidebar-link <?php echo $currentDir === 'users' ? 'active' : ''; ?>">
            <span class="sidebar-icon">🔐</span> Utilisateurs & Rôles
        </a>
        <a href="/admin/settings/" class="sidebar-link <?php echo $currentDir === 'settings' ? 'active' : ''; ?>">
            <span class="sidebar-icon">⚙️</span> Paramètres
        </a>

        <div class="sidebar-footer">
            <a href="/admin/logout.php" class="sidebar-link sidebar-logout">
                <span class="sidebar-icon">🚪</span> Déconnexion
            </a>
        </div>
    </nav>
</aside>
