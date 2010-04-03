-- phpMyAdmin SQL Dump
-- version 3.2.5
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Apr 02, 2010 at 09:55 PM
-- Server version: 5.1.43
-- PHP Version: 5.3.1

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";

--
-- Database: `dm1`
--

-- --------------------------------------------------------

--
-- Table structure for table `AuthChild`
--

CREATE TABLE IF NOT EXISTS `AuthChild` (
  `auth_id` int(11) NOT NULL,
  `child_id` int(11) NOT NULL,
  `cond` varchar(250) NOT NULL DEFAULT '',
  PRIMARY KEY (`auth_id`,`child_id`),
  KEY `child_id` (`child_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `AuthChild`
--

INSERT INTO `AuthChild` (`auth_id`, `child_id`, `cond`) VALUES
(1, 6, '');

-- --------------------------------------------------------

--
-- Table structure for table `AuthItem`
--

CREATE TABLE IF NOT EXISTS `AuthItem` (
  `auth_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(64) NOT NULL,
  `alt_name` varchar(64) DEFAULT NULL,
  `type` tinyint(4) NOT NULL,
  `description` text,
  `cond` varchar(250) NOT NULL DEFAULT '',
  `bizrule` text,
  `data` text,
  PRIMARY KEY (`auth_id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=26 ;

--
-- Dumping data for table `AuthItem`
--

INSERT INTO `AuthItem` (`auth_id`, `name`, `alt_name`, `type`, `description`, `cond`, `bizrule`, `data`) VALUES
(1, 'Administrator', '', 2, 'Can do anything', '', '', ''),
(2, 'Anonymous User', NULL, 2, 'A user who has not registered or has not logged in.', '', NULL, NULL),
(3, 'Authenticated User', NULL, 2, NULL, '', NULL, NULL),
(4, 'Author', NULL, 2, 'Computed role to indicate whether current user is author of target object (which could be posts, messages, comments etc.).', '', NULL, NULL),
(5, 'Owner', NULL, 2, 'Computed role to indicate whether current user is author of target object (which could be profile, inbox,  etc.).', '', NULL, NULL),
(6, '/', 'Allow All Routes', 1, 'When used for Yii routes access check, this automatically includes all routes.', '', '', NULL),
(7, 'Post Manager', NULL, 1, NULL, '', NULL, NULL),
(8, 'User Manager', NULL, 1, NULL, '', NULL, NULL),
(10, 'Delete User', NULL, 0, NULL, '', NULL, NULL),
(11, 'Create User', NULL, 0, NULL, '', NULL, NULL),
(12, 'Edit User', NULL, 0, NULL, '', NULL, NULL),
(13, 'View User', NULL, 0, NULL, '', NULL, NULL),
(16, 'Delete Post', NULL, 0, NULL, '', NULL, NULL),
(17, 'Create Post', NULL, 0, NULL, '', NULL, NULL),
(18, 'Edit Post', NULL, 0, NULL, '', NULL, NULL),
(19, 'View Post', NULL, 0, NULL, '', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `AuthPath`
--

CREATE TABLE IF NOT EXISTS `AuthPath` (
  `senior_id` int(11) NOT NULL,
  `junior_id` int(11) NOT NULL,
  `distance` int(11) NOT NULL,
  `path` text NOT NULL,
  `senior_cond` varchar(250) NOT NULL DEFAULT '',
  `junior_cond` varchar(250) NOT NULL DEFAULT '',
  `chained_cond` text NOT NULL,
  KEY `senior_junior` (`senior_id`,`junior_id`),
  KEY `junior_senior` (`junior_id`,`senior_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Dumping data for table `AuthPath`
--

INSERT INTO `AuthPath` (`senior_id`, `junior_id`, `distance`, `path`, `senior_cond`, `junior_cond`, `chained_cond`) VALUES
(1, 1, 0, '', '', '', ''),
(2, 2, 0, '', '', '', ''),
(3, 3, 0, '', '', '', ''),
(4, 4, 0, '', '', '', ''),
(5, 5, 0, '', '', '', ''),
(6, 6, 0, '', '', '', ''),
(7, 7, 0, '', '', '', ''),
(8, 8, 0, '', '', '', ''),
(10, 10, 0, '', '', '', ''),
(11, 11, 0, '', '', '', ''),
(12, 12, 0, '', '', '', ''),
(13, 13, 0, '', '', '', ''),
(16, 16, 0, '', '', '', ''),
(17, 17, 0, '', '', '', ''),
(18, 18, 0, '', '', '', ''),
(19, 19, 0, '', '', '', ''),
(1, 6, 1, ',', '', '', ';;');

-- --------------------------------------------------------

--
-- Table structure for table `AuthUser`
--

CREATE TABLE IF NOT EXISTS `AuthUser` (
  `user_id` int(11) NOT NULL,
  `auth_id` int(11) NOT NULL,
  `cond` varchar(250) NOT NULL DEFAULT '',
  `bizrule` text,
  `data` text,
  PRIMARY KEY (`user_id`,`auth_id`),
  KEY `auth_id` (`auth_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `AuthUser`
--

INSERT INTO `AuthUser` (`user_id`, `auth_id`, `cond`, `bizrule`, `data`) VALUES
(1, 1, '', '', NULL);

--
-- Constraints for dumped tables
--

--
-- Constraints for table `AuthChild`
--
ALTER TABLE `AuthChild`
  ADD CONSTRAINT `AuthChild_ibfk_1` FOREIGN KEY (`auth_id`) REFERENCES `AuthItem` (`auth_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `AuthChild_ibfk_2` FOREIGN KEY (`child_id`) REFERENCES `AuthItem` (`auth_id`) ON DELETE CASCADE;

--
-- Constraints for table `AuthUser`
--
ALTER TABLE `AuthUser`
  ADD CONSTRAINT `AuthUser_ibfk_1` FOREIGN KEY (`auth_id`) REFERENCES `AuthItem` (`auth_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `AuthUser_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
