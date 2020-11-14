ALTER TABLE `{$wpdb->prefix}wpjb_mail` ADD `files` TEXT NOT NULL AFTER `mail_bcc` ; --

INSERT INTO `{$wpdb->prefix}wpjb_meta` (`name`, `meta_object`, `meta_type`, `meta_value`) VALUES
('form_code', 'job', 2, ''),
('form_code', 'company', 2, ''),
('form_code', 'apply', 2, ''),
('form_code', 'resume', 2, ''),
('stripe_intent', 'payment', 2, ''); --

INSERT INTO `{$wpdb->prefix}wpjb_meta` (`id`, `name`, `meta_object`, `meta_type`, `meta_value`) VALUES 
(NULL, 'stripe_save_cc', 'payment', '1', ''), 
(NULL, 'stripe_payment_intent_id', 'payment', '1', '') ; --