
DROP TABLE IF EXISTS `brokers`;

CREATE TABLE `brokers` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `key` varchar(8) NOT NULL,
  `password` varchar(8) NOT NULL,
  `is_active` tinyint(1) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=latin1;

/*Data for the table `brokers` */

/*
insert  into `brokers`(`id`,`key`,`password`,`is_active`) values (1,'randomid','keygen08',1),(2,'2ndrando','keyget07',1);
*/

/*Table structure for table `users` */

DROP TABLE IF EXISTS `users`;

CREATE TABLE `users` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(255) NOT NULL,
  `password` varchar(32) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=latin1;

/*Data for the table `users` */

/*
insert  into `users`(`id`,`username`,`password`) values (1,'test','test'),(2,'jhon','test');
*/