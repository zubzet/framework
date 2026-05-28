/*
    Organization Data for Organization Tests
*/


INSERT INTO `z_organization`(`id`, `name`, `active`, `created`) VALUES
(500, 'org_byId_Active', 1, '2000-01-01 12:00:00'),
(501, 'org_byId_Inactive', 0, '2000-01-01 12:00:00'),

(502, 'org_byName_Shared', 1, '2000-01-01 12:00:00'),
(503, 'org_byName_Shared', 1, '2000-01-01 12:00:00'),
(504, 'org_byName_Inactive', 0, '2000-01-01 12:00:00'),

(505, 'org_byUser', 1, '2000-01-01 12:00:00'),

(506, 'org_getUsers', 1, '2000-01-01 12:00:00'),

(507, 'org_updateName', 1, '2000-01-01 12:00:00'),

(508, 'org_remove', 1, '2000-01-01 12:00:00'),

(509, 'org_userOrg_Initial', 1, '2000-01-01 12:00:00'),
(510, 'org_userOrg_Updated', 1, '2000-01-01 12:00:00'),

(511, 'org_userByOrganization_Empty', 1, '2000-01-01 12:00:00');


INSERT INTO `z_user`(`id`, `email`, `password`, `salt`, `active`, `created`, `verified`, `organizationId`) VALUES
(500, 'org_byUser@cypress.test', NULL, NULL, 1, '2000-01-01 12:00:00', '2000-01-01 12:00:00', 505),

(501, 'org_getUsers_1_Active@cypress.test', NULL, NULL, 1, '2000-01-01 12:00:00', '2000-01-01 12:00:00', 506),
(502, 'org_getUsers_2_Active@cypress.test', NULL, NULL, 1, '2000-01-01 12:00:00', '2000-01-01 12:00:00', 506),
(503, 'org_getUsers_1_Inactive@cypress.test', NULL, NULL, 0, '2000-01-01 12:00:00', '2000-01-01 12:00:00', 506),

(504, 'org_userOrg_Assign@cypress.test', NULL, NULL, 1, '2000-01-01 12:00:00', '2000-01-01 12:00:00', NULL),
(505, 'org_userOrg_Update@cypress.test', NULL, NULL, 1, '2000-01-01 12:00:00', '2000-01-01 12:00:00', 509);


/*
    Organization <-> Group Link Tests

    Groups (z_role with is_group = 1) referenced from organizations to back them
    with permission groups. IDs stay below 330 so that Role::add/Group::add tests
    keep their expected next-id of 330 after seeding.
*/
INSERT INTO `z_role`(`id`, `name`, `active`, `created`, `is_group`) VALUES
(240, 'org_getGroup_Group', 1, '2000-01-01 12:00:00', 1),
(241, 'org_userOrg_groupSync_Initial_Group', 1, '2000-01-01 12:00:00', 1),
(242, 'org_userOrg_groupSync_Updated_Group', 1, '2000-01-01 12:00:00', 1),
(243, 'org_userOrg_groupSync_Unset_Group', 1, '2000-01-01 12:00:00', 1);


INSERT INTO `z_organization`(`id`, `name`, `groupId`, `active`, `created`) VALUES
-- Organization with a linked group used by Organization::getGroup
(512, 'org_getGroup', 240, 1, '2000-01-01 12:00:00'),

-- Organization without a linked group used by Organization::getGroup (null case)
(513, 'org_getGroupNull', NULL, 1, '2000-01-01 12:00:00'),

-- Organizations used by User::updateOrganization group-sync tests
(514, 'org_userOrg_groupSync_Initial', 241, 1, '2000-01-01 12:00:00'),
(515, 'org_userOrg_groupSync_Updated', 242, 1, '2000-01-01 12:00:00'),
(516, 'org_userOrg_groupSync_Unset', 243, 1, '2000-01-01 12:00:00');


INSERT INTO `z_user`(`id`, `email`, `password`, `salt`, `active`, `created`, `verified`, `organizationId`) VALUES
-- Starts without an organization, then receives org 514 (with group 241)
(550, 'org_userOrg_groupSyncAssign@cypress.test', NULL, NULL, 1, '2000-01-01 12:00:00', '2000-01-01 12:00:00', NULL),

-- Starts in org 514 (group 241), then switched to org 515 (group 242)
(551, 'org_userOrg_groupSyncChange@cypress.test', NULL, NULL, 1, '2000-01-01 12:00:00', '2000-01-01 12:00:00', 514),

-- Starts in org 516 (group 243), then unset to null
(552, 'org_userOrg_groupSyncUnset@cypress.test', NULL, NULL, 1, '2000-01-01 12:00:00', '2000-01-01 12:00:00', 516);


-- Pre-existing user-group memberships matching the seeded organization assignments
INSERT INTO `z_user_role`(`id`, `role`, `user`, `active`, `created`) VALUES
(550, 241, 551, 1, '2000-01-01 12:00:00'),
(551, 243, 552, 1, '2000-01-01 12:00:00');
