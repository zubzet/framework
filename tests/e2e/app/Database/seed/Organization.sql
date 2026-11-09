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


-- Users for Request::requireOrganization and the membership checks: a member of
-- org 505, a user without an organization, and a member of the removed (inactive) org 501
INSERT INTO `z_user`(`id`, `email`, `password`, `salt`, `active`, `created`, `verified`, `organizationId`) VALUES
(560, 'org_check_member@cypress.test', NULL, NULL, 1, '2000-01-01 12:00:00', '2000-01-01 12:00:00', 505),
(561, 'org_check_none@cypress.test', NULL, NULL, 1, '2000-01-01 12:00:00', '2000-01-01 12:00:00', NULL),
(562, 'org_check_removed@cypress.test', NULL, NULL, 1, '2000-01-01 12:00:00', '2000-01-01 12:00:00', 501);


/*
    Organization Permission Tests

    Organizations hold permissions, roles and groups that their members inherit.
    Every behaviour has its own organizations, users and roles. Roles stay in 150-184
    (below 330, so the Role::add/Group::add tests keep their expected next-id of 330),
    organizations in 520-553, users in 570-599, the join rows start at 700. Permission
    names start with orgperm. so no other spec's permission lookup matches them, and
    no wildcard is seeded because it would match every lookup.
*/
INSERT INTO `z_role`(`id`, `name`, `active`, `created`, `is_group`) VALUES
(150, 'orgperm_getPermissions_Role', 1, '2000-01-01 12:00:00', 0),
(151, 'orgperm_getPermissions_Group', 1, '2000-01-01 12:00:00', 1),
(152, 'orgperm_getPermissions_InactiveRole', 0, '2000-01-01 12:00:00', 0),

(153, 'orgperm_getRoles_RoleA', 1, '2000-01-01 12:00:00', 0),
(154, 'orgperm_getRoles_RoleB', 1, '2000-01-01 12:00:00', 0),
(155, 'orgperm_getRoles_Group', 1, '2000-01-01 12:00:00', 1),
(156, 'orgperm_getRoles_InactiveRole', 0, '2000-01-01 12:00:00', 0),
(182, 'orgperm_getRoles_InactiveLinkRole', 1, '2000-01-01 12:00:00', 0),
(183, 'orgperm_getRoles_InactiveGroup', 0, '2000-01-01 12:00:00', 1),

(157, 'orgperm_byRole_Role', 1, '2000-01-01 12:00:00', 0),
(158, 'orgperm_byRole_OtherRole', 1, '2000-01-01 12:00:00', 0),
(159, 'orgperm_byRole_Group', 1, '2000-01-01 12:00:00', 1),

(160, 'orgperm_byAccess_Role', 1, '2000-01-01 12:00:00', 0),
(184, 'orgperm_byAccess_InactiveRole', 0, '2000-01-01 12:00:00', 0),

(161, 'orgperm_userAccess_OrganizationRole', 1, '2000-01-01 12:00:00', 0),
(162, 'orgperm_userAccess_UserRole', 1, '2000-01-01 12:00:00', 0),

(163, 'orgperm_byUser_OrganizationRole', 1, '2000-01-01 12:00:00', 0),
(164, 'orgperm_byUser_SharedRole', 1, '2000-01-01 12:00:00', 0),
(165, 'orgperm_byUser_OrganizationGroup', 1, '2000-01-01 12:00:00', 1),
(166, 'orgperm_byUser_SharedGroup', 1, '2000-01-01 12:00:00', 1),
(167, 'orgperm_byUser_DirectRole', 1, '2000-01-01 12:00:00', 0),
(168, 'orgperm_byUser_DirectGroup', 1, '2000-01-01 12:00:00', 1),
(169, 'orgperm_byUser_InactiveRole', 0, '2000-01-01 12:00:00', 0),

