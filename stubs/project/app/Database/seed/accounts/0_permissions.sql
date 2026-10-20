INSERT INTO `z_role` (`id`, `name`) VALUES
(1, 'Admin'),
(2, 'User');

INSERT INTO `z_role_permission` (`role`, `name`) VALUES
-- Admin
(1, '*.*'),

-- User
(2, 'dashboard');
