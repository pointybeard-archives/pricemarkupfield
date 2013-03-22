<?php

	require_once FACE . '/interface.exportablefield.php';
	require_once FACE . '/interface.importablefield.php';

	class FieldPriceMarkup extends Field implements ExportableField, ImportableField {
		public function __construct() {
			parent::__construct();
			$this->_name = __('Price Markup');
			$this->_required = true;
			$this->set('required', 'no');
		}

	/*-------------------------------------------------------------------------
		Setup:
	-------------------------------------------------------------------------*/

		public function mustBeUnique(){
			return true;
		}

		public function isSortable() {
			return true;
		}

		public function canFilter() {
			return true;
		}

		public function allowDatasourceOutputGrouping() {
			return true;
		}

		public function allowDatasourceParamOutput() {
			return true;
		}

		public function canPrePopulate() {
			return true;
		}

		public function createTable() {
			return Symphony::Database()->query(
				"CREATE TABLE IF NOT EXISTS `tbl_entries_data_" . $this->get('id') . "` (
				  `id` int(11) unsigned NOT NULL auto_increment,
				  `entry_id` int(11) unsigned NOT NULL,
				  `value` double default NULL,
				  PRIMARY KEY  (`id`),
				  KEY `entry_id` (`entry_id`),
				  KEY `value` (`value`)
				) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci"
			);
		}

	/*-------------------------------------------------------------------------
		Settings:
	-------------------------------------------------------------------------*/

		public function displaySettingsPanel(XMLElement &$wrapper, $errors = null) {
			parent::displaySettingsPanel($wrapper, $errors);
			$context = Administration::instance()->Page->getContext();
			
			if($context[0] == 'edit'){
				$section_id = (int)$context[1];
				
				$section = SectionManager::fetch($section_id);
				$options = array();

				$section_fields = $section->fetchFields('number');
				if(is_array($section_fields)){

					$fields = array();
					foreach($section_fields as $f){
						if($f->get('id') != $this->get('id') && $f->canPrePopulate()) {
							$fields[] = array(
								$f->get('id'),
								is_array($this->get('related_field_id')) ? in_array($f->get('id'), $this->get('related_field_id')) : false,
								$f->get('label')
							);
						}
					}

					if(!empty($fields)) {
						$options[] = array(
							'label' => $section->get('name'),
							'options' => $fields
						);
					}
				}

				$label = Widget::Label(__('Field to markup'));
				$label->appendChild(
					Widget::Select('fields['.$this->get('sortorder').'][related_field_id]', $options)
				);

				// Add options
				if(isset($errors['related_field_id'])) {
					$wrapper->appendChild(Widget::Error($label, $errors['related_field_id']));
				}
				else $wrapper->appendChild($label);
			}
			
			$div = new XMLElement('div', NULL, array('class' => 'two columns'));
			$this->appendRequiredCheckbox($div);
			$this->appendShowColumnCheckbox($div);
			$wrapper->appendChild($div);
		}

		public function commit(){
			if(!parent::commit()) return false;

			$id = $this->get('id');

			if($id === false) return false;

			$fields = array();
			$fields['field_id'] = $id;
			if($this->get('related_field_id') != '') $fields['related_field_id'] = $this->get('related_field_id');

			if(!FieldManager::saveSettings($id, $fields)) return false;

			return true;
		}

	/*-------------------------------------------------------------------------
		Input:
	-------------------------------------------------------------------------*/

		public function displayPublishPanel(XMLElement &$wrapper, $data = null, $flagWithError = null, $fieldnamePrefix = null, $fieldnamePostfix = null, $entry_id = null){
			
			$div = new XMLElement('div');
			
			$value = $data['value'];
			
			$related_field_data = $entry_id != NULL ? Symphony::Database()->fetchVar('value', 0, sprintf(
				"SELECT `value` FROM `tbl_entries_data_%d` WHERE `entry_id` = %d LIMIT 1",
				$this->get('related_field_id'), $entry_id
			)) : NULL;
			
			$related_field_handle = Symphony::Database()->fetchVar('element_name', 0, sprintf(
				"SELECT `element_name` FROM `tbl_fields` WHERE `id` = %d LIMIT 1",
				$this->get('related_field_id')
			));
			
			$label = Widget::Label($this->get('label'));
			if($this->get('required') != 'yes') {
				$label->appendChild(new XMLElement('i', __('Optional')));
			}
			$div->appendChild($label);
			
			$div->appendChild(new XMLElement('i', __('Actual:')));
			
			$div->appendChild(
				Widget::Input(
					'fields'.$fieldnamePrefix.'['.$this->get('element_name').']'.$fieldnamePostfix,
					(strlen($value) != 0 ? $value : NULL),
					'text', array('class' => 'actual-value', 'onchange' => 'updatePercentageFromActual(this.value)')
				)
			);
			
			
			$div->appendChild(new XMLElement('i', __('Percentage:')));
			
			$div->appendChild(
				Widget::Input(
					'fields'.$fieldnamePrefix.'['.$this->get('element_name').'_percentage]'.$fieldnamePostfix,
					(($related_field_data > 0 && $value > 0) 
						? number_format((((float)$value / (float)$related_field_data) * 100.0) - 100.0, 2, '.', '') 
						: NULL),
					'text', array('class' => 'percentage', 'onchange' => 'updateMarkupFromPercentage(this.value)')
				)
			);
			
			$markup_source = new XMLElement('input');
			$markup_source->setAttribute('class', 'markup-source-field');
			$markup_source->setAttribute('value', $related_field_handle);
			$div->appendChild($markup_source);
			
			if($flagWithError != NULL) {
				$wrapper->appendChild(Widget::Error($div, $flagWithError));
			}
			else {
				$wrapper->appendChild($div);
			}
		}

		public function checkPostFieldData($data, &$message, $entry_id = null) {
			$message = NULL;

			if($this->get('required') == 'yes' && strlen($data) == 0) {
				$message = __('This is a required field.');
				return self::__MISSING_FIELDS__;
			}

			if(strlen($data) > 0 && !is_numeric($data)) {
				$message = __('Must be a number.');
				return self::__INVALID_FIELDS__;
			}

			return self::__OK__;
		}

		public function processRawFieldData($data, &$status, &$message=null, $simulate = false, $entry_id = null) {
			$status = self::__OK__;

			if (strlen(trim($data)) == 0) return array();

			$result = array(
				'value' => $data
			);

			return $result;
		}

	/*-------------------------------------------------------------------------
		Import:
	-------------------------------------------------------------------------*/

		public function getImportModes() {
			return array(
				'getValue' =>		ImportableField::STRING_VALUE,
				'getPostdata' =>	ImportableField::ARRAY_VALUE
			);
		}

		public function prepareImportValue($data, $mode, $entry_id = null) {
			$message = $status = null;
			$modes = (object)$this->getImportModes();

			if($mode === $modes->getValue) {
				return $data;
			}
			else if($mode === $modes->getPostdata) {
				return $this->processRawFieldData($data, $status, $message, true, $entry_id);
			}

			return null;
		}

	/*-------------------------------------------------------------------------
		Export:
	-------------------------------------------------------------------------*/

		/**
		 * Return a list of supported export modes for use with `prepareExportValue`.
		 *
		 * @return array
		 */
		public function getExportModes() {
			return array(
				'getUnformatted' =>	ExportableField::UNFORMATTED,
				'getPostdata' =>	ExportableField::POSTDATA
			);
		}

		/**
		 * Give the field some data and ask it to return a value using one of many
		 * possible modes.
		 *
		 * @param mixed $data
		 * @param integer $mode
		 * @param integer $entry_id
		 * @return string|null
		 */
		public function prepareExportValue($data, $mode, $entry_id = null) {
			$modes = (object)$this->getExportModes();

			// Export unformatted:
			if ($mode === $modes->getUnformatted || $mode === $modes->getPostdata) {
				return isset($data['value'])
					? $data['value']
					: null;
			}

			return null;
		}

	/*-------------------------------------------------------------------------
		Filtering:
	-------------------------------------------------------------------------*/

		public function buildDSRetrievalSQL($data, &$joins, &$where, $andOperation = false) {
			$field_id = $this->get('id');
			$expression = " `t$field_id`.`value` ";

			// X to Y support
			if(preg_match('/^(-?(?:\d+(?:\.\d+)?|\.\d+)) to (-?(?:\d+(?:\.\d+)?|\.\d+))$/i', $data[0], $match)) {

				$joins .= " LEFT JOIN `tbl_entries_data_$field_id` AS `t$field_id` ON (`e`.`id` = `t$field_id`.entry_id) ";
				$where .= " AND `t$field_id`.`value` BETWEEN {$match[1]} AND {$match[2]} ";

			}

			// Equal to or less/greater than X
			else if(preg_match('/^(equal to or )?(less|greater) than (-?(?:\d+(?:\.\d+)?|\.\d+))$/i', $data[0], $match)) {

				switch($match[2]) {
					case 'less':
						$expression .= '<';
						break;

					case 'greater':
						$expression .= '>';
						break;
				}

				if($match[1]){
					$expression .= '=';
				}

				$expression .= " {$match[3]} ";

				$joins .= " LEFT JOIN `tbl_entries_data_$field_id` AS `t$field_id` ON (`e`.`id` = `t$field_id`.entry_id) ";
				$where .= " AND $expression ";

			}

			// Look for <=/< or >=/> symbols
			else if(preg_match('/^(=?[<>]=?) (-?(?:\d+(?:\.\d+)?|\.\d+))$/i', $data[0], $match)) {

				$joins .= " LEFT JOIN `tbl_entries_data_$field_id` AS `t$field_id` ON (`e`.`id` = `t$field_id`.entry_id) ";
				$where .= sprintf(
					" AND %s %s %f",
					$expression,
					$match[1],
					$match[2]
				);

			}

			else parent::buildDSRetrievalSQL($data, $joins, $where, $andOperation);

			return true;
		}
		
		public function appendFormattedElement(XMLElement &$wrapper, $data, $encode = false, $mode = null, $entry_id = null) {
			if(!is_array($data) || empty($data) || is_null($data['value'])) return;
			
			$value = $data['value'];
			$element = new XMLElement($this->get('element_name'), $value);

			$related_field_data = $entry_id != NULL ? Symphony::Database()->fetchVar('value', 0, sprintf(
				"SELECT `value` FROM `tbl_entries_data_%d` WHERE `entry_id` = %d LIMIT 1",
				$this->get('related_field_id'), $entry_id
			)) : NULL;
			
			$related_field_handle = Symphony::Database()->fetchVar('element_name', 0, sprintf(
				"SELECT `element_name` FROM `tbl_fields` WHERE `id` = %d LIMIT 1",
				$this->get('related_field_id')
			));

			$element->setAttribute('source-price-field', $related_field_handle);
			$element->setAttribute('percentage', 
				number_format((((float)$value / (float)$related_field_data) * 100.0) - 100.0, 2, '.', '')
			);

			$wrapper->appendChild($element);
		}
		
	/*-------------------------------------------------------------------------
		Grouping:
	-------------------------------------------------------------------------*/

		public function groupRecords($records) {
			if(!is_array($records) || empty($records)) return;

			$groups = array($this->get('element_name') => array());

			foreach($records as $r) {
				$data = $r->getData($this->get('id'));

				$value = $data['value'];

				if(!isset($groups[$this->get('element_name')][$value])) {
					$groups[$this->get('element_name')][$value] = array(
						'attr' => array('value' => $value),
						'records' => array(),
						'groups' => array()
					);
				}

				$groups[$this->get('element_name')][$value]['records'][] = $r;

			}

			return $groups;
		}

	}
