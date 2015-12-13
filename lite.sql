SET NAMES utf8;
SET time_zone = '+00:00';
SET foreign_key_checks = 0;
SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';

DROP TABLE IF EXISTS `channel`;
CREATE TABLE `channel` (
  `channel_id` int(11) NOT NULL AUTO_INCREMENT,
  `channel` varchar(80) NOT NULL,
  `products_api` varchar(256) NOT NULL,
  PRIMARY KEY (`channel_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `channel` (`channel_id`, `channel`, `products_api`) VALUES
(1,	'Shopify',	'https://{API_KEY}:{API_PASSWORD}@{STORE_NAME}.myshopify.com/admin/products.json?fields=id,title,variants'),
(11,	'Vend',	'https://{STORE_NAME}.vendhq.com/api/products?access_token={ACCESS_TOKEN}');

DROP TABLE IF EXISTS `product`;
CREATE TABLE `product` (
  `product_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `title` varchar(80) DEFAULT NULL,
  `created` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `product` (`product_id`, `title`, `created`, `updated`) VALUES
(1261,	'Archimedes Principle',	'2015-12-12 20:59:58',	'2015-12-12 20:59:58'),
(1271,	'Euclidean Geometry',	'2015-12-12 20:59:58',	'2015-12-12 20:59:58'),
(1281,	'Pythagorean Theorem',	'2015-12-12 20:59:58',	'2015-12-12 20:59:58'),
(1291,	'Sieve of Eratosthenes',	'2015-12-12 21:00:56',	'2015-12-12 21:00:56');

DROP TABLE IF EXISTS `store`;
CREATE TABLE `store` (
  `store_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `vendor_id` bigint(20) unsigned NOT NULL,
  `channel_id` int(10) unsigned NOT NULL,
  `store_name` varchar(80) DEFAULT NULL,
  `api_key` varchar(256) DEFAULT NULL,
  `api_password` varchar(256) DEFAULT NULL,
  `access_token` varchar(256) DEFAULT NULL,
  PRIMARY KEY (`store_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `store` (`store_id`, `vendor_id`, `channel_id`, `store_name`, `api_key`, `api_password`, `access_token`) VALUES
(11,	1,	1,	'charlesqwu',	'83f5b437ac96988d9ce243be8236487a',	'79d73d41f61f73032a4d100d33e4f933',	''),
(21,	1,	11,	'charlesqwu',	'',	'',	'iENOIAoEb4DGMVnmcAl3KklwQcmBnsQzqcx9jIPS');

DROP TABLE IF EXISTS `variant`;
CREATE TABLE `variant` (
  `variant_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `product_id` bigint(20) unsigned NOT NULL,
  `store_id` bigint(20) unsigned NOT NULL,
  `title` varchar(256) NOT NULL,
  `sku` varchar(32) NOT NULL,
  `price` decimal(10,2) unsigned NOT NULL DEFAULT '0.00',
  `qty` decimal(10,3) unsigned NOT NULL DEFAULT '0.000',
  `created` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`variant_id`),
  UNIQUE KEY `sku_store_id` (`sku`,`store_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `variant` (`variant_id`, `product_id`, `store_id`, `title`, `sku`, `price`, `qty`, `created`, `updated`) VALUES
(3451,	1261,	11,	'Archimedes Principle',	'A001',	2.00,	401.000,	'2015-12-12 21:00:56',	'2015-12-12 21:00:56'),
(3461,	1271,	11,	'Euclidean Geometry',	'E001',	3.12,	501.000,	'2015-12-12 21:00:56',	'2015-12-12 21:00:56'),
(3471,	1271,	11,	'Euclidean Geometry',	'E002',	3.21,	502.000,	'2015-12-12 21:00:56',	'2015-12-12 21:00:56'),
(3481,	1281,	11,	'Pythagorean Theorem',	'P001',	1.00,	301.000,	'2015-12-12 21:00:56',	'2015-12-12 21:00:56'),
(3491,	1281,	11,	'Pythagorean Theorem',	'P002',	1.00,	302.000,	'2015-12-12 21:00:56',	'2015-12-12 21:00:56'),
(3501,	1281,	11,	'Pythagorean Theorem',	'P003',	1.00,	303.000,	'2015-12-12 21:00:56',	'2015-12-12 21:00:56'),
(3511,	1271,	21,	'Euclidean Geometry',	'E001',	3.00,	2001.000,	'2015-12-12 21:00:56',	'2015-12-12 21:00:56'),
(3521,	1281,	21,	'Pythagorean Theorem / S',	'P001',	1.06,	1001.000,	'2015-12-12 21:00:56',	'2015-12-12 21:00:56'),
(3531,	1281,	21,	'Pythagorean Theorem / M',	'P002',	1.11,	1002.000,	'2015-12-12 21:00:56',	'2015-12-12 21:00:56'),
(3541,	1291,	21,	'Sieve of Eratosthenes / R',	'S001',	5.01,	501.000,	'2015-12-12 21:00:56',	'2015-12-12 21:00:56'),
(3551,	1291,	21,	'Sieve of Eratosthenes / G',	'S002',	5.11,	503.000,	'2015-12-12 21:00:56',	'2015-12-12 21:00:56');

DROP TABLE IF EXISTS `vendor`;
CREATE TABLE `vendor` (
  `vendor_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `vendor` varchar(80) NOT NULL,
  PRIMARY KEY (`vendor_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `vendor` (`vendor_id`, `vendor`) VALUES (1,	'charlesqwu');
