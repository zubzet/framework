INSERT INTO `z_user` (`id`, `email`, `password`, `salt`, `languageId`, `language_code`, `verified`) VALUES
(1000, 'locale-de@zierhut-it.de', '772e7e18b509ee9dbf4a53d415187fa49c68c991873e3282c0025e9e53d4c946125f184c34e04a7fcd5136fcdc04bedc17afd981380ee05ccb7683e7d83ec615', '4401287036553e310907533.22322450', 0, 'de', CURRENT_TIMESTAMP()),
(1001, 'locale-en@zierhut-it.de', '772e7e18b509ee9dbf4a53d415187fa49c68c991873e3282c0025e9e53d4c946125f184c34e04a7fcd5136fcdc04bedc17afd981380ee05ccb7683e7d83ec615', '4401287036553e310907533.22322450', 0, 'en', CURRENT_TIMESTAMP()),
(1002, 'locale-undefined@zierhut-it.de', '772e7e18b509ee9dbf4a53d415187fa49c68c991873e3282c0025e9e53d4c946125f184c34e04a7fcd5136fcdc04bedc17afd981380ee05ccb7683e7d83ec615', '4401287036553e310907533.22322450', 0, NULL, CURRENT_TIMESTAMP()),
(1003, 'locale-uncovered@zierhut-it.de', '772e7e18b509ee9dbf4a53d415187fa49c68c991873e3282c0025e9e53d4c946125f184c34e04a7fcd5136fcdc04bedc17afd981380ee05ccb7683e7d83ec615', '4401287036553e310907533.22322450', 0, 'fr', CURRENT_TIMESTAMP());

INSERT INTO `z_logintoken` (`token`, `userId`, `userId_exec`, `created`) VALUES
('user_locale_de', 1000, 1000, NOW()),
('user_locale_en', 1001, 1001, NOW()),
('user_locale_undefined', 1002, 1002, NOW()),
('user_locale_uncovered', 1003, 1003, NOW());