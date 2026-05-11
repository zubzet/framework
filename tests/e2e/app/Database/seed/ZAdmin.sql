/*
    Seed for the z-admin spec (tests/cypress/e2e/z-admin/zadmin.cy.js).

    Role 245 is the target of the action_roles delete test. Sits in the
    244-249 free slot (Permission.sql uses 200-229; Organization.sql uses
    230-236 + 240-243; FrameworkApi.sql uses 250-251) so z_role
    AUTO_INCREMENT remains at 330, matching permission/role.cy.js's
    "create new role" id=330 assertion.
*/

INSERT INTO `z_role`(`id`, `name`, `active`, `created`) VALUES
(245, 'zadmin_RoleDeleteTarget', 1, '2000-01-01 12:00:00');
