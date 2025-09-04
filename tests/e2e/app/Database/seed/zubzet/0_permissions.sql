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
(2, 'data.ingest-raw'),
(2, 'notifications'),
(2, 'stats.user-count'),
(2, 'table.companies'),

-- Customer
(3, 'customer'),
(3, 'dashboard'),
(3, 'company.profile'),

-- Customer: Only after setup
(4, 'customer.isSetup'),
(4, 'invoices.view'),
-- (4, 'company.sepa'),
(4, 'orders.view'),
(4, 'orders.add'),
(4, 'orders.delete'),
(4, 'leads.view'),
(4, 'leads.download'),
(4, 'settings.segmentation'),
(4, 'notifications');