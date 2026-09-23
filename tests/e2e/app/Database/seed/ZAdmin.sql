/*
    Seed for the z-admin spec (tests/cypress/e2e/z-admin/zadmin.cy.js).

    Role 245 is the target of the action_roles delete test, 246-247 feed the
    edit_user role options and 248 the is_org_assignable checkbox. They sit
    in the 244-249 free slot (Permission.sql uses 200-229; Organization.sql uses
    230-236 + 240-243; FrameworkApi.sql uses 250-251) so z_role
    AUTO_INCREMENT remains at 330, matching permission/role.cy.js's
    "create new role" id=330 assertion.
*/

INSERT INTO `z_role`(`id`, `name`, `is_group`, `is_org_assignable`, `active`, `created`) VALUES
(245, 'zadmin_RoleDeleteTarget', 0, 0, 1, '2000-01-01 12:00:00'),
(246, 'zadmin_HeldGroup', 1, 0, 1, '2000-01-01 12:00:00'),
(247, 'zadmin_OtherGroup', 1, 0, 1, '2000-01-01 12:00:00'),
(248, 'zadmin_OrgAssignable', 0, 1, 1, '2000-01-01 12:00:00');

-- User 246 holds group 246 but not 247
INSERT INTO `z_user`(`id`, `email`, `password`, `salt`, `active`, `created`, `verified`) VALUES
(246, 'zadmin_group_member@cypress.test', NULL, NULL, 1, '2000-01-01 12:00:00', '2000-01-01 12:00:00');

INSERT INTO `z_user_role`(`id`, `role`, `user`, `active`, `created`) VALUES
(246, 246, 246, 1, '2000-01-01 12:00:00');
