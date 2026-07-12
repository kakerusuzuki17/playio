-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- ホスト: 127.0.0.1
-- 生成日時: 2026-07-12 11:26:32
-- サーバのバージョン： 10.4.32-MariaDB
-- PHP のバージョン: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- データベース: `playio`
--

-- --------------------------------------------------------

--
-- テーブルの構造 `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- テーブルのデータのダンプ `categories`
--

INSERT INTO `categories` (`id`, `name`) VALUES
(4, 'スクリーンショット'),
(2, 'タイムアタック'),
(1, 'ハイスコア'),
(3, '作ったもの'),
(5, '動画'),
(6, '雑談');

-- --------------------------------------------------------

--
-- テーブルの構造 `cleartime`
--

CREATE TABLE `cleartime` (
  `id` int(11) NOT NULL,
  `post_id` int(11) NOT NULL,
  `time_ms` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- テーブルのデータのダンプ `cleartime`
--

INSERT INTO `cleartime` (`id`, `post_id`, `time_ms`) VALUES
(1, 36, 5025678),
(2, 43, 132061),
(9, 51, 128710),
(10, 52, 137060);

-- --------------------------------------------------------

--
-- テーブルの構造 `favorite_games`
--

CREATE TABLE `favorite_games` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `game_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- テーブルのデータのダンプ `favorite_games`
--

INSERT INTO `favorite_games` (`id`, `user_id`, `game_id`, `created_at`) VALUES
(2, 3, 18, '2026-07-11 09:42:59'),
(4, 3, 16, '2026-07-12 05:54:36');

-- --------------------------------------------------------

--
-- テーブルの構造 `games`
--

