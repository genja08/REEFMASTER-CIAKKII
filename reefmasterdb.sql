-- --------------------------------------------------------
-- Host:                         127.0.0.1
-- Server version:               8.0.30 - MySQL Community Server - GPL
-- Server OS:                    Win64
-- HeidiSQL Version:             12.1.0.6537
-- --------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

-- Dumping data for table reefmaster.akkiis: ~0 rows (approximately)
REPLACE INTO `akkiis` (`id`, `cites_document_id`, `customer_id`, `nomor_cites`, `company_address`, `country`, `office_phone`, `email`, `contact_person`, `tujuan`, `mobile_phone`, `airport_of_arrival`, `tanggal_terbit`, `tanggal_expired`, `tanggal_ekspor`, `no_awb`, `no_aju`, `no_pendaftaran`, `tanggal_pendaftaran`, `created_at`, `updated_at`) VALUES
	(2, 1, 1, '15314', 'ARGENTINA', 'ARGENTINA', '+54 11 485 96032', 'info@cgda.com.ar', 'Mr. Agustin Villanucci', 'ARGEN', '+54 9 11 5702-9727', 'Buenos Aires', '2025-01-01', '2025-01-12', '2026-03-03', '12345', '54321', '23145', '2026-01-01', '2025-05-27 20:37:05', '2025-05-27 20:37:05');

-- Dumping data for table reefmaster.akkii_items: ~1 rows (approximately)
REPLACE INTO `akkii_items` (`id`, `akkii_id`, `product_id`, `qty_cites`, `qty_sisa`, `qty_realisasi`, `keterangan`, `created_at`, `updated_at`) VALUES
	(2, 2, 1, 1, 1, NULL, NULL, '2025-05-27 20:37:05', '2025-05-27 20:37:05');

-- Dumping data for table reefmaster.cache: ~2 rows (approximately)
REPLACE INTO `cache` (`key`, `value`, `expiration`) VALUES
	('laravel_cache_livewire-rate-limiter:a17961fa74e9275d529f489537f179c05d50c2f3', 'i:1;', 1748587520),
	('laravel_cache_livewire-rate-limiter:a17961fa74e9275d529f489537f179c05d50c2f3:timer', 'i:1748587520;', 1748587520);

-- Dumping data for table reefmaster.cache_locks: ~0 rows (approximately)

-- Dumping data for table reefmaster.cites_documents: ~0 rows (approximately)
REPLACE INTO `cites_documents` (`id`, `nomor`, `issued_date`, `expired_date`, `airport_of_arrival`, `customer_id`, `created_at`, `updated_at`) VALUES
	(1, '15314', '2025-01-01', '2025-01-12', 'Buenos Aires', 1, '2025-05-27 17:20:11', '2025-05-27 17:20:11');

-- Dumping data for table reefmaster.cites_items: ~0 rows (approximately)
REPLACE INTO `cites_items` (`id`, `cites_document_id`, `product_id`, `product_name`, `qty_cites`, `created_at`, `updated_at`) VALUES
	(1, 1, 1, 'Acanthastrea sp.', 2, '2025-05-27 17:20:11', '2025-05-27 17:20:11');

-- Dumping data for table reefmaster.customer_discounts: ~0 rows (approximately)
REPLACE INTO `customer_discounts` (`id`, `customer_id`, `jenis_discount`, `discount`, `created_at`, `updated_at`) VALUES
	(1, 1, 'HARD CORAL', '10%', '2025-05-27 17:19:32', '2025-05-27 17:19:32');

-- Dumping data for table reefmaster.data_customers: ~0 rows (approximately)
REPLACE INTO `data_customers` (`id`, `company_name`, `company_address`, `office_phone`, `contact_person`, `mobile_phone`, `country`, `email`, `tujuan`, `airport_of_arrival`, `created_at`, `updated_at`) VALUES
	(1, 'GREGORIO CARRERAS', 'ARGENTINA', '+54 11 485 96032', 'Mr. Agustin Villanucci', '+54 9 11 5702-9727', 'ARGENTINA', 'info@cgda.com.ar', 'ARGEN', 'Buenos Aires', '2025-05-27 17:19:32', '2025-05-27 17:19:32');

-- Dumping data for table reefmaster.failed_jobs: ~0 rows (approximately)

-- Dumping data for table reefmaster.jobs: ~0 rows (approximately)

-- Dumping data for table reefmaster.job_batches: ~0 rows (approximately)

