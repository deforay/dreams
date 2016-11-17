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