CREATE TABLE `games` (
  `id` int(11) NOT NULL,
  `igdb_id` int(11) NOT NULL,
  `name` text NOT NULL,
  `cover` text NOT NULL,
  `genres` text NOT NULL,
  `release_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- テーブルのデータのダンプ `games`
--

INSERT INTO `games` (`id`, `igdb_id`, `name`, `cover`, `genres`, `release_date`) VALUES
(1, 37441, 'Hanjuku Hero: Aa Sekai yo Hanjuku Nare', '', '', NULL),
(2, 7060, 'Lucha Libre AAA: Héroes del Ring', 'https://images.igdb.com/igdb/image/upload/t_cover_big/co5tvj.jpg', 'Fighting,Sport', NULL),
(3, 367759, 'AA Soldiers', 'https://images.igdb.com/igdb/image/upload/t_cover_big/coah9t.jpg', 'Indie', NULL),
(4, 87003, 'Aa', 'https://images.igdb.com/igdb/image/upload/t_cover_big/co8p7d.jpg', 'Puzzle,Strategy,Arcade', NULL),
(5, 112429, 'Super Robot Taisen DD', 'https://images.igdb.com/igdb/image/upload/t_cover_big/co1rms.jpg', 'Role-playing (RPG),Simulator,Turn-based strategy (TBS)', NULL),
(6, 245238, '20 em 1: Game 20', 'https://images.igdb.com/igdb/image/upload/t_cover_big/coa97w.jpg', '', NULL),
(7, 104999, 'Monster Hunter Frontier ZZenith', 'https://images.igdb.com/igdb/image/upload/t_cover_big/co406u.jpg', 'Hack and slash/Beat \'em up', NULL),
(8, 176072, 'AAA Clock', 'https://images.igdb.com/igdb/image/upload/t_cover_big/co407x.jpg', 'Platform,Simulator', NULL),
(9, 49121, 'Aa Harimanada', 'https://images.igdb.com/igdb/image/upload/t_cover_big/co6yi0.jpg', 'Fighting,Sport', NULL),
(10, 258872, 'Digital Ange: Dennou Tenshi SS', 'https://images.igdb.com/igdb/image/upload/t_cover_big/co6uif.jpg', 'Adventure,Visual Novel', NULL),
(11, 251671, 'The Legend of Heroes: Trails into Reverie - SSS Summer Splash Set', 'https://images.igdb.com/igdb/image/upload/t_cover_big/co6px3.jpg', 'Role-playing (RPG),Turn-based strategy (TBS)', NULL),
(12, 191692, 'Street Fighter 6', 'https://images.igdb.com/igdb/image/upload/t_cover_big/co9wxo.jpg', 'Fighting,Arcade', NULL),
(13, 338616, 'Mario Kart Tour: Mario Bros. Tour', 'https://images.igdb.com/igdb/image/upload/t_cover_big/co9mlw.jpg', 'Racing', NULL),
(14, 1090, 'Donkey Kong Country', 'https://images.igdb.com/igdb/image/upload/t_cover_big/co70qn.jpg', 'Platform', NULL),
(15, 90101, 'Super Smash Bros. Ultimate', 'https://images.igdb.com/igdb/image/upload/t_cover_big/co2255.jpg', 'Fighting, Platform', NULL),
(16, 338105, 'Kirby Air Riders', 'https://images.igdb.com/igdb/image/upload/t_cover_big/coaauz.jpg', 'Racing, Adventure, Arcade', NULL),
(17, 9927, 'Persona 5', 'https://images.igdb.com/igdb/image/upload/t_cover_big/co1r76.jpg', 'Role-playing (RPG), Adventure', NULL),
(18, 36550, 'Yakuza: Like a Dragon', 'https://images.igdb.com/igdb/image/upload/t_cover_big/co2em9.jpg', 'Role-playing (RPG), Adventure', NULL);

-- --------------------------------------------------------

--
-- テーブルの構造 `highscore`
--

CREATE TABLE `highscore` (
  `id` int(11) NOT NULL,
  `post_id` int(11) NOT NULL,
  `score` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- テーブルの構造 `likes`
--

CREATE TABLE `likes` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `post_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- テーブルのデータのダンプ `likes`
--

INSERT INTO `likes` (`id`, `user_id`, `post_id`, `created_at`) VALUES
(2, 3, 3, '2026-07-08 05:58:16'),
(3, 3, 2, '2026-07-08 05:58:17'),
(50, 3, 4, '2026-07-08 06:54:04'),
(58, 3, 5, '2026-07-08 07:05:15'),
(61, 3, 7, '2026-07-08 07:29:41'),
(63, 3, 10, '2026-07-08 07:42:35');

-- --------------------------------------------------------

--
-- テーブルの構造 `posts`
--

CREATE TABLE `posts` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `content` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `igdb_id` int(11) DEFAULT NULL,
  `reply_post_id` int(11) DEFAULT NULL,
  `game_id` int(11) DEFAULT NULL,
  `category_id` int(11) DEFAULT NULL,
  `spoiler` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- テーブルのデータのダンプ `posts`
--

INSERT INTO `posts` (`id`, `user_id`, `content`, `created_at`, `igdb_id`, `reply_post_id`, `game_id`, `category_id`, `spoiler`) VALUES
(1, 1, 'あああ', '2026-07-08 03:18:22', NULL, NULL, NULL, NULL, 0),
(2, 1, 'あああ', '2026-07-08 03:19:28', NULL, NULL, NULL, NULL, 0),
(3, 1, 'ｓｓｓ', '2026-07-08 03:19:31', NULL, NULL, NULL, NULL, 0),
(4, 3, 'あさっさあｓ', '2026-07-08 03:36:19', NULL, NULL, NULL, NULL, 0),
(5, 3, 'あああ', '2026-07-08 06:21:19', NULL, NULL, NULL, NULL, 0),
(7, 3, 'あ', '2026-07-08 07:21:41', NULL, NULL, NULL, NULL, 0),
(8, 3, 'アカウント', '2026-07-08 07:21:45', NULL, NULL, NULL, NULL, 0),
(9, 3, 'あああ', '2026-07-08 07:40:27', NULL, NULL, NULL, NULL, 0),
(10, 3, 'あああ', '2026-07-08 07:42:33', NULL, NULL, NULL, NULL, 0),
(11, 3, 'あああ', '2026-07-08 07:45:17', NULL, NULL, NULL, NULL, 0),
(12, 3, 'あああ', '2026-07-08 07:45:35', NULL, NULL, NULL, NULL, 0),
(13, 3, 'aaaa', '2026-07-08 07:46:39', NULL, NULL, NULL, NULL, 0),
(14, 3, 'asaaa', '2026-07-08 07:46:47', NULL, NULL, NULL, NULL, 0),
(15, 3, 'aaaaa', '2026-07-08 08:01:42', NULL, NULL, NULL, NULL, 0),
(16, 3, 'a', '2026-07-08 08:01:49', NULL, NULL, NULL, NULL, 0),
(17, 3, 'asasa', '2026-07-08 08:02:01', NULL, NULL, NULL, NULL, 0),
(18, 3, 'asasaasasass', '2026-07-08 08:25:50', NULL, NULL, NULL, NULL, 0),
(19, 3, 'aaa\r\n', '2026-07-09 07:14:36', NULL, NULL, NULL, NULL, 0),
(20, 3, 'aaa\r\n', '2026-07-09 07:17:34', NULL, NULL, NULL, NULL, 0),
(21, 3, 'aaa\r\n', '2026-07-09 07:18:35', NULL, NULL, NULL, NULL, 0),
(22, 3, 'asasasaaasass', '2026-07-09 07:29:51', 37441, NULL, NULL, NULL, 0),
(23, 3, 'asasaasasaszzz', '2026-07-09 07:37:30', 2, NULL, NULL, NULL, 0),
(24, 3, 'asasaasa', '2026-07-09 08:14:53', 3, NULL, NULL, NULL, 0),
(25, 3, 'assasaazzzxzxxzxxz', '2026-07-09 08:36:19', 4, NULL, NULL, NULL, 0),
(26, 3, 'asaasasad', '2026-07-09 08:50:18', NULL, NULL, 5, NULL, 0),
(27, 3, 'sdsd', '2026-07-09 08:50:29', NULL, NULL, 6, NULL, 0),
(28, 3, 'test', '2026-07-09 12:32:03', NULL, NULL, 7, NULL, 0),
(29, 3, 'あｓｓ', '2026-07-09 12:34:49', NULL, NULL, 8, NULL, 0),
(30, 3, '11', '2026-07-10 07:52:02', NULL, NULL, 7, 4, 0),
(31, 3, 'sss', '2026-07-10 07:53:09', NULL, NULL, 9, 4, 0),
(32, 3, 'aa', '2026-07-10 07:53:20', NULL, NULL, 10, 3, 0),
(33, 3, 'dffs', '2026-07-10 07:54:35', NULL, NULL, 11, 5, 0),
(34, 3, 'ffgdddg', '2026-07-10 07:55:57', NULL, NULL, 1, 4, 0),
(35, 3, 'あさ', '2026-07-10 07:59:17', NULL, NULL, 1, 5, 1),
(36, 3, 'dsdsddsddsf', '2026-07-10 08:22:58', NULL, NULL, 9, 2, 0),
(38, 3, 'aasaasasassaa', '2026-07-10 08:52:04', NULL, NULL, 12, 4, 0),
(39, 3, 'sssds', '2026-07-10 09:07:52', NULL, NULL, 13, 4, 0),
(40, 3, 'aaaaa', '2026-07-10 09:10:58', NULL, NULL, 14, 4, 0),
(41, 3, 'ttest', '2026-07-10 11:40:51', NULL, NULL, 12, 6, 0),
(42, 3, 'za', '2026-07-11 05:00:39', NULL, NULL, 12, 6, 1),
(43, 3, 'test', '2026-07-11 05:18:43', NULL, NULL, 15, 2, 0),
(51, 4, '新記録出た！', '2026-07-12 08:01:32', NULL, NULL, 16, 2, 0),
(52, 3, 'タイムアタック', '2026-07-12 08:03:22', NULL, NULL, 16, 2, 0);

-- --------------------------------------------------------

--
-- テーブルの構造 `post_media`
--

CREATE TABLE `post_media` (
  `id` int(11) NOT NULL,
  `post_id` int(11) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_type` enum('image','video') NOT NULL,
  `display_order` tinyint(4) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- テーブルのデータのダンプ `post_media`
--

INSERT INTO `post_media` (`id`, `post_id`, `file_name`, `file_type`, `display_order`, `created_at`) VALUES
(4, 38, '6a50b2b4a5a67_20250420232124_1.jpg', 'image', 1, '2026-07-10 08:52:04'),
(5, 38, '6a50b2b4a733e_20250420232957_1.jpg', 'image', 2, '2026-07-10 08:52:04'),
(6, 38, '6a50b2b4a82a7_20250421224240_1.jpg', 'image', 3, '2026-07-10 08:52:04'),
(7, 39, '6a50b66877e78_2025061518182800_c.jpg', 'image', 1, '2026-07-10 09:07:52'),
(8, 40, '6a50b722a971d_2025081712040100_c.jpg', 'image', 1, '2026-07-10 09:10:58'),
(9, 40, '6a50b722aa054_2025081721194100_c.jpg', 'image', 2, '2026-07-10 09:10:58'),
(10, 40, '6a50b722aaeeb_2025090922295600_c.jpg', 'image', 3, '2026-07-10 09:10:58'),
(11, 40, '6a50b722ab9c2_2025090922432200_c.jpg', 'image', 4, '2026-07-10 09:10:58'),
(12, 43, '6a51d23374f47_IMG_3119.JPG', 'image', 1, '2026-07-11 05:18:43'),
(20, 51, '6a5349dce9270_IMG_3123.JPG', 'image', 1, '2026-07-12 08:01:32'),
(21, 52, '6a534a4ab1545_IMG_3120.JPG', 'image', 1, '2026-07-12 08:03:22');

-- --------------------------------------------------------

--
-- テーブルの構造 `post_tags`
--

CREATE TABLE `post_tags` (
  `id` int(11) NOT NULL,
  `post_id` int(11) NOT NULL,
  `tag_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- テーブルのデータのダンプ `post_tags`
--

INSERT INTO `post_tags` (`id`, `post_id`, `tag_id`, `created_at`) VALUES
(1, 29, 1, '2026-07-09 12:34:49'),
(2, 41, 2, '2026-07-10 11:40:51'),
(3, 41, 3, '2026-07-10 11:40:51'),
(8, 51, 4, '2026-07-12 08:01:32'),
(9, 51, 7, '2026-07-12 08:01:32'),
(10, 51, 8, '2026-07-12 08:01:32'),
(11, 52, 4, '2026-07-12 08:03:22'),
(12, 52, 9, '2026-07-12 08:03:22'),
(13, 52, 10, '2026-07-12 08:03:22');

-- --------------------------------------------------------

--
-- テーブルの構造 `replies`
--

CREATE TABLE `replies` (
  `id` int(11) NOT NULL,
  `post_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `content` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- テーブルの構造 `tags`
--

CREATE TABLE `tags` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- テーブルのデータのダンプ `tags`
--

INSERT INTO `tags` (`id`, `name`, `created_at`) VALUES
(1, 'タイムアタック', '2026-07-09 12:34:11'),
(2, 'test', '2026-07-10 11:40:51'),
(3, 'ice', '2026-07-10 11:40:51'),
(4, 'ギャラクティック・ノヴァ', '2026-07-12 08:00:00'),
(5, 'ファイル', '2026-07-12 08:00:32'),
(6, 'ハイスコア', '2026-07-12 08:00:32'),
(7, 'リック', '2026-07-12 08:01:32'),
(8, 'ウイリーバイク', '2026-07-12 08:01:32'),
(9, 'ドロッチェ', '2026-07-12 08:03:22'),
(10, 'ウイリースクーター', '2026-07-12 08:03:22');

-- --------------------------------------------------------

--
-- テーブルの構造 `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `user_name` varchar(30) NOT NULL,
  `account_id` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- テーブルのデータのダンプ `users`
--

INSERT INTO `users` (`id`, `user_name`, `account_id`, `password`, `created_at`) VALUES
(1, 'テスト', 'AAAAA', '$2y$10$cWgQCSGqYx5pnct.uz5eFe9wFK.v0Uy8zaN6bLfeVE82VomXNe9TK', '2026-07-08 03:09:50'),
(3, 'テスト', 'test', '$2y$10$GKLl2jmvTrNWK5NdB2QJb.VWewyebuHJ.7SMLipacEno9zYgokD5G', '2026-07-08 03:36:15'),
(4, 'User', 'user', '$2y$10$qvpJe5QXAFT49thvL.FJiuQE6cHriHTXPc4YxdtJps05xCi5maVlG', '2026-07-12 07:50:56');

--
-- ダンプしたテーブルのインデックス
--

--
-- テーブルのインデックス `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- テーブルのインデックス `cleartime`
--
ALTER TABLE `cleartime`
  ADD PRIMARY KEY (`id`);

--
-- テーブルのインデックス `favorite_games`
--
ALTER TABLE `favorite_games`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_favorite` (`user_id`,`game_id`);

--
-- テーブルのインデックス `games`
--
ALTER TABLE `games`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `igdb_id` (`igdb_id`);

--
-- テーブルのインデックス `highscore`
--
ALTER TABLE `highscore`
  ADD PRIMARY KEY (`id`);

--
-- テーブルのインデックス `likes`
--
ALTER TABLE `likes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`,`post_id`),
  ADD KEY `post_id` (`post_id`);

--
-- テーブルのインデックス `posts`
--
ALTER TABLE `posts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- テーブルのインデックス `post_media`
--
ALTER TABLE `post_media`
  ADD PRIMARY KEY (`id`),
  ADD KEY `post_id` (`post_id`);

--
-- テーブルのインデックス `post_tags`
--
ALTER TABLE `post_tags`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_post_tag` (`post_id`,`tag_id`);

--
-- テーブルのインデックス `replies`
--
ALTER TABLE `replies`
  ADD PRIMARY KEY (`id`),
  ADD KEY `post_id` (`post_id`),
  ADD KEY `user_id` (`user_id`);

--
-- テーブルのインデックス `tags`
--
ALTER TABLE `tags`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- テーブルのインデックス `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `account_Id` (`account_id`) USING BTREE;

--
-- ダンプしたテーブルの AUTO_INCREMENT
--

--
-- テーブルの AUTO_INCREMENT `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- テーブルの AUTO_INCREMENT `cleartime`
--
ALTER TABLE `cleartime`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- テーブルの AUTO_INCREMENT `favorite_games`
--
ALTER TABLE `favorite_games`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- テーブルの AUTO_INCREMENT `games`
--
ALTER TABLE `games`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- テーブルの AUTO_INCREMENT `highscore`
--
ALTER TABLE `highscore`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- テーブルの AUTO_INCREMENT `likes`
--
ALTER TABLE `likes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=69;

--
-- テーブルの AUTO_INCREMENT `posts`
--
ALTER TABLE `posts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=54;

--
-- テーブルの AUTO_INCREMENT `post_media`
--
ALTER TABLE `post_media`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- テーブルの AUTO_INCREMENT `post_tags`
--
ALTER TABLE `post_tags`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- テーブルの AUTO_INCREMENT `replies`
--
ALTER TABLE `replies`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- テーブルの AUTO_INCREMENT `tags`
--
ALTER TABLE `tags`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- テーブルの AUTO_INCREMENT `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- ダンプしたテーブルの制約
--

--
-- テーブルの制約 `likes`
--
ALTER TABLE `likes`
  ADD CONSTRAINT `likes_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `likes_ibfk_2` FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`);

--
-- テーブルの制約 `posts`
--
ALTER TABLE `posts`
  ADD CONSTRAINT `posts_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- テーブルの制約 `post_media`
--
ALTER TABLE `post_media`
  ADD CONSTRAINT `post_media_ibfk_1` FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`);

--
-- テーブルの制約 `replies`
--
ALTER TABLE `replies`
  ADD CONSTRAINT `replies_ibfk_1` FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `replies_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
