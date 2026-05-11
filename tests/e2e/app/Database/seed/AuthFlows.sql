/*
    User Data for Auth Flow Tests (account/auth-flows.cy.js)

    ID range: 600–620 (users + tokens) — non-overlapping with Permission (100s),
    Role (200s), Group (300s), Session (400s).

    Password hash for "password" matches the canonical seed in zubzet/1_users.sql,
    so cy.request POSTs to /login can use the literal string "password".
*/


INSERT INTO `z_user`(`id`, `email`, `password`, `salt`, `active`, `created`, `verified`) VALUES
-- 600: action_logout — has an active session token below
(600, 'auth_logout@cypress.test',
    '772e7e18b509ee9dbf4a53d415187fa49c68c991873e3282c0025e9e53d4c946125f184c34e04a7fcd5136fcdc04bedc17afd981380ee05ccb7683e7d83ec615',
    '4401287036553e310907533.22322450',
    1, '2000-01-01 12:00:00', '2000-01-01 12:00:00'),

-- 601: action_forgot_password — verified user, will receive reset link by mail
(601, 'auth_forgot@cypress.test',
    '772e7e18b509ee9dbf4a53d415187fa49c68c991873e3282c0025e9e53d4c946125f184c34e04a7fcd5136fcdc04bedc17afd981380ee05ccb7683e7d83ec615',
    '4401287036553e310907533.22322450',
    1, '2000-01-01 12:00:00', '2000-01-01 12:00:00'),

-- 602: action_reset — verified user; test will request reset, then consume it
(602, 'auth_reset@cypress.test',
    '772e7e18b509ee9dbf4a53d415187fa49c68c991873e3282c0025e9e53d4c946125f184c34e04a7fcd5136fcdc04bedc17afd981380ee05ccb7683e7d83ec615',
    '4401287036553e310907533.22322450',
    1, '2000-01-01 12:00:00', '2000-01-01 12:00:00'),

-- 603: action_verify — NOT yet verified; pre-seeded z_email_verify token below
(603, 'auth_verify@cypress.test', NULL, NULL,
    1, '2000-01-01 12:00:00', NULL),

-- 604: action_index rate-limit — verified user; many failed login attempts
(604, 'auth_ratelimit@cypress.test',
    '772e7e18b509ee9dbf4a53d415187fa49c68c991873e3282c0025e9e53d4c946125f184c34e04a7fcd5136fcdc04bedc17afd981380ee05ccb7683e7d83ec615',
    '4401287036553e310907533.22322450',
    1, '2000-01-01 12:00:00', '2000-01-01 12:00:00');


-- Active session token for user 600 (used by the logout test)
INSERT INTO `z_logintoken` (`id`, `token`, `userId`, `userId_exec`, `extended_seconds`, `created`, `active`) VALUES
(600, '0600a00000000000000000000000000000000000', 600, 600, NULL, NOW(), 1);


-- Pending email-verify token for user 603 (consumed by action_verify happy path)
INSERT INTO `z_email_verify`(`id`, `token`, `user`, `end`, `active`, `created`) VALUES
(600, 'verify-token-603-active', 603, '2099-01-01 00:00:00', 1, '2000-01-01 12:00:00');
