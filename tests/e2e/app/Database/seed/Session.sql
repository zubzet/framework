/*
    User and Session Data for Session Tests
*/


INSERT INTO `z_user`(`id`, `email`, `password`, `salt`, `active`, `created`, `verified`) VALUES
(400, 'session_byUser@cypress.test', NULL, NULL, 1, '2000-01-01 12:00:00', '2000-01-01 12:00:00'),
(401, 'session_invalidate@cypress.test', NULL, NULL, 1, '2000-01-01 12:00:00', '2000-01-01 12:00:00'),
(402, 'session_getters@cypress.test', NULL, NULL, 1, '2000-01-01 12:00:00', '2000-01-01 12:00:00'),
(403, 'session_setExtensionTime@cypress.test', NULL, NULL, 1, '2000-01-01 12:00:00', '2000-01-01 12:00:00'),
(404, 'session_extendSession@cypress.test', NULL, NULL, 1, '2000-01-01 12:00:00', '2000-01-01 12:00:00'),
(405, 'session_isExpiredActive@cypress.test', NULL, NULL, 1, '2000-01-01 12:00:00', '2000-01-01 12:00:00'),
(406, 'session_isExpiredExpired@cypress.test', NULL, NULL, 1, '2000-01-01 12:00:00', '2000-01-01 12:00:00'),
(407, 'session_isExpiredExtended@cypress.test', NULL, NULL, 1, '2000-01-01 12:00:00', '2000-01-01 12:00:00'),
(408, 'session_refresh@cypress.test', NULL, NULL, 1, '2000-01-01 12:00:00', '2000-01-01 12:00:00');


INSERT INTO `z_logintoken` (`id`, `token`, `userId`, `userId_exec`, `extended_seconds`, `created`, `active`) VALUES
-- byUser: user 400 has 2 active sessions (a, b) and 1 inactive session (c)
(400, '0400a00000000000000000000000000000000000', 400, 400, NULL, '2025-01-01 12:00:00', 1),
(401, '0400b00000000000000000000000000000000000', 400, 401, NULL, '2025-01-01 12:00:00', 1),
(402, '0400c00000000000000000000000000000000000', 400, 400, NULL, '2025-01-01 12:00:00', 0),

-- invalidate: user 401 has 1 active session to be invalidated
(403, '0401a00000000000000000000000000000000000', 401, 401, NULL, '2025-01-01 12:00:00', 1),

-- getters: user 402 has 1 session with predictable values (extendedSeconds = 300)
(404, '0402a00000000000000000000000000000000000', 402, 402, 300, '2025-01-01 12:00:00', 1),

-- setExtensionTime: user 403
(405, '0403a00000000000000000000000000000000000', 403, 403, NULL, '2025-01-01 12:00:00', 1),

-- extendSession: user 404 already has extended_seconds = 100 (will be increased)
(406, '0404a00000000000000000000000000000000000', 404, 404, 100, '2025-01-01 12:00:00', 1),

-- isExpiredActive: user 405, created NOW = not yet expired
(407, '0405a00000000000000000000000000000000000', 405, 405, NULL, NOW(), 1),

-- isExpiredExpired: user 406, created in year 2000 = long expired
(408, '0406a00000000000000000000000000000000000', 406, 406, NULL, '2000-01-01 12:00:00', 1),

-- isExpiredExtended: user 407, created in year 2000 but extension covers until ~2063 = not expired
(409, '0407a00000000000000000000000000000000000', 407, 407, 2000000000, '2000-01-01 12:00:00', 1),

-- refresh: user 408, extension will be updated and refreshed
(410, '0408a00000000000000000000000000000000000', 408, 408, NULL, '2025-01-01 12:00:00', 1);


/*
    Authentication flow tests (cookie-based login)
*/

INSERT INTO `z_user`(`id`, `email`, `password`, `salt`, `active`, `created`, `verified`) VALUES
-- auth_valid: a user with a currently valid session
(409, 'session_auth_valid@cypress.test', NULL, NULL, 1, '2000-01-01 12:00:00', '2000-01-01 12:00:00'),
-- auth_invalidated: session will be explicitly invalidated before the auth check
(410, 'session_auth_invalidated@cypress.test', NULL, NULL, 1, '2000-01-01 12:00:00', '2000-01-01 12:00:00'),
-- auth_expired: session is long expired (created 2000), no extension
(411, 'session_auth_expired@cypress.test', NULL, NULL, 1, '2000-01-01 12:00:00', '2000-01-01 12:00:00'),
-- auth_extended: session is long expired but has a large extension → still valid
(412, 'session_auth_extended@cypress.test', NULL, NULL, 1, '2000-01-01 12:00:00', '2000-01-01 12:00:00'),
-- auth_extend_after_expire: expired session, will be accessed (auto-invalidated), then extension is attempted
(413, 'session_auth_extend_after_expire@cypress.test', NULL, NULL, 1, '2000-01-01 12:00:00', '2000-01-01 12:00:00');

INSERT INTO `z_logintoken` (`id`, `token`, `userId`, `userId_exec`, `extended_seconds`, `created`, `active`) VALUES
-- auth_valid: active, recently created session
(411, '0409a00000000000000000000000000000000000', 409, 409, NULL, NOW(), 1),
-- auth_invalidated: will be invalidated via /session/invalidateForAuth before the cookie test
(412, '0410a00000000000000000000000000000000000', 410, 410, NULL, NOW(), 1),
-- auth_expired: created year 2000, no extension → expired
(413, '0411a00000000000000000000000000000000000', 411, 411, NULL, '2000-01-01 12:00:00', 1),
-- auth_extended: created year 2000, large extension (~63 years) → still valid
(414, '0412a00000000000000000000000000000000000', 412, 412, 2000000000, '2000-01-01 12:00:00', 1),
-- auth_extend_after_expire: expired, will be auto-invalidated on first use, then extension attempted
(415, '0413a00000000000000000000000000000000000', 413, 413, NULL, '2000-01-01 12:00:00', 1);


/*
    byToken tests
*/

INSERT INTO `z_user`(`id`, `email`, `password`, `salt`, `active`, `created`, `verified`) VALUES
(420, 'session_byToken@cypress.test', NULL, NULL, 1, '2000-01-01 12:00:00', '2000-01-01 12:00:00');

INSERT INTO `z_logintoken` (`id`, `token`, `userId`, `userId_exec`, `extended_seconds`, `created`, `active`) VALUES
-- byToken: active session — should be returned
(420, '0420a00000000000000000000000000000000000', 420, 420, NULL, NOW(), 1),
-- byToken: inactive session — should return null
(421, '0420b00000000000000000000000000000000000', 420, 420, NULL, NOW(), 0);


/*
    add tests
*/

INSERT INTO `z_user`(`id`, `email`, `password`, `salt`, `active`, `created`, `verified`) VALUES
-- add: user for Session::add($user) — userExec defaults to user
(421, 'session_add@cypress.test', NULL, NULL, 1, '2000-01-01 12:00:00', '2000-01-01 12:00:00'),
-- add with exec: user and separate exec user for Session::add($user, $exec)
(422, 'session_add_user@cypress.test', NULL, NULL, 1, '2000-01-01 12:00:00', '2000-01-01 12:00:00'),
(423, 'session_add_exec@cypress.test', NULL, NULL, 1, '2000-01-01 12:00:00', '2000-01-01 12:00:00');