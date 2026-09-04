-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Feb 05, 2024 at 10:32 AM
-- Server version: 10.4.28-MariaDB
-- PHP Version: 8.2.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `sportee`
--

-- --------------------------------------------------------

--
-- Table structure for table `failed_jobs`
--

CREATE TABLE `failed_jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `indoors`
--

CREATE TABLE `indoors` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `title` varchar(255) NOT NULL,
  `tags` varchar(255) NOT NULL,
  `location` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `website` varchar(255) NOT NULL,
  `description` longtext NOT NULL,
  `contact_number` varchar(255) NOT NULL,
  `price` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `photo` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `indoors`
--

INSERT INTO `indoors` (`id`, `user_id`, `title`, `tags`, `location`, `email`, `website`, `description`, `contact_number`, `price`, `created_at`, `updated_at`, `photo`) VALUES
(20, 2, 'ViTech Sports Pavillion Badminton Courts', 'Badminton', 'Bokundara Rd, Pannipitiya', 'ViTech@gmail.com', 'www.ViTech.com', 'Whether you\'re a casual player or a seasoned athlete, our badminton court offers an adaptable space. Tailored for singles or doubles play, it accommodates every player\'s style and skill level.', '0775252711', '3000.00', '2024-01-07 06:30:17', '2024-01-07 06:30:17', 'photos/Ndh62Pw8JKMo1I09MCOspzfJAb7ZmXirfQ10GQcs.jpg'),
(22, 2, 'JD indoor cricket court', 'Cricket', 'Carmel Mawatha, Wattala', 'JDindoor@gmail.com', 'www.JD.com', 'Beyond the court, we prioritize your comfort. Avail yourself of amenities like changing rooms for a quick refresh, ensuring your badminton experience is not only intense but also convenient.', '0778122277', '3000.00', '2024-01-07 06:35:40', '2024-01-07 06:35:40', 'photos/GEqmfy9MZze3ocVMy1KQ2uPTlxESarlYFbrqazb5.jpg'),
(23, 2, 'Shuttle Power Badminton Court', 'Badminton', '136 Poorwarama Rd, Colombo 006', 'ShuttlePower@yahoo.com', 'www.shuttlePower.com', 'Experience the thrill of badminton at rates that outshine the competition. Our commitment to excellence ensures a top-notch badminton court without compromising your budget', '0779694299', '4000.00', '2024-01-07 06:39:03', '2024-01-07 06:39:03', 'photos/cZq0Fwym2O767lIwI3Rs7NT7hikAeyXV4GDBvLWC.jpg'),
(24, 2, 'X Sports Arena - Futsal & Gym', 'Futsal, Cricket', '184 Central Rd, Colombo 015', 'XSportsArena@gmail.com', 'www.XSportsArena.com', 'We go beyond providing a futsal court; we deliver an experience. Immerse yourself in a space where skill meets affordability, and amenities cater to your every need, creating a memorable journey beyond the pitch', '0701705599', '3500.00', '2024-01-07 06:41:37', '2024-01-07 06:41:37', 'photos/NCAZ20ewIvwEt67zB25V5BjuFkEM5QQUep0BYKBz.jpg'),
(25, 2, 'Excel Futsal Arena', 'Futsal, Cricket', 'Excel World Entertainment Park', 'ExcelFutsalArena@gmail.com', 'www.ExcelFutsalArena.com', 'Immerse yourself in the perfect futsal setting with a total length of 72 feet and a width of 42 feet. Specifically designed for 5-a-side games, these dimensions offer an ideal playing field where every player\'s performance can shine.', '0778122254', '4000.00', '2024-01-07 06:44:05', '2024-01-07 06:44:05', 'photos/2D1FkpYDT45pCKlKadICd8go0Q7K0v51NxE2ZuSU.jpg'),
(26, 5, 'Futsal Park', 'Futsal, Cricket, Badminton', 'Maligawatha, Colombo 010', 'Futsalpark@gmail.com', 'www.futsalpark.com', 'While the futsal court is designed for 5-a-side games, its adaptability ensures a dynamic experience catering to the unique styles and preferences of every player. The field becomes a canvas for individual brilliance and team dynamics', '0701705511', '2500.00', '2024-01-07 06:47:21', '2024-01-18 00:45:27', 'photos/ehQDZRCzef6kzd6M9gPzOAvzzhX9tBP4O5udLzEJ.jpg'),
(31, 5, 'Wembley Sports Indoor', 'Futsal, Cricket', 'Dehiwala-Mount Lavinia', 'wembley@gmail.com', 'www.Wembley.com', 'lorem', '077 82888 14', '3500.00', '2024-01-18 05:42:16', '2024-01-18 05:42:42', 'photos/x6trjvwwTDcPURRSyoSsXoXXPFsIOffDqRbw4YVR.jpg'),
(32, 8, 'Etihad Indoor', 'Football', '160 Central Rd, Colombo 015', 'etihad@gmail.com', 'www.etihad.com', 'The City of Manchester Stadium, known as the Etihad Stadium for sponsorship reasons, is the home of Premier League club Manchester City, with a domestic football capacity of 53,400', '0778122245', '5000.00', '2024-01-30 00:14:43', '2024-01-30 00:15:12', 'photos/RljgPfPuyXoNGkNptoRBIRlUm1WY3HvyrjstMgvi.webp'),
(33, 5, 'CampNou Indoor Club', 'Futsal', 'Colombo 10', 'CampNou@gmail.com', 'www.CampNou.com', 'Camp Nou is an indoor haven that transcends traditional expectations. With its vast expanse of modern architecture and cutting-edge facilities, it stands as a testament to the fusion of innovation and sporting passion. Entering Camp Nou is like stepping into a world where every detail is meticulously designed to create an immersive experience.', '0112441036', '4000.00', '2024-02-05 01:55:38', '2024-02-05 02:32:29', 'photos/iC4CO0tLVFYiVbafxqj2pWudF5ttRvOen4YzaXU5.jpg');

-- --------------------------------------------------------

--
-- Table structure for table `migrations`
--

CREATE TABLE `migrations` (
  `id` int(10) UNSIGNED NOT NULL,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `migrations`
--

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
(89, '2014_10_12_000000_create_users_table', 1),
(90, '2014_10_12_100000_create_password_reset_tokens_table', 1),
(91, '2019_08_19_000000_create_failed_jobs_table', 1),
(92, '2019_12_14_000001_create_personal_access_tokens_table', 1),
(93, '2023_12_15_090200_create_indoors_table', 1),
(94, '2024_01_03_132133_create_tournament_table', 1),
(95, '2024_01_11_073559_add_role_id_to_users_table', 2);

-- --------------------------------------------------------

--
-- Table structure for table `password_reset_tokens`
--

CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `personal_access_tokens`
--

CREATE TABLE `personal_access_tokens` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tokenable_type` varchar(255) NOT NULL,
  `tokenable_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `token` varchar(64) NOT NULL,
  `abilities` text DEFAULT NULL,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tournaments`
