INSERT INTO `role` (`id`, `name`, `description`) VALUES(3, 'superadmin', 'Super admin, role for root user');
INSERT INTO `role_user` (`user_id`, `role_id`) VALUES (2, 3);