(170, 'orgperm_users_Role', 1, '2000-01-01 12:00:00', 0),
(171, 'orgperm_users_Group', 1, '2000-01-01 12:00:00', 1),
(172, 'orgperm_users_OtherRole', 1, '2000-01-01 12:00:00', 0),

(173, 'orgperm_mutateRoles_Role', 1, '2000-01-01 12:00:00', 0),
(174, 'orgperm_mutateGroups_Group', 1, '2000-01-01 12:00:00', 1),
(175, 'orgperm_remove_Role', 1, '2000-01-01 12:00:00', 0),
(176, 'orgperm_inactiveOrganization_Role', 1, '2000-01-01 12:00:00', 0),
(177, 'orgperm_roleRemoved_Role', 1, '2000-01-01 12:00:00', 0),
(178, 'orgperm_legacy_Role', 1, '2000-01-01 12:00:00', 0),
(179, 'orgperm_viaRole_Role', 1, '2000-01-01 12:00:00', 0),
(180, 'orgperm_viaRole_Group', 1, '2000-01-01 12:00:00', 1),
(181, 'orgperm_regression_Role', 1, '2000-01-01 12:00:00', 0);


INSERT INTO `z_organization`(`id`, `name`, `active`, `created`) VALUES
(520, 'orgperm_direct', 1, '2000-01-01 12:00:00'),
(521, 'orgperm_getPermissions', 1, '2000-01-01 12:00:00'),
(522, 'orgperm_getRoles', 1, '2000-01-01 12:00:00'),
(523, 'orgperm_direct_Other', 1, '2000-01-01 12:00:00'),

(524, 'orgperm_byRole_RoleAndGroup', 1, '2000-01-01 12:00:00'),
(525, 'orgperm_byRole_RoleOnly', 1, '2000-01-01 12:00:00'),
(526, 'orgperm_byRole_Inactive', 0, '2000-01-01 12:00:00'),
(527, 'orgperm_byRole_InactiveLink', 1, '2000-01-01 12:00:00'),

(528, 'orgperm_byAccess_DirectAndRole', 1, '2000-01-01 12:00:00'),
(529, 'orgperm_byAccess_DirectOnly', 1, '2000-01-01 12:00:00'),
(530, 'orgperm_byAccess_RoleOnly', 1, '2000-01-01 12:00:00'),
(531, 'orgperm_byAccess_Other', 1, '2000-01-01 12:00:00'),
(532, 'orgperm_byAccess_Inactive', 0, '2000-01-01 12:00:00'),
(533, 'orgperm_byAccess_InactiveRows', 1, '2000-01-01 12:00:00'),

(534, 'orgperm_userAccess_Direct', 1, '2000-01-01 12:00:00'),
(535, 'orgperm_userAccess_Role', 1, '2000-01-01 12:00:00'),
(536, 'orgperm_userAccess_Both', 1, '2000-01-01 12:00:00'),
(537, 'orgperm_userAccess_Inactive', 0, '2000-01-01 12:00:00'),
(538, 'orgperm_userAccess_Partial', 1, '2000-01-01 12:00:00'),

(539, 'orgperm_byUser', 1, '2000-01-01 12:00:00'),

(540, 'orgperm_users', 1, '2000-01-01 12:00:00'),
(541, 'orgperm_users_Other', 1, '2000-01-01 12:00:00'),
(542, 'orgperm_users_Inactive', 0, '2000-01-01 12:00:00'),

(543, 'orgperm_mutatePermissions', 1, '2000-01-01 12:00:00'),
(544, 'orgperm_mutateRoles', 1, '2000-01-01 12:00:00'),
(545, 'orgperm_mutateGroups', 1, '2000-01-01 12:00:00'),
(546, 'orgperm_move_From', 1, '2000-01-01 12:00:00'),
(547, 'orgperm_move_To', 1, '2000-01-01 12:00:00'),
(548, 'orgperm_remove', 1, '2000-01-01 12:00:00'),
(549, 'orgperm_inactiveOrganization', 0, '2000-01-01 12:00:00'),
(550, 'orgperm_roleRemoved', 1, '2000-01-01 12:00:00'),
(551, 'orgperm_legacy', 1, '2000-01-01 12:00:00'),
(552, 'orgperm_regression_Empty', 1, '2000-01-01 12:00:00'),
(553, 'orgperm_viaRole', 1, '2000-01-01 12:00:00');


