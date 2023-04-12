/*
 Navicat Premium Data Transfer

 Source Server         : localhost
 Source Server Type    : MySQL
 Source Server Version : 100427
 Source Host           : localhost:3306
 Source Schema         : eva_invoice

 Target Server Type    : MySQL
 Target Server Version : 100427
 File Encoding         : 65001

 Date: 08/04/2023 14:45:14
*/

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- Table structure for countries
-- ----------------------------
DROP TABLE IF EXISTS `countries`;
CREATE TABLE `countries`  (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `code` varchar(2) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `prefix` varchar(6) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `currency` varchar(3) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 251 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of countries
-- ----------------------------
INSERT INTO `countries` VALUES (1, 'Afghanistan', 'AF', '+93', 'AFN');
INSERT INTO `countries` VALUES (2, 'Aland Islands', 'AX', '+358', 'EUR');
INSERT INTO `countries` VALUES (3, 'Albania', 'AL', '+355', 'ALL');
INSERT INTO `countries` VALUES (4, 'Algeria', 'DZ', '+213', 'DZD');
INSERT INTO `countries` VALUES (5, 'American Samoa', 'AS', '+1', 'USD');
INSERT INTO `countries` VALUES (6, 'Andorra', 'AD', '+376', 'EUR');
INSERT INTO `countries` VALUES (7, 'Angola', 'AO', '+244', 'AOA');
INSERT INTO `countries` VALUES (8, 'Anguilla', 'AI', '+1', 'XCD');
INSERT INTO `countries` VALUES (9, 'Antarctica', 'AQ', '+672', 'EUR');
INSERT INTO `countries` VALUES (10, 'Antigua and Barbuda', 'AG', '+1', 'XCD');
INSERT INTO `countries` VALUES (11, 'Argentina', 'AR', '+54', 'ARS');
INSERT INTO `countries` VALUES (12, 'Armenia', 'AM', '+374', 'AMD');
INSERT INTO `countries` VALUES (13, 'Aruba', 'AW', '+297', 'AWG');
INSERT INTO `countries` VALUES (14, 'Australia', 'AU', '+61', 'AUD');
INSERT INTO `countries` VALUES (15, 'Austria', 'AT', '+43', 'EUR');
INSERT INTO `countries` VALUES (16, 'Azerbaijan', 'AZ', '+994', 'AZN');
INSERT INTO `countries` VALUES (17, 'Bahamas', 'BS', '+1', 'BSD');
INSERT INTO `countries` VALUES (18, 'Bahrain', 'BH', '+973', 'BHD');
INSERT INTO `countries` VALUES (19, 'Bangladesh', 'BD', '+880', 'BDT');
INSERT INTO `countries` VALUES (20, 'Barbados', 'BB', '+1', 'BBD');
INSERT INTO `countries` VALUES (21, 'Belarus', 'BY', '+375', 'BYN');
INSERT INTO `countries` VALUES (22, 'Belgium', 'BE', '+32', 'EUR');
INSERT INTO `countries` VALUES (23, 'Belize', 'BZ', '+501', 'BZD');
INSERT INTO `countries` VALUES (24, 'Benin', 'BJ', '+229', 'XOF');
INSERT INTO `countries` VALUES (25, 'Bermuda', 'BM', '+1', 'BMD');
INSERT INTO `countries` VALUES (26, 'Bhutan', 'BT', '+975', 'INR');
INSERT INTO `countries` VALUES (27, 'Bolivia', 'BO', '+591', 'BOB');
INSERT INTO `countries` VALUES (28, 'Bonaire, Saint Eustatius and Saba ', 'BQ', '+599', 'USD');
INSERT INTO `countries` VALUES (29, 'Bosnia and Herzegovina', 'BA', '+387', 'BAM');
INSERT INTO `countries` VALUES (30, 'Botswana', 'BW', '+267', 'BWP');
INSERT INTO `countries` VALUES (31, 'Bouvet Island', 'BV', '+47', 'NOK');
INSERT INTO `countries` VALUES (32, 'Brazil', 'BR', '+55', 'BRL');
INSERT INTO `countries` VALUES (33, 'British Indian Ocean Territory', 'IO', '+246', 'USD');
INSERT INTO `countries` VALUES (34, 'British Virgin Islands', 'VG', '+1', 'USD');
INSERT INTO `countries` VALUES (35, 'Brunei', 'BN', '+673', 'BND');
INSERT INTO `countries` VALUES (36, 'Bulgaria', 'BG', '+359', 'BGN');
INSERT INTO `countries` VALUES (37, 'Burkina Faso', 'BF', '+226', 'XOF');
INSERT INTO `countries` VALUES (38, 'Burundi', 'BI', '+257', 'BIF');
INSERT INTO `countries` VALUES (39, 'Cambodia', 'KH', '+855', 'KHR');
INSERT INTO `countries` VALUES (40, 'Cameroon', 'CM', '+237', 'XAF');
INSERT INTO `countries` VALUES (41, 'Canada', 'CA', '+1', 'CAD');
INSERT INTO `countries` VALUES (42, 'Cape Verde', 'CV', '+238', 'CVE');
INSERT INTO `countries` VALUES (43, 'Cayman Islands', 'KY', '+1', 'KYD');
INSERT INTO `countries` VALUES (44, 'Central African Republic', 'CF', '+236', 'XAF');
INSERT INTO `countries` VALUES (45, 'Chad', 'TD', '+235', 'XAF');
INSERT INTO `countries` VALUES (46, 'Chile', 'CL', '+56', 'CLP');
INSERT INTO `countries` VALUES (47, 'China', 'CN', '+86', 'CNY');
INSERT INTO `countries` VALUES (48, 'Christmas Island', 'CX', '+61', 'AUD');
INSERT INTO `countries` VALUES (49, 'Cocos Islands', 'CC', '+891', 'AUD');
INSERT INTO `countries` VALUES (50, 'Colombia', 'CO', '+57', 'COU');
INSERT INTO `countries` VALUES (51, 'Comoros', 'KM', '+269', 'KMF');
INSERT INTO `countries` VALUES (52, 'Cook Islands', 'CK', '+682', 'NZD');
INSERT INTO `countries` VALUES (53, 'Costa Rica', 'CR', '+506', 'CRC');
INSERT INTO `countries` VALUES (54, 'Croatia', 'HR', '+385', 'HRK');
INSERT INTO `countries` VALUES (55, 'Cuba', 'CU', '+53', 'CUP');
INSERT INTO `countries` VALUES (56, 'Curacao', 'CW', '+599', 'ANG');
INSERT INTO `countries` VALUES (57, 'Cyprus', 'CY', '+357', 'EUR');
INSERT INTO `countries` VALUES (58, 'Czech Republic', 'CZ', '+420', 'CZK');
INSERT INTO `countries` VALUES (59, 'Democratic Republic of the Congo', 'CD', '+243', 'CDF');
INSERT INTO `countries` VALUES (60, 'Denmark', 'DK', '+45', 'DKK');
INSERT INTO `countries` VALUES (61, 'Djibouti', 'DJ', '+253', 'DJF');
INSERT INTO `countries` VALUES (62, 'Dominica', 'DM', '+1', 'XCD');
INSERT INTO `countries` VALUES (63, 'Dominican Republic', 'DO', '+1', 'DOP');
INSERT INTO `countries` VALUES (64, 'East Timor', 'TL', '+670', 'USD');
INSERT INTO `countries` VALUES (65, 'Ecuador', 'EC', '+593', 'USD');
INSERT INTO `countries` VALUES (66, 'Egypt', 'EG', '+20', 'EGP');
INSERT INTO `countries` VALUES (67, 'El Salvador', 'SV', '+503', 'USD');
INSERT INTO `countries` VALUES (68, 'Equatorial Guinea', 'GQ', '+240', 'XAF');
INSERT INTO `countries` VALUES (69, 'Eritrea', 'ER', '+291', 'ERN');
INSERT INTO `countries` VALUES (70, 'Estonia', 'EE', '+372', 'EUR');
INSERT INTO `countries` VALUES (71, 'Ethiopia', 'ET', '+251', 'ETB');
INSERT INTO `countries` VALUES (72, 'Falkland Islands', 'FK', '+500', 'FKP');
INSERT INTO `countries` VALUES (73, 'Faroe Islands', 'FO', '+298', 'DKK');
INSERT INTO `countries` VALUES (74, 'Fiji', 'FJ', '+679', 'FJD');
INSERT INTO `countries` VALUES (75, 'Finland', 'FI', '+358', 'EUR');
INSERT INTO `countries` VALUES (76, 'France', 'FR', '+33', 'EUR');
INSERT INTO `countries` VALUES (77, 'French Guiana', 'GF', '+594', 'EUR');
INSERT INTO `countries` VALUES (78, 'French Polynesia', 'PF', '+689', 'XPF');
INSERT INTO `countries` VALUES (79, 'French Southern Territories', 'TF', '+262', 'EUR');
INSERT INTO `countries` VALUES (80, 'Gabon', 'GA', '+241', 'XAF');
INSERT INTO `countries` VALUES (81, 'Gambia', 'GM', '+220', 'GMD');
INSERT INTO `countries` VALUES (82, 'Georgia', 'GE', '+995', 'GEL');
INSERT INTO `countries` VALUES (83, 'Germany', 'DE', '+49', 'EUR');
INSERT INTO `countries` VALUES (84, 'Ghana', 'GH', '+233', 'GHS');
INSERT INTO `countries` VALUES (85, 'Gibraltar', 'GI', '+350', 'GIP');
INSERT INTO `countries` VALUES (86, 'Greece', 'GR', '+30', 'EUR');
INSERT INTO `countries` VALUES (87, 'Greenland', 'GL', '+299', 'DKK');
INSERT INTO `countries` VALUES (88, 'Grenada', 'GD', '+1', 'XCD');
INSERT INTO `countries` VALUES (89, 'Guadeloupe', 'GP', '+590', 'EUR');
INSERT INTO `countries` VALUES (90, 'Guam', 'GU', '+1', 'USD');
INSERT INTO `countries` VALUES (91, 'Guatemala', 'GT', '+502', 'GTQ');
INSERT INTO `countries` VALUES (92, 'Guernsey', 'GG', '+44', 'GBP');
INSERT INTO `countries` VALUES (93, 'Guinea', 'GN', '+224', 'GNF');
INSERT INTO `countries` VALUES (94, 'Guinea-Bissau', 'GW', '+245', 'XOF');
INSERT INTO `countries` VALUES (95, 'Guyana', 'GY', '+592', 'GYD');
INSERT INTO `countries` VALUES (96, 'Haiti', 'HT', '+509', 'USD');
INSERT INTO `countries` VALUES (97, 'Heard Island and McDonald Islands', 'HM', '+672', 'AUD');
INSERT INTO `countries` VALUES (98, 'Honduras', 'HN', '+504', 'HNL');
INSERT INTO `countries` VALUES (99, 'Hong Kong', 'HK', '+852', 'HKD');
INSERT INTO `countries` VALUES (100, 'Hungary', 'HU', '+36', 'HUF');
INSERT INTO `countries` VALUES (101, 'Iceland', 'IS', '+354', 'ISK');
INSERT INTO `countries` VALUES (102, 'India', 'IN', '+91', 'INR');
INSERT INTO `countries` VALUES (103, 'Indonesia', 'ID', '+62', 'IDR');
INSERT INTO `countries` VALUES (104, 'Iran', 'IR', '+98', 'IRR');
INSERT INTO `countries` VALUES (105, 'Iraq', 'IQ', '+964', 'IQD');
INSERT INTO `countries` VALUES (106, 'Ireland', 'IE', '+353', 'EUR');
INSERT INTO `countries` VALUES (107, 'Isle of Man', 'IM', '+44', 'GBP');
INSERT INTO `countries` VALUES (108, 'Israel', 'IL', '+972', 'ILS');
INSERT INTO `countries` VALUES (109, 'Italy', 'IT', '+39', 'EUR');
INSERT INTO `countries` VALUES (110, 'Ivory Coast', 'CI', '+225', 'XOF');
INSERT INTO `countries` VALUES (111, 'Jamaica', 'JM', '+1', 'JMD');
INSERT INTO `countries` VALUES (112, 'Japan', 'JP', '+81', 'JPY');
INSERT INTO `countries` VALUES (113, 'Jersey', 'JE', '+44', 'GBP');
INSERT INTO `countries` VALUES (114, 'Jordan', 'JO', '+962', 'JOD');
INSERT INTO `countries` VALUES (115, 'Kazakhstan', 'KZ', '+7', 'KZT');
INSERT INTO `countries` VALUES (116, 'Kenya', 'KE', '+254', 'KES');
INSERT INTO `countries` VALUES (117, 'Kiribati', 'KI', '+686', 'AUD');
INSERT INTO `countries` VALUES (118, 'Kosovo', 'XK', '+383', 'EUR');
INSERT INTO `countries` VALUES (119, 'Kuwait', 'KW', '+965', 'KWD');
INSERT INTO `countries` VALUES (120, 'Kyrgyzstan', 'KG', '+996', 'KGS');
INSERT INTO `countries` VALUES (121, 'Laos', 'LA', '+856', 'LAK');
INSERT INTO `countries` VALUES (122, 'Latvia', 'LV', '+371', 'EUR');
INSERT INTO `countries` VALUES (123, 'Lebanon', 'LB', '+961', 'LBP');
INSERT INTO `countries` VALUES (124, 'Lesotho', 'LS', '+266', 'ZAR');
INSERT INTO `countries` VALUES (125, 'Liberia', 'LR', '+231', 'LRD');
INSERT INTO `countries` VALUES (126, 'Libya', 'LY', '+218', 'LYD');
INSERT INTO `countries` VALUES (127, 'Liechtenstein', 'LI', '+423', 'CHF');
INSERT INTO `countries` VALUES (128, 'Lithuania', 'LT', '+370', 'EUR');
INSERT INTO `countries` VALUES (129, 'Luxembourg', 'LU', '+352', 'EUR');
INSERT INTO `countries` VALUES (130, 'Macao', 'MO', '+853', 'MOP');
INSERT INTO `countries` VALUES (131, 'Macedonia', 'MK', '+389', 'MKD');
INSERT INTO `countries` VALUES (132, 'Madagascar', 'MG', '+261', 'MGA');
INSERT INTO `countries` VALUES (133, 'Malawi', 'MW', '+265', 'MWK');
INSERT INTO `countries` VALUES (134, 'Malaysia', 'MY', '+60', 'MYR');
INSERT INTO `countries` VALUES (135, 'Maldives', 'MV', '+960', 'MVR');
INSERT INTO `countries` VALUES (136, 'Mali', 'ML', '+223', 'XOF');
INSERT INTO `countries` VALUES (137, 'Malta', 'MT', '+356', 'EUR');
INSERT INTO `countries` VALUES (138, 'Marshall Islands', 'MH', '+692', 'USD');
INSERT INTO `countries` VALUES (139, 'Martinique', 'MQ', '+596', 'EUR');
INSERT INTO `countries` VALUES (140, 'Mauritania', 'MR', '+222', 'MRU');
INSERT INTO `countries` VALUES (141, 'Mauritius', 'MU', '+230', 'MUR');
INSERT INTO `countries` VALUES (142, 'Mayotte', 'YT', '+262', 'EUR');
INSERT INTO `countries` VALUES (143, 'Mexico', 'MX', '+52', 'MXV');
INSERT INTO `countries` VALUES (144, 'Micronesia', 'FM', '+691', 'USD');
INSERT INTO `countries` VALUES (145, 'Moldova', 'MD', '+373', 'MDL');
INSERT INTO `countries` VALUES (146, 'Monaco', 'MC', '+377', 'EUR');
INSERT INTO `countries` VALUES (147, 'Mongolia', 'MN', '+976', 'MNT');
INSERT INTO `countries` VALUES (148, 'Montenegro', 'ME', '+382', 'EUR');
INSERT INTO `countries` VALUES (149, 'Montserrat', 'MS', '+1', 'XCD');
INSERT INTO `countries` VALUES (150, 'Morocco', 'MA', '+212', 'MAD');
INSERT INTO `countries` VALUES (151, 'Mozambique', 'MZ', '+258', 'MZN');
INSERT INTO `countries` VALUES (152, 'Myanmar', 'MM', '+95', 'MMK');
INSERT INTO `countries` VALUES (153, 'Namibia', 'NA', '+264', 'ZAR');
INSERT INTO `countries` VALUES (154, 'Nauru', 'NR', '+674', 'AUD');
INSERT INTO `countries` VALUES (155, 'Nepal', 'NP', '+977', 'NPR');
INSERT INTO `countries` VALUES (156, 'Netherlands', 'NL', '+31', 'EUR');
INSERT INTO `countries` VALUES (157, 'New Caledonia', 'NC', '+687', 'XPF');
INSERT INTO `countries` VALUES (158, 'New Zealand', 'NZ', '+64', 'NZD');
INSERT INTO `countries` VALUES (159, 'Nicaragua', 'NI', '+505', 'NIO');
INSERT INTO `countries` VALUES (160, 'Niger', 'NE', '+227', 'XOF');
INSERT INTO `countries` VALUES (161, 'Nigeria', 'NG', '+234', 'NGN');
INSERT INTO `countries` VALUES (162, 'Niue', 'NU', '+683', 'NZD');
INSERT INTO `countries` VALUES (163, 'Norfolk Island', 'NF', '+672', 'AUD');
INSERT INTO `countries` VALUES (164, 'North Korea', 'KP', '+850', 'KPW');
INSERT INTO `countries` VALUES (165, 'Northern Mariana Islands', 'MP', '+1', 'USD');
INSERT INTO `countries` VALUES (166, 'Norway', 'NO', '+47', 'NOK');
INSERT INTO `countries` VALUES (167, 'Oman', 'OM', '+968', 'OMR');
INSERT INTO `countries` VALUES (168, 'Pakistan', 'PK', '+92', 'PKR');
INSERT INTO `countries` VALUES (169, 'Palau', 'PW', '+680', 'USD');
INSERT INTO `countries` VALUES (170, 'Palestinian Territory', 'PS', '+970', 'NIS');
INSERT INTO `countries` VALUES (171, 'Panama', 'PA', '+507', 'USD');
INSERT INTO `countries` VALUES (172, 'Papua New Guinea', 'PG', '+675', 'PGK');
INSERT INTO `countries` VALUES (173, 'Paraguay', 'PY', '+595', 'PYG');
INSERT INTO `countries` VALUES (174, 'Peru', 'PE', '+51', 'PEN');
INSERT INTO `countries` VALUES (175, 'Philippines', 'PH', '+63', 'PHP');
INSERT INTO `countries` VALUES (176, 'Pitcairn', 'PN', '+870', 'NZD');
INSERT INTO `countries` VALUES (177, 'Poland', 'PL', '+48', 'PLN');
INSERT INTO `countries` VALUES (178, 'Portugal', 'PT', '+351', 'EUR');
INSERT INTO `countries` VALUES (179, 'Puerto Rico', 'PR', '+1', 'USD');
INSERT INTO `countries` VALUES (180, 'Qatar', 'QA', '+974', 'QAR');
INSERT INTO `countries` VALUES (181, 'Republic of the Congo', 'CG', '+242', 'XAF');
INSERT INTO `countries` VALUES (182, 'Reunion', 'RE', '+262', 'EUR');
INSERT INTO `countries` VALUES (183, 'Romania', 'RO', '+40', 'RON');
INSERT INTO `countries` VALUES (184, 'Russia', 'RU', '+7', 'RUB');
INSERT INTO `countries` VALUES (185, 'Rwanda', 'RW', '+250', 'RWF');
INSERT INTO `countries` VALUES (186, 'Saint Barthelemy', 'BL', '+590', 'EUR');
INSERT INTO `countries` VALUES (187, 'Saint Helena', 'SH', '+290', 'SHP');
INSERT INTO `countries` VALUES (188, 'Saint Kitts and Nevis', 'KN', '+1', 'XCD');
INSERT INTO `countries` VALUES (189, 'Saint Lucia', 'LC', '+1', 'XCD');
INSERT INTO `countries` VALUES (190, 'Saint Martin', 'MF', '+1', 'EUR');
INSERT INTO `countries` VALUES (191, 'Saint Pierre and Miquelon', 'PM', '+508', 'EUR');
INSERT INTO `countries` VALUES (192, 'Saint Vincent and the Grenadines', 'VC', '+1', 'XCD');
INSERT INTO `countries` VALUES (193, 'Samoa', 'WS', '+685', 'WST');
INSERT INTO `countries` VALUES (194, 'San Marino', 'SM', '+378', 'EUR');
INSERT INTO `countries` VALUES (195, 'Sao Tome and Principe', 'ST', '+239', 'STN');
INSERT INTO `countries` VALUES (196, 'Saudi Arabia', 'SA', '+966', 'SAR');
INSERT INTO `countries` VALUES (197, 'Senegal', 'SN', '+221', 'XOF');
INSERT INTO `countries` VALUES (198, 'Serbia', 'RS', '+381', 'RSD');
INSERT INTO `countries` VALUES (199, 'Seychelles', 'SC', '+248', 'SCR');
INSERT INTO `countries` VALUES (200, 'Sierra Leone', 'SL', '+232', 'SLL');
INSERT INTO `countries` VALUES (201, 'Singapore', 'SG', '+65', 'SGD');
INSERT INTO `countries` VALUES (202, 'Sint Maarten', 'SX', '+1', 'ANG');
INSERT INTO `countries` VALUES (203, 'Slovakia', 'SK', '+421', 'EUR');
INSERT INTO `countries` VALUES (204, 'Slovenia', 'SI', '+386', 'EUR');
INSERT INTO `countries` VALUES (205, 'Solomon Islands', 'SB', '+677', 'SBD');
INSERT INTO `countries` VALUES (206, 'Somalia', 'SO', '+252', 'SOS');
INSERT INTO `countries` VALUES (207, 'South Africa', 'ZA', '+27', 'ZAR');
INSERT INTO `countries` VALUES (208, 'South Georgia and the South Sandwich Islands', 'GS', '+500', 'GBP');
INSERT INTO `countries` VALUES (209, 'South Korea', 'KR', '+82', 'KRW');
INSERT INTO `countries` VALUES (210, 'South Sudan', 'SS', '+211', 'SSP');
INSERT INTO `countries` VALUES (211, 'Spain', 'ES', '+34', 'EUR');
INSERT INTO `countries` VALUES (212, 'Sri Lanka', 'LK', '+94', 'LKR');
INSERT INTO `countries` VALUES (213, 'Sudan', 'SD', '+249', 'SDG');
INSERT INTO `countries` VALUES (214, 'Suriname', 'SR', '+597', 'SRD');
INSERT INTO `countries` VALUES (215, 'Svalbard and Jan Mayen', 'SJ', '+47', 'NOK');
INSERT INTO `countries` VALUES (216, 'Swaziland', 'SZ', '+268', 'SZL');
INSERT INTO `countries` VALUES (217, 'Sweden', 'SE', '+46', 'SEK');
INSERT INTO `countries` VALUES (218, 'Switzerland', 'CH', '+41', 'CHW');
INSERT INTO `countries` VALUES (219, 'Syria', 'SY', '+963', 'SYP');
INSERT INTO `countries` VALUES (220, 'Taiwan', 'TW', '+886', 'TWD');
INSERT INTO `countries` VALUES (221, 'Tajikistan', 'TJ', '+992', 'TJS');
INSERT INTO `countries` VALUES (222, 'Tanzania', 'TZ', '+255', 'TZS');
INSERT INTO `countries` VALUES (223, 'Thailand', 'TH', '+66', 'THB');
INSERT INTO `countries` VALUES (224, 'Togo', 'TG', '+228', 'XOF');
INSERT INTO `countries` VALUES (225, 'Tokelau', 'TK', '+690', 'NZD');
INSERT INTO `countries` VALUES (226, 'Tonga', 'TO', '+676', 'TOP');
INSERT INTO `countries` VALUES (227, 'Trinidad and Tobago', 'TT', '+1', 'TTD');
INSERT INTO `countries` VALUES (228, 'Tunisia', 'TN', '+216', 'TND');
INSERT INTO `countries` VALUES (229, 'Turkey', 'TR', '+90', 'TRY');
INSERT INTO `countries` VALUES (230, 'Turkmenistan', 'TM', '+993', 'TMT');
INSERT INTO `countries` VALUES (231, 'Turks and Caicos Islands', 'TC', '+1', 'USD');
INSERT INTO `countries` VALUES (232, 'Tuvalu', 'TV', '+688', 'AUD');
INSERT INTO `countries` VALUES (233, 'U.S. Virgin Islands', 'VI', '+1', 'USD');
INSERT INTO `countries` VALUES (234, 'Uganda', 'UG', '+256', 'UGX');
INSERT INTO `countries` VALUES (235, 'Ukraine', 'UA', '+380', 'UAH');
INSERT INTO `countries` VALUES (236, 'United Arab Emirates', 'AE', '+971', 'AED');
INSERT INTO `countries` VALUES (237, 'United Kingdom', 'GB', '+44', 'GBP');
INSERT INTO `countries` VALUES (238, 'United States', 'US', '+1', 'USD');
INSERT INTO `countries` VALUES (239, 'United States Minor Outlying Islands', 'UM', '+1', 'USD');
INSERT INTO `countries` VALUES (240, 'Uruguay', 'UY', '+598', 'UYU');
INSERT INTO `countries` VALUES (241, 'Uzbekistan', 'UZ', '+998', 'UZS');
INSERT INTO `countries` VALUES (242, 'Vanuatu', 'VU', '+678', 'VUV');
INSERT INTO `countries` VALUES (243, 'Vatican', 'VA', '+39', 'EUR');
INSERT INTO `countries` VALUES (244, 'Venezuela', 'VE', '+58', 'VEF');
INSERT INTO `countries` VALUES (245, 'Vietnam', 'VN', '+84', 'VND');
INSERT INTO `countries` VALUES (246, 'Wallis and Futuna', 'WF', '+681', 'XPF');
INSERT INTO `countries` VALUES (247, 'Western Sahara', 'EH', '+212', 'MAD');
INSERT INTO `countries` VALUES (248, 'Yemen', 'YE', '+967', 'YER');
INSERT INTO `countries` VALUES (249, 'Zambia', 'ZM', '+260', 'ZMW');
INSERT INTO `countries` VALUES (250, 'Zimbabwe', 'ZW', '+263', 'ZWL');

SET FOREIGN_KEY_CHECKS = 1;
