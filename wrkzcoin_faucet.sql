-- Adminer 4.6.3 MySQL dump

SET NAMES utf8;
SET time_zone = '+00:00';
SET foreign_key_checks = 0;
SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';

SET NAMES utf8mb4;

DROP TABLE IF EXISTS `wrkzcoin_faucet`;
CREATE TABLE `wrkzcoin_faucet` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `wallet` varchar(128) NOT NULL,
  `lastDateGet` int(11) NOT NULL,
  `lastPaid` float NOT NULL,
  `lastip` varchar(17) NOT NULL,
  `Session` varchar(128) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- 2018-07-04 01:42:43