INSERT INTO `z_user`(`id`, `email`, `password`, `salt`, `active`, `created`, `verified`, `organizationId`) VALUES
-- Direct permissions: member of 520, no organization, member of 523
(570, 'orgperm_direct_member@cypress.test', NULL, NULL, 1, '2000-01-01 12:00:00', '2000-01-01 12:00:00', 520),
(571, 'orgperm_direct_none@cypress.test', NULL, NULL, 1, '2000-01-01 12:00:00', '2000-01-01 12:00:00', NULL),
(572, 'orgperm_direct_other@cypress.test', NULL, NULL, 1, '2000-01-01 12:00:00', '2000-01-01 12:00:00', 523),

-- User::byAccessToAll / byAccessToAnyOf
(573, 'orgperm_userAccess_direct@cypress.test', NULL, NULL, 1, '2000-01-01 12:00:00', '2000-01-01 12:00:00', 534),
(574, 'orgperm_userAccess_role@cypress.test', NULL, NULL, 1, '2000-01-01 12:00:00', '2000-01-01 12:00:00', 535),
(575, 'orgperm_userAccess_both@cypress.test', NULL, NULL, 1, '2000-01-01 12:00:00', '2000-01-01 12:00:00', 536),
(576, 'orgperm_userAccess_none@cypress.test', NULL, NULL, 1, '2000-01-01 12:00:00', '2000-01-01 12:00:00', NULL),
(577, 'orgperm_userAccess_inactiveOrganization@cypress.test', NULL, NULL, 1, '2000-01-01 12:00:00', '2000-01-01 12:00:00', 537),
(578, 'orgperm_userAccess_inactiveUser@cypress.test', NULL, NULL, 0, '2000-01-01 12:00:00', '2000-01-01 12:00:00', 534),
(579, 'orgperm_userAccess_partial@cypress.test', NULL, NULL, 1, '2000-01-01 12:00:00', '2000-01-01 12:00:00', 538),

-- Role::byUser / Group::byUser
(580, 'orgperm_byUser@cypress.test', NULL, NULL, 1, '2000-01-01 12:00:00', '2000-01-01 12:00:00', 539),

-- User::byRole / byGroup / Role::getUsers
(581, 'orgperm_users_member@cypress.test', NULL, NULL, 1, '2000-01-01 12:00:00', '2000-01-01 12:00:00', 540),
(582, 'orgperm_users_memberAndDirect@cypress.test', NULL, NULL, 1, '2000-01-01 12:00:00', '2000-01-01 12:00:00', 540),
(583, 'orgperm_users_memberInactive@cypress.test', NULL, NULL, 0, '2000-01-01 12:00:00', '2000-01-01 12:00:00', 540),
(584, 'orgperm_users_direct@cypress.test', NULL, NULL, 1, '2000-01-01 12:00:00', '2000-01-01 12:00:00', NULL),
(585, 'orgperm_users_otherOrganization@cypress.test', NULL, NULL, 1, '2000-01-01 12:00:00', '2000-01-01 12:00:00', 541),
(586, 'orgperm_users_inactiveOrganization@cypress.test', NULL, NULL, 1, '2000-01-01 12:00:00', '2000-01-01 12:00:00', 542),

