-- Fixtures for account/two-factor*.cy.js. last_2fa stays NULL, php writes it: see /TwoFactorProbe/ageSession

INSERT INTO `z_user`(`id`, `email`, `password`, `salt`, `active`, `created`, `verified`, `organizationId`, `totp_secret`, `totp_confirmed_at`) VALUES
-- off, with a password: enrollment
(850, 'twofactor_enroll@cypress.test', '772e7e18b509ee9dbf4a53d415187fa49c68c991873e3282c0025e9e53d4c946125f184c34e04a7fcd5136fcdc04bedc17afd981380ee05ccb7683e7d83ec615', '4401287036553e310907533.22322450', 1, '2020-01-01 12:00:00', '2020-01-01 12:00:00', NULL, NULL, NULL),

-- on: disabling needs a code
(851, 'twofactor_active@cypress.test', NULL, NULL, 1, '2020-01-01 12:00:00', '2020-01-01 12:00:00', NULL, 'JBSWY3DPEHPK3PXPJBSWY3DPEHPK3PXP', '2025-01-01 10:00:00'),

-- on, with a password: the login challenge
(852, 'twofactor_login@cypress.test', '772e7e18b509ee9dbf4a53d415187fa49c68c991873e3282c0025e9e53d4c946125f184c34e04a7fcd5136fcdc04bedc17afd981380ee05ccb7683e7d83ec615', '4401287036553e310907533.22322450', 1, '2020-01-01 12:00:00', '2020-01-01 12:00:00', NULL, 'JBSWY3DPEHPK3PXPJBSWY3DPEHPK3PXP', '2025-01-01 10:00:00'),

-- on: an admin takes it off
(853, 'twofactor_forced@cypress.test', NULL, NULL, 1, '2020-01-01 12:00:00', '2020-01-01 12:00:00', NULL, 'JBSWY3DPEHPK3PXPJBSWY3DPEHPK3PXP', '2025-01-01 10:00:00'),

-- on: the freshness gate
(854, 'twofactor_gate@cypress.test', NULL, NULL, 1, '2020-01-01 12:00:00', '2020-01-01 12:00:00', NULL, 'JBSWY3DPEHPK3PXPJBSWY3DPEHPK3PXP', '2025-01-01 10:00:00'),

-- off: the gate has nothing to ask
(855, 'twofactor_plain@cypress.test', NULL, NULL, 1, '2020-01-01 12:00:00', '2020-01-01 12:00:00', NULL, NULL, NULL),

-- a secret nobody confirmed
(856, 'twofactor_pending@cypress.test', NULL, NULL, 1, '2020-01-01 12:00:00', '2020-01-01 12:00:00', NULL, 'JBSWY3DPEHPK3PXPJBSWY3DPEHPK3PXP', NULL),

-- on, own secret: the try budget, and codes not being universal
(857, 'twofactor_tries@cypress.test', NULL, NULL, 1, '2020-01-01 12:00:00', '2020-01-01 12:00:00', NULL, 'GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ', '2025-01-01 10:00:00');

INSERT INTO `z_logintoken` (`id`, `uuid`, `token`, `userId`, `userId_exec`, `name`, `device`, `reason`, `ip_creation`, `ip_last`, `is_permanent`, `is_apikey`, `expires_at`, `last_used`, `created`, `active`) VALUES
(850, '00000000-0000-7000-8000-000000000850', '0850a00000000000000000000000000000000000', 850, 850, NULL, 'Cypress Chrome on Linux', NULL, NULL, NULL, 0, 0, NULL, NULL, NOW(), 1),
(851, '00000000-0000-7000-8000-000000000851', '0851a00000000000000000000000000000000000', 851, 851, NULL, 'Cypress Chrome on Linux', NULL, NULL, NULL, 0, 0, NULL, NULL, NOW(), 1),
(852, '00000000-0000-7000-8000-000000000852', '0852a00000000000000000000000000000000000', 852, 852, NULL, 'Cypress Chrome on Linux', NULL, NULL, NULL, 0, 0, NULL, NULL, NOW(), 1),
(853, '00000000-0000-7000-8000-000000000853', '0853a00000000000000000000000000000000000', 853, 853, NULL, 'Cypress Chrome on Linux', NULL, NULL, NULL, 0, 0, NULL, NULL, NOW(), 1),

-- gate: two logins, so one can be aged and the other not
(854, '00000000-0000-7000-8000-000000000854', '0854a00000000000000000000000000000000000', 854, 854, NULL, 'Cypress Chrome on Linux', NULL, NULL, NULL, 0, 0, NULL, NULL, NOW(), 1),
(855, '00000000-0000-7000-8000-000000000855', '0854b00000000000000000000000000000000000', 854, 854, 'Second browser', NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NOW(), 1),
-- gate: an api key, which never passes
(856, '00000000-0000-7000-8000-000000000856', '0854c00000000000000000000000000000000000', 854, 854, 'Deployment pipeline', NULL, NULL, NULL, NULL, 1, 1, NULL, NULL, NOW(), 1),

(857, '00000000-0000-7000-8000-000000000857', '0855a00000000000000000000000000000000000', 855, 855, NULL, 'Cypress Chrome on Linux', NULL, NULL, NULL, 0, 0, NULL, NULL, NOW(), 1),
-- plain: an api key the gate lets through
(858, '00000000-0000-7000-8000-000000000858', '0855b00000000000000000000000000000000000', 855, 855, 'Plain pipeline', NULL, NULL, NULL, NULL, 1, 1, NULL, NULL, NOW(), 1),

(859, '00000000-0000-7000-8000-000000000859', '0856a00000000000000000000000000000000000', 856, 856, NULL, 'Cypress Chrome on Linux', NULL, NULL, NULL, 0, 0, NULL, NULL, NOW(), 1),
(860, '00000000-0000-7000-8000-000000000860', '0857a00000000000000000000000000000000000', 857, 857, NULL, 'Cypress Chrome on Linux', NULL, NULL, NULL, 0, 0, NULL, NULL, NOW(), 1);
