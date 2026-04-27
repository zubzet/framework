/*
    Organization Data for Organization Tests
*/


INSERT INTO `z_organization`(`id`, `name`, `active`, `created`) VALUES
(400, 'org_byId_Active', 1, '2000-01-01 12:00:00'),
(401, 'org_byId_Inactive', 0, '2000-01-01 12:00:00'),

(402, 'org_byName_Shared', 1, '2000-01-01 12:00:00'),
(403, 'org_byName_Shared', 1, '2000-01-01 12:00:00'),
(404, 'org_byName_Inactive', 0, '2000-01-01 12:00:00'),

(405, 'org_byUser', 1, '2000-01-01 12:00:00'),

(406, 'org_getUsers', 1, '2000-01-01 12:00:00'),

(407, 'org_updateName', 1, '2000-01-01 12:00:00'),

(408, 'org_remove', 1, '2000-01-01 12:00:00'),

(409, 'org_userOrg_Initial', 1, '2000-01-01 12:00:00'),
(410, 'org_userOrg_Updated', 1, '2000-01-01 12:00:00');


INSERT INTO `z_user`(`id`, `email`, `password`, `salt`, `active`, `created`, `verified`, `organizationId`) VALUES
(400, 'org_byUser@cypress.test', NULL, NULL, 1, '2000-01-01 12:00:00', '2000-01-01 12:00:00', 405),

(401, 'org_getUsers_1_Active@cypress.test', NULL, NULL, 1, '2000-01-01 12:00:00', '2000-01-01 12:00:00', 406),
(402, 'org_getUsers_2_Active@cypress.test', NULL, NULL, 1, '2000-01-01 12:00:00', '2000-01-01 12:00:00', 406),
(403, 'org_getUsers_1_Inactive@cypress.test', NULL, NULL, 0, '2000-01-01 12:00:00', '2000-01-01 12:00:00', 406),

(404, 'org_userOrg_Assign@cypress.test', NULL, NULL, 1, '2000-01-01 12:00:00', '2000-01-01 12:00:00', NULL),
(405, 'org_userOrg_Update@cypress.test', NULL, NULL, 1, '2000-01-01 12:00:00', '2000-01-01 12:00:00', 409);
