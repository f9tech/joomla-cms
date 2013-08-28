# Placeholder file for database changes for version 3.2.0
INSERT INTO `#__assets` (`id`, `parent_id`, `lft`, `rgt`, `level`, `name`, `title`, `rules`) VALUES
(36, 1, 69, 70, 1, 'com_services', 'com_services', '{}');

INSERT INTO `#__extensions` (`extension_id`, `name`, `type`, `element`, `folder`, `client_id`, `enabled`, `access`, `protected`, `manifest_cache`, `params`, `custom_data`, `system_data`, `checked_out`, `checked_out_time`, `ordering`, `state`) VALUES
(30, 'com_services', 'component', 'com_services', '', 1, 1, 0, 0, '{"name":"com_services","type":"component","creationDate":"2013-06-31","author":"Joomla! Project","copyright":"Copyright Info","authorEmail":"Joomla@joomla.com","authorUrl":"http:\/\/joomla.org","version":"1.0.0","description":"Front End Admin Services Configuration Manager","group":""}', '{}', '', '', 0, '0000-00-00 00:00:00', 0, 0),
(318, 'mod_admin_services', 'module', 'mod_admin_services', '', 0, 1, 0, 0, '{"name":"mod_admin_services","type":"module","creationDate":"June 2013","author":"Joomla! Project","copyright":"Copyright (C) 2005 - 2013 Open Source Matters. All rights\n\t\treserved.\n\t","authorEmail":"admin@joomla.org","authorUrl":"www.joomla.org","version":"3.2.0","description":"MOD_ADMIN_SERVICES_XML_DESCRIPTION","group":""}', '{"config_visible":"1"}', '', '', 0, '0000-00-00 00:00:00', 0, 0);
