-- phpMyAdmin SQL Dump
-- version 4.8.2
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Erstellungszeit: 26. Feb 2019 um 19:53
-- Server-Version: 10.1.29-MariaDB
-- PHP-Version: 7.2.8

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Datenbank: `skdb`
--

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `country`
--

CREATE TABLE `country` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Daten für Tabelle `country`
--

INSERT INTO `country` (`id`, `name`, `active`, `created`) VALUES
(1, 'Afghanistan', 1, '2019-01-14 19:17:54'),
(2, 'Åland Islands', 1, '2019-01-14 19:17:54'),
(3, 'Albania', 1, '2019-01-14 19:17:54'),
(4, 'Algeria', 1, '2019-01-14 19:17:54'),
(5, 'American Samoa', 1, '2019-01-14 19:17:54'),
(6, 'Andorra', 1, '2019-01-14 19:17:54'),
(7, 'Angola', 1, '2019-01-14 19:17:54'),
(8, 'Anguilla', 1, '2019-01-14 19:17:54'),
(9, 'Antarctica', 1, '2019-01-14 19:17:54'),
(10, 'Antigua and Barbuda', 1, '2019-01-14 19:17:54'),
(11, 'Argentina', 1, '2019-01-14 19:17:54'),
(12, 'Armenia', 1, '2019-01-14 19:17:54'),
(13, 'Aruba', 1, '2019-01-14 19:17:54'),
(14, 'Australia', 1, '2019-01-14 19:17:54'),
(15, 'Austria', 1, '2019-01-14 19:17:54'),
(16, 'Azerbaijan', 1, '2019-01-14 19:17:54'),
(17, 'Bahamas', 1, '2019-01-14 19:17:54'),
(18, 'Bahrain', 1, '2019-01-14 19:17:54'),
(19, 'Bangladesh', 1, '2019-01-14 19:17:54'),
(20, 'Barbados', 1, '2019-01-14 19:17:54'),
(21, 'Belarus', 1, '2019-01-14 19:17:54'),
(22, 'Belgium', 1, '2019-01-14 19:17:54'),
(23, 'Belize', 1, '2019-01-14 19:17:54'),
(24, 'Benin', 1, '2019-01-14 19:17:54'),
(25, 'Bermuda', 1, '2019-01-14 19:17:54'),
(26, 'Bhutan', 1, '2019-01-14 19:17:54'),
(27, 'Bolivia, Plurinational State of', 1, '2019-01-14 19:17:54'),
(28, 'Bonaire, Sint Eustatius and Saba', 1, '2019-01-14 19:17:54'),
(29, 'Bosnia and Herzegovina', 1, '2019-01-14 19:17:54'),
(30, 'Botswana', 1, '2019-01-14 19:17:54'),
(31, 'Bouvet Island', 1, '2019-01-14 19:17:54'),
(32, 'Brazil', 1, '2019-01-14 19:17:54'),
(33, 'British Indian Ocean Territory', 1, '2019-01-14 19:17:54'),
(34, 'Brunei Darussalam', 1, '2019-01-14 19:17:54'),
(35, 'Bulgaria', 1, '2019-01-14 19:17:54'),
(36, 'Burkina Faso', 1, '2019-01-14 19:17:54'),
(37, 'Burundi', 1, '2019-01-14 19:17:54'),
(38, 'Cambodia', 1, '2019-01-14 19:17:54'),
(39, 'Cameroon', 1, '2019-01-14 19:17:54'),
(40, 'Canada', 1, '2019-01-14 19:17:54'),
(41, 'Cape Verde', 1, '2019-01-14 19:17:54'),
(42, 'Cayman Islands', 1, '2019-01-14 19:17:54'),
(43, 'Central African Republic', 1, '2019-01-14 19:17:54'),
(44, 'Chad', 1, '2019-01-14 19:17:54'),
(45, 'Chile', 1, '2019-01-14 19:17:54'),
(46, 'China', 1, '2019-01-14 19:17:54'),
(47, 'Christmas Island', 1, '2019-01-14 19:17:54'),
(48, 'Cocos (Keeling) Islands', 1, '2019-01-14 19:17:54'),
(49, 'Colombia', 1, '2019-01-14 19:17:54'),
(50, 'Comoros', 1, '2019-01-14 19:17:54'),
(51, 'Congo', 1, '2019-01-14 19:17:54'),
(52, 'Congo, the Democratic Republic of the', 1, '2019-01-14 19:17:54'),
(53, 'Cook Islands', 1, '2019-01-14 19:17:54'),
(54, 'Costa Rica', 1, '2019-01-14 19:17:54'),
(55, 'Côte d\'Ivoire', 1, '2019-01-14 19:17:54'),
(56, 'Croatia', 1, '2019-01-14 19:17:54'),
(57, 'Cuba', 1, '2019-01-14 19:17:54'),
(58, 'Curaçao', 1, '2019-01-14 19:17:54'),
(59, 'Cyprus', 1, '2019-01-14 19:17:54'),
(60, 'Czech Republic', 1, '2019-01-14 19:17:54'),
(61, 'Denmark', 1, '2019-01-14 19:17:54'),
(62, 'Djibouti', 1, '2019-01-14 19:17:54'),
(63, 'Dominica', 1, '2019-01-14 19:17:54'),
(64, 'Dominican Republic', 1, '2019-01-14 19:17:54'),
(65, 'Ecuador', 1, '2019-01-14 19:17:54'),
(66, 'Egypt', 1, '2019-01-14 19:17:54'),
(67, 'El Salvador', 1, '2019-01-14 19:17:54'),
(68, 'Equatorial Guinea', 1, '2019-01-14 19:17:54'),
(69, 'Eritrea', 1, '2019-01-14 19:17:54'),
(70, 'Estonia', 1, '2019-01-14 19:17:54'),
(71, 'Ethiopia', 1, '2019-01-14 19:17:54'),
(72, 'Falkland Islands (Malvinas)', 1, '2019-01-14 19:17:54'),
(73, 'Faroe Islands', 1, '2019-01-14 19:17:54'),
(74, 'Fiji', 1, '2019-01-14 19:17:54'),
(75, 'Finland', 1, '2019-01-14 19:17:54'),
(76, 'France', 1, '2019-01-14 19:17:54'),
(77, 'French Guiana', 1, '2019-01-14 19:17:54'),
(78, 'French Polynesia', 1, '2019-01-14 19:17:54'),
(79, 'French Southern Territories', 1, '2019-01-14 19:17:54'),
(80, 'Gabon', 1, '2019-01-14 19:17:54'),
(81, 'Gambia', 1, '2019-01-14 19:17:54'),
(82, 'Georgia', 1, '2019-01-14 19:17:54'),
(83, 'Germany', 1, '2019-01-14 19:17:54'),
(84, 'Ghana', 1, '2019-01-14 19:17:54'),
(85, 'Gibraltar', 1, '2019-01-14 19:17:54'),
(86, 'Greece', 1, '2019-01-14 19:17:54'),
(87, 'Greenland', 1, '2019-01-14 19:17:54'),
(88, 'Grenada', 1, '2019-01-14 19:17:54'),
(89, 'Guadeloupe', 1, '2019-01-14 19:17:54'),
(90, 'Guam', 1, '2019-01-14 19:17:54'),
(91, 'Guatemala', 1, '2019-01-14 19:17:54'),
(92, 'Guernsey', 1, '2019-01-14 19:17:54'),
(93, 'Guinea', 1, '2019-01-14 19:17:54'),
(94, 'Guinea-Bissau', 1, '2019-01-14 19:17:54'),
(95, 'Guyana', 1, '2019-01-14 19:17:54'),
(96, 'Haiti', 1, '2019-01-14 19:17:54'),
(97, 'Heard Island and McDonald Islands', 1, '2019-01-14 19:17:54'),
(98, 'Holy See (Vatican City State)', 1, '2019-01-14 19:17:54'),
(99, 'Honduras', 1, '2019-01-14 19:17:54'),
(100, 'Hong Kong', 1, '2019-01-14 19:17:54'),
(101, 'Hungary', 1, '2019-01-14 19:17:54'),
(102, 'Iceland', 1, '2019-01-14 19:17:54'),
(103, 'India', 1, '2019-01-14 19:17:54'),
(104, 'Indonesia', 1, '2019-01-14 19:17:54'),
(105, 'Iran, Islamic Republic of', 1, '2019-01-14 19:17:54'),
(106, 'Iraq', 1, '2019-01-14 19:17:54'),
(107, 'Ireland', 1, '2019-01-14 19:17:54'),
(108, 'Isle of Man', 1, '2019-01-14 19:17:54'),
(109, 'Israel', 1, '2019-01-14 19:17:54'),
(110, 'Italy', 1, '2019-01-14 19:17:54'),
(111, 'Jamaica', 1, '2019-01-14 19:17:54'),
(112, 'Japan', 1, '2019-01-14 19:17:54'),
(113, 'Jersey', 1, '2019-01-14 19:17:54'),
(114, 'Jordan', 1, '2019-01-14 19:17:54'),
(115, 'Kazakhstan', 1, '2019-01-14 19:17:54'),
(116, 'Kenya', 1, '2019-01-14 19:17:54'),
(117, 'Kiribati', 1, '2019-01-14 19:17:54'),
(118, 'Korea, Democratic People\'s Republic of', 1, '2019-01-14 19:17:54'),
(119, 'Korea, Republic of', 1, '2019-01-14 19:17:54'),
(120, 'Kuwait', 1, '2019-01-14 19:17:54'),
(121, 'Kyrgyzstan', 1, '2019-01-14 19:17:54'),
(122, 'Lao People\'s Democratic Republic', 1, '2019-01-14 19:17:54'),
(123, 'Latvia', 1, '2019-01-14 19:17:54'),
(124, 'Lebanon', 1, '2019-01-14 19:17:54'),
(125, 'Lesotho', 1, '2019-01-14 19:17:54'),
(126, 'Liberia', 1, '2019-01-14 19:17:55'),
(127, 'Libya', 1, '2019-01-14 19:17:55'),
(128, 'Liechtenstein', 1, '2019-01-14 19:17:55'),
(129, 'Lithuania', 1, '2019-01-14 19:17:55'),
(130, 'Luxembourg', 1, '2019-01-14 19:17:55'),
(131, 'Macao', 1, '2019-01-14 19:17:55'),
(132, 'Macedonia, the former Yugoslav Republic of', 1, '2019-01-14 19:17:55'),
(133, 'Madagascar', 1, '2019-01-14 19:17:55'),
(134, 'Malawi', 1, '2019-01-14 19:17:55'),
(135, 'Malaysia', 1, '2019-01-14 19:17:55'),
(136, 'Maldives', 1, '2019-01-14 19:17:55'),
(137, 'Mali', 1, '2019-01-14 19:17:55'),
(138, 'Malta', 1, '2019-01-14 19:17:55'),
(139, 'Marshall Islands', 1, '2019-01-14 19:17:55'),
(140, 'Martinique', 1, '2019-01-14 19:17:55'),
(141, 'Mauritania', 1, '2019-01-14 19:17:55'),
(142, 'Mauritius', 1, '2019-01-14 19:17:55'),
(143, 'Mayotte', 1, '2019-01-14 19:17:55'),
(144, 'Mexico', 1, '2019-01-14 19:17:55'),
(145, 'Micronesia, Federated States of', 1, '2019-01-14 19:17:55'),
(146, 'Moldova, Republic of', 1, '2019-01-14 19:17:55'),
(147, 'Monaco', 1, '2019-01-14 19:17:55'),
(148, 'Mongolia', 1, '2019-01-14 19:17:55'),
(149, 'Montenegro', 1, '2019-01-14 19:17:55'),
(150, 'Montserrat', 1, '2019-01-14 19:17:55'),
(151, 'Morocco', 1, '2019-01-14 19:17:55'),
(152, 'Mozambique', 1, '2019-01-14 19:17:55'),
(153, 'Myanmar', 1, '2019-01-14 19:17:55'),
(154, 'Namibia', 1, '2019-01-14 19:17:55'),
(155, 'Nauru', 1, '2019-01-14 19:17:55'),
(156, 'Nepal', 1, '2019-01-14 19:17:55'),
(157, 'Netherlands', 1, '2019-01-14 19:17:55'),
(158, 'New Caledonia', 1, '2019-01-14 19:17:55'),
(159, 'New Zealand', 1, '2019-01-14 19:17:55'),
(160, 'Nicaragua', 1, '2019-01-14 19:17:55'),
(161, 'Niger', 1, '2019-01-14 19:17:55'),
(162, 'Nigeria', 1, '2019-01-14 19:17:55'),
(163, 'Niue', 1, '2019-01-14 19:17:55'),
(164, 'Norfolk Island', 1, '2019-01-14 19:17:55'),
(165, 'Northern Mariana Islands', 1, '2019-01-14 19:17:55'),
(166, 'Norway', 1, '2019-01-14 19:17:55'),
(167, 'Oman', 1, '2019-01-14 19:17:55'),
(168, 'Pakistan', 1, '2019-01-14 19:17:55'),
(169, 'Palau', 1, '2019-01-14 19:17:55'),
(170, 'Palestinian Territory, Occupied', 1, '2019-01-14 19:17:55'),
(171, 'Panama', 1, '2019-01-14 19:17:55'),
(172, 'Papua New Guinea', 1, '2019-01-14 19:17:55'),
(173, 'Paraguay', 1, '2019-01-14 19:17:55'),
(174, 'Peru', 1, '2019-01-14 19:17:55'),
(175, 'Philippines', 1, '2019-01-14 19:17:55'),
(176, 'Pitcairn', 1, '2019-01-14 19:17:55'),
(177, 'Poland', 1, '2019-01-14 19:17:55'),
(178, 'Portugal', 1, '2019-01-14 19:17:55'),
(179, 'Puerto Rico', 1, '2019-01-14 19:17:55'),
(180, 'Qatar', 1, '2019-01-14 19:17:55'),
(181, 'Réunion', 1, '2019-01-14 19:17:55'),
(182, 'Romania', 1, '2019-01-14 19:17:55'),
(183, 'Russian Federation', 1, '2019-01-14 19:17:55'),
(184, 'Rwanda', 1, '2019-01-14 19:17:55'),
(185, 'Saint Barthélemy', 1, '2019-01-14 19:17:55'),
(186, 'Saint Helena, Ascension and Tristan da Cunha', 1, '2019-01-14 19:17:55'),
(187, 'Saint Kitts and Nevis', 1, '2019-01-14 19:17:55'),
(188, 'Saint Lucia', 1, '2019-01-14 19:17:55'),
(189, 'Saint Martin (French part)', 1, '2019-01-14 19:17:55'),
(190, 'Saint Pierre and Miquelon', 1, '2019-01-14 19:17:55'),
(191, 'Saint Vincent and the Grenadines', 1, '2019-01-14 19:17:55'),
(192, 'Samoa', 1, '2019-01-14 19:17:55'),
(193, 'San Marino', 1, '2019-01-14 19:17:55'),
(194, 'Sao Tome and Principe', 1, '2019-01-14 19:17:55'),
(195, 'Saudi Arabia', 1, '2019-01-14 19:17:55'),
(196, 'Senegal', 1, '2019-01-14 19:17:55'),
(197, 'Serbia', 1, '2019-01-14 19:17:55'),
(198, 'Seychelles', 1, '2019-01-14 19:17:55'),
(199, 'Sierra Leone', 1, '2019-01-14 19:17:55'),
(200, 'Singapore', 1, '2019-01-14 19:17:55'),
(201, 'Sint Maarten (Dutch part)', 1, '2019-01-14 19:17:55'),
(202, 'Slovakia', 1, '2019-01-14 19:17:55'),
(203, 'Slovenia', 1, '2019-01-14 19:17:55'),
(204, 'Solomon Islands', 1, '2019-01-14 19:17:55'),
(205, 'Somalia', 1, '2019-01-14 19:17:55'),
(206, 'South Africa', 1, '2019-01-14 19:17:55'),
(207, 'South Georgia and the South Sandwich Islands', 1, '2019-01-14 19:17:55'),
(208, 'South Sudan', 1, '2019-01-14 19:17:55'),
(209, 'Spain', 1, '2019-01-14 19:17:55'),
(210, 'Sri Lanka', 1, '2019-01-14 19:17:55'),
(211, 'Sudan', 1, '2019-01-14 19:17:55'),
(212, 'Suriname', 1, '2019-01-14 19:17:55'),
(213, 'Svalbard and Jan Mayen', 1, '2019-01-14 19:17:55'),
(214, 'Swaziland', 1, '2019-01-14 19:17:55'),
(215, 'Sweden', 1, '2019-01-14 19:17:55'),
(216, 'Switzerland', 1, '2019-01-14 19:17:55'),
(217, 'Syrian Arab Republic', 1, '2019-01-14 19:17:55'),
(218, 'Taiwan, Province of China', 1, '2019-01-14 19:17:55'),
(219, 'Tajikistan', 1, '2019-01-14 19:17:55'),
(220, 'Tanzania, United Republic of', 1, '2019-01-14 19:17:55'),
(221, 'Thailand', 1, '2019-01-14 19:17:55'),
(222, 'Timor-Leste', 1, '2019-01-14 19:17:55'),
(223, 'Togo', 1, '2019-01-14 19:17:55'),
(224, 'Tokelau', 1, '2019-01-14 19:17:55'),
(225, 'Tonga', 1, '2019-01-14 19:17:55'),
(226, 'Trinidad and Tobago', 1, '2019-01-14 19:17:55'),
(227, 'Tunisia', 1, '2019-01-14 19:17:55'),
(228, 'Turkey', 1, '2019-01-14 19:17:55'),
(229, 'Turkmenistan', 1, '2019-01-14 19:17:55'),
(230, 'Turks and Caicos Islands', 1, '2019-01-14 19:17:55'),
(231, 'Tuvalu', 1, '2019-01-14 19:17:55'),
(232, 'Uganda', 1, '2019-01-14 19:17:55'),
(233, 'Ukraine', 1, '2019-01-14 19:17:55'),
(234, 'United Arab Emirates', 1, '2019-01-14 19:17:55'),
(235, 'United Kingdom', 1, '2019-01-14 19:17:55'),
(236, 'United States', 1, '2019-01-14 19:17:55'),
(237, 'United States Minor Outlying Islands', 1, '2019-01-14 19:17:55'),
(238, 'Uruguay', 1, '2019-01-14 19:17:55'),
(239, 'Uzbekistan', 1, '2019-01-14 19:17:55'),
(240, 'Vanuatu', 1, '2019-01-14 19:17:55'),
(241, 'Venezuela, Bolivarian Republic of', 1, '2019-01-14 19:17:55'),
(242, 'Viet Nam', 1, '2019-01-14 19:17:55'),
(243, 'Virgin Islands, British', 1, '2019-01-14 19:17:55'),
(244, 'Virgin Islands, U.S.', 1, '2019-01-14 19:17:55'),
(245, 'Wallis and Futuna', 1, '2019-01-14 19:17:55'),
(246, 'Western Sahara', 1, '2019-01-14 19:17:55'),
(247, 'Yemen', 1, '2019-01-14 19:17:55'),
(248, 'Zambia', 1, '2019-01-14 19:17:55'),
(249, 'Afghanistan', 1, '2019-01-14 19:18:01'),
(250, 'Åland Islands', 1, '2019-01-14 19:18:01'),
(251, 'Albania', 1, '2019-01-14 19:18:01'),
(252, 'Algeria', 1, '2019-01-14 19:18:01'),
(253, 'American Samoa', 1, '2019-01-14 19:18:01'),
(254, 'Andorra', 1, '2019-01-14 19:18:01'),
(255, 'Angola', 1, '2019-01-14 19:18:01'),
(256, 'Anguilla', 1, '2019-01-14 19:18:01'),
(257, 'Antarctica', 1, '2019-01-14 19:18:01'),
(258, 'Antigua and Barbuda', 1, '2019-01-14 19:18:01'),
(259, 'Argentina', 1, '2019-01-14 19:18:01'),
(260, 'Armenia', 1, '2019-01-14 19:18:01'),
(261, 'Aruba', 1, '2019-01-14 19:18:01'),
(262, 'Australia', 1, '2019-01-14 19:18:01'),
(263, 'Austria', 1, '2019-01-14 19:18:01'),
(264, 'Azerbaijan', 1, '2019-01-14 19:18:01'),
(265, 'Bahamas', 1, '2019-01-14 19:18:01'),
(266, 'Bahrain', 1, '2019-01-14 19:18:01'),
(267, 'Bangladesh', 1, '2019-01-14 19:18:01'),
(268, 'Barbados', 1, '2019-01-14 19:18:01'),
(269, 'Belarus', 1, '2019-01-14 19:18:01'),
(270, 'Belgium', 1, '2019-01-14 19:18:01'),
(271, 'Belize', 1, '2019-01-14 19:18:01'),
(272, 'Benin', 1, '2019-01-14 19:18:01'),
(273, 'Bermuda', 1, '2019-01-14 19:18:01'),
(274, 'Bhutan', 1, '2019-01-14 19:18:01'),
(275, 'Bolivia, Plurinational State of', 1, '2019-01-14 19:18:01'),
(276, 'Bonaire, Sint Eustatius and Saba', 1, '2019-01-14 19:18:01'),
(277, 'Bosnia and Herzegovina', 1, '2019-01-14 19:18:01'),
(278, 'Botswana', 1, '2019-01-14 19:18:01'),
(279, 'Bouvet Island', 1, '2019-01-14 19:18:01'),
(280, 'Brazil', 1, '2019-01-14 19:18:01'),
(281, 'British Indian Ocean Territory', 1, '2019-01-14 19:18:01'),
(282, 'Brunei Darussalam', 1, '2019-01-14 19:18:01'),
(283, 'Bulgaria', 1, '2019-01-14 19:18:01'),
(284, 'Burkina Faso', 1, '2019-01-14 19:18:01'),
(285, 'Burundi', 1, '2019-01-14 19:18:01'),
(286, 'Cambodia', 1, '2019-01-14 19:18:01'),
(287, 'Cameroon', 1, '2019-01-14 19:18:02'),
(288, 'Canada', 1, '2019-01-14 19:18:02'),
(289, 'Cape Verde', 1, '2019-01-14 19:18:02'),
(290, 'Cayman Islands', 1, '2019-01-14 19:18:02'),
(291, 'Central African Republic', 1, '2019-01-14 19:18:02'),
(292, 'Chad', 1, '2019-01-14 19:18:02'),
(293, 'Chile', 1, '2019-01-14 19:18:02'),
(294, 'China', 1, '2019-01-14 19:18:02'),
(295, 'Christmas Island', 1, '2019-01-14 19:18:02'),
(296, 'Cocos (Keeling) Islands', 1, '2019-01-14 19:18:02'),
(297, 'Colombia', 1, '2019-01-14 19:18:02'),
(298, 'Comoros', 1, '2019-01-14 19:18:02'),
(299, 'Congo', 1, '2019-01-14 19:18:02'),
(300, 'Congo, the Democratic Republic of the', 1, '2019-01-14 19:18:02'),
(301, 'Cook Islands', 1, '2019-01-14 19:18:02'),
(302, 'Costa Rica', 1, '2019-01-14 19:18:02'),
(303, 'Côte d\'Ivoire', 1, '2019-01-14 19:18:02'),
(304, 'Croatia', 1, '2019-01-14 19:18:02'),
(305, 'Cuba', 1, '2019-01-14 19:18:02'),
(306, 'Curaçao', 1, '2019-01-14 19:18:02'),
(307, 'Cyprus', 1, '2019-01-14 19:18:02'),
(308, 'Czech Republic', 1, '2019-01-14 19:18:02'),
(309, 'Denmark', 1, '2019-01-14 19:18:02'),
(310, 'Djibouti', 1, '2019-01-14 19:18:02'),
(311, 'Dominica', 1, '2019-01-14 19:18:02'),
(312, 'Dominican Republic', 1, '2019-01-14 19:18:02'),
(313, 'Ecuador', 1, '2019-01-14 19:18:02'),
(314, 'Egypt', 1, '2019-01-14 19:18:02'),
(315, 'El Salvador', 1, '2019-01-14 19:18:02'),
(316, 'Equatorial Guinea', 1, '2019-01-14 19:18:02'),
(317, 'Eritrea', 1, '2019-01-14 19:18:02'),
(318, 'Estonia', 1, '2019-01-14 19:18:02'),
(319, 'Ethiopia', 1, '2019-01-14 19:18:02'),
(320, 'Falkland Islands (Malvinas)', 1, '2019-01-14 19:18:02'),
(321, 'Faroe Islands', 1, '2019-01-14 19:18:02'),
(322, 'Fiji', 1, '2019-01-14 19:18:02'),
(323, 'Finland', 1, '2019-01-14 19:18:02'),
(324, 'France', 1, '2019-01-14 19:18:02'),
(325, 'French Guiana', 1, '2019-01-14 19:18:02'),
(326, 'French Polynesia', 1, '2019-01-14 19:18:02'),
(327, 'French Southern Territories', 1, '2019-01-14 19:18:02'),
(328, 'Gabon', 1, '2019-01-14 19:18:02'),
(329, 'Gambia', 1, '2019-01-14 19:18:02'),
(330, 'Georgia', 1, '2019-01-14 19:18:02'),
(331, 'Germany', 1, '2019-01-14 19:18:02'),
(332, 'Ghana', 1, '2019-01-14 19:18:02'),
(333, 'Gibraltar', 1, '2019-01-14 19:18:02'),
(334, 'Greece', 1, '2019-01-14 19:18:02'),
(335, 'Greenland', 1, '2019-01-14 19:18:02'),
(336, 'Grenada', 1, '2019-01-14 19:18:02'),
(337, 'Guadeloupe', 1, '2019-01-14 19:18:02'),
(338, 'Guam', 1, '2019-01-14 19:18:02'),
(339, 'Guatemala', 1, '2019-01-14 19:18:02'),
(340, 'Guernsey', 1, '2019-01-14 19:18:02'),
(341, 'Guinea', 1, '2019-01-14 19:18:02'),
(342, 'Guinea-Bissau', 1, '2019-01-14 19:18:02'),
(343, 'Guyana', 1, '2019-01-14 19:18:02'),
(344, 'Haiti', 1, '2019-01-14 19:18:02'),
(345, 'Heard Island and McDonald Islands', 1, '2019-01-14 19:18:02'),
(346, 'Holy See (Vatican City State)', 1, '2019-01-14 19:18:02'),
(347, 'Honduras', 1, '2019-01-14 19:18:02'),
(348, 'Hong Kong', 1, '2019-01-14 19:18:02'),
(349, 'Hungary', 1, '2019-01-14 19:18:02'),
(350, 'Iceland', 1, '2019-01-14 19:18:02'),
(351, 'India', 1, '2019-01-14 19:18:02'),
(352, 'Indonesia', 1, '2019-01-14 19:18:02'),
(353, 'Iran, Islamic Republic of', 1, '2019-01-14 19:18:02'),
(354, 'Iraq', 1, '2019-01-14 19:18:02'),
(355, 'Ireland', 1, '2019-01-14 19:18:02'),
(356, 'Isle of Man', 1, '2019-01-14 19:18:02'),
(357, 'Israel', 1, '2019-01-14 19:18:02'),
(358, 'Italy', 1, '2019-01-14 19:18:02'),
(359, 'Jamaica', 1, '2019-01-14 19:18:02'),
(360, 'Japan', 1, '2019-01-14 19:18:02'),
(361, 'Jersey', 1, '2019-01-14 19:18:02'),
(362, 'Jordan', 1, '2019-01-14 19:18:02'),
(363, 'Kazakhstan', 1, '2019-01-14 19:18:02'),
(364, 'Kenya', 1, '2019-01-14 19:18:02'),
(365, 'Kiribati', 1, '2019-01-14 19:18:02'),
(366, 'Korea, Democratic People\'s Republic of', 1, '2019-01-14 19:18:02'),
(367, 'Korea, Republic of', 1, '2019-01-14 19:18:02'),
(368, 'Kuwait', 1, '2019-01-14 19:18:02'),
(369, 'Kyrgyzstan', 1, '2019-01-14 19:18:02'),
(370, 'Lao People\'s Democratic Republic', 1, '2019-01-14 19:18:02'),
(371, 'Latvia', 1, '2019-01-14 19:18:02'),
(372, 'Lebanon', 1, '2019-01-14 19:18:02'),
(373, 'Lesotho', 1, '2019-01-14 19:18:02'),
(374, 'Liberia', 1, '2019-01-14 19:18:02'),
(375, 'Libya', 1, '2019-01-14 19:18:02'),
(376, 'Liechtenstein', 1, '2019-01-14 19:18:02'),
(377, 'Lithuania', 1, '2019-01-14 19:18:02'),
(378, 'Luxembourg', 1, '2019-01-14 19:18:02'),
(379, 'Macao', 1, '2019-01-14 19:18:02'),
(380, 'Macedonia, the former Yugoslav Republic of', 1, '2019-01-14 19:18:02'),
(381, 'Madagascar', 1, '2019-01-14 19:18:02'),
(382, 'Malawi', 1, '2019-01-14 19:18:02'),
(383, 'Malaysia', 1, '2019-01-14 19:18:02'),
(384, 'Maldives', 1, '2019-01-14 19:18:02'),
(385, 'Mali', 1, '2019-01-14 19:18:02'),
(386, 'Malta', 1, '2019-01-14 19:18:02'),
(387, 'Marshall Islands', 1, '2019-01-14 19:18:02'),
(388, 'Martinique', 1, '2019-01-14 19:18:02'),
(389, 'Mauritania', 1, '2019-01-14 19:18:02'),
(390, 'Mauritius', 1, '2019-01-14 19:18:02'),
(391, 'Mayotte', 1, '2019-01-14 19:18:02'),
(392, 'Mexico', 1, '2019-01-14 19:18:02'),
(393, 'Micronesia, Federated States of', 1, '2019-01-14 19:18:02'),
(394, 'Moldova, Republic of', 1, '2019-01-14 19:18:02'),
(395, 'Monaco', 1, '2019-01-14 19:18:02'),
(396, 'Mongolia', 1, '2019-01-14 19:18:02'),
(397, 'Montenegro', 1, '2019-01-14 19:18:02'),
(398, 'Montserrat', 1, '2019-01-14 19:18:02'),
(399, 'Morocco', 1, '2019-01-14 19:18:02'),
(400, 'Mozambique', 1, '2019-01-14 19:18:02'),
(401, 'Myanmar', 1, '2019-01-14 19:18:02'),
(402, 'Namibia', 1, '2019-01-14 19:18:02'),
(403, 'Nauru', 1, '2019-01-14 19:18:02'),
(404, 'Nepal', 1, '2019-01-14 19:18:02'),
(405, 'Netherlands', 1, '2019-01-14 19:18:02'),
(406, 'New Caledonia', 1, '2019-01-14 19:18:02'),
(407, 'New Zealand', 1, '2019-01-14 19:18:02'),
(408, 'Nicaragua', 1, '2019-01-14 19:18:02'),
(409, 'Niger', 1, '2019-01-14 19:18:02'),
(410, 'Nigeria', 1, '2019-01-14 19:18:02'),
(411, 'Niue', 1, '2019-01-14 19:18:02'),
(412, 'Norfolk Island', 1, '2019-01-14 19:18:02'),
(413, 'Northern Mariana Islands', 1, '2019-01-14 19:18:02'),
(414, 'Norway', 1, '2019-01-14 19:18:02'),
(415, 'Oman', 1, '2019-01-14 19:18:02'),
(416, 'Pakistan', 1, '2019-01-14 19:18:02'),
(417, 'Palau', 1, '2019-01-14 19:18:02'),
(418, 'Palestinian Territory, Occupied', 1, '2019-01-14 19:18:02'),
(419, 'Panama', 1, '2019-01-14 19:18:02'),
(420, 'Papua New Guinea', 1, '2019-01-14 19:18:02'),
(421, 'Paraguay', 1, '2019-01-14 19:18:02'),
(422, 'Peru', 1, '2019-01-14 19:18:02'),
(423, 'Philippines', 1, '2019-01-14 19:18:02'),
(424, 'Pitcairn', 1, '2019-01-14 19:18:02'),
(425, 'Poland', 1, '2019-01-14 19:18:02'),
(426, 'Portugal', 1, '2019-01-14 19:18:02'),
(427, 'Puerto Rico', 1, '2019-01-14 19:18:02'),
(428, 'Qatar', 1, '2019-01-14 19:18:02'),
(429, 'Réunion', 1, '2019-01-14 19:18:02'),
(430, 'Romania', 1, '2019-01-14 19:18:02'),
(431, 'Russian Federation', 1, '2019-01-14 19:18:02'),
(432, 'Rwanda', 1, '2019-01-14 19:18:02'),
(433, 'Saint Barthélemy', 1, '2019-01-14 19:18:02'),
(434, 'Saint Helena, Ascension and Tristan da Cunha', 1, '2019-01-14 19:18:02'),
(435, 'Saint Kitts and Nevis', 1, '2019-01-14 19:18:02'),
(436, 'Saint Lucia', 1, '2019-01-14 19:18:02'),
(437, 'Saint Martin (French part)', 1, '2019-01-14 19:18:02'),
(438, 'Saint Pierre and Miquelon', 1, '2019-01-14 19:18:02'),
(439, 'Saint Vincent and the Grenadines', 1, '2019-01-14 19:18:02'),
(440, 'Samoa', 1, '2019-01-14 19:18:02'),
(441, 'San Marino', 1, '2019-01-14 19:18:02'),
(442, 'Sao Tome and Principe', 1, '2019-01-14 19:18:02'),
(443, 'Saudi Arabia', 1, '2019-01-14 19:18:02'),
(444, 'Senegal', 1, '2019-01-14 19:18:02'),
(445, 'Serbia', 1, '2019-01-14 19:18:02'),
(446, 'Seychelles', 1, '2019-01-14 19:18:02'),
(447, 'Sierra Leone', 1, '2019-01-14 19:18:02'),
(448, 'Singapore', 1, '2019-01-14 19:18:02'),
(449, 'Sint Maarten (Dutch part)', 1, '2019-01-14 19:18:02'),
(450, 'Slovakia', 1, '2019-01-14 19:18:02'),
(451, 'Slovenia', 1, '2019-01-14 19:18:02'),
(452, 'Solomon Islands', 1, '2019-01-14 19:18:02'),
(453, 'Somalia', 1, '2019-01-14 19:18:02'),
(454, 'South Africa', 1, '2019-01-14 19:18:02'),
(455, 'South Georgia and the South Sandwich Islands', 1, '2019-01-14 19:18:02'),
(456, 'South Sudan', 1, '2019-01-14 19:18:02'),
(457, 'Spain', 1, '2019-01-14 19:18:02'),
(458, 'Sri Lanka', 1, '2019-01-14 19:18:02'),
(459, 'Sudan', 1, '2019-01-14 19:18:02'),
(460, 'Suriname', 1, '2019-01-14 19:18:02'),
(461, 'Svalbard and Jan Mayen', 1, '2019-01-14 19:18:02'),
(462, 'Swaziland', 1, '2019-01-14 19:18:02'),
(463, 'Sweden', 1, '2019-01-14 19:18:02'),
(464, 'Switzerland', 1, '2019-01-14 19:18:02'),
(465, 'Syrian Arab Republic', 1, '2019-01-14 19:18:02'),
(466, 'Taiwan, Province of China', 1, '2019-01-14 19:18:02'),
(467, 'Tajikistan', 1, '2019-01-14 19:18:02'),
(468, 'Tanzania, United Republic of', 1, '2019-01-14 19:18:02'),
(469, 'Thailand', 1, '2019-01-14 19:18:02'),
(470, 'Timor-Leste', 1, '2019-01-14 19:18:02'),
(471, 'Togo', 1, '2019-01-14 19:18:02'),
(472, 'Tokelau', 1, '2019-01-14 19:18:02'),
(473, 'Tonga', 1, '2019-01-14 19:18:02'),
(474, 'Trinidad and Tobago', 1, '2019-01-14 19:18:02'),
(475, 'Tunisia', 1, '2019-01-14 19:18:02'),
(476, 'Turkey', 1, '2019-01-14 19:18:02'),
(477, 'Turkmenistan', 1, '2019-01-14 19:18:02'),
(478, 'Turks and Caicos Islands', 1, '2019-01-14 19:18:02'),
(479, 'Tuvalu', 1, '2019-01-14 19:18:02'),
(480, 'Uganda', 1, '2019-01-14 19:18:02'),
(481, 'Ukraine', 1, '2019-01-14 19:18:02'),
(482, 'United Arab Emirates', 1, '2019-01-14 19:18:02'),
(483, 'United Kingdom', 1, '2019-01-14 19:18:02'),
(484, 'United States', 1, '2019-01-14 19:18:02'),
(485, 'United States Minor Outlying Islands', 1, '2019-01-14 19:18:02'),
(486, 'Uruguay', 1, '2019-01-14 19:18:02'),
(487, 'Uzbekistan', 1, '2019-01-14 19:18:02'),
(488, 'Vanuatu', 1, '2019-01-14 19:18:02'),
(489, 'Venezuela, Bolivarian Republic of', 1, '2019-01-14 19:18:02'),
(490, 'Viet Nam', 1, '2019-01-14 19:18:02'),
(491, 'Virgin Islands, British', 1, '2019-01-14 19:18:02'),
(492, 'Virgin Islands, U.S.', 1, '2019-01-14 19:18:02'),
(493, 'Wallis and Futuna', 1, '2019-01-14 19:18:02'),
(494, 'Western Sahara', 1, '2019-01-14 19:18:02'),
(495, 'Yemen', 1, '2019-01-14 19:18:02'),
(496, 'Zambia', 1, '2019-01-14 19:18:02'),
(497, 'Zimbabwe', 1, '2019-01-14 19:18:02');

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `cvgenerate`
--

