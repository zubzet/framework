-- Logins
INSERT INTO `z_user` (`id`, `email`, `password`, `salt`, `languageId`, `verified`) VALUES
(1, 'admin@zierhut-it.de', '772e7e18b509ee9dbf4a53d415187fa49c68c991873e3282c0025e9e53d4c946125f184c34e04a7fcd5136fcdc04bedc17afd981380ee05ccb7683e7d83ec615', '4401287036553e310907533.22322450', 0, CURRENT_TIMESTAMP()),
(2, 'support@zierhut-it.de', '772e7e18b509ee9dbf4a53d415187fa49c68c991873e3282c0025e9e53d4c946125f184c34e04a7fcd5136fcdc04bedc17afd981380ee05ccb7683e7d83ec615', '4401287036553e310907533.22322450', 0, CURRENT_TIMESTAMP()),
(3, 'customer@zierhut-it.de', '772e7e18b509ee9dbf4a53d415187fa49c68c991873e3282c0025e9e53d4c946125f184c34e04a7fcd5136fcdc04bedc17afd981380ee05ccb7683e7d83ec615', '4401287036553e310907533.22322450', 1, CURRENT_TIMESTAMP()),
(4, 'not-activated@domain.de', '772e7e18b509ee9dbf4a53d415187fa49c68c991873e3282c0025e9e53d4c946125f184c34e04a7fcd5136fcdc04bedc17afd981380ee05ccb7683e7d83ec615', '4401287036553e310907533.22322450', 0, NULL),
(5, 'customer-new@zierhut-it.de', '772e7e18b509ee9dbf4a53d415187fa49c68c991873e3282c0025e9e53d4c946125f184c34e04a7fcd5136fcdc04bedc17afd981380ee05ccb7683e7d83ec615', '4401287036553e310907533.22322450', 0, CURRENT_TIMESTAMP());

INSERT INTO `z_user_role` (`user`, `role`) VALUES
(1, 1),     -- Admin
(2, 2),     -- Support
(3, 3),     -- Customer
(3, 4),     -- Customer
(5, 3);     -- New Customer

-- Tokens
INSERT INTO `z_logintoken` (`token`, `userId`, `userId_exec`, `created`) VALUES
('aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa01', 1, 1, NOW()),
('aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa02', 2, 2, NOW()),
('aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa03', 3, 3, NOW()),
('aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa05', 5, 5, NOW());
