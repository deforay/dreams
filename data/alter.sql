--Pal 14/11/2016
CREATE TABLE `employee` (
  `employee_id` int(11) NOT NULL,
  `employee_name` varchar(255) DEFAULT NULL,
  `employee_code` varchar(45) NOT NULL,
  `password` varchar(500) DEFAULT NULL,
  `role` int(11) NOT NULL,
  `email` varchar(250) DEFAULT NULL,
  `mobile` VARCHAR(45) DEFAULT NULL,
  `alt_contact` int(11) DEFAULT NULL,
  `status` varchar(45) DEFAULT NULL,
  `created_on` datetime NOT NULL
)

ALTER TABLE `employee`
  ADD PRIMARY KEY (`employee_id`);
  
ALTER TABLE `employee`
  MODIFY `employee_id` int(11) NOT NULL AUTO_INCREMENT;
  
CREATE TABLE `role` (
  `role_id` int(11) NOT NULL,
  `role_name` varchar(255) DEFAULT NULL,
  `role_code` varchar(45) DEFAULT NULL,
  `has_global_access` varchar(45) NOT NULL DEFAULT 'no',
  `role_description` text,
  `role_status` varchar(45) DEFAULT NULL
)

ALTER TABLE `role`
  ADD PRIMARY KEY (`role_id`);
  
ALTER TABLE `role`
  MODIFY `role_id` int(11) NOT NULL AUTO_INCREMENT;
  
--Pal 15/11/2016
ALTER TABLE `employee` CHANGE `alt_contact` `alt_contact` VARCHAR(45) NULL DEFAULT NULL;