-- Mutations: permissions, roles, groups, moving, removing the organization, deactivating a role
(587, 'orgperm_mutatePermissions@cypress.test', NULL, NULL, 1, '2000-01-01 12:00:00', '2000-01-01 12:00:00', 543),
(588, 'orgperm_mutateRoles@cypress.test', NULL, NULL, 1, '2000-01-01 12:00:00', '2000-01-01 12:00:00', 544),
(589, 'orgperm_mutateGroups@cypress.test', NULL, NULL, 1, '2000-01-01 12:00:00', '2000-01-01 12:00:00', 545),
(590, 'orgperm_move@cypress.test', NULL, NULL, 1, '2000-01-01 12:00:00', '2000-01-01 12:00:00', 546),
(591, 'orgperm_remove@cypress.test', NULL, NULL, 1, '2000-01-01 12:00:00', '2000-01-01 12:00:00', 548),
(592, 'orgperm_inactiveOrganization@cypress.test', NULL, NULL, 1, '2000-01-01 12:00:00', '2000-01-01 12:00:00', 549),
(593, 'orgperm_roleRemoved@cypress.test', NULL, NULL, 1, '2000-01-01 12:00:00', '2000-01-01 12:00:00', 550),

-- Request::checkPermission: a member of 551 and a user without an organization
(594, 'orgperm_legacy_member@cypress.test', NULL, NULL, 1, '2000-01-01 12:00:00', '2000-01-01 12:00:00', 551),
(595, 'orgperm_legacy_none@cypress.test', NULL, NULL, 1, '2000-01-01 12:00:00', '2000-01-01 12:00:00', NULL),

-- Regression: a user without an organization and a member of one that holds nothing
(596, 'orgperm_regression_none@cypress.test', NULL, NULL, 1, '2000-01-01 12:00:00', '2000-01-01 12:00:00', NULL),
(597, 'orgperm_regression_member@cypress.test', NULL, NULL, 1, '2000-01-01 12:00:00', '2000-01-01 12:00:00', 552),

-- Roles and groups of an organization: a member and a user without an organization
(598, 'orgperm_viaRole_member@cypress.test', NULL, NULL, 1, '2000-01-01 12:00:00', '2000-01-01 12:00:00', 553),
(599, 'orgperm_viaRole_none@cypress.test', NULL, NULL, 1, '2000-01-01 12:00:00', '2000-01-01 12:00:00', NULL);


INSERT INTO `z_organization_permission`(`id`, `organization`, `name`, `active`, `created`) VALUES
-- 520 grants its members read, write and everything below orgperm.wild, the inactive row grants nothing
(700, 520, 'orgperm.direct.read', 1, '2000-01-01 12:00:00'),
(701, 520, 'orgperm.direct.write', 1, '2000-01-01 12:00:00'),
(702, 520, 'orgperm.wild.*', 1, '2000-01-01 12:00:00'),
(703, 520, 'orgperm.direct.inactive', 0, '2000-01-01 12:00:00'),
(704, 523, 'orgperm.direct.other', 1, '2000-01-01 12:00:00'),

-- 521 holds "shared" directly and through its role
(705, 521, 'orgperm.getPermissions.direct', 1, '2000-01-01 12:00:00'),
(706, 521, 'orgperm.getPermissions.directInactive', 0, '2000-01-01 12:00:00'),
(707, 521, 'orgperm.getPermissions.shared', 1, '2000-01-01 12:00:00'),

-- Organization::byAccessToAll / byAccessToAnyOf
(708, 528, 'orgperm.byAccess.1', 1, '2000-01-01 12:00:00'),
(709, 529, 'orgperm.byAccess.1', 1, '2000-01-01 12:00:00'),
(710, 531, 'orgperm.byAccess.other', 1, '2000-01-01 12:00:00'),
(711, 532, 'orgperm.byAccess.1', 1, '2000-01-01 12:00:00'),
(712, 533, 'orgperm.byAccess.1', 0, '2000-01-01 12:00:00'),

