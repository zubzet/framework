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
(246, 'zpanel_group_member@cypress.test', NULL, NULL, 1, '2000-01-01 12:00:00', '2000-01-01 12:00:00');

INSERT INTO `z_user_role`(`id`, `role`, `user`, `active`, `created`) VALUES
(246, 246, 246, 1, '2000-01-01 12:00:00');

-- 252-253 are free on develop as well and stay below the 330 AUTO_INCREMENT
INSERT INTO `z_role`(`id`, `name`, `active`, `created`, `is_group`) VALUES
(252, 'zadmin_OrganizationDetail_Group', 1, '2000-01-01 12:00:00', 1),
(253, 'zadmin_OrganizationAssign_Group', 1, '2000-01-01 12:00:00', 1);

INSERT INTO `z_organization`(`id`, `name`, `groupId`, `active`, `created`) VALUES
(560, 'zadmin_OrganizationRenameTarget', NULL, 1, '2000-01-01 12:00:00'),
(561, 'zadmin_OrganizationDeleteTarget', NULL, 1, '2000-01-01 12:00:00'),
(562, 'zadmin_OrganizationDetail', 252, 1, '2000-01-01 12:00:00'),
(563, 'zadmin_OrganizationAssign', 253, 1, '2000-01-01 12:00:00');

-- The emails avoid the substring "admin": core/query-builder.cy.js asserts
-- the exact result set of a `email LIKE '%admin%'` query over z_user.
INSERT INTO `z_user`(`id`, `email`, `password`, `salt`, `active`, `created`, `verified`, `organizationId`) VALUES
-- Member shown on the detail page of organization 562
(563, 'zpanel_organizationMember@cypress.test', NULL, NULL, 1, '2000-01-01 12:00:00', '2000-01-01 12:00:00', 562),

-- Starts without an organization, then receives 563 (group 253)
(564, 'zpanel_organizationAssignTarget@cypress.test', NULL, NULL, 1, '2000-01-01 12:00:00', '2000-01-01 12:00:00', NULL),

-- Starts in 563 (group 253), then gets unassigned
(565, 'zpanel_organizationUnassignTarget@cypress.test', NULL, NULL, 1, '2000-01-01 12:00:00', '2000-01-01 12:00:00', 563);

-- Group memberships matching the seeded organizations of users 563 and 565
INSERT INTO `z_user_role`(`id`, `role`, `user`, `active`, `created`) VALUES
(560, 253, 565, 1, '2000-01-01 12:00:00'),
(561, 252, 563, 1, '2000-01-01 12:00:00');
