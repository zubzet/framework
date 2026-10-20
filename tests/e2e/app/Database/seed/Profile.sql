INSERT INTO `z_organization`(`id`, `name`, `active`, `created`) VALUES
(900, 'profile_Organization', 1, '2000-01-01 12:00:00');

INSERT INTO `z_user`(`id`, `email`, `password`, `salt`, `active`, `created`, `verified`, `organizationId`) VALUES
(900, 'profile_view@cypress.test', NULL, NULL, 1, '2020-05-17 09:00:00', '2020-05-17 09:00:00', 900),
(901, 'profile_plain@cypress.test', NULL, NULL, 1, '2021-06-18 09:00:00', '2021-06-18 09:00:00', NULL),

(902, 'profile_password@cypress.test', '772e7e18b509ee9dbf4a53d415187fa49c68c991873e3282c0025e9e53d4c946125f184c34e04a7fcd5136fcdc04bedc17afd981380ee05ccb7683e7d83ec615', '4401287036553e310907533.22322450', 1, '2000-01-01 12:00:00', '2000-01-01 12:00:00', NULL),
(903, 'profile_reject@cypress.test', '772e7e18b509ee9dbf4a53d415187fa49c68c991873e3282c0025e9e53d4c946125f184c34e04a7fcd5136fcdc04bedc17afd981380ee05ccb7683e7d83ec615', '4401287036553e310907533.22322450', 1, '2000-01-01 12:00:00', '2000-01-01 12:00:00', NULL),

(904, 'profile_sessions@cypress.test', NULL, NULL, 1, '2000-01-01 12:00:00', '2000-01-01 12:00:00', NULL),
(905, 'profile_api_keys@cypress.test', NULL, NULL, 1, '2000-01-01 12:00:00', '2000-01-01 12:00:00', NULL),
(906, 'profile_foreign@cypress.test', NULL, NULL, 1, '2000-01-01 12:00:00', '2000-01-01 12:00:00', NULL),
(907, 'profile_guards@cypress.test', NULL, NULL, 1, '2000-01-01 12:00:00', '2000-01-01 12:00:00', NULL),
(908, 'profile_self_revoke@cypress.test', NULL, NULL, 1, '2000-01-01 12:00:00', '2000-01-01 12:00:00', NULL);

INSERT INTO `z_logintoken` (`id`, `uuid`, `token`, `userId`, `userId_exec`, `name`, `device`, `reason`, `ip_creation`, `ip_last`, `is_permanent`, `is_apikey`, `expires_at`, `last_used`, `created`, `active`) VALUES
-- view: the login cypress carries, so the page marks it as this browser
(900, '00000000-0000-7000-8000-000000000900', '0900a00000000000000000000000000000000000', 900, 900, NULL, 'Cypress Chrome on Linux', NULL, '203.0.113.10', '203.0.113.10', 0, 0, NULL, NULL, NOW(), 1),
-- view: a named session with fixed dates that was never used since
(901, '00000000-0000-7000-8000-000000000901', '0900b00000000000000000000000000000000000', 900, 900, 'Laptop at home', 'Firefox on Linux', 'z-admin impersonation', '203.0.113.11', NULL, 0, 0, '2099-01-02 10:15:00', NULL, '2025-03-04 08:30:00', 1),
-- view: an api key that never expires and was used once
(902, '00000000-0000-7000-8000-000000000902', '0900c00000000000000000000000000000000000', 900, 900, 'Deployment pipeline', NULL, NULL, '203.0.113.12', '203.0.113.13', 1, 1, NULL, '2025-04-05 07:15:00', '2025-03-05 06:00:00', 1),

-- plain: a login without any api key next to it
(903, '00000000-0000-7000-8000-000000000903', '0901a00000000000000000000000000000000000', 901, 901, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NOW(), 1),

-- password: the browser login and a second one, both ended by the change
(904, '00000000-0000-7000-8000-000000000904', '0902a00000000000000000000000000000000000', 902, 902, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NOW(), 1),
(905, '00000000-0000-7000-8000-000000000905', '0902b00000000000000000000000000000000000', 902, 902, 'Other browser', NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NOW(), 1),
-- password: an api key the change has to spare
(906, '00000000-0000-7000-8000-000000000906', '0902c00000000000000000000000000000000000', 902, 902, 'Survives the change', NULL, NULL, NULL, NULL, 1, 1, NULL, NULL, NOW(), 1),

-- reject: only a browser login, the failing posts change nothing
(907, '00000000-0000-7000-8000-000000000907', '0903a00000000000000000000000000000000000', 903, 903, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NOW(), 1),

-- sessions: the browser login, a rename target and a revoke target
(908, '00000000-0000-7000-8000-000000000908', '0904a00000000000000000000000000000000000', 904, 904, NULL, 'Cypress Chrome on Linux', NULL, NULL, NULL, 0, 0, NULL, NULL, NOW(), 1),
(909, '00000000-0000-7000-8000-000000000909', '0904b00000000000000000000000000000000000', 904, 904, 'Tablet in the kitchen', 'Safari on iPad', NULL, NULL, NULL, 0, 0, NULL, NULL, '2025-03-06 11:00:00', 1),
(910, '00000000-0000-7000-8000-000000000910', '0904c00000000000000000000000000000000000', 904, 904, NULL, 'Edge on Windows', NULL, NULL, NULL, 0, 0, NULL, NULL, '2025-03-07 12:00:00', 1),
-- sessions: an api key that clearing the sessions has to spare
(911, '00000000-0000-7000-8000-000000000911', '0904d00000000000000000000000000000000000', 904, 904, 'Nightly backup', NULL, NULL, NULL, NULL, 1, 1, NULL, NULL, NOW(), 1),

-- api keys: the browser login and an expiring key to revoke
(912, '00000000-0000-7000-8000-000000000912', '0905a00000000000000000000000000000000000', 905, 905, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NOW(), 1),
(913, '00000000-0000-7000-8000-000000000913', '0905b00000000000000000000000000000000000', 905, 905, 'Reporting job', NULL, NULL, '203.0.113.14', NULL, 0, 1, '2099-06-07 10:00:00', NULL, '2025-02-06 10:00:00', 1),

-- foreign: a session and an api key of somebody else, neither may be touched
(914, '00000000-0000-7000-8000-000000000914', '0906a00000000000000000000000000000000000', 906, 906, 'Not yours', NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NOW(), 1),
(915, '00000000-0000-7000-8000-000000000915', '0906b00000000000000000000000000000000000', 906, 906, 'Not yours either', NULL, NULL, NULL, NULL, 1, 1, NULL, NULL, NOW(), 1),

-- guards: one of each kind, so the endpoint tests can address the wrong one
(916, '00000000-0000-7000-8000-000000000916', '0907a00000000000000000000000000000000000', 907, 907, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NOW(), 1),
(917, '00000000-0000-7000-8000-000000000917', '0907b00000000000000000000000000000000000', 907, 907, 'Guarded key', NULL, NULL, NULL, NULL, 1, 1, NULL, NULL, NOW(), 1),

-- self revoke: the only login of its user, revoked from the page it renders
(918, '00000000-0000-7000-8000-000000000918', '0908a00000000000000000000000000000000000', 908, 908, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NOW(), 1);
