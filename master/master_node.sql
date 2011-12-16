
CREATE TABLE `datasets` (
    `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
    `title` varchar(50) NOT NULL,
    `description` text,
    `annotation_file` varchar(32) NOT NULL,
    `variant_type` varchar(24) NOT NULL,
    `raw_filename` varchar(100) NOT NULL,
    `status` int(11) NOT NULL DEFAULT '0',
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