--

CREATE TABLE `tournaments` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `title` varchar(255) NOT NULL,
  `noOFplayers` varchar(255) NOT NULL,
  `location` varchar(255) NOT NULL,
  `description` longtext NOT NULL,
  `tournamentDate` date NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tournaments`
--

INSERT INTO `tournaments` (`id`, `title`, `noOFplayers`, `location`, `description`, `tournamentDate`, `created_at`, `updated_at`) VALUES
(2, 'Jolly Boys Tournament', 'Five Vs Five', 'Ciel Indoor', 'Gather your squad and dive into the heart of competition. Our 5v5 futsal tournament is the arena where teams showcase their prowess, aiming for glory in a dynamic setting where every pass, goal, and save counts.', '2024-02-02', NULL, NULL),
(3, 'RCL', 'Six vs six', 'Colombo Futsal Club', 'Brace yourself for the pinnacle of futsal excitement as teams clash in a 6v6 competition. The fast-paced action and strategic maneuvers promise an electrifying spectacle where skill, teamwork, and intensity converge', '2024-01-31', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `role_id` tinyint(4) NOT NULL DEFAULT 2
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `email_verified_at`, `password`, `remember_token`, `created_at`, `updated_at`, `role_id`) VALUES
(2, 'admin', 'admin@admin.com', NULL, '$2y$12$3cXXtxTosxEkHpwHcXzRCOxY7lvK/sVYgkFI7lwfmLcvIqlYey8TC', NULL, '2024-01-05 14:13:02', '2024-01-31 23:15:30', 1),
(3, 'Zephr Pittman', 'cikaxohy@mailinator.com', NULL, '$2y$12$2WGrtSSKB9OQY3aSm93Emey8VVZuxqsWFGPZpB4i09mxDXFhrCXDy', NULL, '2024-01-07 09:01:48', '2024-01-30 00:10:47', 2),
(5, 'Miller', 'Miller@gmail.com', NULL, '$2y$12$r0QgwwFRiBMWYAJ.qlJ6DeOuMjtJ.t7qzi9nwtMJ8kpjjXhIS7FYS', NULL, '2024-01-18 05:40:10', '2024-01-18 05:40:10', 2),
(7, 'Ranjan', 'ranjan@gmail.com', NULL, '$2y$12$5kBp8i7rQzW2cg65fBx6neZp0L3Vx3FhKvKRdozMeFJhmjchbHSCK', NULL, '2024-01-25 04:09:48', '2024-01-25 04:10:26', 4),
(8, 'Ajith', 'ajith@gmail.com', NULL, '$2y$12$b.TeOgZ/XdMf7tVnOl5dreh6RH3AKoA78kXVs0aGwtFiMOcBKP.vm', NULL, '2024-01-30 00:12:16', '2024-02-05 01:13:56', 2);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`);

--
-- Indexes for table `indoors`
--
ALTER TABLE `indoors`
  ADD PRIMARY KEY (`id`),
  ADD KEY `indoors_user_id_foreign` (`user_id`);

--
-- Indexes for table `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD PRIMARY KEY (`email`);

--
-- Indexes for table `personal_access_tokens`
--
ALTER TABLE `personal_access_tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  ADD KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`);

--
-- Indexes for table `tournaments`
--
ALTER TABLE `tournaments`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `users_email_unique` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `indoors`
--
ALTER TABLE `indoors`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=34;

--
-- AUTO_INCREMENT for table `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=96;

--
-- AUTO_INCREMENT for table `personal_access_tokens`
--
ALTER TABLE `personal_access_tokens`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tournaments`
--
ALTER TABLE `tournaments`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `indoors`
--
ALTER TABLE `indoors`
  ADD CONSTRAINT `indoors_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
