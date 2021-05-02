CREATE TABLE `#__finder_links_terms` (
	`link_id` INT(10) UNSIGNED NOT NULL,
	`term_id` INT(10) UNSIGNED NOT NULL,
	`weight` FLOAT UNSIGNED NOT NULL,
	PRIMARY KEY (`link_id`, `term_id`),
	INDEX `idx_term_weight` (`term_id`, `weight`),
	INDEX `idx_link_term_weight` (`link_id`, `term_id`, `weight`)
)
COLLATE='utf8mb4_general_ci'
ENGINE=InnoDB
;

ALTER TABLE `#__finder_terms`
	CHANGE COLUMN `language` `language` CHAR(7) NOT NULL DEFAULT '' AFTER `links`;
 
ALTER TABLE `#__finder_terms_common`
	CHANGE COLUMN `language` `language` CHAR(7) NOT NULL DEFAULT '' AFTER `term`;
 
ALTER TABLE `#__finder_tokens`
	CHANGE COLUMN `language` `language` CHAR(7) NOT NULL DEFAULT '' AFTER `context`;
 
ALTER TABLE `#__finder_tokens_aggregate`
	CHANGE COLUMN `language` `language` CHAR(7) NOT NULL DEFAULT '' AFTER `total_weight`;
 
ALTER TABLE `#__finder_links`
	CHANGE COLUMN `language` `language` CHAR(7) NOT NULL DEFAULT '' AFTER `access`; 

CREATE TABLE IF NOT EXISTS `#__jlfinder_taxonomy` (
	`id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
	`parent_id` INT(10) UNSIGNED NOT NULL DEFAULT '0',
	`lft` INT(11) NOT NULL DEFAULT '0',
	`rgt` INT(11) NOT NULL DEFAULT '0',
	`level` INT(10) UNSIGNED NOT NULL DEFAULT '0',
	`path` VARCHAR(400) NOT NULL DEFAULT '',
	`title` VARCHAR(255) NOT NULL DEFAULT '',
	`alias` VARCHAR(400) NOT NULL DEFAULT '',
	`state` TINYINT(1) UNSIGNED NOT NULL DEFAULT '1',
	`access` TINYINT(1) UNSIGNED NOT NULL DEFAULT '1',
	`language` CHAR(7) NOT NULL DEFAULT '',
	PRIMARY KEY (`id`),
	INDEX `idx_state` (`state`),
	INDEX `idx_access` (`access`),
	INDEX `idx_path` (`path`(100)),
	INDEX `idx_left_right` (`lft`, `rgt`),
	INDEX `idx_alias` (`alias`(100)),
	INDEX `idx_language` (`language`),
	INDEX `idx_parent_published` (`parent_id`, `state`, `access`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_general_ci;

INSERT INTO `#__jlfinder_taxonomy` (`id`, `parent_id`, `lft`, `rgt`, `level`, `path`, `title`, `alias`, `state`, `access`, `language`) VALUES
(1, 0, 0, 1, 0, '', 'ROOT', 'root', 1, 1, '*');