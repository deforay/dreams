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

ALTER TABLE `employee` ADD `country` INT(11) NULL DEFAULT NULL AFTER `mobile`;

CREATE TABLE `country` (
  `country_id` int(11) NOT NULL,
  `country_name` varchar(255) DEFAULT NULL,
  `country_code` varchar(255) DEFAULT NULL,
  `status` varchar(45) DEFAULT NULL
)

ALTER TABLE `country`
  ADD PRIMARY KEY (`country_id`);
  
ALTER TABLE `country`
  MODIFY `country_id` int(11) NOT NULL AUTO_INCREMENT;
  
--Pal 16/11/2016
ALTER TABLE `employee` ADD `user_name` VARCHAR(255) NULL DEFAULT NULL AFTER `employee_code`;

CREATE TABLE `facility_type` (
  `facility_type_id` int(11) NOT NULL,
  `facility_type_name` varchar(255) DEFAULT NULL,
  `status` varchar(45) DEFAULT NULL
)

ALTER TABLE `facility_type`
  ADD PRIMARY KEY (`facility_type_id`);
  
ALTER TABLE `facility_type`
  MODIFY `facility_type_id` int(11) NOT NULL AUTO_INCREMENT;
  
INSERT INTO `facility_type` (`facility_type_id`, `facility_type_name`, `status`) VALUES
(1, 'clinic', 'active'),
(2, 'lab', 'active'),
(3, 'hub', 'active');

CREATE TABLE `facility` (
  `facility_id` int(11) NOT NULL,
  `facility_name` varchar(255) DEFAULT NULL,
  `facility_code` varchar(255) DEFAULT NULL,
  `facility_type` int(11) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `contact_person` varchar(255) DEFAULT NULL,
  `phone_number` varchar(255) DEFAULT NULL,
  `country` varchar(255) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `latitude` varchar(255) DEFAULT NULL,
  `longitude` varchar(255) DEFAULT NULL,
  `status` varchar(255) DEFAULT NULL
)

ALTER TABLE `facility`
  ADD PRIMARY KEY (`facility_id`);
  
ALTER TABLE `facility`
  MODIFY `facility_id` int(11) NOT NULL AUTO_INCREMENT;
  
ALTER TABLE `facility` CHANGE `country` `country` INT(11) NULL DEFAULT NULL;

CREATE TABLE `anc_site` (
  `anc_site_id` int(11) NOT NULL,
  `anc_site_name` varchar(255) DEFAULT NULL,
  `anc_site_code` varchar(255) DEFAULT NULL,
  `anc_site_type` int(11) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `contact_person` varchar(255) DEFAULT NULL,
  `phone_number` varchar(255) DEFAULT NULL,
  `country` int(11) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `latitude` varchar(255) DEFAULT NULL,
  `longitude` varchar(255) DEFAULT NULL,
  `status` varchar(255) DEFAULT NULL
)

ALTER TABLE `anc_site`
  ADD PRIMARY KEY (`anc_site_id`);
  
ALTER TABLE `anc_site`
  MODIFY `anc_site_id` int(11) NOT NULL AUTO_INCREMENT;
  
ALTER TABLE `role` DROP `has_global_access`;

--Pal 17/11/2016

CREATE TABLE `data_collection` (
  `data_collection_id` int(11) NOT NULL,
  `surveillance_id` varchar(45) DEFAULT NULL,
  `specimen_collected_date` date DEFAULT NULL,
  `anc_site` int(11) DEFAULT NULL,
  `anc_patient_id` varchar(45) DEFAULT NULL,
  `age` int(11) DEFAULT NULL,
  `specimen_picked_up_date_at_anc` date DEFAULT NULL,
  `lab` int(11) DEFAULT NULL,
  `lab_specimen_id` varchar(45) DEFAULT NULL,
  `receipt_date_at_central_lab` date DEFAULT NULL,
  `rejection_reason` int(11) DEFAULT NULL,
  `final_lag_avidity_odn` varchar(45) DEFAULT NULL,
  `lag_avidity_result` varchar(45) DEFAULT NULL,
  `hiv_rna` varchar(45) DEFAULT NULL,
  `hiv_rna_gt_1000` varchar(45) DEFAULT NULL,
  `recent_infection` varchar(45) DEFAULT NULL,
  `result_dispatched_date_to_clinic` date DEFAULT NULL,
  `asante_rapid_recency_assy` varchar(45) DEFAULT NULL,
  `added_on` datetime DEFAULT NULL,
  `added_by` int(11) DEFAULT NULL
)

ALTER TABLE `data_collection`
  ADD PRIMARY KEY (`data_collection_id`);
  
ALTER TABLE `data_collection`
  MODIFY `data_collection_id` int(11) NOT NULL AUTO_INCREMENT
  
CREATE TABLE `specimen_rejection_reason` (
  `rejection_reason_id` int(11) NOT NULL,
  `rejection_reason` varchar(255) DEFAULT NULL,
  `rejection_code` varchar(45) DEFAULT NULL,
  `status` varchar(45) DEFAULT NULL
)

ALTER TABLE `specimen_rejection_reason`
  ADD PRIMARY KEY (`rejection_reason_id`);
  
