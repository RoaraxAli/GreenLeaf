-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 23, 2025 at 11:47 AM
-- Server version: 10.4.27-MariaDB
-- PHP Version: 8.2.0

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `greenleaf_db`
--

DELIMITER $$
--
-- Procedures
--
CREATE DEFINER=`root`@`localhost` PROCEDURE `GetPlantRecommendations` (IN `user_id_param` INT, IN `limit_param` INT)   BEGIN
    SELECT DISTINCT p.*
    FROM plants p
    LEFT JOIN user_garden ug ON p.plant_id = ug.plant_id AND ug.user_id = user_id_param
    LEFT JOIN reviews r ON p.plant_id = r.plant_id
    WHERE ug.plant_id IS NULL 
    AND p.is_active = TRUE
    AND p.stock_quantity > 0
    ORDER BY COALESCE(AVG(r.rating), 0) DESC, p.created_at DESC
    LIMIT limit_param;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `UpdatePlantStock` (IN `order_id_param` INT)   BEGIN
    DECLARE done INT DEFAULT FALSE;
    DECLARE plant_id_var INT;
    DECLARE quantity_var INT;
    
    DECLARE cur CURSOR FOR 
        SELECT plant_id, quantity 
        FROM order_items 
        WHERE order_id = order_id_param;
    
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;
    
    OPEN cur;
    
    read_loop: LOOP
        FETCH cur INTO plant_id_var, quantity_var;
        IF done THEN
            LEAVE read_loop;
        END IF;
        
        UPDATE plants 
        SET stock_quantity = stock_quantity - quantity_var 
        WHERE plant_id = plant_id_var;
    END LOOP;
    
    CLOSE cur;
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `blogs`
--

