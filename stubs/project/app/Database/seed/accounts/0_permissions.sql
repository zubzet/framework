INSERT INTO `z_role` (`id`, `name`) VALUES
(1, 'Admin'),
(2, 'Support'),
(3, 'Customer'),
(4, 'Customer Setup');

INSERT INTO `z_role_permission` (`role`, `name`) VALUES
-- Admin
(1, '*.*'),

-- Support
(2, 'support'),
(2, 'dashboard'),

-- Customer
(3, 'customer'),
(3, 'dashboard'),

-- Customer: Only after setup
(4, 'customer.isSetup');