INSERT INTO `z_organization`(`id`, `name`, `active`, `created`) VALUES
(800, 'profile_Organization', 1, '2000-01-01 12:00:00');

INSERT INTO `z_user`(`id`, `email`, `password`, `salt`, `active`, `created`, `verified`, `organizationId`) VALUES
(800, 'profile_view@cypress.test', NULL, NULL, 1, '2020-05-17 09:00:00', '2020-05-17 09:00:00', 800),
(801, 'profile_plain@cypress.test', NULL, NULL, 1, '2021-06-18 09:00:00', '2021-06-18 09:00:00', NULL),

(802, 'profile_password@cypress.test', '772e7e18b509ee9dbf4a53d415187fa49c68c991873e3282c0025e9e53d4c946125f184c34e04a7fcd5136fcdc04bedc17afd981380ee05ccb7683e7d83ec615', '4401287036553e310907533.22322450', 1, '2000-01-01 12:00:00', '2000-01-01 12:00:00', NULL),
(803, 'profile_reject@cypress.test', '772e7e18b509ee9dbf4a53d415187fa49c68c991873e3282c0025e9e53d4c946125f184c34e04a7fcd5136fcdc04bedc17afd981380ee05ccb7683e7d83ec615', '4401287036553e310907533.22322450', 1, '2000-01-01 12:00:00', '2000-01-01 12:00:00', NULL),

(804, 'profile_sessions@cypress.test', NULL, NULL, 1, '2000-01-01 12:00:00', '2000-01-01 12:00:00', NULL),
(805, 'profile_api_keys@cypress.test', NULL, NULL, 1, '2000-01-01 12:00:00', '2000-01-01 12:00:00', NULL),
(806, 'profile_foreign@cypress.test', NULL, NULL, 1, '2000-01-01 12:00:00', '2000-01-01 12:00:00', NULL),
(807, 'profile_guards@cypress.test', NULL, NULL, 1, '2000-01-01 12:00:00', '2000-01-01 12:00:00', NULL),
(808, 'profile_self_revoke@cypress.test', NULL, NULL, 1, '2000-01-01 12:00:00', '2000-01-01 12:00:00', NULL);

INSERT INTO `z_logintoken` (`id`, `uuid`, `token`, `userId`, `userId_exec`, `name`, `device`, `reason`, `ip_creation`, `ip_last`, `is_permanent`, `is_apikey`, `expires_at`, `last_used`, `created`, `active`) VALUES
-- view: the login cypress carries, so the page marks it as this browser
(800, '00000000-0000-7000-8000-000000000800', '0800a00000000000000000000000000000000000', 800, 800, NULL, 'Cypress Chrome on Linux', NULL, '203.0.113.10', '203.0.113.10', 0, 0, NULL, NULL, NOW(), 1),
-- view: a named session with fixed dates that was never used since
(801, '00000000-0000-7000-8000-000000000801', '0800b00000000000000000000000000000000000', 800, 800, 'Laptop at home', 'Firefox on Linux', 'z-admin impersonation', '203.0.113.11', NULL, 0, 0, '2099-01-02 10:15:00', NULL, '2025-03-04 08:30:00', 1),
-- view: an api key that never expires and was used once
(802, '00000000-0000-7000-8000-000000000802', '0800c00000000000000000000000000000000000', 800, 800, 'Deployment pipeline', NULL, NULL, '203.0.113.12', '203.0.113.13', 1, 1, NULL, '2025-04-05 07:15:00', '2025-03-05 06:00:00', 1),

-- plain: a login without any api key next to it
(803, '00000000-0000-7000-8000-000000000803', '0801a00000000000000000000000000000000000', 801, 801, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NOW(), 1),

-- password: the browser login and a second one, both ended by the change
(804, '00000000-0000-7000-8000-000000000804', '0802a00000000000000000000000000000000000', 802, 802, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NOW(), 1),
(805, '00000000-0000-7000-8000-000000000805', '0802b00000000000000000000000000000000000', 802, 802, 'Other browser', NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NOW(), 1),
-- password: an api key the change has to spare
(806, '00000000-0000-7000-8000-000000000806', '0802c00000000000000000000000000000000000', 802, 802, 'Survives the change', NULL, NULL, NULL, NULL, 1, 1, NULL, NULL, NOW(), 1),

-- reject: only a browser login, the failing posts change nothing
(807, '00000000-0000-7000-8000-000000000807', '0803a00000000000000000000000000000000000', 803, 803, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NOW(), 1),

-- sessions: the browser login, a rename target and a revoke target
(808, '00000000-0000-7000-8000-000000000808', '0804a00000000000000000000000000000000000', 804, 804, NULL, 'Cypress Chrome on Linux', NULL, NULL, NULL, 0, 0, NULL, NULL, NOW(), 1),
(809, '00000000-0000-7000-8000-000000000809', '0804b00000000000000000000000000000000000', 804, 804, 'Tablet in the kitchen', 'Safari on iPad', NULL, NULL, NULL, 0, 0, NULL, NULL, '2025-03-06 11:00:00', 1),
(810, '00000000-0000-7000-8000-000000000810', '0804c00000000000000000000000000000000000', 804, 804, NULL, 'Edge on Windows', NULL, NULL, NULL, 0, 0, NULL, NULL, '2025-03-07 12:00:00', 1),
-- sessions: an api key that clearing the sessions has to spare
(811, '00000000-0000-7000-8000-000000000811', '0804d00000000000000000000000000000000000', 804, 804, 'Nightly backup', NULL, NULL, NULL, NULL, 1, 1, NULL, NULL, NOW(), 1),

-- api keys: the browser login and an expiring key to revoke
(812, '00000000-0000-7000-8000-000000000812', '0805a00000000000000000000000000000000000', 805, 805, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NOW(), 1),
(813, '00000000-0000-7000-8000-000000000813', '0805b00000000000000000000000000000000000', 805, 805, 'Reporting job', NULL, NULL, '203.0.113.14', NULL, 0, 1, '2099-06-07 10:00:00', NULL, '2025-02-06 10:00:00', 1),

-- foreign: a session and an api key of somebody else, neither may be touched
(814, '00000000-0000-7000-8000-000000000814', '0806a00000000000000000000000000000000000', 806, 806, 'Not yours', NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NOW(), 1),
(815, '00000000-0000-7000-8000-000000000815', '0806b00000000000000000000000000000000000', 806, 806, 'Not yours either', NULL, NULL, NULL, NULL, 1, 1, NULL, NULL, NOW(), 1),

-- guards: one of each kind, so the endpoint tests can address the wrong one
(816, '00000000-0000-7000-8000-000000000816', '0807a00000000000000000000000000000000000', 807, 807, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NOW(), 1),
(817, '00000000-0000-7000-8000-000000000817', '0807b00000000000000000000000000000000000', 807, 807, 'Guarded key', NULL, NULL, NULL, NULL, 1, 1, NULL, NULL, NOW(), 1),

-- self revoke: the only login of its user, revoked from the page it renders
(818, '00000000-0000-7000-8000-000000000818', '0808a00000000000000000000000000000000000', 808, 808, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NOW(), 1);