CREATE TABLE `blogs` (
  `blog_id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `content` text NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `author_id` int(11) DEFAULT NULL,
  `category` varchar(50) DEFAULT 'General',
  `is_published` tinyint(1) DEFAULT 1,
  `views` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `blogs`
--

INSERT INTO `blogs` (`blog_id`, `title`, `content`, `image`, `author_id`, `category`, `is_published`, `views`, `created_at`, `updated_at`) VALUES
(1, '10 Best Indoor Plants for Beginners', 'Starting your plant journey can be overwhelming, but these 10 plants are perfect for beginners. From the nearly indestructible Snake Plant to the forgiving Pothos, these green companions will help you build confidence in plant care.\r\n\r\n**1. Snake Plant (Sansevieria)**\r\nThe snake plant is virtually indestructible. It tolerates low light, infrequent watering, and neglect. Perfect for busy lifestyles or dark corners.\r\n\r\n**2. Pothos**\r\nThis trailing vine grows quickly and forgives mistakes. It can thrive in water or soil and tolerates various light conditions.\r\n\r\n**3. ZZ Plant**\r\nWith glossy leaves and low maintenance needs, the ZZ plant is perfect for offices and low-light areas.\r\n\r\n**4. Spider Plant**\r\nEasy to grow and produces baby plants, making it great for sharing with friends.\r\n\r\n**5. Peace Lily**\r\nBeautiful white flowers and tells you when it needs water by drooping.\r\n\r\nRemember, the key to success is starting simple and learning as you grow your collection!', '/placeholder.svg?height=400&width=600', 1, 'Beginner Tips', 1, 245, '2025-08-23 08:37:40', '2025-08-23 08:37:40'),
(2, 'The Science of Air-Purifying Plants', 'Did you know that certain plants can actually clean the air in your home? NASA\'s Clean Air Study identified several plants that remove common household toxins.\r\n\r\n**Top Air-Purifying Plants:**\r\n- Snake Plant: Removes formaldehyde and benzene\r\n- Peace Lily: Filters ammonia, benzene, and formaldehyde\r\n- Spider Plant: Removes formaldehyde and xylene\r\n- Rubber Plant: Eliminates formaldehyde\r\n\r\n**How It Works:**\r\nPlants absorb gases through their leaves and roots, breaking down toxins and releasing clean oxygen. The microorganisms in the soil also play a crucial role in this process.\r\n\r\n**Placement Tips:**\r\nFor maximum benefit, place one medium-sized plant per 100 square feet of living space. Bedrooms benefit greatly from air-purifying plants as they continue working while you sleep.', '/placeholder.svg?height=400&width=600', 1, 'Plant Science', 1, 189, '2025-08-23 08:37:40', '2025-08-23 08:37:40'),
(3, 'Seasonal Plant Care: Spring Preparation', 'Spring is the perfect time to refresh your plant care routine and prepare your green friends for their growing season.\r\n\r\n**Spring Plant Care Checklist:**\r\n\r\n**Repotting:**\r\nCheck if plants are root-bound and need larger pots. Spring is the ideal time for repotting as plants enter their active growing phase.\r\n\r\n**Fertilizing:**\r\nResume regular fertilizing after the winter dormancy period. Use a balanced, diluted fertilizer every 2-4 weeks.\r\n\r\n**Pruning:**\r\nRemove dead, damaged, or yellowing leaves. Prune leggy growth to encourage bushier plants.\r\n\r\n**Pest Check:**\r\nInspect plants for pests that may have developed during winter. Common spring pests include aphids, spider mites, and scale insects.\r\n\r\n**Watering Adjustment:**\r\nAs days get longer and temperatures rise, plants will need more frequent watering. Check soil moisture regularly.\r\n\r\n**Light Considerations:**\r\nMove plants closer to windows or outdoors (gradually) to take advantage of increased daylight hours.', '/placeholder.svg?height=400&width=600', 1, 'Seasonal Care', 1, 156, '2025-08-23 08:37:40', '2025-08-23 08:37:40'),
(4, 'Creating Your First Indoor Garden', 'Transform your living space into a green oasis with these tips for creating your first indoor garden.\r\n\r\n**Planning Your Space:**\r\nStart by assessing your available light. South-facing windows get the most light, while north-facing windows receive the least. Match plants to your light conditions for success.\r\n\r\n**Choosing Your Plants:**\r\nBegin with 3-5 plants of varying heights and textures. Mix trailing plants, upright growers, and bushy varieties for visual interest.\r\n\r\n**Essential Supplies:**\r\n- Quality potting soil\r\n- Pots with drainage holes\r\n- Watering can with narrow spout\r\n- Plant food/fertilizer\r\n- Pruning shears\r\n\r\n**Design Tips:**\r\nGroup plants in odd numbers and vary heights using plant stands or hanging planters. Consider the mature size of plants when arranging.\r\n\r\n**Maintenance Schedule:**\r\nCreate a weekly routine: check soil moisture, rotate plants for even growth, and inspect for pests or problems.\r\n\r\nRemember, every expert was once a beginner. Start small, learn from mistakes, and enjoy the journey!', '/placeholder.svg?height=400&width=600', 1, 'Indoor Gardening', 1, 203, '2025-08-23 08:37:40', '2025-08-23 08:37:40');

-- --------------------------------------------------------

--
-- Table structure for table `cart`
--

CREATE TABLE `cart` (
  `cart_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `plant_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `added_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cart`
--

INSERT INTO `cart` (`cart_id`, `user_id`, `plant_id`, `quantity`, `added_at`) VALUES
(1, 2, 8, 1, '2025-08-23 08:37:40'),
(2, 2, 11, 2, '2025-08-23 08:37:40'),
(3, 3, 1, 1, '2025-08-23 08:37:40'),
(4, 4, 9, 1, '2025-08-23 08:37:40'),
(5, 5, 12, 3, '2025-08-23 08:37:40');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `order_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `order_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `total_amount` decimal(10,2) NOT NULL,
  `status` enum('pending','confirmed','shipped','delivered','cancelled') DEFAULT 'pending',
  `shipping_address` text DEFAULT NULL,
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`order_id`, `user_id`, `order_date`, `total_amount`, `status`, `shipping_address`, `notes`) VALUES
(1, 2, '2025-08-23 08:37:40', '75.98', 'delivered', '456 Garden Lane, Plant City', NULL),
(2, 3, '2025-08-23 08:37:40', '129.97', 'shipped', '789 Green Avenue, Leaf Town', NULL),
(3, 4, '2025-08-23 08:37:40', '45.99', 'confirmed', '321 Nature Road, Garden City', NULL),
(4, 5, '2025-08-23 08:37:40', '89.99', 'pending', '654 Botanical Street, Flora City', NULL),
(5, 6, '2025-08-23 08:52:15', '28.98', 'pending', 'Prob karachi', ''),
(6, 6, '2025-08-23 09:43:54', '21.99', 'pending', 'ABC road', '');

--
-- Triggers `orders`
--
DELIMITER $$
CREATE TRIGGER `update_stock_on_order_confirm` AFTER UPDATE ON `orders` FOR EACH ROW BEGIN
    IF NEW.status = 'confirmed' AND OLD.status = 'pending' THEN
        CALL UpdatePlantStock(NEW.order_id);
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `order_item_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `plant_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`order_item_id`, `order_id`, `plant_id`, `quantity`, `price`) VALUES
(1, 1, 1, 1, '29.99'),
(2, 1, 3, 2, '19.99'),
(3, 1, 6, 1, '27.99'),
(4, 2, 2, 1, '45.99'),
(5, 2, 4, 1, '89.99'),
(6, 3, 5, 1, '34.99'),
(7, 3, 7, 1, '16.99'),
(8, 4, 4, 1, '89.99'),
(9, 5, 11, 1, '18.99'),
(10, 6, 20, 1, '12.00');

-- --------------------------------------------------------

--
-- Table structure for table `plants`
--

CREATE TABLE `plants` (
  `plant_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `botanical_name` varchar(100) DEFAULT NULL,
  `category` enum('Indoor','Outdoor','Flowering','Air-Purifying','Seasonal') NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `care_level` enum('Easy','Medium','Hard') DEFAULT 'Medium',
  `light_requirement` enum('Low','Medium','High','Direct') NOT NULL,
  `watering_schedule` varchar(50) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `image` varchar(255) DEFAULT 'default-plant.jpg',
  `stock_quantity` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `plants`
--

INSERT INTO `plants` (`plant_id`, `name`, `botanical_name`, `category`, `price`, `care_level`, `light_requirement`, `watering_schedule`, `description`, `image`, `stock_quantity`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Snake Plant', 'Sansevieria trifasciata', 'Indoor', '29.99', 'Easy', 'Low', 'Every 2-3 weeks', 'Perfect for beginners! This hardy plant tolerates low light and infrequent watering. Great for bedrooms and offices.', '/placeholder.svg?height=300&width=300', 25, 1, '2025-08-23 08:37:40', '2025-08-23 08:37:40'),
(2, 'Monstera Deliciosa', 'Monstera deliciosa', 'Indoor', '45.99', 'Medium', 'Medium', 'Weekly', 'The Instagram-famous plant with beautiful split leaves. Loves bright, indirect light and regular watering.', '/placeholder.svg?height=300&width=300', 15, 1, '2025-08-23 08:37:40', '2025-08-23 08:37:40'),
(3, 'Pothos Golden', 'Epipremnum aureum', 'Indoor', '19.99', 'Easy', 'Low', 'Weekly', 'Trailing vine perfect for hanging baskets or shelves. Very forgiving and grows quickly in various light conditions.', '/placeholder.svg?height=300&width=300', 30, 1, '2025-08-23 08:37:40', '2025-08-23 08:37:40'),
(4, 'Fiddle Leaf Fig', 'Ficus lyrata', 'Indoor', '89.99', 'Hard', 'High', 'Weekly', 'Statement plant with large, violin-shaped leaves. Requires bright light and consistent care but makes a stunning focal point.', '/placeholder.svg?height=300&width=300', 8, 1, '2025-08-23 08:37:40', '2025-08-23 08:37:40'),
(5, 'ZZ Plant', 'Zamioculcas zamiifolia', 'Indoor', '34.99', 'Easy', 'Low', 'Every 2-3 weeks', 'Glossy, dark green leaves that thrive in low light. Perfect for offices and low-maintenance plant parents.', '/placeholder.svg?height=300&width=300', 20, 1, '2025-08-23 08:37:40', '2025-08-23 08:37:40'),
(6, 'Peace Lily', 'Spathiphyllum wallisii', 'Air-Purifying', '27.99', 'Medium', 'Medium', 'Weekly', 'Beautiful white flowers and excellent air purification. Tells you when it needs water by drooping slightly.', '/placeholder.svg?height=300&width=300', 18, 1, '2025-08-23 08:37:40', '2025-08-23 08:37:40'),
(7, 'Spider Plant', 'Chlorophytum comosum', 'Air-Purifying', '16.99', 'Easy', 'Medium', 'Weekly', 'Classic houseplant that produces baby plants. Great for beginners and excellent air purifier.', '/placeholder.svg?height=300&width=300', 22, 1, '2025-08-23 08:37:40', '2025-08-23 08:37:40'),
(8, 'Rubber Plant', 'Ficus elastica', 'Air-Purifying', '39.99', 'Medium', 'Medium', 'Weekly', 'Glossy, thick leaves that make a bold statement. Great for removing toxins from indoor air.', '/placeholder.svg?height=300&width=300', 12, 1, '2025-08-23 08:37:40', '2025-08-23 08:37:40'),
(9, 'African Violet', 'Saintpaulia ionantha', 'Flowering', '22.99', 'Medium', 'Medium', 'Twice weekly', 'Compact flowering plant with velvety leaves. Blooms repeatedly with proper care and bright, indirect light.', '/placeholder.svg?height=300&width=300', 14, 1, '2025-08-23 08:37:40', '2025-08-23 08:37:40'),
(10, 'Orchid Phalaenopsis', 'Phalaenopsis amabilis', 'Flowering', '49.99', 'Hard', 'Medium', 'Weekly', 'Elegant flowering plant with long-lasting blooms. Requires specific care but rewards with stunning flowers.', '/placeholder.svg?height=300&width=300', 10, 1, '2025-08-23 08:37:40', '2025-08-23 08:37:40'),
(11, 'Begonia', 'Begonia semperflorens', 'Flowering', '18.99', 'Easy', 'Medium', 'Twice weekly', 'Colorful flowers and attractive foliage. Perfect for adding color to indoor spaces.', '/placeholder.svg?height=300&width=300', 15, 1, '2025-08-23 08:37:40', '2025-08-23 08:52:15'),
(12, 'Lavender', 'Lavandula angustifolia', 'Outdoor', '24.99', 'Easy', 'High', 'Weekly', 'Fragrant herb perfect for gardens. Attracts pollinators and can be used for cooking and aromatherapy.', '/placeholder.svg?height=300&width=300', 20, 1, '2025-08-23 08:37:40', '2025-08-23 08:37:40'),
(13, 'Rosemary', 'Rosmarinus officinalis', 'Outdoor', '19.99', 'Easy', 'High', 'Weekly', 'Aromatic herb perfect for cooking. Drought-tolerant once established and attracts beneficial insects.', '/placeholder.svg?height=300&width=300', 25, 1, '2025-08-23 08:37:40', '2025-08-23 08:37:40'),
(14, 'Tomato Plant', 'Solanum lycopersicum', 'Outdoor', '12.99', 'Medium', 'High', 'Daily', 'Grow your own fresh tomatoes! Requires full sun and regular watering for best fruit production.', '/placeholder.svg?height=300&width=300', 30, 1, '2025-08-23 08:37:40', '2025-08-23 08:37:40'),
(15, 'Sunflower', 'Helianthus annuus', 'Outdoor', '8.99', 'Easy', 'Direct', 'Daily', 'Cheerful annual that follows the sun. Great for children and attracts birds and pollinators.', '/placeholder.svg?height=300&width=300', 35, 1, '2025-08-23 08:37:40', '2025-08-23 08:37:40'),
(16, 'Poinsettia', 'Euphorbia pulcherrima', 'Seasonal', '32.99', 'Medium', 'Medium', 'Twice weekly', 'Classic holiday plant with colorful bracts. Perfect for winter decorating and gift-giving.', '/placeholder.svg?height=300&width=300', 12, 1, '2025-08-23 08:37:40', '2025-08-23 08:37:40'),
(17, 'Easter Lily', 'Lilium longiflorum', 'Seasonal', '28.99', 'Medium', 'Medium', 'Weekly', 'Traditional Easter flower with fragrant white blooms. Symbol of rebirth and new beginnings.', '/placeholder.svg?height=300&width=300', 8, 1, '2025-08-23 08:37:40', '2025-08-23 08:37:40'),
(18, 'Pumpkin Plant', 'Cucurbita pepo', 'Seasonal', '15.99', 'Medium', 'High', 'Daily', 'Grow your own Halloween pumpkins! Requires space and full sun but very rewarding.', '/placeholder.svg?height=300&width=300', 18, 1, '2025-08-23 08:37:40', '2025-08-23 08:37:40'),
(19, 'New Plant 123', 'Plant new', 'Indoor', '55.00', 'Easy', 'Low', 'Weekly', 'No Desc', 'default-plant.jpg', 55, 1, '2025-08-23 08:53:26', '2025-08-23 08:53:26'),
(20, 'ABCD', 'ABCD', 'Air-Purifying', '12.00', 'Easy', 'Low', 'Weekly', 'asd', 'default-plant.jpg', 44, 1, '2025-08-23 09:42:36', '2025-08-23 09:43:54');

-- --------------------------------------------------------

--
-- Stand-in structure for view `plant_stats`
-- (See below for the actual view)
--
CREATE TABLE `plant_stats` (
`plant_id` int(11)
,`name` varchar(100)
,`price` decimal(10,2)
,`stock_quantity` int(11)
,`avg_rating` decimal(14,4)
,`review_count` bigint(21)
,`total_sold` decimal(32,0)
);

-- --------------------------------------------------------

--
-- Table structure for table `reminders`
--

CREATE TABLE `reminders` (
  `reminder_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `plant_id` int(11) NOT NULL,
  `garden_id` int(11) DEFAULT NULL,
  `reminder_date` date NOT NULL,
  `reminder_time` time DEFAULT '09:00:00',
  `type` enum('watering','fertilizing','pruning','repotting') NOT NULL,
  `message` text DEFAULT NULL,
  `is_completed` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reminders`
--

INSERT INTO `reminders` (`reminder_id`, `user_id`, `plant_id`, `garden_id`, `reminder_date`, `reminder_time`, `type`, `message`, `is_completed`, `created_at`) VALUES
(1, 2, 1, 1, '2024-01-15', '09:00:00', 'watering', 'Time to water the snake plant', 0, '2025-08-23 08:37:40'),
(2, 2, 3, 2, '2024-01-12', '09:00:00', 'watering', 'Check if pothos needs water', 0, '2025-08-23 08:37:40'),
(3, 3, 2, 3, '2024-01-14', '09:00:00', 'fertilizing', 'Monthly fertilizing for monstera', 0, '2025-08-23 08:37:40'),
(4, 4, 5, 4, '2024-01-20', '09:00:00', 'watering', 'ZZ plant watering day', 0, '2025-08-23 08:37:40'),
(5, 5, 4, 5, '2024-01-13', '09:00:00', 'watering', 'Fiddle leaf fig watering - check soil first', 0, '2025-08-23 08:37:40');

-- --------------------------------------------------------

--
-- Table structure for table `reviews`
--

CREATE TABLE `reviews` (
  `review_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `plant_id` int(11) NOT NULL,
  `rating` int(11) DEFAULT NULL CHECK (`rating` >= 1 and `rating` <= 5),
  `review_text` text DEFAULT NULL,
  `is_approved` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reviews`
--

INSERT INTO `reviews` (`review_id`, `user_id`, `plant_id`, `rating`, `review_text`, `is_approved`, `created_at`) VALUES
(1, 2, 1, 5, 'Amazing plant for beginners! I\'ve had it for 6 months and it\'s thriving with minimal care.', 1, '2025-08-23 08:37:41'),
(2, 3, 2, 4, 'Beautiful plant but needs more humidity than expected. Still love it!', 1, '2025-08-23 08:37:41'),
(3, 4, 5, 5, 'Perfect for my office. Looks great and requires very little attention.', 1, '2025-08-23 08:37:41'),
(4, 5, 3, 5, 'Fast-growing and forgiving. Great for hanging baskets!', 1, '2025-08-23 08:37:41'),
(5, 2, 6, 4, 'Lovely flowers and really does purify the air. Sensitive to overwatering though.', 1, '2025-08-23 08:37:41'),
(6, 3, 7, 5, 'Classic houseplant that\'s nearly impossible to kill. Highly recommend!', 1, '2025-08-23 08:37:41');

-- --------------------------------------------------------

--
-- Table structure for table `saved_blogs`
--

CREATE TABLE `saved_blogs` (
  `save_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `blog_id` int(11) NOT NULL,
  `saved_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `saved_blogs`
--

INSERT INTO `saved_blogs` (`save_id`, `user_id`, `blog_id`, `saved_at`) VALUES
(1, 2, 1, '2025-08-23 08:37:41'),
(2, 2, 4, '2025-08-23 08:37:41'),
(3, 3, 2, '2025-08-23 08:37:41'),
(4, 3, 3, '2025-08-23 08:37:41'),
(5, 4, 1, '2025-08-23 08:37:41'),
(6, 4, 2, '2025-08-23 08:37:41'),
(7, 5, 3, '2025-08-23 08:37:41'),
(8, 5, 4, '2025-08-23 08:37:41');

--
-- Triggers `saved_blogs`
--
DELIMITER $$
CREATE TRIGGER `increment_blog_views` AFTER INSERT ON `saved_blogs` FOR EACH ROW BEGIN
    UPDATE blogs 
    SET views = views + 1 
    WHERE blog_id = NEW.blog_id;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `profile_image` varchar(255) DEFAULT 'default-avatar.jpg',
  `full_name` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `role` enum('user','admin') DEFAULT 'user',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `username`, `email`, `password_hash`, `profile_image`, `full_name`, `phone`, `address`, `role`, `created_at`, `updated_at`) VALUES
(1, 'admin', 'admin@greenleafs.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'default-avatar.jpg', 'Admin User', '+1234567890', '123 Admin Street, Admin City', 'admin', '2025-08-23 08:37:40', '2025-08-23 09:14:26'),
(2, 'john_doe', 'john@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'default-avatar.jpg', 'John Doe', '+1234567891', '456 Garden Lane, Plant City', 'user', '2025-08-23 08:37:40', '2025-08-23 08:37:40'),
(3, 'jane_smith', 'jane@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'default-avatar.jpg', 'Jane Smith', '+1234567892', '789 Green Avenue, Leaf Town', 'user', '2025-08-23 08:37:40', '2025-08-23 08:37:40'),
(4, 'plant_lover', 'lover@plants.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'default-avatar.jpg', 'Plant Lover', '+1234567893', '321 Nature Road, Garden City', 'user', '2025-08-23 08:37:40', '2025-08-23 08:37:40'),
(5, 'green_thumb', 'green@thumb.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'default-avatar.jpg', 'Green Thumb', '+1234567894', '654 Botanical Street, Flora City', 'user', '2025-08-23 08:37:40', '2025-08-23 08:37:40'),
(6, 'ASD', 'roaraxyt@gmail.com', '$2y$10$aWA7jQsVwNLI7r7drfONNeVvFKuMizW4zlHFVeISpEN1oV12LrXF6', 'default-avatar.jpg', 'roarax', NULL, NULL, 'admin', '2025-08-23 08:43:37', '2025-08-23 08:45:58');

-- --------------------------------------------------------

--
-- Table structure for table `user_garden`
--

CREATE TABLE `user_garden` (
  `garden_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `plant_id` int(11) NOT NULL,
  `plant_name` varchar(100) DEFAULT NULL,
  `date_added` timestamp NOT NULL DEFAULT current_timestamp(),
  `notes` text DEFAULT NULL,
  `growth_images` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_garden`
--

INSERT INTO `user_garden` (`garden_id`, `user_id`, `plant_id`, `plant_name`, `date_added`, `notes`, `growth_images`) VALUES
(1, 2, 1, 'My First Snake Plant', '2025-08-23 08:37:40', 'Doing great in the bedroom corner. Very low maintenance!', NULL),
(2, 2, 3, 'Kitchen Pothos', '2025-08-23 08:37:40', 'Growing beautifully on top of the refrigerator.', NULL),
(3, 3, 2, 'Living Room Monstera', '2025-08-23 08:37:40', 'New leaf just unfurled! So exciting to watch it grow.', NULL),
(4, 4, 5, 'Office ZZ Plant', '2025-08-23 08:37:40', 'Perfect for my windowless office. Thriving despite low light.', NULL),
(5, 5, 4, 'Statement Fiddle Leaf', '2025-08-23 08:37:40', 'Challenging but so worth it. Learning about proper watering.', NULL);

-- --------------------------------------------------------

--
-- Stand-in structure for view `user_order_history`
-- (See below for the actual view)
--
CREATE TABLE `user_order_history` (
`order_id` int(11)
,`user_id` int(11)
,`username` varchar(50)
,`order_date` timestamp
,`total_amount` decimal(10,2)
,`status` enum('pending','confirmed','shipped','delivered','cancelled')
,`item_count` bigint(21)
);

-- --------------------------------------------------------

--
-- Structure for view `plant_stats`
--
DROP TABLE IF EXISTS `plant_stats`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `plant_stats`  AS SELECT `p`.`plant_id` AS `plant_id`, `p`.`name` AS `name`, `p`.`price` AS `price`, `p`.`stock_quantity` AS `stock_quantity`, coalesce(avg(`r`.`rating`),0) AS `avg_rating`, count(`r`.`review_id`) AS `review_count`, coalesce(sum(`oi`.`quantity`),0) AS `total_sold` FROM ((`plants` `p` left join `reviews` `r` on(`p`.`plant_id` = `r`.`plant_id` and `r`.`is_approved` = 1)) left join `order_items` `oi` on(`p`.`plant_id` = `oi`.`plant_id`)) GROUP BY `p`.`plant_id`, `p`.`name`, `p`.`price`, `p`.`stock_quantity``stock_quantity`  ;

-- --------------------------------------------------------

--
-- Structure for view `user_order_history`
--
DROP TABLE IF EXISTS `user_order_history`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `user_order_history`  AS SELECT `o`.`order_id` AS `order_id`, `o`.`user_id` AS `user_id`, `u`.`username` AS `username`, `o`.`order_date` AS `order_date`, `o`.`total_amount` AS `total_amount`, `o`.`status` AS `status`, count(`oi`.`order_item_id`) AS `item_count` FROM ((`orders` `o` join `users` `u` on(`o`.`user_id` = `u`.`user_id`)) left join `order_items` `oi` on(`o`.`order_id` = `oi`.`order_id`)) GROUP BY `o`.`order_id`, `o`.`user_id`, `u`.`username`, `o`.`order_date`, `o`.`total_amount`, `o`.`status``status`  ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `blogs`
--
ALTER TABLE `blogs`
  ADD PRIMARY KEY (`blog_id`),
  ADD KEY `author_id` (`author_id`),
  ADD KEY `idx_blogs_published` (`is_published`);

--
-- Indexes for table `cart`
--
ALTER TABLE `cart`
  ADD PRIMARY KEY (`cart_id`),
  ADD UNIQUE KEY `unique_user_plant` (`user_id`,`plant_id`),
  ADD KEY `plant_id` (`plant_id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`order_id`),
  ADD KEY `idx_orders_user_id` (`user_id`),
  ADD KEY `idx_orders_status` (`status`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`order_item_id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `plant_id` (`plant_id`);

--
-- Indexes for table `plants`
--
ALTER TABLE `plants`
  ADD PRIMARY KEY (`plant_id`),
  ADD KEY `idx_plants_category` (`category`),
  ADD KEY `idx_plants_price` (`price`),
  ADD KEY `idx_plants_care_level` (`care_level`);

--
-- Indexes for table `reminders`
--
ALTER TABLE `reminders`
  ADD PRIMARY KEY (`reminder_id`),
  ADD KEY `plant_id` (`plant_id`),
  ADD KEY `garden_id` (`garden_id`),
  ADD KEY `idx_reminders_date` (`reminder_date`),
  ADD KEY `idx_reminders_user_id` (`user_id`);

--
-- Indexes for table `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`review_id`),
  ADD UNIQUE KEY `unique_user_plant_review` (`user_id`,`plant_id`),
  ADD KEY `idx_reviews_plant_id` (`plant_id`),
  ADD KEY `idx_reviews_approved` (`is_approved`);

--
-- Indexes for table `saved_blogs`
--
ALTER TABLE `saved_blogs`
  ADD PRIMARY KEY (`save_id`),
  ADD UNIQUE KEY `unique_user_blog` (`user_id`,`blog_id`),
  ADD KEY `blog_id` (`blog_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `user_garden`
--
ALTER TABLE `user_garden`
  ADD PRIMARY KEY (`garden_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `plant_id` (`plant_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `blogs`
--
ALTER TABLE `blogs`
  MODIFY `blog_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `cart`
--
ALTER TABLE `cart`
  MODIFY `cart_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `order_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `order_item_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `plants`
--
ALTER TABLE `plants`
  MODIFY `plant_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `reminders`
--
ALTER TABLE `reminders`
  MODIFY `reminder_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `review_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `saved_blogs`
--
ALTER TABLE `saved_blogs`
  MODIFY `save_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `user_garden`
--
ALTER TABLE `user_garden`
  MODIFY `garden_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `blogs`
--
ALTER TABLE `blogs`
  ADD CONSTRAINT `blogs_ibfk_1` FOREIGN KEY (`author_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

--
-- Constraints for table `cart`
--
ALTER TABLE `cart`
  ADD CONSTRAINT `cart_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `cart_ibfk_2` FOREIGN KEY (`plant_id`) REFERENCES `plants` (`plant_id`) ON DELETE CASCADE;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`plant_id`) REFERENCES `plants` (`plant_id`) ON DELETE CASCADE;

--
-- Constraints for table `reminders`
--
ALTER TABLE `reminders`
  ADD CONSTRAINT `reminders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reminders_ibfk_2` FOREIGN KEY (`plant_id`) REFERENCES `plants` (`plant_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reminders_ibfk_3` FOREIGN KEY (`garden_id`) REFERENCES `user_garden` (`garden_id`) ON DELETE CASCADE;

--
-- Constraints for table `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reviews_ibfk_2` FOREIGN KEY (`plant_id`) REFERENCES `plants` (`plant_id`) ON DELETE CASCADE;

--
-- Constraints for table `saved_blogs`
--
ALTER TABLE `saved_blogs`
  ADD CONSTRAINT `saved_blogs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `saved_blogs_ibfk_2` FOREIGN KEY (`blog_id`) REFERENCES `blogs` (`blog_id`) ON DELETE CASCADE;

--
-- Constraints for table `user_garden`
--
ALTER TABLE `user_garden`
  ADD CONSTRAINT `user_garden_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_garden_ibfk_2` FOREIGN KEY (`plant_id`) REFERENCES `plants` (`plant_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
