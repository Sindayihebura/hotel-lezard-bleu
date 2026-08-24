-- ====================================================================
-- Trigger bookings — À exécuter via CLI MySQL ou phpMyAdmin UNIQUEMENT
-- NE PAS exécuter via PDO (DELIMITER non supporté par PDO)
-- ====================================================================
-- Commande CLI : mysql -u root -p hotel_lezardbleu < migrations/trigger_bookings.sql

DELIMITER $$

DROP TRIGGER IF EXISTS `trg_bookings_check_dates`$$

CREATE TRIGGER `trg_bookings_check_dates`
BEFORE INSERT ON `bookings`
FOR EACH ROW
BEGIN
  IF NEW.date_depart <= NEW.date_arrivee THEN
    SIGNAL SQLSTATE '45000'
    SET MESSAGE_TEXT = 'La date de depart doit etre posterieure a la date d arrivee.';
  END IF;
END$$

DELIMITER ;