-- User::byAccessToAll / byAccessToAnyOf
(713, 534, 'orgperm.userAccess.1', 1, '2000-01-01 12:00:00'),
(714, 536, 'orgperm.userAccess.1', 1, '2000-01-01 12:00:00'),
(715, 536, 'orgperm.userAccess.2', 1, '2000-01-01 12:00:00'),
(716, 537, 'orgperm.userAccess.1', 1, '2000-01-01 12:00:00'),
(717, 537, 'orgperm.userAccess.2', 1, '2000-01-01 12:00:00'),
(718, 538, 'orgperm.userAccess.1', 1, '2000-01-01 12:00:00'),

(719, 546, 'orgperm.move.from', 1, '2000-01-01 12:00:00'),
(720, 547, 'orgperm.move.to', 1, '2000-01-01 12:00:00'),
(721, 548, 'orgperm.remove.direct', 1, '2000-01-01 12:00:00'),
(722, 549, 'orgperm.inactiveOrganization.direct', 1, '2000-01-01 12:00:00'),
(723, 551, 'orgperm.legacy.direct', 1, '2000-01-01 12:00:00');


INSERT INTO `z_role_permission`(`id`, `role`, `name`, `active`, `created`) VALUES
(700, 150, 'orgperm.getPermissions.role', 1, '2000-01-01 12:00:00'),
(701, 150, 'orgperm.getPermissions.shared', 1, '2000-01-01 12:00:00'),
(702, 150, 'orgperm.getPermissions.roleInactive', 0, '2000-01-01 12:00:00'),
(703, 151, 'orgperm.getPermissions.group', 1, '2000-01-01 12:00:00'),
(704, 152, 'orgperm.getPermissions.inactiveRole', 1, '2000-01-01 12:00:00'),

(705, 160, 'orgperm.byAccess.2', 1, '2000-01-01 12:00:00'),
(706, 184, 'orgperm.byAccess.2', 1, '2000-01-01 12:00:00'),

(707, 161, 'orgperm.userAccess.1', 1, '2000-01-01 12:00:00'),
(708, 162, 'orgperm.userAccess.2', 1, '2000-01-01 12:00:00'),

(709, 173, 'orgperm.mutateRoles.perm', 1, '2000-01-01 12:00:00'),
(710, 174, 'orgperm.mutateGroups.perm', 1, '2000-01-01 12:00:00'),
(711, 175, 'orgperm.remove.role', 1, '2000-01-01 12:00:00'),
(712, 176, 'orgperm.inactiveOrganization.role', 1, '2000-01-01 12:00:00'),
(713, 177, 'orgperm.roleRemoved.perm', 1, '2000-01-01 12:00:00'),
(714, 178, 'orgperm.legacy.role', 1, '2000-01-01 12:00:00'),
(715, 179, 'orgperm.viaRole.perm', 1, '2000-01-01 12:00:00'),
(716, 180, 'orgperm.viaGroup.perm', 1, '2000-01-01 12:00:00'),
(717, 181, 'orgperm.regression.role', 1, '2000-01-01 12:00:00');


INSERT INTO `z_organization_role`(`id`, `organization`, `role`, `active`, `created`) VALUES
-- 521: a role, a group and a deactivated role, all with permissions
(700, 521, 150, 1, '2000-01-01 12:00:00'),
(701, 521, 151, 1, '2000-01-01 12:00:00'),
(702, 521, 152, 1, '2000-01-01 12:00:00'),

-- 522: two roles and a group, plus a deactivated role, an inactive link and a deactivated group
(703, 522, 153, 1, '2000-01-01 12:00:00'),
(704, 522, 154, 1, '2000-01-01 12:00:00'),
(705, 522, 155, 1, '2000-01-01 12:00:00'),
(706, 522, 156, 1, '2000-01-01 12:00:00'),
(707, 522, 182, 0, '2000-01-01 12:00:00'),
(708, 522, 183, 1, '2000-01-01 12:00:00'),

