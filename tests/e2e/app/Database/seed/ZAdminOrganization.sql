/*
    Seed for the z-admin organization spec (tests/cypress/e2e/z-admin/organization.cy.js).

    Users, organizations, sessions, permissions and invites use the 800 range.
    An invited address has no account yet, it is created when the invite is accepted.
    Roles use 260-263, a free slot below 330 so permission/role.cy.js keeps
    its expected next role id. Invite tokens are readable on purpose, the
    spec opens them directly.
*/

INSERT INTO `z_role`(`id`, `name`, `is_group`, `is_org_assignable`, `active`, `created`) VALUES
(260, 'zorg_Assignable_Role', 0, 1, 1, '2000-01-01 12:00:00'),
(261, 'zorg_Assignable_Group', 1, 1, 1, '2000-01-01 12:00:00'),
(262, 'zorg_Plain_Role', 0, 0, 1, '2000-01-01 12:00:00'),
(263, 'zorg_Main_Group', 1, 0, 1, '2000-01-01 12:00:00');

INSERT INTO `z_organization`(`id`, `name`, `groupId`, `active`, `created`) VALUES
(800, 'zorg_Main', 263, 1, '2000-01-01 12:00:00'),
(801, 'zorg_Other', NULL, 1, '2000-01-01 12:00:00'),
(802, 'zorg_Removed', NULL, 0, '2000-01-01 12:00:00'),
(803, 'zorg_Rename', NULL, 1, '2000-01-01 12:00:00');

INSERT INTO `z_user`(`id`, `email`, `password`, `salt`, `active`, `created`, `verified`, `organizationId`) VALUES
-- Manages org 800 with every organization permission
(800, 'zorg_manager@cypress.test', NULL, NULL, 1, '2000-01-01 12:00:00', '2000-01-01 12:00:00', 800),
-- Member of org 800 without permissions
(801, 'zorg_member@cypress.test', NULL, NULL, 1, '2000-01-01 12:00:00', '2000-01-01 12:00:00', 800),
-- Holds every organization permission but belongs to no organization
(802, 'zorg_orgless@cypress.test', NULL, NULL, 1, '2000-01-01 12:00:00', '2000-01-01 12:00:00', NULL),
-- Member of another organization
(806, 'zorg_other_member@cypress.test', NULL, NULL, 1, '2000-01-01 12:00:00', '2000-01-01 12:00:00', 801),
-- Deactivated, still holds its address
(807, 'zorg_deactivated@cypress.test', NULL, NULL, 0, '2000-01-01 12:00:00', '2000-01-01 12:00:00', NULL),
-- May only rename org 803
(808, 'zorg_renamer@cypress.test', NULL, NULL, 1, '2000-01-01 12:00:00', '2000-01-01 12:00:00', 803),
-- Logged in while opening an invitation
(810, 'zorg_stranger@cypress.test', NULL, NULL, 1, '2000-01-01 12:00:00', '2000-01-01 12:00:00', NULL),
-- May invite into org 800, but not by email
(811, 'zorg_inviter@cypress.test', NULL, NULL, 1, '2000-01-01 12:00:00', '2000-01-01 12:00:00', 800),
-- Holds an address that cannot be invited anymore
(813, 'zorg_account_exists@cypress.test', NULL, NULL, 1, '2000-01-01 12:00:00', '2000-01-01 12:00:00', NULL);

INSERT INTO `z_user_role`(`id`, `role`, `user`, `active`, `created`) VALUES
(800, 261, 801, 1, '2000-01-01 12:00:00'),
(801, 262, 801, 1, '2000-01-01 12:00:00');

INSERT INTO `z_user_permission`(`id`, `name`, `user`, `active`, `created`) VALUES
(800, 'z.organization.invite', 800, 1, '2000-01-01 12:00:00'),
(801, 'z.organization.roles', 800, 1, '2000-01-01 12:00:00'),
(802, 'z.organization.rename', 800, 1, '2000-01-01 12:00:00'),
(803, 'z.organization.invite', 802, 1, '2000-01-01 12:00:00'),
(804, 'z.organization.roles', 802, 1, '2000-01-01 12:00:00'),
(805, 'z.organization.rename', 802, 1, '2000-01-01 12:00:00'),
(806, 'z.organization.rename', 808, 1, '2000-01-01 12:00:00'),
(807, 'z.organization.manage', 800, 1, '2000-01-01 12:00:00'),
(808, 'z.organization.manage', 802, 1, '2000-01-01 12:00:00'),
(809, 'z.organization.invite.email', 800, 1, '2000-01-01 12:00:00'),
(810, 'z.organization.invite', 811, 1, '2000-01-01 12:00:00');

INSERT INTO `z_logintoken` (`id`, `token`, `userId`, `userId_exec`, `extended_seconds`, `created`, `active`) VALUES
(800, '0800a00000000000000000000000000000000000', 800, 800, NULL, NOW(), 1),
(801, '0801a00000000000000000000000000000000000', 801, 801, NULL, NOW(), 1),
(802, '0802a00000000000000000000000000000000000', 802, 802, NULL, NOW(), 1),
(808, '0808a00000000000000000000000000000000000', 808, 808, NULL, NOW(), 1),
(810, '0810a00000000000000000000000000000000000', 810, 810, NULL, NOW(), 1),
(811, '0811a00000000000000000000000000000000000', 811, 811, NULL, NOW(), 1);

INSERT INTO `z_organization_invite`(`id`, `organizationId`, `email`, `roleId`, `token`, `active`, `created`) VALUES
(800, 800, 'zorg_invitee@cypress.test', NULL, 'zorg_token_accept_ui', 1, NOW()),
(801, 800, 'zorg_invitee_request@cypress.test', NULL, 'zorg_token_accept_request', 1, NOW()),
(802, 800, 'zorg_expired@cypress.test', NULL, 'zorg_token_expired', 1, NOW() - INTERVAL 8 DAY),
(803, 802, 'zorg_removed_org@cypress.test', NULL, 'zorg_token_removed_org', 1, NOW()),
(804, 800, 'zorg_revoked@cypress.test', NULL, 'zorg_token_revoked', 0, NOW()),
(805, 800, 'zorg_open@cypress.test', NULL, 'zorg_token_open', 1, NOW()),
(806, 800, 'zorg_revoke@cypress.test', NULL, 'zorg_token_revoke', 1, NOW()),
(807, 801, 'zorg_foreign@cypress.test', NULL, 'zorg_token_foreign', 1, NOW()),
(808, 800, 'zorg_reinvite@cypress.test', NULL, 'zorg_token_reinvite', 1, NOW() - INTERVAL 8 DAY),
(809, 800, 'zorg_already_invited@cypress.test', NULL, 'zorg_token_already_invited', 1, NOW()),
-- The address got an account after the invite was created
(810, 800, 'zorg_account_exists@cypress.test', NULL, 'zorg_token_account_exists', 1, NOW()),
-- Comes with an assignable role
(811, 800, 'zorg_invitee_role@cypress.test', 260, 'zorg_token_accept_role', 1, NOW()),
-- Comes with a role that is no longer released to organizations
(812, 800, 'zorg_unreleased@cypress.test', 262, 'zorg_token_unreleased_role', 1, NOW()),
-- Never accepted, the password checks run against it
(813, 800, 'zorg_validation@cypress.test', NULL, 'zorg_token_validate', 1, NOW());