CREATE TABLE `cvgenerate` (
  `id` int(11) NOT NULL,
  `employeeId` int(11) NOT NULL,
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Daten für Tabelle `cvgenerate`
--

INSERT INTO `cvgenerate` (`id`, `employeeId`, `created`) VALUES
(1, 2, '2019-02-26 15:13:20'),
(2, 2, '2019-02-26 15:13:27');

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `cvreferences`
--

CREATE TABLE `cvreferences` (
  `id` int(11) NOT NULL,
  `employeeId` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `short_description` text NOT NULL,
  `position` varchar(255) NOT NULL,
  `client` varchar(255) DEFAULT NULL,
  `start` date NOT NULL,
  `end` date DEFAULT NULL,
  `skillId` int(11) NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Daten für Tabelle `cvreferences`
--

INSERT INTO `cvreferences` (`id`, `employeeId`, `title`, `description`, `short_description`, `position`, `client`, `start`, `end`, `skillId`, `active`, `created`) VALUES
(8, 2, 'Collaborative Planning, Replenishment & Forecasting with SAP', 'Assistant to SCM-Manager, member of project office \n\nAnalysis of different software products (SAP SCM/APO, ECC, BW, EXCEL) about coverage and feasibility \n\nDevelopment of a strategic business process to visualize, analyze, assess and decide on project portfolio \n\nCreating several planning strategies in SAP \n\nCost-effort estimations \n\nAnalysis of scenarios for demand equalization \n\nCreating Training Documents for End-User \n\nAnalyzing different SAP-Addon Tools \n\nDevelopment of decision criteria to evaluate cost/benefit', 'Analysis of different software products (SAP SCM/APO, ECC, BW, EXCEL) about coverage and feasibility ', 'SAP Consultant', 'Umicore', '2018-12-13', NULL, 1, 1, '2019-01-18 20:56:23'),
(9, 2, 'S/4 HANA Demo21 Project', '-- Assess production planning and detailed scheduling (PP/DS) functionality in terms of PP/DS to S/4 HANA migration \r\nCreation of customer proof-of-concept (master data & customizing) in PP-PI and PP/DS in order to compare them in S/4HANA \r\n-- Installation **and** setup of S/4 HANA on Amazon Web Services (AWS) \r\n-- Participate in the SAP S/4 HANA Demo21 workshop for SAP Partners \r\n-- Analyze **feasibility options** for HANA appliance cloud hosting using the Amazon Web Services (AWS) \r\nTraining in Demo-System', 'Installation, feasibility study & POC for S/4HANA 1601 within SAP Demo21 workshop', 'Hands on Experience', NULL, '1017-04-01', '2017-04-04', 6, 1, '2019-01-18 20:56:34');

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `education`
--

CREATE TABLE `education` (
  `id` int(11) NOT NULL,
  `personalInformationId` int(11) NOT NULL,
  `start` date DEFAULT NULL,
  `graduation` date NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text,
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Daten für Tabelle `education`
--

INSERT INTO `education` (`id`, `personalInformationId`, `start`, `graduation`, `title`, `description`, `active`, `created`) VALUES
(1, 1, '2019-01-16', '2019-01-17', 'Hallo prof hist', 'MANAGER 4 REAL ESTATE', 0, '2019-01-16 17:44:49'),
(2, 1, '2019-01-04', '2019-01-26', 'heyyyyyyya                        ', 'Ass Wiper', 0, '2019-01-16 18:41:50'),
(3, 1, '2019-01-02', '2019-01-10', 'dadawdawd', 'awdawdawd', 0, '2019-01-16 19:12:39'),
(4, 1, '2019-01-09', '2019-01-03', 'dawdawdawd                                                \n                        ', 'dawdawdawdaw', 0, '2019-01-16 19:27:38'),
(5, 1, '0010-10-10', '0020-02-20', '                                                                                                                                                                                                                                                              \n', 'Something', 1, '2019-01-20 12:28:52'),
(6, 1, '0000-00-00', '0000-00-00', '                                                                                                                                                                                                                                                              \n', 'hgfhgf', 1, '2019-01-20 12:28:58');

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `employee`
--

CREATE TABLE `employee` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `firstName` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `profile_picture` int(11) NOT NULL DEFAULT '1',
  `permissionLevel` int(11) NOT NULL DEFAULT '0',
  `password` varchar(255) NOT NULL,
  `salt` varchar(255) NOT NULL,
  `notificationsEnabled_Time` tinyint(1) NOT NULL DEFAULT '1',
  `notificationsEnabled_Skills` tinyint(1) NOT NULL DEFAULT '1',
  `languageId` int(11) NOT NULL DEFAULT '0',
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Daten für Tabelle `employee`
--

INSERT INTO `employee` (`id`, `name`, `firstName`, `email`, `profile_picture`, `permissionLevel`, `password`, `salt`, `notificationsEnabled_Time`, `notificationsEnabled_Skills`, `languageId`, `created`) VALUES
(2, 'Klausen', 'Peter', 'alex@zierhut-it.de', 18, 2, '6065d5362232ede975aa9ea7892ac872ef11a5440a427140da9f9e260466d8da3d70930ba97315667caecd31d5cad0f23bd7206d741043988a996bea6ce7246e', '14330882085c49ced0b15e78.24790638', 1, 1, 0, '2019-01-04 20:50:29'),
(3, 'dawdawd', 'wdawdawda', 'awdawd@deded.de', 1, 1, '', '', 1, 1, 0, '2019-02-26 15:31:06');

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `file`
--

CREATE TABLE `file` (
  `id` int(11) NOT NULL,
  `reference` varchar(255) NOT NULL,
  `type` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `extension` varchar(255) NOT NULL,
  `size` int(11) NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Daten für Tabelle `file`
--

INSERT INTO `file` (`id`, `reference`, `type`, `name`, `extension`, `size`, `active`, `created`) VALUES
(1, 'default_pp', 'image/png', 'businessman-310819_1280.png', 'png', 153986, 1, '2019-02-16 14:17:54'),
(7, '5c681df5048fb', 'image/jpeg', 'Anzug_Rot_mund_zu_-_Kopie.jpeg', 'jpeg', 39291, 1, '2019-02-16 14:28:05'),
(8, '5c681ea44f7e8', 'image/jpeg', 'Anzug_Rot_mund_zu_-_Kopie.jpeg', 'jpeg', 39291, 1, '2019-02-16 14:31:00'),
(9, '5c681ebc0d998', 'image/jpeg', 'Anzug_Rot_mund_zu_-_Kopie.jpeg', 'jpeg', 39291, 1, '2019-02-16 14:31:24'),
(10, '5c681ebfd6c4f', 'image/jpeg', 'Anzug_Rot_mund_zu_-_Kopie.jpeg', 'jpeg', 39291, 1, '2019-02-16 14:31:27'),
(11, '5c754f6f3b808', 'image/jpeg', '65f4247e-559b-461b-886f-5d152ad6d45f.jpg', 'jpg', 208361, 1, '2019-02-26 14:38:39'),
(12, '5c755b5510c04', 'image/png', 'hall.png', 'png', 1850594, 1, '2019-02-26 15:29:25'),
(13, '5c755efeaaea4', 'image/png', 'hallway2.png', 'png', 1932828, 1, '2019-02-26 15:45:02'),
(14, '5c755f09d86d7', 'image/png', 'hallway2.png', 'png', 1932828, 1, '2019-02-26 15:45:13'),
(15, '5c755f27866fd', 'image/png', 'hallway2.png', 'png', 1932828, 1, '2019-02-26 15:45:43'),
(16, '5c755f51e25f5', 'image/png', 'hallway2.png', 'png', 1932828, 1, '2019-02-26 15:46:25'),
(17, '5c755fca91fa4', 'image/png', 'blender.PNG', 'png', 111444, 1, '2019-02-26 15:48:26'),
(18, '5c7561f931f3c', 'image/jpeg', 'images.jpg', 'jpg', 217708, 1, '2019-02-26 15:57:45');

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `language`
--

CREATE TABLE `language` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `nativeName` varchar(255) NOT NULL,
  `value` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Daten für Tabelle `language`
--

INSERT INTO `language` (`id`, `name`, `nativeName`, `value`) VALUES
(0, 'English', 'English', 'EN'),
(1, 'German (Formal)', 'Deutsch (Formal)', 'DE_Formal');

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `logintoken`
--

CREATE TABLE `logintoken` (
  `id` int(11) NOT NULL,
  `token` varchar(255) NOT NULL,
  `employeeId` int(11) NOT NULL,
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Daten für Tabelle `logintoken`
--

INSERT INTO `logintoken` (`id`, `token`, `employeeId`, `created`) VALUES
(3, '5c2fcad5c3dd973302611836939', 2, '2019-01-04 21:06:29'),
(4, '5c2fcadb6ee2498112996295129', 2, '2019-01-04 21:06:35'),
(5, '5c2fd48355fad55467751170268', 2, '2019-01-04 21:47:47'),
(6, '5c2fd6637a17171078767557089', 2, '2019-01-04 21:55:47'),
(7, '5c2fd66a64d7585388519813234', 2, '2019-01-04 21:55:54'),
(8, '5c2fd6702c29d54429047528961', 2, '2019-01-04 21:56:00'),
(9, '5c2fd6bb8c8c928308628186146', 2, '2019-01-04 21:57:15'),
(10, '5c2fd7ad2619218052858949552', 2, '2019-01-04 22:01:17'),
(11, '5c30664bf2b1674209202353003', 2, '2019-01-05 08:09:47'),
(12, '5c30664d7e3f082441930324113', 2, '2019-01-05 08:09:49'),
(13, '5c49ced0b35ef67977598710210', 2, '2019-01-24 14:42:24'),
(14, '5c49ced43290f39571481337315', 2, '2019-01-24 14:42:28'),
(15, '5c647c24764e529094934945796', 2, '2019-02-13 20:20:52'),
(16, '5c6592b40ad5b10636656678406', 2, '2019-02-14 16:09:24'),
(17, '5c67fbb62daee92479523852877', 2, '2019-02-16 12:01:58'),
(18, '5c67fbcd3ea0410000253377497', 2, '2019-02-16 12:02:21'),
(19, '5c67fbcdcf7ca59802341772704', 2, '2019-02-16 12:02:21'),
(20, '5c67fbce0562c29932146887795', 2, '2019-02-16 12:02:22'),
(21, '5c67fbce2bc3895835544656571', 2, '2019-02-16 12:02:22'),
(22, '5c67fbcef091918965341657945', 2, '2019-02-16 12:02:22'),
(23, '5c67fbcf21e9929135399438469', 2, '2019-02-16 12:02:23'),
(24, '5c67fbcf4c46762394446340805', 2, '2019-02-16 12:02:23'),
(25, '5c67fbcf73cd829065948829947', 2, '2019-02-16 12:02:23'),
(26, '5c67fbcf9f6a586908305772908', 2, '2019-02-16 12:02:23');

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `logintry`
--

CREATE TABLE `logintry` (
  `id` int(11) NOT NULL,
  `employeeId` int(11) NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `permissionlevelname`
--

CREATE TABLE `permissionlevelname` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `value` int(11) NOT NULL,
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Daten für Tabelle `permissionlevelname`
--

INSERT INTO `permissionlevelname` (`id`, `name`, `value`, `created`) VALUES
(1, 'Employee', 0, '2019-01-20 20:35:28'),
(2, 'Employee (Processing privileged)', 1, '2019-01-20 20:35:28'),
(3, 'Administrator', 2, '2019-01-20 20:35:28');

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `personalinformation`
--

CREATE TABLE `personalinformation` (
  `id` int(11) NOT NULL,
  `employeeId` int(11) NOT NULL,
  `addr_country` varchar(255) NOT NULL,
  `addr_state` varchar(255) NOT NULL,
  `addr_city` varchar(255) NOT NULL,
  `addr_zip` varchar(255) NOT NULL,
  `addr_street` varchar(255) NOT NULL,
  `addr_street_number` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `web` varchar(255) NOT NULL,
  `tel` varchar(255) NOT NULL,
  `mobil` varchar(255) NOT NULL,
  `fax` varchar(255) NOT NULL,
  `position` varchar(255) NOT NULL,
  `nationality` varchar(255) NOT NULL,
  `birthdate` date NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Daten für Tabelle `personalinformation`
--

INSERT INTO `personalinformation` (`id`, `employeeId`, `addr_country`, `addr_state`, `addr_city`, `addr_zip`, `addr_street`, `addr_street_number`, `email`, `web`, `tel`, `mobil`, `fax`, `position`, `nationality`, `birthdate`, `active`, `created`) VALUES
(1, 2, 'Germany', 'NRWestfalen', 'Solingener', '42653', 'Nummern Stra?e', '69', 'rofl@zierhut-it.de', 'https://zierhut-it.de/rofl', '2307930', '0176 565 19451', '2307931', 'Very Nice Developer', 'German', '1980-10-23', 1, '2019-01-14 18:35:11');

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `professionalhistory`
--

CREATE TABLE `professionalhistory` (
  `id` int(11) NOT NULL,
  `personalInformationId` int(11) NOT NULL,
  `start` date NOT NULL,
  `end` date DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `position` varchar(255) DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Daten für Tabelle `professionalhistory`
--

INSERT INTO `professionalhistory` (`id`, `personalInformationId`, `start`, `end`, `title`, `position`, `active`, `created`) VALUES
(1, 1, '2019-01-16', '2019-01-17', 'Hallo prof hist', 'MANAGER 4 REAL ESTATE', 1, '2019-01-16 17:44:12'),
(2, 1, '0000-00-00', NULL, '', NULL, 0, '2019-01-16 19:11:14'),
(3, 1, '2000-01-24', '2000-01-24', '654564564', 'jhkjhkjh', 1, '2019-01-16 19:27:50'),
(4, 1, '2019-01-04', '2019-01-10', 'LEEEEEERRRRRR', NULL, 1, '2019-01-16 19:41:08'),
(5, 1, '2019-01-04', '2019-01-10', '', 'LEEEEEERRRRRR', 1, '2019-01-16 19:41:40'),
(6, 1, '2019-01-04', '2019-01-10', '', 'fgdgfd', 1, '2019-01-16 19:42:04');

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `skill`
--

CREATE TABLE `skill` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `categoryId` int(11) NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Daten für Tabelle `skill`
--

INSERT INTO `skill` (`id`, `name`, `categoryId`, `active`, `created`) VALUES
(1, 'Word', 3, 1, '2019-01-06 12:06:22'),
(2, 'PHP', 2, 1, '2019-01-06 12:06:22'),
(3, 'Test', 6, 0, '2019-01-06 13:10:33'),
(4, 'Javascript', 2, 1, '2019-01-12 17:20:10'),
(5, 'Python', 2, 1, '2019-01-12 17:20:10'),
(6, 'Java', 2, 1, '2019-01-12 17:20:10'),
(7, 'Ruby', 2, 1, '2019-01-12 17:20:10'),
(8, 'C  ', 2, 1, '2019-01-12 17:20:10'),
(9, 'C', 2, 1, '2019-01-12 17:20:10'),
(10, 'C#', 2, 1, '2019-01-12 17:20:10'),
(11, '.NET', 2, 1, '2019-01-12 17:20:10'),
(12, 'Visual Basic', 2, 1, '2019-01-12 17:20:10'),
(13, 'Visual F#', 2, 1, '2019-01-12 17:20:10'),
(14, 'Batch', 2, 1, '2019-01-12 17:20:10'),
(15, 'French', 1, 1, '2019-01-12 17:39:42');

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `skillassignment`
--

CREATE TABLE `skillassignment` (
  `id` int(11) NOT NULL,
  `skillId` int(11) NOT NULL,
  `scaleId` int(11) NOT NULL,
  `experience` double NOT NULL,
  `employeeId` int(11) NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Daten für Tabelle `skillassignment`
--

INSERT INTO `skillassignment` (`id`, `skillId`, `scaleId`, `experience`, `employeeId`, `active`, `created`) VALUES
(1, 2, 3, 2, 2, 0, '2019-01-06 12:07:46'),
(2, 1, 1, 50, 2, 1, '2019-01-06 12:07:46'),
(3, 4, 5, 3, 2, 1, '2019-01-12 19:20:33'),
(4, 5, 1, 0, 2, 0, '2019-01-18 20:03:09'),
(5, 5, 1, 0, 2, 1, '2019-01-18 20:03:17'),
(6, 5, 1, 0, 2, 1, '2019-01-18 20:04:22'),
(7, 5, 1, 0, 2, 1, '2019-01-18 20:04:42'),
(8, 5, 1, 0, 2, 1, '2019-01-18 20:05:43'),
(9, 5, 1, 0, 2, 1, '2019-01-18 20:06:32'),
(10, 5, 1, 0, 2, 1, '2019-01-18 20:08:17'),
(11, 5, 1, 0, 2, 1, '2019-01-18 20:09:20'),
(12, 2, 1, 0, 2, 1, '2019-02-14 16:11:10');

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `skillcategory`
--

CREATE TABLE `skillcategory` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Daten für Tabelle `skillcategory`
--

INSERT INTO `skillcategory` (`id`, `name`, `created`) VALUES
(1, 'Language', '2019-01-06 11:50:58'),
(2, 'Programming', '2019-01-06 11:50:58'),
(3, 'Software', '2019-01-06 11:50:58'),
(4, 'Framework', '2019-01-06 11:50:58'),
(5, 'Licenses', '2019-01-06 11:50:58'),
(6, 'Consulting', '2019-01-06 13:28:40'),
(7, 'Other', '2019-01-06 11:50:58');

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `skillscale`
--

CREATE TABLE `skillscale` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `value` int(11) NOT NULL,
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Daten für Tabelle `skillscale`
--

INSERT INTO `skillscale` (`id`, `name`, `value`, `created`) VALUES
(1, 'Basic knowledge', 2, '2019-01-12 18:33:47'),
(2, 'Limited experience', 4, '2019-01-12 18:33:47'),
(3, 'Intermediate', 6, '2019-01-12 18:33:47'),
(4, 'Advanced', 8, '2019-01-12 18:33:47'),
(5, 'Expert', 10, '2019-01-12 18:33:47');

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `statisticdata`
--

CREATE TABLE `statisticdata` (
  `Id` int(11) NOT NULL,
  `Key` varchar(255) NOT NULL,
  `Value` int(11) NOT NULL,
  `Timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `time`
--

CREATE TABLE `time` (
  `id` int(11) NOT NULL,
  `employeeId` int(11) NOT NULL,
  `day` date NOT NULL,
  `start` time DEFAULT NULL,
  `end` time DEFAULT NULL,
  `duration` int(11) NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Daten für Tabelle `time`
--

INSERT INTO `time` (`id`, `employeeId`, `day`, `start`, `end`, `duration`, `active`, `created`) VALUES
(3, 2, '2019-01-01', '05:04:00', '09:10:00', 742, 0, '2019-01-05 15:39:27'),
(36, 2, '2019-01-06', '08:04:00', '02:06:00', 742, 0, '2019-01-05 15:39:27'),
(37, 2, '2019-01-05', '09:00:00', '17:30:00', 240, 0, '2019-01-05 17:08:15'),
(38, 2, '2019-01-06', '09:00:00', '17:30:00', 128, 0, '2019-01-05 17:08:15'),
(39, 2, '2019-01-06', '09:00:00', '17:30:00', 8120, 0, '2019-01-05 17:12:31'),
(40, 2, '2019-01-05', '09:00:00', '17:30:00', 240, 0, '2019-01-05 17:12:31'),
(41, 2, '2019-01-06', '09:00:00', '17:30:00', 0, 0, '2019-01-05 17:13:55'),
(42, 2, '2019-01-05', '09:00:00', '17:30:00', 240, 0, '2019-01-05 17:13:55'),
(43, 2, '2019-01-06', '09:00:00', '17:30:00', 0, 0, '2019-01-05 17:14:28'),
(44, 2, '2019-01-05', '09:00:00', '17:30:00', 240, 0, '2019-01-05 17:14:28'),
(45, 2, '2019-01-06', '09:00:00', '17:30:00', 14120, 0, '2019-01-05 17:14:34'),
(46, 2, '2019-01-05', '09:00:00', '17:30:00', 240, 0, '2019-01-05 17:14:34'),
(47, 2, '2019-01-06', '09:00:00', '17:30:00', 132, 0, '2019-01-05 17:14:47'),
(48, 2, '2019-01-05', '09:00:00', '17:30:00', 240, 0, '2019-01-05 17:14:47'),
(49, 2, '2019-01-05', '09:00:00', '17:30:00', 240, 0, '2019-01-05 17:14:51'),
(50, 2, '2019-01-05', '09:00:00', '17:30:00', 240, 0, '2019-01-05 17:15:07'),
(51, 2, '2019-01-05', '09:00:00', '17:30:00', 240, 0, '2019-01-05 17:18:32'),
(52, 2, '2019-01-05', '09:00:00', '17:30:00', 240, 0, '2019-01-05 17:20:09'),
(53, 2, '2019-01-05', '09:00:00', '17:30:00', 240, 0, '2019-01-05 17:25:12'),
(54, 2, '2019-01-05', '09:00:00', '17:30:00', 240, 0, '2019-01-05 17:27:52'),
(55, 2, '2019-01-06', '09:00:00', '17:30:00', 240, 0, '2019-01-05 17:27:52'),
(56, 2, '2019-01-07', '09:00:00', '17:30:00', 240, 0, '2019-01-05 17:27:52'),
(57, 2, '2019-01-07', '09:00:00', '17:30:00', 240, 0, '2019-01-05 17:27:53'),
(58, 2, '2019-01-06', '09:00:00', '17:30:00', 240, 0, '2019-01-05 17:27:53'),
(59, 2, '2019-01-05', '09:00:00', '17:30:00', 240, 0, '2019-01-05 17:27:53'),
(60, 2, '2020-01-05', '09:00:00', '17:30:00', 240, 0, '2019-01-05 17:27:56'),
(61, 2, '2020-01-05', '09:00:00', '17:30:00', 240, 0, '2019-01-05 17:28:14'),
(62, 2, '2021-01-05', '09:00:00', '17:30:00', 240, 0, '2019-01-05 17:31:34'),
(63, 2, '2021-01-05', '09:00:00', '17:30:00', 240, 0, '2019-01-05 17:32:33'),
(64, 2, '2020-01-05', '09:00:00', '17:30:00', 240, 0, '2019-01-05 17:33:34'),
(65, 2, '2020-01-05', '09:00:00', '17:30:00', 240, 0, '2019-01-05 17:34:18'),
(66, 2, '2020-01-05', '09:00:00', '17:30:00', 240, 0, '2019-01-05 17:34:24'),
(67, 2, '2019-01-07', '09:00:00', '17:30:00', 240, 0, '2019-01-05 20:44:23'),
(68, 2, '2020-01-05', '09:00:00', '17:30:00', 240, 0, '2019-01-05 20:44:23'),
(69, 2, '2020-01-06', '09:00:00', '17:30:00', 240, 0, '2019-01-05 20:44:23'),
(70, 2, '2020-01-12', '09:00:00', '17:30:00', 240, 0, '2019-01-05 20:44:23'),
(71, 2, '0000-00-00', '00:00:00', '00:00:00', 0, 0, '2019-01-05 20:44:23'),
(72, 2, '0000-00-00', '00:00:00', '00:00:00', 0, 0, '2019-01-05 20:44:23'),
(73, 2, '2019-01-13', '09:00:00', '17:30:00', 240, 0, '2019-01-05 20:50:13'),
(74, 2, '2019-01-14', '09:00:00', '17:30:00', 240, 0, '2019-01-05 20:50:13'),
(75, 2, '2019-01-15', '09:00:00', '17:30:00', 240, 0, '2019-01-05 20:50:13'),
(76, 2, '2019-01-13', '09:00:00', '17:30:00', 240, 0, '2019-01-20 12:23:51'),
(77, 2, '2019-01-14', '09:00:00', '17:30:00', 240, 0, '2019-01-20 12:23:51'),
(78, 2, '2019-01-15', '09:00:00', '17:30:00', 240, 0, '2019-01-20 12:23:51'),
(79, 2, '2019-01-13', '09:00:00', '17:30:00', 240, 0, '2019-01-20 12:23:56'),
(80, 2, '2019-01-14', '09:00:00', '17:30:00', 240, 0, '2019-01-20 12:23:56'),
(81, 2, '2019-01-15', '09:00:00', '17:30:00', 240, 0, '2019-01-20 12:23:56'),
(82, 2, '2019-01-13', '09:00:00', '17:30:00', 240, 0, '2019-01-20 12:24:09'),
(83, 2, '2019-01-14', '09:00:00', '17:30:00', 240, 0, '2019-01-20 12:24:09'),
(84, 2, '2019-01-15', '09:00:00', '17:30:00', 250, 0, '2019-01-20 12:24:09'),
(85, 2, '2019-01-13', '09:00:00', '17:30:00', 240, 1, '2019-01-20 12:30:18'),
(86, 2, '2019-01-14', '09:00:00', '17:30:00', 240, 1, '2019-01-20 12:30:18'),
(87, 2, '2019-01-15', '09:00:00', '17:30:00', 250, 1, '2019-01-20 12:30:18');

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `uniqueref`
--

CREATE TABLE `uniqueref` (
  `id` int(11) NOT NULL,
  `ref` varchar(255) NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Daten für Tabelle `uniqueref`
--

INSERT INTO `uniqueref` (`id`, `ref`, `active`, `created`) VALUES
(43, '5c68155696457', 1, '2019-02-16 13:51:18'),
(44, '5c6815bf099e4', 1, '2019-02-16 13:53:03'),
(45, '5c6815d27f467', 1, '2019-02-16 13:53:22'),
(46, '5c6815e2797ca', 1, '2019-02-16 13:53:38'),
(47, '5c6815e584431', 1, '2019-02-16 13:53:41'),
(48, '5c6815f7bf16a', 1, '2019-02-16 13:53:59'),
(49, '5c68160bddd57', 1, '2019-02-16 13:54:19'),
(50, '5c6816281ea76', 1, '2019-02-16 13:54:48'),
(51, '5c68162fecbc0', 1, '2019-02-16 13:54:55'),
(52, '5c6816366456e', 1, '2019-02-16 13:55:02'),
(53, '5c68163d54645', 1, '2019-02-16 13:55:09'),
(54, '5c6816532af6f', 1, '2019-02-16 13:55:31'),
(55, '5c6816b57ab17', 1, '2019-02-16 13:57:09'),
(56, '5c6816c85c207', 1, '2019-02-16 13:57:28'),
(57, '5c6816d573cdf', 1, '2019-02-16 13:57:41'),
(58, '5c6816e14925d', 1, '2019-02-16 13:57:53'),
(59, '5c68172d151f5', 1, '2019-02-16 13:59:09'),
(60, '5c6817754b097', 1, '2019-02-16 14:00:21'),
(61, '5c6817dc9a45b', 1, '2019-02-16 14:02:04'),
(62, '5c681850d392f', 1, '2019-02-16 14:04:00'),
(63, '5c68190945da3', 1, '2019-02-16 14:07:05'),
(64, '5c68193614134', 1, '2019-02-16 14:07:50'),
(65, '5c6819da693b0', 1, '2019-02-16 14:10:34'),
(66, '5c681b92792d3', 1, '2019-02-16 14:17:54'),
(67, '5c681df5048fb', 1, '2019-02-16 14:28:05'),
(68, '5c681ea44f7e8', 1, '2019-02-16 14:31:00'),
(69, '5c681ebc0d998', 1, '2019-02-16 14:31:24'),
(70, '5c681ebfd6c4f', 1, '2019-02-16 14:31:27'),
(71, '5c754f6f3b808', 1, '2019-02-26 14:38:39'),
(72, '5c755b5510c04', 1, '2019-02-26 15:29:25'),
(73, '5c755b5537535', 1, '2019-02-26 15:29:25'),
(74, '5c755cbb08d87', 1, '2019-02-26 15:35:23'),
(75, '5c755cc0d8162', 1, '2019-02-26 15:35:28'),
(76, '5c755ce5a056c', 1, '2019-02-26 15:36:05'),
(77, '5c755d20dbf9f', 1, '2019-02-26 15:37:04'),
(78, '5c755efeaaea4', 1, '2019-02-26 15:45:02'),
(79, '5c755f09d86d7', 1, '2019-02-26 15:45:13'),
(80, '5c755f27866fd', 1, '2019-02-26 15:45:43'),
(81, '5c755f51e25f5', 1, '2019-02-26 15:46:25'),
(82, '5c755fca91fa4', 1, '2019-02-26 15:48:26'),
(83, '5c7561f931f3c', 1, '2019-02-26 15:57:45');

--
-- Indizes der exportierten Tabellen
--

--
-- Indizes für die Tabelle `country`
--
ALTER TABLE `country`
  ADD PRIMARY KEY (`id`);

--
-- Indizes für die Tabelle `cvgenerate`
--
ALTER TABLE `cvgenerate`
  ADD PRIMARY KEY (`id`);

--
-- Indizes für die Tabelle `cvreferences`
--
ALTER TABLE `cvreferences`
  ADD PRIMARY KEY (`id`);

--
-- Indizes für die Tabelle `education`
--
ALTER TABLE `education`
  ADD PRIMARY KEY (`id`);

--
-- Indizes für die Tabelle `employee`
--
ALTER TABLE `employee`
  ADD PRIMARY KEY (`id`);

--
-- Indizes für die Tabelle `file`
--
ALTER TABLE `file`
  ADD PRIMARY KEY (`id`);

--
-- Indizes für die Tabelle `language`
--
ALTER TABLE `language`
  ADD PRIMARY KEY (`id`);

--
-- Indizes für die Tabelle `logintoken`
--
ALTER TABLE `logintoken`
  ADD PRIMARY KEY (`id`);

--
-- Indizes für die Tabelle `logintry`
--
ALTER TABLE `logintry`
  ADD PRIMARY KEY (`id`);

--
-- Indizes für die Tabelle `permissionlevelname`
--
ALTER TABLE `permissionlevelname`
  ADD PRIMARY KEY (`id`);

--
-- Indizes für die Tabelle `personalinformation`
--
ALTER TABLE `personalinformation`
  ADD PRIMARY KEY (`id`);

--
-- Indizes für die Tabelle `professionalhistory`
--
ALTER TABLE `professionalhistory`
  ADD PRIMARY KEY (`id`);

--
-- Indizes für die Tabelle `skill`
--
ALTER TABLE `skill`
  ADD PRIMARY KEY (`id`);

--
-- Indizes für die Tabelle `skillassignment`
--
ALTER TABLE `skillassignment`
  ADD PRIMARY KEY (`id`);

--
-- Indizes für die Tabelle `skillcategory`
--
ALTER TABLE `skillcategory`
  ADD PRIMARY KEY (`id`);

--
-- Indizes für die Tabelle `skillscale`
--
ALTER TABLE `skillscale`
  ADD PRIMARY KEY (`id`);

--
-- Indizes für die Tabelle `time`
--
ALTER TABLE `time`
  ADD PRIMARY KEY (`id`);

--
-- Indizes für die Tabelle `uniqueref`
--
ALTER TABLE `uniqueref`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT für exportierte Tabellen
--

--
-- AUTO_INCREMENT für Tabelle `country`
--
ALTER TABLE `country`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=498;

--
-- AUTO_INCREMENT für Tabelle `cvgenerate`
--
ALTER TABLE `cvgenerate`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT für Tabelle `cvreferences`
--
ALTER TABLE `cvreferences`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT für Tabelle `education`
--
ALTER TABLE `education`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT für Tabelle `employee`
--
ALTER TABLE `employee`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT für Tabelle `file`
--
ALTER TABLE `file`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT für Tabelle `language`
--
ALTER TABLE `language`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT für Tabelle `logintoken`
--
ALTER TABLE `logintoken`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT für Tabelle `logintry`
--
ALTER TABLE `logintry`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `permissionlevelname`
--
ALTER TABLE `permissionlevelname`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT für Tabelle `personalinformation`
--
ALTER TABLE `personalinformation`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT für Tabelle `professionalhistory`
--
ALTER TABLE `professionalhistory`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT für Tabelle `skill`
--
ALTER TABLE `skill`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT für Tabelle `skillassignment`
--
ALTER TABLE `skillassignment`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT für Tabelle `skillcategory`
--
ALTER TABLE `skillcategory`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT für Tabelle `skillscale`
--
ALTER TABLE `skillscale`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT für Tabelle `time`
--
ALTER TABLE `time`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=88;

--
-- AUTO_INCREMENT für Tabelle `uniqueref`
--
ALTER TABLE `uniqueref`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=84;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
