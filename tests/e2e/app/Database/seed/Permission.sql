/*
    User Data for Permission Tests
*/


INSERT INTO `z_user`(`id`, `email`, `password`, `salt`, `active`, `created`, `verified`) VALUES
(100, 'user_byId_Active@cypress.test', NULL, NULL, 1, '2000-01-01 12:00:00', '2000-01-01 12:00:00'),
(101, 'user_byId_Inactive@cypress.test', NULL, NULL, 0, '2000-01-01 12:00:00', '2000-01-01 12:00:00'),

(102, 'user_byEmail_Active@cypress.test', NULL, NULL, 1, '2000-01-01 12:00:00', '2000-01-01 12:00:00'),
(103, 'user_byEmail_Inactive@cypress.test', NULL, NULL, 0, '2000-01-01 12:00:00', '2000-01-01 12:00:00'),

(104, 'user_byNotVerified_1_Active@cypress.test', NULL, NULL, 1, '2000-01-01 12:00:00', '2027-01-01 12:00:00'),
(105, 'user_byNotVerified_2_Active@cypress.test', NULL, NULL, 1, '2000-01-01 12:00:00', '2027-01-01 12:00:00'),
(106, 'user_byNotVerified_3_Active@cypress.test', NULL, NULL, 1, '2000-01-01 12:00:00', '2029-01-01 12:00:00'),
(107, 'user_byNotVerified_4_Active@cypress.test', NULL, NULL, 1, '2000-01-01 12:00:00', '2030-01-01 12:00:00'),
(108, 'user_byNotVerified_5_Active@cypress.test', NULL, NULL, 1, '2000-01-01 12:00:00', NULL),
(109, 'user_byNotVerified_1_Inactive@cypress.test', NULL, NULL, 0, '2000-01-01 12:00:00', '2030-01-01 12:00:00'),
(110, 'user_byNotVerified_2_Inactive@cypress.test', NULL, NULL, 0, '2000-01-01 12:00:00', NULL),

(111, 'user_byRole_1_Active@cypress.test', NULL, NULL, 1, '2000-01-01 12:00:00', '2000-01-01 12:00:00'),
(112, 'user_byRole_2_Active@cypress.test', NULL, NULL, 1, '2000-01-01 12:00:00', '2000-01-01 12:00:00'),
(113, 'user_byRole_3_Active@cypress.test', NULL, NULL, 1, '2000-01-01 12:00:00', '2000-01-01 12:00:00'),
(114, 'user_byRole_1_Inactive@cypress.test', NULL, NULL, 0, '2000-01-01 12:00:00', '2000-01-01 12:00:00'),
(115, 'user_byRole_1_Inactive-z_user_role@cypress.test', NULL, NULL, 1, '2000-01-01 12:00:00', '2000-01-01 12:00:00'),

(116, 'user_delete@cypress.test', NULL, NULL, 1, '2000-01-01 12:00:00', '2000-01-01 12:00:00'),
(117, 'user_delete-Interaction@cypress.test', NULL, NULL, 1, '2000-01-01 12:00:00', '2000-01-01 12:00:00'),

(118, 'user_updateEmail@cypress.test', NULL, NULL, 1, '2000-01-01 12:00:00', '2000-01-01 12:00:00'),

(119, 'user_updatePassword@cypress.test', '772e7e18b509ee9dbf4a53d415187fa49c68c991873e3282c0025e9e53d4c946125f184c34e04a7fcd5136fcdc04bedc17afd981380ee05ccb7683e7d83ec615', '4401287036553e310907533.22322450', 1, '2000-01-01 12:00:00', '2000-01-01 12:00:00'),

(120, 'user_verify@cypress.test', NULL, NULL, 1, '2000-01-01 12:00:00', NULL),
(121, 'user_verifySpecific@cypress.test', NULL, NULL, 1, '2000-01-01 12:00:00', NULL),

(122, 'user_isVerifiedOnNull@cypress.test', NULL, NULL, 1, '2000-01-01 12:00:00', NULL),
(123, 'user_isVerifiedNotNull@cypress.test', NULL, NULL, 1, '2000-01-01 12:00:00', '2025-01-01 12:00:00'),