ALTER TABLE `specimen_rejection_reason`
  MODIFY `rejection_reason_id` int(11) NOT NULL AUTO_INCREMENT
  
--Pal 18/11/2016
ALTER TABLE `data_collection` ADD `country` INT(11) NULL DEFAULT NULL AFTER `asante_rapid_recency_assy`;

CREATE TABLE `data_collection_event_log` (
  `data_collection_event_log_id` int(11) NOT NULL,
  `data_collection_id` int(11) NOT NULL,
  `surveillance_id` varchar(45) DEFAULT NULL,
  `specimen_collected_date` date DEFAULT NULL,
  `anc_site` int(11) DEFAULT NULL,
  `anc_patient_id` varchar(45) DEFAULT NULL,
  `age` int(11) DEFAULT NULL,
  `specimen_picked_up_date_at_anc` date DEFAULT NULL,
  `lab` int(11) DEFAULT NULL,
  `lab_specimen_id` varchar(45) DEFAULT NULL,
  `receipt_date_at_central_lab` date DEFAULT NULL,
  `rejection_reason` int(11) DEFAULT NULL,
  `final_lag_avidity_odn` varchar(45) DEFAULT NULL,
  `lag_avidity_result` varchar(45) DEFAULT NULL,
  `hiv_rna` varchar(45) DEFAULT NULL,
  `hiv_rna_gt_1000` varchar(45) DEFAULT NULL,
  `recent_infection` varchar(45) DEFAULT NULL,
  `result_dispatched_date_to_clinic` date DEFAULT NULL,
  `asante_rapid_recency_assy` varchar(45) DEFAULT NULL,
  `country` int(11) DEFAULT NULL,
  `added_on` datetime DEFAULT NULL,
  `added_by` int(11) DEFAULT NULL,
  `updated_on` datetime DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL
)

ALTER TABLE `data_collection_event_log`
  ADD PRIMARY KEY (`data_collection_event_log_id`);
  
ALTER TABLE `data_collection_event_log`
  MODIFY `data_collection_event_log_id` int(11) NOT NULL AUTO_INCREMENT;
  
ALTER TABLE `data_collection` ADD `lock_state` VARCHAR(45) NULL DEFAULT NULL AFTER `country`, ADD `status` VARCHAR(45) NOT NULL DEFAULT 'pending' AFTER `lock_state`;

ALTER TABLE `data_collection_event_log` ADD `lock_state` VARCHAR(45) NULL DEFAULT NULL AFTER `country`, ADD `status` VARCHAR(45) NOT NULL DEFAULT 'pending' AFTER `lock_state`

--Pal 19/11/2016
ALTER TABLE `data_collection` CHANGE `status` `status` VARCHAR(45) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL DEFAULT 'awaiting';

ALTER TABLE `data_collection` DROP `status`;

ALTER TABLE `data_collection` ADD `status` INT(11) NULL DEFAULT NULL AFTER `lock_state`;

ALTER TABLE `data_collection_event_log` CHANGE `status` `status` INT(11) NULL DEFAULT NULL;

CREATE TABLE `test_status` (
  `test_status_id` int(11) NOT NULL,
  `test_status_name` varchar(255) DEFAULT NULL
)

ALTER TABLE `test_status`
  ADD PRIMARY KEY (`test_status_id`);
  
ALTER TABLE `test_status`
  MODIFY `test_status_id` int(11) NOT NULL AUTO_INCREMENT
  
INSERT INTO `test_status` (`test_status_id`, `test_status_name`) VALUES
(1, 'Awaiting Clinic Approval'),
(2, 'Accepted'),
(3, 'Rejected'),
(4, 'Hold'),
(5, 'Sample Reordered'),
(6, 'Invalid'),
(7, 'Lost');

ALTER TABLE `data_collection` ADD `request_state` VARCHAR(45) NULL DEFAULT NULL AFTER `lock_state`;

ALTER TABLE `data_collection_event_log` ADD `request_state` VARCHAR(45) NULL DEFAULT NULL AFTER `lock_state`;

--Pal 22/11/2016
DROP TABLE employee

ALTER TABLE `user`
  DROP `employee_name`;
  
ALTER TABLE `user` CHANGE `employee_id` `user_id` INT(11) NOT NULL AUTO_INCREMENT, CHANGE `employee_code` `user_code` VARCHAR(45) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL;

ALTER TABLE `user` ADD `full_name` VARCHAR(255) NULL DEFAULT NULL AFTER `user_id`;

ALTER TABLE `data_collection`
  DROP `lock_state`,
  DROP `request_state`;
  
ALTER TABLE `data_collection_event_log`
  DROP `lock_state`,
  DROP `request_state`;
  
--Saravanan 22-nov-2016
  CREATE TABLE `user_country_map` (
  `country_map_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `country_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

ALTER TABLE `user_country_map`
  ADD PRIMARY KEY (`country_map_id`);

ALTER TABLE `user_country_map`
  MODIFY `country_map_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;
  
--Pal 24/11/2016
INSERT INTO `test_status` (`test_status_id`, `test_status_name`) VALUES (NULL, 'unlocked');

ALTER TABLE `data_collection` ADD `result_mail_sent` VARCHAR(45) NOT NULL DEFAULT 'no' AFTER `country`;