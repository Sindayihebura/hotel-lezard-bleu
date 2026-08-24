<?php

declare(strict_types=1);

namespace App\Reports;

use PDO;
use App\Payments\CurrencyService;

/**
 * ReportService — Génération des rapports hôteliers
 * Hôtel Le Lézard Bleu & Spa, Bujumbura, Burundi
 */
class ReportService
{
    private PDO             $pdo;
    private CurrencyService $currency;

    public function __construct(PDO $pdo)
    {
        $this->pdo      = $pdo;
        $this->currency = new CurrencyService($pdo);
    }

    // ── Rapport occupation ────────────────────────────────────────────

    /**
     * Taux d'occupation par période.
     */
    public function getOccupancyReport(string $from, string $to): array
    {
        $totalRooms = (int)$this->pdo->query("SELECT COUNT(*) FROM rooms WHERE is_active = 1")->fetchColumn();
        if ($totalRooms === 0) {
            return ['total_rooms' => 0, 'occupied_nights' => 0, 'rate' => 0.0, 'by_room' => []];
        }

        // Nombre total de nuits disponibles dans la période
        $days = max(1, (int)(new \DateTimeImmutable($to))->diff(new \DateTimeImmutable($from))->days);
        $totalNightsAvailable = $totalRooms * $days;

        // Nuits occupées (réservations non annulées)
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) FROM bookings
            WHERE statut NOT IN ('cancelled','no_show')
              AND date_arrivee < :to
              AND date_depart  > :from
        ");
        $stmt->execute([':from' => $from, ':to' => $to]);
        $occupiedBookings = (int)$stmt->fetchColumn();

        // Détail par chambre
        $stmt = $this->pdo->prepare("
            SELECT r.id, r.name, r.room_number,
                   COUNT(b.id) AS nb_bookings,
                   COALESCE(SUM(b.nb_nights), 0) AS total_nights,
                   COALESCE(SUM(b.total_bif), 0) AS revenue_bif
            FROM rooms r
            LEFT JOIN bookings b ON b.room_id = r.id
                AND b.statut NOT IN ('cancelled','no_show')
                AND b.date_arrivee < :to
                AND b.date_depart  > :from
            WHERE r.is_active = 1
            GROUP BY r.id
            ORDER BY revenue_bif DESC
        ");
        $stmt->execute([':from' => $from, ':to' => $to]);
        $byRoom = $stmt->fetchAll();

        foreach ($byRoom as &$r) {
            $r['revenue_formatted'] = $this->currency->formatBif((int)$r['revenue_bif']);
            $r['occupancy_rate']    = $days > 0
                ? round((float)$r['total_nights'] / $days * 100, 1) : 0.0;
        }
        unset($r);

        return [
            'period'                  => ['from' => $from, 'to' => $to, 'days' => $days],
            'total_rooms'             => $totalRooms,
            'total_nights_available'  => $totalNightsAvailable,
            'occupied_bookings'       => $occupiedBookings,
            'occupancy_rate'          => round($occupiedBookings / max(1, $totalRooms * $days) * 100, 1),
            'by_room'                 => $byRoom,
        ];
    }

    // ── Rapport revenus ───────────────────────────────────────────────