(124, 'user_refresh@cypress.test', NULL, NULL, 1, '2000-01-01 12:00:00', '2025-01-01 12:00:00'),

(125, 'user_getRoles@cypress.test', NULL, NULL, 1, '2000-01-01 12:00:00', '2000-01-01 12:00:00'),

(126, 'user_getPermissions@cypress.test', NULL, NULL, 1, '2000-01-01 12:00:00', '2000-01-01 12:00:00'),

(127, 'user_byIds_1_Active@cypress.test', NULL, NULL, 1, '2000-01-01 12:00:00', '2000-01-01 12:00:00'),
(128, 'user_byIds_2_Active@cypress.test', NULL, NULL, 1, '2000-01-01 12:00:00', '2000-01-01 12:00:00'),
(129, 'user_byIds_3_Active@cypress.test', NULL, NULL, 1, '2000-01-01 12:00:00', '2000-01-01 12:00:00'),
(130, 'user_byIds_1_Inactive@cypress.test', NULL, NULL, 0, '2000-01-01 12:00:00', '2000-01-01 12:00:00'),
(131, 'user_byIds_2_Inactive@cypress.test', NULL, NULL, 0, '2000-01-01 12:00:00', '2000-01-01 12:00:00'),

(132, 'user_byAccessToAll_1_Active@cypress.test', NULL, NULL, 1, '2000-01-01 12:00:00', '2000-01-01 12:00:00'),
(133, 'user_byAccessToAll_2_Active@cypress.test', NULL, NULL, 1, '2000-01-01 12:00:00', '2000-01-01 12:00:00'),
(134, 'user_byAccessToAll_3_Active@cypress.test', NULL, NULL, 1, '2000-01-01 12:00:00', '2000-01-01 12:00:00'),
(135, 'user_byAccessToAll_1_Inactive@cypress.test', NULL, NULL, 0, '2000-01-01 12:00:00', '2000-01-01 12:00:00'),
(136, 'user_byAccessToAll_1_Inactive-z_role@cypress.test', NULL, NULL, 1, '2000-01-01 12:00:00', '2000-01-01 12:00:00'),
(137, 'user_byAccessToAll_1_Inactive-z_role_permission@cypress.test', NULL, NULL, 1, '2000-01-01 12:00:00', '2000-01-01 12:00:00'),
(138, 'user_byAccessToAll_1_Inactive-z_user_role@cypress.test', NULL, NULL, 1, '2000-01-01 12:00:00', '2000-01-01 12:00:00'),

(139, 'user_byAccessToAnyOf_1_Active@cypress.test', NULL, NULL, 1, '2000-01-01 12:00:00', '2000-01-01 12:00:00'),
(140, 'user_byAccessToAnyOf_2_Active@cypress.test', NULL, NULL, 1, '2000-01-01 12:00:00', '2000-01-01 12:00:00'),
(141, 'user_byAccessToAnyOf_3_Active@cypress.test', NULL, NULL, 1, '2000-01-01 12:00:00', '2000-01-01 12:00:00'),
(142, 'user_byAccessToAnyOf_4_Active@cypress.test', NULL, NULL, 1, '2000-01-01 12:00:00', '2000-01-01 12:00:00'),
(145, 'user_byAccessToAnyOf_1_Inactive@cypress.test', NULL, NULL, 0, '2000-01-01 12:00:00', '2000-01-01 12:00:00'),
(146, 'user_byAccessToAnyOf_1_Inactive-z_role@cypress.test', NULL, NULL, 1, '2000-01-01 12:00:00', '2000-01-01 12:00:00'),
(147, 'user_byAccessToAnyOf_1_Inactive-z_role_permission@cypress.test', NULL, NULL, 1, '2000-01-01 12:00:00', '2000-01-01 12:00:00'),
(148, 'user_byAccessToAnyOf_1_Inactive-z_user_role@cypress.test', NULL, NULL, 1, '2000-01-01 12:00:00', '2000-01-01 12:00:00'),
(149, 'user_byAccessToAnyOf_1_Inactive-z_user_permission@cypress.test', NULL, NULL, 1, '2000-01-01 12:00:00', '2000-01-01 12:00:00'),

