
# This is a fix for InnoDB in MySQL >= 4.1.x
# It "suspends judgement" for fkey relationships until are tables are set.
SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------------------------------
-- game
-- ---------------------------------------------------------------------

DROP TABLE IF EXISTS `game`;

CREATE TABLE `game`
(
    `id` INTEGER NOT NULL AUTO_INCREMENT,
    `visible` TINYINT DEFAULT 0 NOT NULL,
    `created_at` DATETIME,
    `updated_at` DATETIME,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- question
-- ---------------------------------------------------------------------

DROP TABLE IF EXISTS `question`;

CREATE TABLE `question`
(
    `id` INTEGER NOT NULL AUTO_INCREMENT,
    `visible` TINYINT DEFAULT 0 NOT NULL,
    `game_id` INTEGER NOT NULL,
    `created_at` DATETIME,
    `updated_at` DATETIME,
    PRIMARY KEY (`id`),
    INDEX `fi_game_id` (`game_id`),
    CONSTRAINT `fk_game_id`
        FOREIGN KEY (`game_id`)
        REFERENCES `game` (`id`)
        ON UPDATE RESTRICT
        ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- answer
-- ---------------------------------------------------------------------

DROP TABLE IF EXISTS `answer`;

CREATE TABLE `answer`
(
    `id` INTEGER NOT NULL AUTO_INCREMENT,
    `visible` TINYINT DEFAULT 0 NOT NULL,
    `correct` TINYINT DEFAULT 0 NOT NULL,
    `question_id` INTEGER NOT NULL,
    `created_at` DATETIME,
    `updated_at` DATETIME,
    PRIMARY KEY (`id`),
    INDEX `fi_question_id` (`question_id`),
    CONSTRAINT `fk_question_id`
        FOREIGN KEY (`question_id`)
        REFERENCES `question` (`id`)
        ON UPDATE RESTRICT
        ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- participate
-- ---------------------------------------------------------------------

DROP TABLE IF EXISTS `participate`;

CREATE TABLE `participate`
(
    `id` INTEGER NOT NULL AUTO_INCREMENT,
    `email` VARCHAR(255) NOT NULL,
    `win` TINYINT DEFAULT 0 NOT NULL,
    `game_id` INTEGER NOT NULL,
    `customer_id` INTEGER,
    PRIMARY KEY (`id`),
    INDEX `fi_game_participate_id` (`game_id`),
    INDEX `fi_game_customer_id` (`customer_id`),
    CONSTRAINT `fk_game_participate_id`
        FOREIGN KEY (`game_id`)
        REFERENCES `game` (`id`)
        ON UPDATE RESTRICT
        ON DELETE CASCADE,
    CONSTRAINT `fk_game_customer_id`
        FOREIGN KEY (`customer_id`)
        REFERENCES `customer` (`id`)
        ON UPDATE RESTRICT
        ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- game_i18n
-- ---------------------------------------------------------------------

DROP TABLE IF EXISTS `game_i18n`;

CREATE TABLE `game_i18n`
(
    `id` INTEGER NOT NULL,
    `locale` VARCHAR(5) DEFAULT 'en_US' NOT NULL,
    `title` VARCHAR(255),
    `description` LONGTEXT,
    PRIMARY KEY (`id`,`locale`),
    CONSTRAINT `game_i18n_fk_a4c5c3`
        FOREIGN KEY (`id`)
        REFERENCES `game` (`id`)
        ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- question_i18n
-- ---------------------------------------------------------------------

DROP TABLE IF EXISTS `question_i18n`;

CREATE TABLE `question_i18n`
(
    `id` INTEGER NOT NULL,
    `locale` VARCHAR(5) DEFAULT 'en_US' NOT NULL,
    `title` VARCHAR(255),
    `description` LONGTEXT,
    PRIMARY KEY (`id`,`locale`),
    CONSTRAINT `question_i18n_fk_e2315c`
        FOREIGN KEY (`id`)
        REFERENCES `question` (`id`)
        ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- answer_i18n
-- ---------------------------------------------------------------------

DROP TABLE IF EXISTS `answer_i18n`;

CREATE TABLE `answer_i18n`
(
    `id` INTEGER NOT NULL,
    `locale` VARCHAR(5) DEFAULT 'en_US' NOT NULL,
    `title` VARCHAR(255),
    `description` LONGTEXT,
    PRIMARY KEY (`id`,`locale`),
    CONSTRAINT `answer_i18n_fk_5201a4`
        FOREIGN KEY (`id`)
        REFERENCES `answer` (`id`)
        ON DELETE CASCADE
) ENGINE=InnoDB;

# This restores the fkey checks, after having unset them earlier
SET FOREIGN_KEY_CHECKS = 1;