    /**
     * Revenus par période, ventilés par mode de paiement.
     */
    public function getRevenueReport(string $from, string $to): array
    {
        // Total BIF
        $stmt = $this->pdo->prepare("
            SELECT COALESCE(SUM(b.total_bif), 0) AS total_bif,
                   COALESCE(SUM(b.total_usd_cents), 0) AS total_usd_cents,
                   COUNT(b.id) AS nb_bookings
            FROM bookings b
            WHERE b.payment_status = 'paid'
              AND b.date_arrivee BETWEEN :from AND :to
        ");
        $stmt->execute([':from' => $from, ':to' => $to]);
        $totals = $stmt->fetch();

        // Par mode de paiement
        $stmt = $this->pdo->prepare("
            SELECT p.provider, p.payment_method,
                   COUNT(p.id) AS nb_payments,
                   SUM(p.amount_bif) AS total_bif
            FROM payments p
            JOIN bookings b ON b.id = p.booking_id
            WHERE p.payment_status = 'successful'
              AND b.date_arrivee BETWEEN :from AND :to
            GROUP BY p.provider, p.payment_method
            ORDER BY total_bif DESC
        ");
        $stmt->execute([':from' => $from, ':to' => $to]);
        $byMethod = $stmt->fetchAll();

        foreach ($byMethod as &$m) {
            $m['total_formatted'] = $this->currency->formatBif((int)$m['total_bif']);
        }
        unset($m);

        // Par mois (si période > 1 mois)
        $stmt = $this->pdo->prepare("
            SELECT DATE_FORMAT(date_arrivee, '%Y-%m') AS month,
                   COUNT(id) AS nb_bookings,
                   SUM(total_bif) AS revenue_bif
            FROM bookings
            WHERE payment_status = 'paid'
              AND date_arrivee BETWEEN :from AND :to
            GROUP BY month
            ORDER BY month ASC
        ");
        $stmt->execute([':from' => $from, ':to' => $to]);
        $byMonth = $stmt->fetchAll();

        return [
            'period'         => ['from' => $from, 'to' => $to],
            'total_bif'      => (int)$totals['total_bif'],
            'total_formatted'=> $this->currency->formatBif((int)$totals['total_bif']),
            'total_usd'      => $this->currency->formatUsd((int)$totals['total_usd_cents']),
            'nb_bookings'    => (int)$totals['nb_bookings'],
            'by_method'      => $byMethod,
            'by_month'       => $byMonth,
        ];
    }

    // ── Rapport annulations ───────────────────────────────────────────

    public function getCancellationReport(string $from, string $to): array
    {
        $stmt = $this->pdo->prepare("
            SELECT b.*, r.name AS room_name
            FROM bookings b
            JOIN rooms r ON r.id = b.room_id
            WHERE b.statut = 'cancelled'
              AND b.cancelled_at BETWEEN :from AND :to_end
            ORDER BY b.cancelled_at DESC
            LIMIT 200
        ");
        $stmt->execute([':from' => $from . ' 00:00:00', ':to_end' => $to . ' 23:59:59']);
        $items = $stmt->fetchAll();

        $total = count($items);
        $revenueLost = array_sum(array_column($items, 'total_bif'));

        return [
            'period'              => ['from' => $from, 'to' => $to],
            'total_cancellations' => $total,
            'revenue_lost_bif'    => (int)$revenueLost,
            'revenue_lost_fmt'    => $this->currency->formatBif((int)$revenueLost),
            'items'               => $items,
        ];
    }

    // ── Rapport clients ───────────────────────────────────────────────

    public function getCustomerReport(): array
    {
        // Top pays d'origine
        $stmt = $this->pdo->query("
            SELECT COALESCE(country_code, 'BI') AS country,
                   COUNT(*) AS nb_customers
            FROM customers
            WHERE is_active = 1 AND is_guest = 0
            GROUP BY country
            ORDER BY nb_customers DESC
            LIMIT 10
        ");
        $byCountry = $stmt->fetchAll();

        // Nouveaux clients par mois (6 derniers mois)
        $stmt = $this->pdo->query("
            SELECT DATE_FORMAT(created_at, '%Y-%m') AS month,
                   COUNT(*) AS nb_new
            FROM customers
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
            GROUP BY month
            ORDER BY month ASC
        ");
        $newByMonth = $stmt->fetchAll();

        $total = (int)$this->pdo->query("SELECT COUNT(*) FROM customers WHERE is_active = 1")->fetchColumn();

        return [
            'total_customers' => $total,
            'by_country'      => $byCountry,
            'new_by_month'    => $newByMonth,
        ];
    }

    // ── Export CSV ────────────────────────────────────────────────────

    /**
     * Générer un CSV des réservations.
     * Limite à 5000 lignes (config security.php).
     */
    public function exportBookingsCsv(string $from, string $to): string
    {
        $stmt = $this->pdo->prepare("
            SELECT b.reference, b.guest_first_name, b.guest_last_name,
                   b.guest_email, b.guest_phone, b.guest_country,
                   b.date_arrivee, b.date_depart, b.nb_nights,
                   b.nb_adults, b.total_bif, b.currency_chosen,
                   b.payment_method, b.payment_status, b.statut,
                   r.name AS room_name,
                   b.created_at
            FROM bookings b
            JOIN rooms r ON r.id = b.room_id
            WHERE b.date_arrivee BETWEEN :from AND :to
            ORDER BY b.date_arrivee DESC
            LIMIT 5000
        ");
        $stmt->execute([':from' => $from, ':to' => $to]);
        $rows = $stmt->fetchAll();

        $output = fopen('php://temp', 'r+');
        // BOM UTF-8 pour Excel
        fwrite($output, "\xEF\xBB\xBF");
        // En-têtes
        fputcsv($output, [
            'Référence', 'Prénom', 'Nom', 'Email', 'Téléphone', 'Pays',
            'Arrivée', 'Départ', 'Nuits', 'Adultes',
            'Total (BIF)', 'Devise', 'Paiement', 'Statut Paiement', 'Statut Réservation',
            'Chambre', 'Créé le',
        ], ';');

        foreach ($rows as $row) {
            fputcsv($output, [
                $row['reference'],
                $row['guest_first_name'],
                $row['guest_last_name'],
                $row['guest_email'],
                $row['guest_phone'],
                $row['guest_country'] ?? '',
                $row['date_arrivee'],
                $row['date_depart'],
                $row['nb_nights'],
                $row['nb_adults'],
                $row['total_bif'],
                $row['currency_chosen'],
                $row['payment_method'],
                $row['payment_status'],
                $row['statut'],
                $row['room_name'],
                $row['created_at'],
            ], ';');
        }

        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);
        return $csv ?: '';
    }
}