(150, 'user_hasAccessToAll_1@cypress.test', NULL, NULL, 1, '2000-01-01 12:00:00', '2000-01-01 12:00:00'),
(151, 'user_hasAccessToAll_2@cypress.test', NULL, NULL, 1, '2000-01-01 12:00:00', '2000-01-01 12:00:00'),

(152, 'user_hasAccessToAnyOf_1@cypress.test', NULL, NULL, 1, '2000-01-01 12:00:00', '2000-01-01 12:00:00'),
(153, 'user_hasAccessToAnyOf_2@cypress.test', NULL, NULL, 1, '2000-01-01 12:00:00', '2000-01-01 12:00:00');


INSERT INTO `z_role`(`id`, `name`, `active`, `created`) VALUES
(100, 'user_byRole', 1, '2000-01-01 12:00:00'),

(101, 'user_getRoles_1_Active', 1, '2000-01-01 12:00:00'),
(102, 'user_getRoles_2_Active', 1, '2000-01-01 12:00:00'),
(103, 'user_getRoles_1_Inactive', 0, '2000-01-01 12:00:00'),
(104, 'user_getRoles_1_Inactive-z_user_role', 1, '2000-01-01 12:00:00'),

(105, 'user_getPermissions_1_Active', 1, '2000-01-01 12:00:00'),
(106, 'user_getPermissions_2_Active', 1, '2000-01-01 12:00:00'),
(107, 'user_getPermissions_1_Inactive', 0, '2000-01-01 12:00:00'),
(108, 'user_getPermissions_1_Inactive-z_user_role', 1, '2000-01-01 12:00:00'),

(109, 'user_byAccessToAll_1_Active', 1, '2000-01-01 12:00:00'),
(110, 'user_byAccessToAll_2_Active', 1, '2000-01-01 12:00:00'),
(111, 'user_byAccessToAll_1_Inactive', 0, '2000-01-01 12:00:00'),
(112, 'user_byAccessToAll_1_Inactive-z_role_permission', 1, '2000-01-01 12:00:00'),
(113, 'user_byAccessToAll_1_Inactive-z_user_role', 1, '2000-01-01 12:00:00'),
(114, 'user_byAccessToAll_1_Inactive-z_user_permission', 1, '2000-01-01 12:00:00'),

(115, 'user_byAccessToAnyOf_1_Active', 1, '2000-01-01 12:00:00'),
(116, 'user_byAccessToAnyOf_1_Inactive', 0, '2000-01-01 12:00:00'),
(117, 'user_byAccessToAnyOf_1_Inactive-z_role_permission', 1, '2000-01-01 12:00:00'),
(118, 'user_byAccessToAnyOf_1_Inactive-z_user_role', 1, '2000-01-01 12:00:00');


INSERT INTO `z_user_role`(`id`, `role`, `user`, `active`, `created`) VALUES
(100, 100, 111, 1, '2000-01-01 12:00:00'),
(101, 100, 112, 1, '2000-01-01 12:00:00'),
(102, 100, 113, 1, '2000-01-01 12:00:00'),
(103, 100, 114, 1, '2000-01-01 12:00:00'),
(104, 100, 115, 0, '2000-01-01 12:00:00'),

(105, 101, 125, 1, '2000-01-01 12:00:00'),
(106, 102, 125, 1, '2000-01-01 12:00:00'),
(107, 103, 125, 1, '2000-01-01 12:00:00'),
(108, 104, 125, 0, '2000-01-01 12:00:00'),

(109, 105, 126, 1, '2000-01-01 12:00:00'),
(110, 106, 126, 1, '2000-01-01 12:00:00'),
(111, 107, 126, 1, '2000-01-01 12:00:00'),
(112, 108, 126, 0, '2000-01-01 12:00:00'),

(113, 109, 133, 1, '2000-01-01 12:00:00'),
(114, 110, 134, 1, '2000-01-01 12:00:00'),
(115, 111, 136, 1, '2000-01-01 12:00:00'),
(116, 112, 137, 1, '2000-01-01 12:00:00'),
(117, 113, 138, 0, '2000-01-01 12:00:00'),

(118, 115, 142, 1, '2000-01-01 12:00:00'),
(119, 116, 146, 1, '2000-01-01 12:00:00'),
(120, 117, 147, 1, '2000-01-01 12:00:00'),
(121, 118, 148, 0, '2000-01-01 12:00:00');