-- Dumping data for table reefmaster.migrations: ~9 rows (approximately)
REPLACE INTO `migrations` (`id`, `migration`, `batch`) VALUES
	(1, '0001_01_01_000000_create_users_table', 1),
	(2, '0001_01_01_000001_create_cache_table', 1),
	(3, '0001_01_01_000002_create_jobs_table', 1),
	(4, '2025_05_27_074918_create_data_customers_table', 1),
	(5, '2025_05_27_080000_create_products_table', 1),
	(6, '2025_05_27_083035_create_customer_discounts_table', 1),
	(7, '2025_05_27_090630_create_cites_documents_table', 1),
	(8, '2025_05_27_090857_create_cites_items_table', 1),
	(9, '2025_05_27_213710_add_coral_fields_to_products_table', 1),
	(10, '2025_05_27_234423_add_unique_index_to_cites_documents_table', 1),
	(11, '2025_05_27_235006_add_unique_index_to_products_table', 1),
	(12, '2025_05_28_001049_create_akkiis_table', 1),
	(13, '2025_05_28_001104_create_akkii_items_table', 1),
	(14, '2025_05_28_004617_add_qty_cites_to_products_table', 2),
	(15, '2025_05_29_042004_add_qty_realisasi_and_keterangan_to_akkii_items_table', 3);

-- Dumping data for table reefmaster.password_reset_tokens: ~0 rows (approximately)

-- Dumping data for table reefmaster.products: ~1 rows (approximately)
REPLACE INTO `products` (`id`, `jenis_coral`, `nama_latin`, `nama_lokal`, `qty_cites`, `created_at`, `updated_at`) VALUES
	(1, 'Hard Coral', 'Acanthastrea sp.', 'Kr.Acanthastrea', 0, '2025-05-27 17:18:55', '2025-05-29 06:59:00');

-- Dumping data for table reefmaster.sessions: ~2 rows (approximately)
REPLACE INTO `sessions` (`id`, `user_id`, `ip_address`, `user_agent`, `payload`, `last_activity`) VALUES
	('RuFwedMHyTliyxX1GXd0uzq1aaXEcNz5pt7jgUdX', 1, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', 'YTo2OntzOjY6Il90b2tlbiI7czo0MDoiMUdXVHpQSU40U1J2enBQME5aazZqVUVVVFhteGJLeVR3djZ6MFhlNiI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6NDk6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMC9hZG1pbi9kYXRhLWN1c3RvbWVycy9jcmVhdGUiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX1zOjM6InVybCI7YTowOnt9czo1MDoibG9naW5fd2ViXzU5YmEzNmFkZGMyYjJmOTQwMTU4MGYwMTRjN2Y1OGVhNGUzMDk4OWQiO2k6MTtzOjE3OiJwYXNzd29yZF9oYXNoX3dlYiI7czo2MDoiJDJ5JDEyJGZiSGk4VjhBUG5QLjlsV1Foc2ZkUGVQaGlha2lSM1FJMDAzRThtdzdiMW5uelpQWmtnTnVpIjt9', 1748587643),
	('vjtazpK4HQJG0omI09YK14QciYOxxoMdI5VJzmUI', 1, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', 'YTo3OntzOjY6Il90b2tlbiI7czo0MDoiUWNsZXE2bGxXOUcwZFVBenhwdjNhaFl3NmY4OU1kTTY5MVYzS2k5QiI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6Mzk6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMC9hZG1pbi9hLWstay1pLWktcyI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fXM6MzoidXJsIjthOjA6e31zOjUwOiJsb2dpbl93ZWJfNTliYTM2YWRkYzJiMmY5NDAxNTgwZjAxNGM3ZjU4ZWE0ZTMwOTg5ZCI7aToxO3M6MTc6InBhc3N3b3JkX2hhc2hfd2ViIjtzOjYwOiIkMnkkMTIkZmJIaThWOEFQblAuOWxXUWhzZmRQZVBoaWFraVIzUUkwMDNFOG13N2Ixbm56WlBaa2dOdWkiO3M6ODoiZmlsYW1lbnQiO2E6MDp7fX0=', 1748501981);

-- Dumping data for table reefmaster.users: ~0 rows (approximately)
REPLACE INTO `users` (`id`, `name`, `email`, `email_verified_at`, `password`, `remember_token`, `created_at`, `updated_at`) VALUES
	(1, 'genja', 'genja@gmail.com', NULL, '$2y$12$fbHi8V8APnP.9lWQhsfdPePhiakiR3QI003E8mw7b1nnzZPZkgNui', NULL, '2025-05-27 17:18:13', '2025-05-27 17:18:13');

/*!40103 SET TIME_ZONE=IFNULL(@OLD_TIME_ZONE, 'system') */;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;
