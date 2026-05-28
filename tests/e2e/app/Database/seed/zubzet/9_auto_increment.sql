-- Pin AUTO_INCREMENT for tables that e2e tests insert into so the first
-- auto-generated ID is independent of how many rows the seed files contain.
-- Adding new seed rows below 10000 will not shift the asserted IDs.
ALTER TABLE `z_user` AUTO_INCREMENT = 10000;
ALTER TABLE `z_organization` AUTO_INCREMENT = 10000;
