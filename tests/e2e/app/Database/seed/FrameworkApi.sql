/*
    Test rows for the framework-API probe spec (framework/api.cy.js).

    z_user IDs use the 700 range (z_user has AUTO_INCREMENT pinned to 10000
    in zubzet/9_auto_increment.sql, so the role/group "create new" tests
    that assert id=10000 are not affected by seed rows below it).

    z_role IDs use 250–251 — a free slot in Permission.sql's role layout
    (it uses 100–118, 200–229, and groups 300–329). Adding rows above 329
    would push AUTO_INCREMENT past 330, breaking
    permission/role.cy.js's "should be possible to create a new role"
    assertion that expects id=330. Stay below 330.
*/

INSERT INTO `z_user`(`id`, `email`, `password`, `salt`, `active`, `created`, `verified`) VALUES
-- 700: changeRoleStateByUserIdAndRoleId target user (no role assigned initially)
(700, 'fwapi_role_state@cypress.test', NULL, NULL, 1, '2000-01-01 12:00:00', '2000-01-01 12:00:00');

INSERT INTO `z_role`(`id`, `name`, `active`) VALUES
-- 250: role used by changeRoleStateByUserIdAndRoleId tests
(250, 'fwapi_RoleState', 1),
-- 251: known role for getRoleIdByRoleName
(251, 'fwapi_KnownRole', 1);