INSERT INTO `z_user_permission`(`id`, `name`, `user`, `active`, `created`) VALUES
(100, 'user_getPermissions_1_Active-z_user_permission.1', 126, 1, '2000-01-01 12:00:00'),
(101, 'user_getPermissions_2_Active-z_user_permission.1', 126, 1, '2000-01-01 12:00:00'),
(102, 'user_getPermissions_1_Inactive-z_user_permission.1', 126, 0, '2000-01-01 12:00:00'),

(103, 'user.byAccessToAll.1', 132, 1, '2000-01-01 12:00:00'),
(104, 'user.byAccessToAll.2', 132, 1, '2000-01-01 12:00:00'),
(105, 'user.byAccessToAll.1', 133, 1, '2000-01-01 12:00:00'),
(106, 'user.byAccessToAll.1', 135, 1, '2000-01-01 12:00:00'),
(107, 'user.byAccessToAll.2', 135, 1, '2000-01-01 12:00:00'),
(108, 'user.byAccessToAll.1', 138, 0, '2000-01-01 12:00:00'),
(109, 'user.byAccessToAll.2', 138, 0, '2000-01-01 12:00:00'),

(110, 'user.byAccessToAnyOf.1', 139, 1, '2000-01-01 12:00:00'),
(111, 'user.byAccessToAnyOf.2', 140, 1, '2000-01-01 12:00:00'),

(112, 'user.byAccessToAnyOf.1', 141, 1, '2000-01-01 12:00:00'),
(113, 'user.byAccessToAnyOf.2', 141, 1, '2000-01-01 12:00:00'),
(114, 'user.byAccessToAnyOf.2', 145, 1, '2000-01-01 12:00:00'),
(115, 'user.byAccessToAnyOf.1', 149, 0, '2000-01-01 12:00:00'),

(150, 'user.hasAccessToAll.1', 150, 1, '2000-01-01 12:00:00'),
(151, 'user.hasAccessToAll.2', 150, 1, '2000-01-01 12:00:00'),
(152, 'user.hasAccessToAll.2', 151, 1, '2000-01-01 12:00:00'),

(153, 'user.hasAccessToAnyOf.2', 152, 1, '2000-01-01 12:00:00');


INSERT INTO `z_role_permission`(`id`, `role`, `name`, `active`, `created`) VALUES
(100, 105,'user_getPermissions_1_Active.should.have.1', 1, '2000-01-01 12:00:00'),
(101, 105,'user_getPermissions_1_Active.should.have.2', 1, '2000-01-01 12:00:00'),
(103, 105,'user_getPermissions_1_Active.should.not.have.1', 0, '2000-01-01 12:00:00'),
(104, 105,'user_getPermissions_1_Active.should.not.have.2', 0, '2000-01-01 12:00:00'),

(105, 106,'user_getPermissions_2_Active.should.have.1', 1, '2000-01-01 12:00:00'),
(106, 106,'user_getPermissions_2_Active.should.have.2', 1, '2000-01-01 12:00:00'),
(107, 106,'user_getPermissions_2_Active.should.not.have.1', 0, '2000-01-01 12:00:00'),
(108, 106,'user_getPermissions_2_Active.should.not.have.2', 0, '2000-01-01 12:00:00'),

(109, 107,'user_getPermissions_1_Inactive.should.not.have.1', 1, '2000-01-01 12:00:00'),
(110, 107,'user_getPermissions_1_Inactive.should.not.have.2', 1, '2000-01-01 12:00:00'),
(111, 107,'user_getPermissions_1_Inactive.should.not.have.3', 0, '2000-01-01 12:00:00'),
(112, 117,'user_getPermissions_1_Inactive.should.not.have.4', 0, '2000-01-01 12:00:00'),

(113, 108,'user_getPermissions_1_Inactive-z_user_role.should.not.have.1', 1, '2000-01-01 12:00:00'),
(114, 108,'user_getPermissions_1_Inactive-z_user_role.should.not.have.2', 1, '2000-01-01 12:00:00'),
(115, 108,'user_getPermissions_1_Inactive-z_user_role.should.not.have.3', 0, '2000-01-01 12:00:00'),
(116, 108,'user_getPermissions_1_Inactive-z_user_role.should.not.have.4', 0, '2000-01-01 12:00:00'),

