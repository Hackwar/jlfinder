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