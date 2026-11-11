/*
    File Data for File Tests
*/


INSERT INTO `z_file`(`id`, `uuid`, `reference`, `type`, `name`, `extension`, `size`, `replaced_by`, `active`, `created`) VALUES
(500, '01890000-0000-7000-8000-000000000500', 'file_byId_Active', 'text/plain', 'byId.txt', 'txt', 11, NULL, 1, '2000-01-01 12:00:00'),
(501, '01890000-0000-7000-8000-000000000501', 'file_byId_Inactive', 'text/plain', 'inactive.txt', 'txt', 12, NULL, 0, '2000-01-01 12:00:00'),

(502, '01890000-0000-7000-8000-000000000502', 'file_byReference', 'image/png', 'byReference.png', 'png', 13, NULL, 1, '2000-01-01 12:00:00'),

(503, '01890000-0000-7000-8000-000000000503', 'file_remove', 'text/plain', 'remove.txt', 'txt', 14, NULL, 1, '2000-01-01 12:00:00'),

(504, '01890000-0000-7000-8000-000000000504', 'file_replaceWith_Old', 'text/plain', 'old.txt', 'txt', 15, NULL, 1, '2000-01-01 12:00:00'),
(505, '01890000-0000-7000-8000-000000000505', 'file_replaceWith_New', 'text/plain', 'new.txt', 'txt', 16, NULL, 1, '2000-01-01 12:00:00'),

(506, '01890000-0000-7000-8000-000000000506', 'file_replacedBy_Old', 'text/plain', 'old.txt', 'txt', 17, 507, 1, '2000-01-01 12:00:00'),
(507, '01890000-0000-7000-8000-000000000507', 'file_replacedBy_New', 'text/plain', 'new.txt', 'txt', 18, NULL, 1, '2000-01-01 12:00:00'),

(508, '01890000-0000-7000-8000-000000000508', 'file_replacedBy_RemovedOld', 'text/plain', 'old.txt', 'txt', 19, 509, 1, '2000-01-01 12:00:00'),
(509, '01890000-0000-7000-8000-000000000509', 'file_replacedBy_RemovedNew', 'text/plain', 'new.txt', 'txt', 20, NULL, 0, '2000-01-01 12:00:00'),

(510, '01890000-0000-7000-8000-000000000510', 'file_refresh', 'text/plain', 'refresh.txt', 'txt', 21, NULL, 1, '2000-01-01 12:00:00'),
(511, '01890000-0000-7000-8000-000000000511', 'file_refresh_Target', 'text/plain', 'target.txt', 'txt', 22, NULL, 1, '2000-01-01 12:00:00'),

(512, '01890000-0000-7000-8000-000000000512', 'file_replaceWith_Overwrite', 'text/plain', 'old.txt', 'txt', 23, 513, 1, '2000-01-01 12:00:00'),
(513, '01890000-0000-7000-8000-000000000513', 'file_replaceWith_Previous', 'text/plain', 'previous.txt', 'txt', 24, NULL, 1, '2000-01-01 12:00:00'),
(514, '01890000-0000-7000-8000-000000000514', 'file_replaceWith_Next', 'text/plain', 'next.txt', 'txt', 25, NULL, 1, '2000-01-01 12:00:00');