(117, 109, 'user.byAccessToAll.2', 1, '2000-01-01 12:00:00'),
(118, 110, 'user.byAccessToAll.1', 1, '2000-01-01 12:00:00'),
(119, 110, 'user.byAccessToAll.2', 1, '2000-01-01 12:00:00'),

(120, 111, 'user.byAccessToAll.1', 1, '2000-01-01 12:00:00'),
(121, 111, 'user.byAccessToAll.2', 1, '2000-01-01 12:00:00'),

(122, 112, 'user.byAccessToAll.1', 0, '2000-01-01 12:00:00'),
(123, 112, 'user.byAccessToAll.2', 1, '2000-01-01 12:00:00'),

(124, 113, 'user.byAccessToAll.1', 1, '2000-01-01 12:00:00'),
(125, 113, 'user.byAccessToAll.2', 1, '2000-01-01 12:00:00'),

(126, 115, 'user.byAccessToAnyOf.1', 1, '2000-01-01 12:00:00'),
(127, 116, 'user.byAccessToAnyOf.1', 1, '2000-01-01 12:00:00'),
(128, 117, 'user.byAccessToAnyOf.1', 0, '2000-01-01 12:00:00'),
(129, 118, 'user.byAccessToAnyOf.1', 1, '2000-01-01 12:00:00');


/*
    Role Data for Permission Tests
*/
INSERT INTO `z_role`(`id`, `name`, `active`, `created`) VALUES
(200, 'role_byId', 1, '2000-01-01 12:00:00'),
(201, 'role_byIdInactive', 0, '2000-01-01 12:00:00'),

(202, 'role_byIds_1_Active', 1, '2000-01-01 12:00:00'),
(203, 'role_byIds_2_Active', 1, '2000-01-01 12:00:00'),
(204, 'role_byIds_1_Inactive', 0, '2000-01-01 12:00:00'),

(205, 'role_byUser_1_Active', 1, '2000-01-01 12:00:00'),
(206, 'role_byUser_2_Active', 1, '2000-01-01 12:00:00'),
(207, 'role_byUser_Inactive', 0, '2000-01-01 12:00:00'),
(208, 'role_byUser_Inactive-z_user_role', 1, '2000-01-01 12:00:00'),

(209, 'role_remove', 1, '2000-01-01 12:00:00'),
(210, 'role_removeInteraction', 1, '2000-01-01 12:00:00'),

(211, 'role_update', 1, '2000-01-01 12:00:00'),

(212, 'role_refresh', 1, '2000-01-01 12:00:00'),

(213, 'role_getPermissions', 1, '2000-01-01 12:00:00'),

(214, 'role_getUsers', 1, '2000-01-01 12:00:00'),

(215, 'role_hasAccessToAll_1', 1, '2000-01-01 12:00:00'),
(216, 'role_hasAccessToAll_2', 1, '2000-01-01 12:00:00'),

(217, 'role_hasAccessToAnyOf_1', 1, '2000-01-01 12:00:00'),
(218, 'role_hasAccessToAnyOf_2', 1, '2000-01-01 12:00:00'),

(219, 'role_byAccessToAll_1', 1, '2000-01-01 12:00:00'),
(220, 'role_byAccessToAll_2', 1, '2000-01-01 12:00:00'),
(221, 'role_byAccessToAll_3', 1, '2000-01-01 12:00:00'),
(222, 'role_byAccessToAll_1_Inactive', 0, '2000-01-01 12:00:00'),
(223, 'role_byAccessToAll_1_Inactive-z_role_permission', 1, '2000-01-01 12:00:00'),

(224, 'role_byAccessToAnyOf_1', 1, '2000-01-01 12:00:00'),
(225, 'role_byAccessToAnyOf_2', 1, '2000-01-01 12:00:00'),
(226, 'role_byAccessToAnyOf_1_Inactive', 0, '2000-01-01 12:00:00'),
(227, 'role_byAccessToAnyOf_1_Inactive-z_role_permission', 1, '2000-01-01 12:00:00'),

(228, 'role_byName_Active', 1, '2000-01-01 12:00:00'),
(229, 'role_byName_Inactive', 0, '2000-01-01 12:00:00');



