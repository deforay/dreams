--Pal 14/11/2016
CREATE TABLE `employee` (
  `

employee

_id` int(11) NOT NULL,
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

--Pal 26/11/2016

CREATE TABLE `global_config` (
  `display_name` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `value` varchar(255) DEFAULT NULL
)

INSERT INTO `global_config` (`display_name`, `name`, `value`) VALUES
('Locking Data After Login', 'locking_data_after_login', '72');

--Pal 28/11/2016
ALTER TABLE `country` CHANGE `status` `country_status` VARCHAR(45) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL;

--Pal 01/12/2016
ALTER TABLE `data_collection` ADD `date_of_test_completion` DATE NULL DEFAULT NULL AFTER `receipt_date_at_central_lab`;

ALTER TABLE `data_collection_event_log` ADD `date_of_test_completion` DATE NULL DEFAULT NULL AFTER `receipt_date_at_central_lab`;

--Pal 02/12/2016
ALTER TABLE `data_collection` ADD `enc_anc_patient_id` VARCHAR(500) NULL DEFAULT NULL AFTER `anc_patient_id`;

ALTER TABLE `data_collection_event_log` ADD `enc_anc_patient_id` VARCHAR(500) NULL DEFAULT NULL AFTER `anc_patient_id`;


--Pal 26/12/2016
INSERT INTO `role` (`role_id`, `role_name`, `role_code`, `role_description`, `role_status`) VALUES (NULL, 'Clinician', 'CL', NULL, 'active');

ALTER TABLE `user` ADD `has_data_reporting_access` VARCHAR(45) NULL DEFAULT NULL AFTER `alt_contact`, ADD `has_print_report_access` VARCHAR(45) NULL DEFAULT NULL AFTER `has_data_reporting_access`;

CREATE TABLE `user_clinic_map` (
  `clinic_map_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `clinic_id` int(11) NOT NULL
)

ALTER TABLE `user_clinic_map`
  ADD PRIMARY KEY (`clinic_map_id`);

ALTER TABLE `user_clinic_map`
  MODIFY `clinic_map_id` int(11) NOT NULL AUTO_INCREMENT
  
--Pal 30/12/2016
CREATE TABLE `anc_form` (
  `field_id` int(11) NOT NULL,
  `field_name` varchar(500) DEFAULT NULL,
  `age_disaggregation` varchar(45) DEFAULT NULL,
  `status` varchar(45) DEFAULT NULL
)

INSERT INTO `anc_form` (`field_id`, `field_name`, `age_disaggregation`, `status`) VALUES
(1, 'no_of_1st_ANC_attendees', 'yes', 'active'),
(2, 'no_known_HIV_positive_at_1st_ANC', 'yes', 'active'),
(3, 'no_with_unknown_HIV_status', 'no', 'active'),
(4, 'no_tested_for_HIV', 'yes', 'active'),
(5, 'no_RDT_positive', 'yes', 'active'),
(6, 'no_RDT_negative', 'yes', 'active'),
(7, 'no_RDT_indeterminate', 'no', 'active'),
(8, 'no_specimen_drawn_for_recency_testing', 'no', 'active'),
(9, 'recency_result', 'yes', 'active'),
(10, 'no_who_received_recency_result', 'no', 'active');

ALTER TABLE `anc_form`
  ADD PRIMARY KEY (`field_id`);
  
ALTER TABLE `anc_form`
  MODIFY `field_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

CREATE TABLE `clinic_data_collection` (
  `cl_data_collection_id` int(11) NOT NULL,
  `anc` int(11) DEFAULT NULL,
  `reporting_month_year` varchar(45) DEFAULT NULL,
  `characteristics_data` text,
  `country` int(11) DEFAULT NULL,
  `added_on` datetime DEFAULT NULL,
  `added_by` int(11) DEFAULT NULL
)

ALTER TABLE `clinic_data_collection`
  ADD PRIMARY KEY (`cl_data_collection_id`);
  
ALTER TABLE `clinic_data_collection`
  MODIFY `cl_data_collection_id` int(11) NOT NULL AUTO_INCREMENT
  
--Pal 31/12/2016
ALTER TABLE `clinic_data_collection` ADD `updated_on` DATETIME NULL DEFAULT NULL AFTER `added_by`, ADD `updated_by` INT(11) NULL DEFAULT NULL AFTER `updated_on`;

--Pal 02/01/2017
ALTER TABLE `user` ADD `has_view_only_access` VARCHAR(45) NULL DEFAULT NULL AFTER `alt_contact`;

CREATE TABLE `user_laboratory_map` (
  `laboratory_map_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `laboratory_id` int(11) NOT NULL
)

ALTER TABLE `user_laboratory_map`
  ADD PRIMARY KEY (`laboratory_map_id`);
  
ALTER TABLE `user_laboratory_map`
  MODIFY `laboratory_map_id` int(11) NOT NULL AUTO_INCREMENT;
  
--Pal 03/01/2017
ALTER TABLE `user` ADD `created_by` INT(11) NULL DEFAULT NULL AFTER `status`;

ALTER TABLE `user` CHANGE `created_by` `created_by` INT(11) NOT NULL;

UPDATE `role` SET `role_name` = 'ANC Data Entry Operator' WHERE `role`.`role_id` = 5;

UPDATE `role` SET `role_code` = 'ANCDEO' WHERE `role`.`role_id` = 5;


--Pal 05/01/2017
alter table user add FOREIGN KEY(role) REFERENCES role(role_id)

--Pal 06/01/2017
ALTER TABLE `user` ADD `last_login` DATETIME NULL DEFAULT NULL AFTER `status`;

ALTER TABLE `user` ADD `comments` TEXT NULL DEFAULT NULL AFTER `has_view_only_access`;

ALTER TABLE `facility` ADD `comments` TEXT NULL DEFAULT NULL AFTER `longitude`;

ALTER TABLE `anc_site` ADD `comments` TEXT NULL DEFAULT NULL AFTER `longitude`;

ALTER TABLE `country` ADD `comments` TEXT NULL DEFAULT NULL AFTER `country_code`;

ALTER TABLE `data_collection` ADD `comments` TEXT NULL DEFAULT NULL AFTER `asante_rapid_recency_assy`;

ALTER TABLE `data_collection_event_log` ADD `comments` TEXT NULL DEFAULT NULL AFTER `asante_rapid_recency_assy`;

--Pal 07/01/2017
ALTER TABLE `clinic_data_collection` ADD `comments` TEXT NULL DEFAULT NULL AFTER `characteristics_data`;

--Pal 12/01/2017
UPDATE `anc_form` SET `field_name` = 'no_of_recency_result' WHERE `anc_form`.`field_id` = 9;

UPDATE `anc_form` SET `field_name` = 'no_of_clients_who_received_recency_result' WHERE `anc_form`.`field_id` = 10;

--Pal 17/03/2017
CREATE TABLE `login_tracker` (
  `tracker_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `logged_in_datetime` datetime NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL
)

ALTER TABLE `login_tracker`
  ADD PRIMARY KEY (`tracker_id`),
  ADD KEY `user_id` (`user_id`);
  
ALTER TABLE `login_tracker`
  MODIFY `tracker_id` int(11) NOT NULL AUTO_INCREMENT
  
--Pal 18/03/2017
ALTER TABLE `data_collection` ADD `updated_on` DATETIME NULL DEFAULT NULL AFTER `added_by`, ADD `updated_by` INT(11) NULL DEFAULT NULL AFTER `updated_on`, ADD `locked_on` DATETIME NULL DEFAULT NULL AFTER `updated_by`, ADD `locked_by` INT(11) NULL DEFAULT NULL AFTER `locked_on`, ADD `unlocked_on` DATETIME NULL DEFAULT NULL AFTER `locked_by`, ADD `unlocked_by` INT(11) NULL DEFAULT NULL AFTER `unlocked_on`;

ALTER TABLE `data_collection_event_log` ADD `locked_on` DATETIME NULL DEFAULT NULL AFTER `updated_by`, ADD `locked_by` INT(11) NULL DEFAULT NULL AFTER `locked_on`, ADD `unlocked_on` DATETIME NULL DEFAULT NULL AFTER `locked_by`, ADD `unlocked_by` INT(11) NULL DEFAULT NULL AFTER `unlocked_on`;

--Pal 05/06/2017
CREATE TABLE `occupation_type` (
  `occupation_id` int(11) NOT NULL,
  `occupation` varchar(500) NOT NULL,
  `occupation_status` varchar(45) NOT NULL DEFAULT 'active'
)

INSERT INTO `occupation_type` (`occupation_id`, `occupation`, `occupation_status`) VALUES
(1, 'Not Currently Working', 'active'),
(2, 'Student', 'active'),
(3, 'Fishing', 'active'),
(4, 'Farming', 'active'),
(5, 'Driver', 'active'),
(6, 'Manual Worker', 'active'),
(7, 'Professional/Managerial', 'active'),
(8, 'Domestic Worker', 'active');

ALTER TABLE `occupation_type`
  ADD PRIMARY KEY (`occupation_id`);
  
ALTER TABLE `occupation_type`
  MODIFY `occupation_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

CREATE TABLE `clinic_risk_assessment` (
  `assessment_id` int(11) NOT NULL,
  `lab` int(11) DEFAULT NULL,
  `enc_anc_patient_id` varchar(500) DEFAULT NULL,
  `interviewer_name` varchar(255) DEFAULT NULL,
  `anc_patient_id` varchar(45) DEFAULT NULL,
  `interview_date` date DEFAULT NULL,
  `occupation` int(11) DEFAULT NULL,
  `degree` varchar(45) DEFAULT NULL,
  `are_married` varchar(25) DEFAULT NULL,
  `age_at_first_marriage` varchar(45) DEFAULT NULL,
  `have_ever_been_widowed` varchar(45) DEFAULT NULL,
  `current_marital_status` varchar(255) DEFAULT NULL,
  `time_of_last_HIV_test` varchar(90) DEFAULT NULL,
  `last_HIV_test_status` varchar(255) DEFAULT NULL,
  `partner_HIV_test_status` varchar(255) DEFAULT NULL,
  `age_at_very_first_sex` int(11) DEFAULT NULL,
  `reason_for_very_first_sex` varchar(45) DEFAULT NULL,
  `no_of_sexual_partners` varchar(45) DEFAULT NULL,
  `no_of_sexual_partners_in_last_six_months` varchar(45) DEFAULT NULL,
  `age_of_main_sexual_partner_at_last_birthday` varchar(45) DEFAULT NULL,
  `age_diff_of_main_sexual_partner` varchar(255) DEFAULT NULL,
  `is_partner_circumcised` varchar(45) DEFAULT NULL,
  `last_time_of_receiving_money_for_sex` varchar(45) DEFAULT NULL,
  `no_of_times_been_pregnant` int(11) DEFAULT NULL,
  `no_of_times_condom_used_before_pregnancy` varchar(45) DEFAULT NULL,
  `no_of_times_condom_used_after_pregnancy` varchar(45) DEFAULT NULL,
  `have_pain_in_lower_abdomen` varchar(45) DEFAULT NULL,
  `have_treated_for_lower_abdomen_pain` varchar(45) DEFAULT NULL,
  `have_treated_for_syphilis` varchar(45) DEFAULT NULL,
  `no_of_days_had_drink_in_last_six_months` varchar(90) DEFAULT NULL,
  `do_have_more_drinks_on_one_occasion` varchar(45) DEFAULT NULL,
  `have_tried_recreational_drugs` varchar(45) DEFAULT NULL,
  `had_recreational_drugs_in_last_six_months` varchar(45) DEFAULT NULL,
  `recreational_drugs` varchar(500) DEFAULT NULL,
  `country` int(11) NOT NULL,
  `added_on` datetime DEFAULT NULL,
  `added_by` int(11) DEFAULT NULL,
  `updated_on` datetime DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL
)

ALTER TABLE `clinic_risk_assessment`
  ADD PRIMARY KEY (`assessment_id`),
  ADD KEY `country` (`country`),
  ADD KEY `lab` (`lab`),
  ADD KEY `added_by` (`added_by`);
  
ALTER TABLE `clinic_risk_assessment`
  MODIFY `assessment_id` int(11) NOT NULL AUTO_INCREMENT