DROP TABLE IF EXISTS `#__maplatlong`;

CREATE TABLE `#__maplatlong` (
	`id`       INT(11)     NOT NULL AUTO_INCREMENT,
	`item_id` INT(11) NOT NULL,
	`modified` datetime NOT NULL,
	`lat` text NOT NULL,
	`long` text NOT NULL,
	PRIMARY KEY (`id`)
)
    ENGINE=InnoDB
    AUTO_INCREMENT =0
    DEFAULT CHARSET=utf8mb4
    DEFAULT COLLATE=utf8mb4_unicode_ci;
