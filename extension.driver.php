<?php

	Class extension_pricemarkupfield extends Extension {

		public function uninstall() {
			Symphony::Database()->query("DROP TABLE `tbl_fields_pricemarkup`");
		}

		public function install() {
			return Symphony::Database()->query("
				CREATE TABLE IF NOT EXISTS `tbl_fields_pricemarkup` (
				  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
				  `field_id` int(11) unsigned NOT NULL,
				  `related_field_id` int(11) unsigned DEFAULT NULL,
				  PRIMARY KEY (`id`),
				  UNIQUE KEY `field_id` (`field_id`),
				  KEY `related_field_id` (`related_field_id`)
				) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci
			");
		}
		
		public function getSubscribedDelegates() {
			return array(
				array(
					'page'		=> '/backend/',
					'delegate'	=> 'InitialiseAdminPageHead',
					'callback'	=> 'addAssetsToPageHead'
				),
			);
		}
		
		public function addAssetsToPageHead($context){
			$page = Administration::instance()->Page->getContext();
		
			if(!isset($page['section_handle']) || !isset($page['page']) || !in_array($page['page'], array('new', 'edit'))){
				return;
			}
			
			Administration::instance()->Page->addStylesheetToHead(
				URL . '/extensions/pricemarkupfield/assets/styles.css', 'screen', 100
			);
			
			Administration::instance()->Page->addScriptToHead(
				URL . '/extensions/pricemarkupfield/assets/styles.js', 100
			);
		}

	}
