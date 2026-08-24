<?php

declare(strict_types=1);

/**
 * Administration — Tableau de bord principal
 * Hôtel Le Lézard Bleu & Spa — Bujumbura, Burundi
 */

require_once dirname(__DIR__) . '/config/bootstrap.php';
require_once dirname(__DIR__) . '/config/database.php';

use App\Auth\AdminAuth;
use App\Security\Logger;

$pdo = getDB();

// ── Authentification obligatoire ──────────────────────────────────────────
$adminAuth = new AdminAuth($pdo);
if (!$adminAuth->check()) {
    safe_redirect('/admin/login.php');
}

$adminUser = $adminAuth->user();
$logger    = new Logger($pdo);

// ── Métriques du jour ─────────────────────────────────────────────────────
$metrics = [
    'arrivals_today'    => 0,
    'departures_today'  => 0,
    'occupied_rooms'    => 0,
    'total_rooms'       => 0,
    'revenue_today_bif' => 0,
    'pending_payments'  => 0,
];

if ($pdo !== null) {
    try {
        $today = date('Y-m-d');

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE date_arrivee = :today AND statut NOT IN ('cancelled','no_show')");
        $stmt->execute([':today' => $today]);
        $metrics['arrivals_today'] = (int) $stmt->fetchColumn();

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE date_depart = :today AND statut = 'checked_in'");
        $stmt->execute([':today' => $today]);
        $metrics['departures_today'] = (int) $stmt->fetchColumn();

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE date_arrivee <= :today AND date_depart > :today2 AND statut = 'checked_in'");
        $stmt->execute([':today' => $today, ':today2' => $today]);
        $metrics['occupied_rooms'] = (int) $stmt->fetchColumn();

        $stmt = $pdo->query("SELECT COUNT(*) FROM rooms WHERE is_active = 1");
        $metrics['total_rooms'] = (int) $stmt->fetchColumn();

        $stmt = $pdo->prepare("SELECT COALESCE(SUM(amount_bif), 0) FROM payments WHERE payment_status = 'successful' AND DATE(created_at) = :today");
        $stmt->execute([':today' => $today]);
        $metrics['revenue_today_bif'] = (int) $stmt->fetchColumn();

        $stmt = $pdo->query("SELECT COUNT(*) FROM payments WHERE payment_status IN ('pending_customer', 'initiated', 'processing')");
        $metrics['pending_payments'] = (int) $stmt->fetchColumn();
    } catch (\PDOException $e) {
        $logger->error('Dashboard metrics error', ['error' => $e->getMessage()]);
    }
}

$occupancyRate = $metrics['total_rooms'] > 0
    ? round(($metrics['occupied_rooms'] / $metrics['total_rooms']) * 100, 1)
    : 0;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de Bord — Administration Le Lézard Bleu</title>
    <meta name="robots" content="noindex, nofollow">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body class="admin-body">

    <!-- Sidebar Admin -->
    <?php require_once __DIR__ . '/includes/sidebar.php'; ?>

    <main class="admin-main">
        <!-- Header -->
        <header class="admin-header">
            <h1 class="admin-page-title">Tableau de Bord</h1>
            <div class="admin-header-right">
                <span class="admin-date"><?php echo format_date_fr(date('Y-m-d')); ?></span>
                <span class="admin-user">
                    👤 <?php echo e($adminUser['first_name'] ?? 'Admin'); ?>
                    <span class="admin-role-badge"><?php echo e($adminUser['role'] ?? ''); ?></span>
                </span>
                <a href="/admin/logout.php" class="btn-admin-sm">Déconnexion</a>
            </div>
        </header>

        <!-- KPI Cards ────────────────────────────────────────────────── -->
        <section class="admin-kpi-grid">

            <div class="kpi-card">
                <div class="kpi-icon">🛬</div>
                <div class="kpi-value"><?php echo $metrics['arrivals_today']; ?></div>
                <div class="kpi-label">Arrivées Aujourd'hui</div>
            </div>

            <div class="kpi-card">
                <div class="kpi-icon">🛫</div>
                <div class="kpi-value"><?php echo $metrics['departures_today']; ?></div>
                <div class="kpi-label">Départs Aujourd'hui</div>
            </div>

            <div class="kpi-card">
                <div class="kpi-icon">🏨</div>
                <div class="kpi-value"><?php echo $metrics['occupied_rooms']; ?>/<?php echo $metrics['total_rooms']; ?></div>
                <div class="kpi-label">Occupation (<?php echo $occupancyRate; ?>%)</div>
            </div>

            <div class="kpi-card kpi-gold">
                <div class="kpi-icon">💰</div>
                <div class="kpi-value"><?php echo format_bif($metrics['revenue_today_bif']); ?></div>
                <div class="kpi-label">Revenus du Jour</div>
            </div>

            <div class="kpi-card <?php echo $metrics['pending_payments'] > 0 ? 'kpi-warning' : ''; ?>">
                <div class="kpi-icon">⏳</div>
                <div class="kpi-value"><?php echo $metrics['pending_payments']; ?></div>
                <div class="kpi-label">Paiements en Attente</div>
            </div>

        </section>

        <!-- Liens rapides ────────────────────────────────────────────── -->
        <section class="admin-quick-actions">
            <h2 class="admin-section-title">Actions Rapides</h2>
            <div class="admin-action-grid">
                <a href="/admin/reservations/" class="action-card">
                    <span class="action-icon">📋</span>
                    <span>Toutes les Réservations</span>
                </a>
                <a href="/admin/reservations/create.php" class="action-card">
                    <span class="action-icon">➕</span>
                    <span>Nouvelle Réservation</span>
                </a>
                <a href="/admin/payments/" class="action-card">
                    <span class="action-icon">💳</span>
                    <span>Paiements</span>
                </a>
                <a href="/admin/rooms/" class="action-card">
                    <span class="action-icon">🛏️</span>
                    <span>Chambres</span>
                </a>
                <a href="/admin/customers/" class="action-card">
                    <span class="action-icon">👥</span>
                    <span>Clients</span>
                </a>
                <a href="/admin/reports/" class="action-card">
                    <span class="action-icon">📊</span>
                    <span>Rapports</span>
                </a>
                <a href="/admin/settings/" class="action-card">
                    <span class="action-icon">⚙️</span>
                    <span>Paramètres</span>
                </a>
                <a href="/admin/audit-logs/" class="action-card">
                    <span class="action-icon">🔍</span>
                    <span>Logs d'Audit</span>
                </a>
            </div>
        </section>

    </main>

    <script src="../assets/js/admin.js"></script>
</body>
</html>
