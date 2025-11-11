USE team03;

CREATE TABLE `Category` (
    `category_id` BIGINT NOT NULL AUTO_INCREMENT,
    `category_name` VARCHAR(255) NOT NULL,
    PRIMARY KEY (`category_id`)
);

CREATE TABLE `Region` (
    `region_id` BIGINT NOT NULL AUTO_INCREMENT,
    `region_name` VARCHAR(255) NOT NULL,
    PRIMARY KEY (`region_id`)
);

CREATE TABLE `Restaurant` (
    `restaurant_id` BIGINT NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(255) NOT NULL,
    `description` TEXT NOT NULL,
    `region_id` BIGINT NOT NULL,
    `category_id` BIGINT NOT NULL,
    `open_time` TIME NOT NULL,
    `close_time` TIME NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `is_active` BOOLEAN DEFAULT TRUE,
    PRIMARY KEY (`restaurant_id`),
    CONSTRAINT `FK_Restaurant_Region` FOREIGN KEY (`region_id`) REFERENCES `Region`(`region_id`),
    CONSTRAINT `FK_Restaurant_Category` FOREIGN KEY (`category_id`) REFERENCES `Category`(`category_id`)
);

CREATE TABLE `Menu` (
    `menu_id` BIGINT NOT NULL AUTO_INCREMENT,
    `menu_name` VARCHAR(255) NOT NULL,
    `price` INT NOT NULL,
    `restaurant_id` BIGINT NOT NULL,
    `is_active` BOOLEAN DEFAULT TRUE,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`menu_id`),
    CONSTRAINT `FK_Menu_Restaurant` FOREIGN KEY (`restaurant_id`) REFERENCES `Restaurant`(`restaurant_id`) ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE TABLE `User` (
    `user_id` BIGINT NOT NULL AUTO_INCREMENT,
    `role` ENUM('ADMIN','USER') NOT NULL,
    `password` VARCHAR(255) NOT NULL,
    `username` VARCHAR(255) NOT NULL,
		`created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `is_active` BOOLEAN DEFAULT TRUE,
    PRIMARY KEY (`user_id`)
);

CREATE TABLE `Review` (
    `review_id` BIGINT NOT NULL AUTO_INCREMENT,
    `taste` INT NOT NULL,
    `cleanliness` INT NOT NULL,
    `kindness` INT NOT NULL,
    `user_id` BIGINT NOT NULL,
    `restaurant_id` BIGINT NOT NULL,
    PRIMARY KEY (`review_id`),
    CONSTRAINT `FK_Review_User` FOREIGN KEY (`user_id`) REFERENCES `User`(`user_id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `FK_Review_Restaurant` FOREIGN KEY (`restaurant_id`) REFERENCES `Restaurant`(`restaurant_id`) ON DELETE CASCADE ON UPDATE CASCADE,
    UNIQUE KEY `uniq_user_restaurant` (`user_id`, `restaurant_id`)
);

CREATE TABLE `Bookmark` (
    `bookmark_id` BIGINT NOT NULL AUTO_INCREMENT,
    `restaurant_id` BIGINT NOT NULL,
    `user_id` BIGINT NOT NULL,
    PRIMARY KEY (`bookmark_id`),
    CONSTRAINT `FK_Bookmark_User` FOREIGN KEY (`user_id`) REFERENCES `User`(`user_id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `FK_Bookmark_Restaurant` FOREIGN KEY (`restaurant_id`) REFERENCES `Restaurant`(`restaurant_id`) ON DELETE CASCADE ON UPDATE CASCADE,
    UNIQUE KEY `uniq_user_restaurant` (`user_id`, `restaurant_id`)
);

CREATE TABLE `Closed_Days` (
    `closed_days_id` BIGINT NOT NULL AUTO_INCREMENT,
    `restaurant_id` BIGINT NOT NULL,
    `day` ENUM('MON','TUE','WED','THU','FRI','SAT','SUN') NOT NULL,
    PRIMARY KEY (`closed_days_id`),
    CONSTRAINT `FK_ClosedDays_Restaurant` FOREIGN KEY (`restaurant_id`) REFERENCES `Restaurant`(`restaurant_id`) ON DELETE CASCADE ON UPDATE CASCADE
);