-- Organization::byRole / byGroup
(709, 524, 157, 1, '2000-01-01 12:00:00'),
(710, 524, 159, 1, '2000-01-01 12:00:00'),
(711, 525, 157, 1, '2000-01-01 12:00:00'),
(712, 526, 157, 1, '2000-01-01 12:00:00'),
(713, 526, 159, 1, '2000-01-01 12:00:00'),
(714, 527, 157, 0, '2000-01-01 12:00:00'),
(715, 527, 159, 0, '2000-01-01 12:00:00'),
(716, 527, 158, 1, '2000-01-01 12:00:00'),

-- Organization::byAccessToAll / byAccessToAnyOf
(717, 528, 160, 1, '2000-01-01 12:00:00'),
(718, 530, 160, 1, '2000-01-01 12:00:00'),
(719, 532, 160, 1, '2000-01-01 12:00:00'),
(720, 533, 184, 1, '2000-01-01 12:00:00'),

-- User::byAccessToAll / byAccessToAnyOf
(721, 535, 161, 1, '2000-01-01 12:00:00'),

-- Role::byUser / Group::byUser: 539 holds a deactivated role as well
(722, 539, 163, 1, '2000-01-01 12:00:00'),
(723, 539, 164, 1, '2000-01-01 12:00:00'),
(724, 539, 165, 1, '2000-01-01 12:00:00'),
(725, 539, 166, 1, '2000-01-01 12:00:00'),
(726, 539, 169, 1, '2000-01-01 12:00:00'),

-- User::byRole / byGroup / Role::getUsers
(727, 540, 170, 1, '2000-01-01 12:00:00'),
(728, 540, 171, 1, '2000-01-01 12:00:00'),
(729, 541, 172, 1, '2000-01-01 12:00:00'),
(730, 542, 170, 1, '2000-01-01 12:00:00'),
(731, 542, 171, 1, '2000-01-01 12:00:00'),

(732, 548, 175, 1, '2000-01-01 12:00:00'),
(733, 549, 176, 1, '2000-01-01 12:00:00'),
(734, 550, 177, 1, '2000-01-01 12:00:00'),
(735, 551, 178, 1, '2000-01-01 12:00:00'),
(736, 553, 179, 1, '2000-01-01 12:00:00'),
(737, 553, 180, 1, '2000-01-01 12:00:00');


INSERT INTO `z_user_permission`(`id`, `name`, `user`, `active`, `created`) VALUES
-- 570 holds read itself as well as through 520
(700, 'orgperm.direct.read', 570, 1, '2000-01-01 12:00:00'),

-- User::byAccessToAll / byAccessToAnyOf: the missing half of a permission pair
(701, 'orgperm.userAccess.2', 573, 1, '2000-01-01 12:00:00'),
(702, 'orgperm.userAccess.2', 578, 1, '2000-01-01 12:00:00'),
(703, 'orgperm.userAccess.1', 576, 1, '2000-01-01 12:00:00'),

(704, 'orgperm.regression.direct', 596, 1, '2000-01-01 12:00:00'),
(705, 'orgperm.regression.direct', 597, 1, '2000-01-01 12:00:00');


INSERT INTO `z_user_role`(`id`, `role`, `user`, `active`, `created`) VALUES
(700, 162, 574, 1, '2000-01-01 12:00:00'),

-- 580 holds the shared role and group through 539 as well as directly
(701, 164, 580, 1, '2000-01-01 12:00:00'),
(702, 166, 580, 1, '2000-01-01 12:00:00'),
(703, 167, 580, 1, '2000-01-01 12:00:00'),
(704, 168, 580, 1, '2000-01-01 12:00:00'),

-- 582 is a member of 540 and holds the role and group directly, 584 only directly
(705, 170, 582, 1, '2000-01-01 12:00:00'),
(706, 171, 582, 1, '2000-01-01 12:00:00'),
(707, 170, 584, 1, '2000-01-01 12:00:00'),
(708, 171, 584, 1, '2000-01-01 12:00:00'),

(709, 181, 596, 1, '2000-01-01 12:00:00'),
(710, 181, 597, 1, '2000-01-01 12:00:00');
