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

/*
    API key tests
*/

INSERT INTO `z_user`(`id`, `email`, `password`, `salt`, `active`, `created`, `verified`) VALUES
-- byUser / byId / byToken: one login, two api keys and one revoked api key
(430, 'session_apikey_kinds@cypress.test', NULL, NULL, 1, '2000-01-01 12:00:00', '2000-01-01 12:00:00'),
-- manage: name and permanence are set through the APIKey object
(431, 'session_apikey_manage@cypress.test', NULL, NULL, 1, '2000-01-01 12:00:00', '2000-01-01 12:00:00'),
-- permanent: expired by age, kept alive by the flag
(432, 'session_apikey_permanent@cypress.test', NULL, NULL, 1, '2000-01-01 12:00:00', '2000-01-01 12:00:00'),
-- auth flow: a permanent api key used as a login cookie
(433, 'session_apikey_auth@cypress.test', NULL, NULL, 1, '2000-01-01 12:00:00', '2000-01-01 12:00:00'),
-- add with name: Session::add($user, name: ...), an ordinary login
(434, 'session_add_name@cypress.test', NULL, NULL, 1, '2000-01-01 12:00:00', '2000-01-01 12:00:00'),
-- clear name: named api key that loses its name again
(435, 'session_apikey_clearname@cypress.test', NULL, NULL, 1, '2000-01-01 12:00:00', '2000-01-01 12:00:00'),
-- login naming: logs in for real, password is "password" like the canonical seed
(436, 'session_login_name@cypress.test',
    '772e7e18b509ee9dbf4a53d415187fa49c68c991873e3282c0025e9e53d4c946125f184c34e04a7fcd5136fcdc04bedc17afd981380ee05ccb7683e7d83ec615',
    '4401287036553e310907533.22322450',
    1, '2000-01-01 12:00:00', '2000-01-01 12:00:00'),
-- add: APIKey::add($user, name: ...) creates the key at runtime
(437, 'session_apikey_add@cypress.test', NULL, NULL, 1, '2000-01-01 12:00:00', '2000-01-01 12:00:00');

INSERT INTO `z_logintoken` (`id`, `token`, `userId`, `userId_exec`, `name`, `is_permanent`, `is_apikey`, `extended_seconds`, `created`, `active`) VALUES
-- kinds: user 430 owns one interactive login …
(430, '0430a00000000000000000000000000000000000', 430, 430, NULL, 0, 0, NULL, '2025-01-01 12:00:00', 1),
-- … two api keys …
(431, '0430b00000000000000000000000000000000000', 430, 430, 'CI pipeline', 1, 1, NULL, '2025-01-01 12:00:00', 1),
(432, '0430c00000000000000000000000000000000000', 430, 430, 'Backup job', 1, 1, NULL, '2025-01-01 12:00:00', 1),
-- … and a revoked api key, which neither class may return
(433, '0430d00000000000000000000000000000000000', 430, 430, 'Revoked key', 1, 1, NULL, '2025-01-01 12:00:00', 0),

-- manage: unnamed, expiring api key that is named and made permanent at runtime
(434, '0431a00000000000000000000000000000000000', 431, 431, NULL, 0, 1, NULL, '2025-01-01 12:00:00', 1),

-- permanent: created in the year 2000 without extension, expired unless permanent
(435, '0432a00000000000000000000000000000000000', 432, 432, 'Never expires', 1, 1, NULL, '2000-01-01 12:00:00', 1),

-- auth flow: same age, sent as a cookie by cypress
(436, '0433a00000000000000000000000000000000000', 433, 433, 'Cookie key', 1, 1, NULL, '2000-01-01 12:00:00', 1),

-- clear name: starts out named
(437, '0435a00000000000000000000000000000000000', 435, 435, 'Temporary name', 0, 1, NULL, '2025-01-01 12:00:00', 1);


/*
    Session permanence (an ordinary login, not an api key)
*/

INSERT INTO `z_user`(`id`, `email`, `password`, `salt`, `active`, `created`, `verified`) VALUES
(438, 'session_permanent_login@cypress.test', NULL, NULL, 1, '2000-01-01 12:00:00', '2000-01-01 12:00:00');

INSERT INTO `z_logintoken` (`id`, `token`, `userId`, `userId_exec`, `name`, `is_permanent`, `is_apikey`, `extended_seconds`, `created`, `active`) VALUES
-- created in the year 2000 without extension, so it is expired until the flag revives it
(438, '0438a00000000000000000000000000000000000', 438, 438, NULL, 0, 0, NULL, '2000-01-01 12:00:00', 1);


/*
    Device, reason and the addresses a session is used from
*/

INSERT INTO `z_user`(`id`, `email`, `password`, `salt`, `active`, `created`, `verified`) VALUES
-- origin: Session::add() records reason, device and ip_creation at runtime
(439, 'session_origin@cypress.test', NULL, NULL, 1, '2000-01-01 12:00:00', '2000-01-01 12:00:00');

INSERT INTO `z_logintoken` (`id`, `token`, `userId`, `userId_exec`, `name`, `device`, `reason`, `ip_creation`, `ip_last`, `is_permanent`, `is_apikey`, `extended_seconds`, `created`, `active`) VALUES
-- ip_last starts stale, the authenticated request carrying this cookie corrects it
(439, '0439a00000000000000000000000000000000000', 439, 439, NULL, NULL, NULL, '203.0.113.7', '203.0.113.7', 0, 0, NULL, NOW(), 1);
