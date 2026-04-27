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
(510, 'org_userOrg_Updated', 1, '2000-01-01 12:00:00');


INSERT INTO `z_user`(`id`, `email`, `password`, `salt`, `active`, `created`, `verified`, `organizationId`) VALUES
(500, 'org_byUser@cypress.test', NULL, NULL, 1, '2000-01-01 12:00:00', '2000-01-01 12:00:00', 505),

(501, 'org_getUsers_1_Active@cypress.test', NULL, NULL, 1, '2000-01-01 12:00:00', '2000-01-01 12:00:00', 506),
(502, 'org_getUsers_2_Active@cypress.test', NULL, NULL, 1, '2000-01-01 12:00:00', '2000-01-01 12:00:00', 506),
(503, 'org_getUsers_1_Inactive@cypress.test', NULL, NULL, 0, '2000-01-01 12:00:00', '2000-01-01 12:00:00', 506),

(504, 'org_userOrg_Assign@cypress.test', NULL, NULL, 1, '2000-01-01 12:00:00', '2000-01-01 12:00:00', NULL),
(505, 'org_userOrg_Update@cypress.test', NULL, NULL, 1, '2000-01-01 12:00:00', '2000-01-01 12:00:00', 509);