INSERT INTO `z_role_permission`(`id`, `role`, `name`, `active`, `created`) VALUES
(200, 213, 'role_getPermissions.should.have.1', 1, '2000-01-01 12:00:00'),
(201, 213, 'role_getPermissions.should.have.2', 1, '2000-01-01 12:00:00'),
(202, 213, 'role_getPermissions.should.not.have.1', 0, '2000-01-01 12:00:00'),


(203, 215, 'role_hasAccessToAll.1', 1, '2000-01-01 12:00:00'),
(204, 215, 'role_hasAccessToAll.2', 1, '2000-01-01 12:00:00'),
(205, 216, 'role_hasAccessToAll.1', 1, '2000-01-01 12:00:00'),


(206, 217, 'role_hasAccessToAnyOf.1', 1, '2000-01-01 12:00:00'),


(207, 219, 'role_byAccessToAll.1', 1, '2000-01-01 12:00:00'),
(208, 219, 'role_byAccessToAll.2', 1, '2000-01-01 12:00:00'),
(209, 220, 'role_byAccessToAll.1', 1, '2000-01-01 12:00:00'),
(210, 220, 'role_byAccessToAll.2', 1, '2000-01-01 12:00:00'),
(211, 221, 'role_byAccessToAll.1', 1, '2000-01-01 12:00:00'),
(212, 222, 'role_byAccessToAll.1', 1, '2000-01-01 12:00:00'),
(213, 222, 'role_byAccessToAll.2', 1, '2000-01-01 12:00:00'),
(214, 223, 'role_byAccessToAll.1', 0, '2000-01-01 12:00:00'),
(215, 223, 'role_byAccessToAll.2', 1, '2000-01-01 12:00:00'),
(216, 223, 'role_byAccessToAll.3', 1, '2000-01-01 12:00:00'),

(217, 224, 'role_byAccessToAnyOf.1', 1, '2000-01-01 12:00:00'),
(218, 224, 'role_byAccessToAnyOf.2', 1, '2000-01-01 12:00:00'),
(219, 225, 'role_byAccessToAnyOf.1', 1, '2000-01-01 12:00:00'),
(220, 226, 'role_byAccessToAnyOf.1', 1, '2000-01-01 12:00:00'),
(221, 226, 'role_byAccessToAnyOf.2', 1, '2000-01-01 12:00:00'),
(222, 227, 'role_byAccessToAnyOf.1', 0, '2000-01-01 12:00:00'),
(223, 228, 'role_byAccessToAnyOf.2', 0, '2000-01-01 12:00:00');


INSERT INTO `z_user`(`id`, `email`, `password`, `salt`, `active`, `created`, `verified`) VALUES
(200, 'role_byUser@cypress.test', NULL, NULL, 1, '2000-01-01 12:00:00', '2000-01-01 12:00:00'),

(201, 'role_getUsers_1_Active@cypress.test', NULL, NULL, 1, '2000-01-01 12:00:00', '2000-01-01 12:00:00'),
(202, 'role_getUsers_2_Active@cypress.test', NULL, NULL, 1, '2000-01-01 12:00:00', '2000-01-01 12:00:00'),
(203, 'role_getUsers_1_Inactive@cypress.test', NULL, NULL, 0, '2000-01-01 12:00:00', '2000-01-01 12:00:00'),
(204, 'role_getUsers_1_Inactive-z_user_role@cypress.test', NULL, NULL, 1, '2000-01-01 12:00:00', '2000-01-01 12:00:00');



INSERT INTO `z_user_role`(`id`, `role`, `user`, `active`, `created`) VALUES
(200, 205, 200, 1, '2000-01-01 12:00:00'),
(201, 206, 200, 1, '2000-01-01 12:00:00'),
(202, 207, 200, 1, '2000-01-01 12:00:00'),
(203, 208, 200, 0, '2000-01-01 12:00:00'),

(204, 214, 201, 1, '2000-01-01 12:00:00'),
(205, 214, 202, 1, '2000-01-01 12:00:00'),
(206, 214, 203, 1, '2000-01-01 12:00:00'),
(207, 214, 204, 0, '2000-01-01 12:00:00');