--
-- Table structure for table `u_users`
--

DROP TABLE IF EXISTS `u_users`;
CREATE TABLE `u_users` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `regtime` timestamp NOT NULL default CURRENT_TIMESTAMP COMMENT 'Time of registration',
  `name` text NOT NULL,
  `username` varchar(25) default NULL,
  `email` varchar(320) default NULL,
  `pass` varchar(40) NOT NULL COMMENT 'Password digest',
  `salt` varchar(13) NOT NULL COMMENT 'Salt',
  `temppass` varchar(13) default NULL COMMENT 'Temporary password used for password recovery',
  `temppasstime` timestamp NULL default NULL COMMENT 'Temporary password generation time',
  `requirespassreset` tinyint(1) NOT NULL default '0' COMMENT 'Flag indicating that user must reset their password before using the site',
  `fb_id` bigint(20) unsigned default NULL COMMENT 'Facebook user ID',
  PRIMARY KEY  (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `fb_id` (`fb_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Table structure for table `u_googlefriendconnect`
--

DROP TABLE IF EXISTS `u_googlefriendconnect`;
CREATE TABLE `u_googlefriendconnect` (
  `user_id` int(10) unsigned NOT NULL COMMENT 'User ID',
  `google_id` varchar(255) NOT NULL COMMENT 'Google Friend Connect ID',
  `userpic` text NOT NULL COMMENT 'Google Friend Connect User picture',
  PRIMARY KEY  (`user_id`,`google_id`),
  CONSTRAINT `gfc_user` FOREIGN KEY (`user_id`) REFERENCES `u_users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Table structure for table `u_invitation`
--

DROP TABLE IF EXISTS `u_invitation`;
CREATE TABLE `u_invitation` (
  `code` char(10) NOT NULL COMMENT 'Code',
  `created` timestamp NOT NULL default CURRENT_TIMESTAMP COMMENT 'When invitation was created',
  `issuedby` bigint(10) unsigned NOT NULL default '1' COMMENT 'User who issued the invitation. Default is Sergey.',
  `sentto` text COMMENT 'Note about who this invitation was sent to',
  `user` bigint(10) unsigned default NULL COMMENT 'User name',
  PRIMARY KEY  (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Table structure for table `u_oid_associations`
--

DROP TABLE IF EXISTS `u_oid_associations`;
CREATE TABLE `u_oid_associations` (
  `server_url` blob NOT NULL,
  `handle` varchar(255) NOT NULL default '',
  `secret` blob NOT NULL,
  `issued` int(11) NOT NULL default '0',
  `lifetime` int(11) NOT NULL default '0',
  `assoc_type` varchar(64) NOT NULL default '',
  PRIMARY KEY  (`server_url`(255),`handle`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Table structure for table `u_oid_nonces`
--

DROP TABLE IF EXISTS `u_oid_nonces`;
CREATE TABLE `u_oid_nonces` (
  `server_url` text NOT NULL,
  `timestamp` int(11) NOT NULL default '0',
  `salt` varchar(40) NOT NULL default '',
  UNIQUE KEY `server_url` (`server_url`(255),`timestamp`,`salt`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

