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

INSERT INTO `z_role`(`id`, `name`, `active`, `created`, `is_group`) VALUES
(246, 'zadmin_OrganizationDetail_Group', 1, '2000-01-01 12:00:00', 1),
(247, 'zadmin_OrganizationAssign_Group', 1, '2000-01-01 12:00:00', 1);

INSERT INTO `z_organization`(`id`, `name`, `groupId`, `active`, `created`) VALUES
(560, 'zadmin_OrganizationRenameTarget', NULL, 1, '2000-01-01 12:00:00'),
(561, 'zadmin_OrganizationDeleteTarget', NULL, 1, '2000-01-01 12:00:00'),
(562, 'zadmin_OrganizationDetail', 246, 1, '2000-01-01 12:00:00'),
(563, 'zadmin_OrganizationAssign', 247, 1, '2000-01-01 12:00:00');

-- The emails avoid the substring "admin": core/query-builder.cy.js asserts
-- the exact result set of a `email LIKE '%admin%'` query over z_user.
INSERT INTO `z_user`(`id`, `email`, `password`, `salt`, `active`, `created`, `verified`, `organizationId`) VALUES
-- Member shown on the detail page of organization 562
(560, 'zpanel_organizationMember@cypress.test', NULL, NULL, 1, '2000-01-01 12:00:00', '2000-01-01 12:00:00', 562),

-- Starts without an organization, then receives 563 (group 247)
(561, 'zpanel_organizationAssignTarget@cypress.test', NULL, NULL, 1, '2000-01-01 12:00:00', '2000-01-01 12:00:00', NULL),

-- Starts in 563 (group 247), then gets unassigned
(562, 'zpanel_organizationUnassignTarget@cypress.test', NULL, NULL, 1, '2000-01-01 12:00:00', '2000-01-01 12:00:00', 563);

-- Group membership matching the seeded organization of user 562
INSERT INTO `z_user_role`(`id`, `role`, `user`, `active`, `created`) VALUES
(560, 247, 562, 1, '2000-01-01 12:00:00');
