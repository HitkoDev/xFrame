<?php

class Editor {
		
	private $loaded = false;
	
	function __construct(){
		$this->propertyTabs = array('property', 'context', 'category');
		$this->init();
	}
	
	private function init(){
		global $xFrame;
		
		$id = $xFrame->getAPIParamter('id');
		if($id){
			$id = $id[0];
			$drafts = $xFrame->getDBTable('draft');
			$draft = $drafts->find(array(
				'targetID' => new MongoId($id),
			));
			if($draft->hasNext()){
				$this->draft = $draft->getNext();
				$this->loaded = true;
			} else {
				$class = $xFrame->getAPIParamter('class');
				if($class){
					$class = $class[0];
					$db = $xFrame->getDBTable($class);
					$target = $db->find(array(
						'_id' => new MongoId($id)
					));
					if($target->hasNext()){
						$target = $target->getNext();
						$target['targetID'] = $target['_id'];
						unset($target['_id']);
						$target['class'] = $class;
						$this->draft = $target;
						$drafts->insert($this->draft);
						$this->loaded = true;
					}
				}
			}
		} else {
			$this->newElement();
		}
		if($this->loaded){
			$this->loaded = false;
			$class = $this->draft['class'];
			$type = $this->draft['type'];
			$db = $xFrame->getDBTable('classType');
			$classDef = $db->find(array(
				'class' => $class,
				'type' => $type,
			));
			if($classDef->hasNext()){
				$this->editor = $classDef->getNext();
				$this->loaded = true;
			}
		}
		$this->fields = array();
	}
	
	function isLoaded(){
		return $this->loaded;
	}
	
	function getTabs(){
		return $this->editor['tabs'];
	}
	
	function getValue($name, $tab = 'main', $set = ''){
		if($tab == 'main'){
			if(isset($this->draft[$name])) return $this->draft[$name];
		} elseif(in_array($tab, $this->propertyTabs)){
			if(isset($this->draft[$tab][$set][$name])) return $this->draft[$tab][$set][$name];
		} else {
			if(isset($this->draft[$tab][$name])) return $this->draft[$tab][$name];
		}
		return false;
	}
	
	function getFields($tab, $set = ''){
		if(isset($this->fields[$tab])) return $this->fields[$tab];
		global $xFrame;
		if(isset($this->editor[$tab]) || in_array($tab, $this->propertyTabs)){
			$fl = array();
			$fields = $this->editor[$tab];
			if(in_array($tab, $this->propertyTabs)){
				if(!isset($this->draft['property_def'])) $this->draft['property_def'] = array();
				$fields = $this->draft['property_def'];
			}
			uasort($fields, "fieldComparator");
			foreach($fields as $key => $field){
				$req = '';
				if(isset($field['required']) && $field['required']) $req = 'required="required"';
				$type = '';
				if(isset($field['type'])) $type = $field['type'];
				$value = '';
				if($tab == 'main' && isset($this->draft[$key])) $value = $this->draft[$key];
				if(in_array($tab, $this->propertyTabs) && isset($this->draft[$tab][$set]['properties'][$key])) $value = $this->draft[$tab][$set]['properties'][$key];
				$id = (string) $this->draft['targetID'];
				$editor = $xFrame->parse($field['element'], array(
					'name' => htmlspecialchars($key),
					'key' => htmlspecialchars($tab . '_' . $key . '_' . $id . '_' . $set),
					'required' => $req,
					'type' => htmlspecialchars($type),
					'value' => str_replace(array('[', ']', '<', '>'), array('&#91;', '&#93;', '&lt;', '&gt;'), htmlspecialchars($value)),
					'_id' => htmlspecialchars($id),
					'class' => htmlspecialchars($this->draft['class']),
					'classType' => htmlspecialchars($this->draft['type']),
				));
				if(!isset($fl[ $field['group'] ])) $fl[ $field['group'] ] = array();
				if($editor) $fl[ $field['group'] ][$key] = $editor;
			}
			$this->fields[$tab] = $fl;
			return $fl;
		}
		return false;
	}
	
	function listPropertySets($tab = 'property'){
		$sets = array();
		foreach($this->draft[$tab] as $key => $val){
			$sets[] = $key;
		}
		return $sets;
	}
	
	function newSet(){
		if(isset($_POST['property-set-name'])){
			$set = $_POST['property-set-name'];
			if(!isset($this->draft['properties'][$set])){
				$this->draft['properties'][$set] = array();
				$this->saveDraft();
				return array(
					'success' => true,
					'message' => 'ok',
				);
			}
			return array(
				'success' => false,
				'message' => 'Set already exists',
			);
		}
		return array(
			'success' => false,
			'message' => 'Empty name',
		);
	}
	
	function addField(){
		global $xFrame;
		$tab = $xFrame->getAPIParamter('tab');
		if(!$tab) return array(
			'success' => false,
			'message' => 'Specify tab',
		);
		$tab = $tab[0];
		$key = isset($_POST['field-name']) ? trim($_POST['field-name']) : '';
		if(!$key) return array(
			'success' => false,
			'message' => 'Missing key',
		);
		$type = isset($_POST['field-type']) ? trim($_POST['field-type']) : '';
		if(!$type) return array(
			'success' => false,
			'message' => 'Missing type',
		);
		$element = isset($_POST['field-element']) ? trim($_POST['field-element']) : '';
		if(!$type) return array(
			'success' => false,
			'message' => 'Missing element',
		);
		$default = isset($_POST['field-default']) ? trim($_POST['field-default']) : '';
		$required = (isset($_POST['field-required']) && trim($_POST['field-required'])) ? true : false;
		$group = (isset($_POST['field-group']) && trim($_POST['field-group']) > 0) ? trim($_POST['field-group']) : 1;
		$order = (isset($_POST['field-order']) && trim($_POST['field-order']) > 0) ? trim($_POST['field-order']) : 0;
		
		$field = array(
			'default' => $default,
			'element' => $element,
			'required' => $required,
			'type' => $type,
			'ordering' => $order,
			'group' => $group,
		);
		
		if($tab == 'property' && !isset($this->draft['property_def'][$key])){
			$this->draft['property_def'][$key] = $field;
			$this->saveDraft();
			return array(
				'success' => true,
				'message' => 'ok',
			);
		}
		if(!isset($this->editor[$tab][$key])){
			$this->editor[$tab][$key] = $field;
			$this->saveEditor();
			return array(
				'success' => true,
				'message' => 'ok',
			);
		}
		return array(
			'success' => false,
			'message' => 'Field exists',
		);
	}
	
	function newElement(){
		global $xFrame;
		
		$class = $xFrame->getAPIParamter('class');
		$type = $xFrame->getAPIParamter('type');
		if($class && $type){
			$class = $class[0];
			$type = $type[0];
			$db = $xFrame->getDBTable('classType');
			$classDef = $db->find(array(
				'class' => $class,
				'type' => $type,
			));
			if($classDef->hasNext()){
				$this->editor = $classDef->getNext();
			}
			$id = new MongoId();
			
			$this->draft = $this->editor['newElement'];
			$this->draft['targetID'] = $id;
			$this->draft['new'] = true;
			$drafts = $xFrame->getDBTable('draft');
			$drafts->insert($this->draft);
			$this->loaded = true;
		}
	}
	
	function setField($tab, $field, $value, $set = ''){
		if($tab == 'main'){
			if(!$value) $value = $this->editor[$tab][$field]['default'];
			if($this->editor[$tab][$field]['required'] && !$value){
				return false;
			}
			if($this->editor[$tab][$field]['type'] == 'password') $value = password_hash($value, PASSWORD_DEFAULT);
			$this->draft[$field] = $value;
			return true;
		} elseif(in_array($tab, $this->propertyTabs)){
			if(!$value) $value = $this->draft['property_def'][$field]['default'];
			if($this->draft['property_def'][$field]['required'] && !$value){
				return false;
			}
			if($this->draft['property_def'][$field]['type'] == 'password') $value = password_hash($value, PASSWORD_DEFAULT);
			if(!isset($this->draft[$tab][$set])) $this->draft[$tab][$set] = array('properties' => array());
			$this->draft[$tab][$set]['properties'][$field] = $value;
		} else {
			if(!$value) $value = $this->editor[$tab][$field]['default'];
			if($this->editor[$tab][$field]['required'] && !$value){
				return false;
			}
			if($this->editor[$tab][$field]['type'] == 'password') $value = password_hash($value, PASSWORD_DEFAULT);
			$this->draft[$tab][$field] = $value;
			return true;
		}
		return true;
	}
	
	function saveDraft(){
		global $xFrame;
		
		$drafts = $xFrame->getDBTable('draft');
		$drafts->update(array(
			'_id' => $this->draft['_id'],
		), $this->draft);
	}
	
	function saveEditor(){
		global $xFrame;
		
		$defs = $xFrame->getDBTable('classType');
		$defs->update(array(
			'_id' => $this->editor['_id'],
		), $this->editor);
	}
	
	function getID(){
		return (string) $this->draft['targetID'];
	}
	
	function getClass(){
		return (string) $this->draft['class'];
	}
	
	function updateFields(){
		global $xFrame;
		
		$success = true;
		$message = array();
		$return = array();
		foreach($_POST as $key => $value){
			$k = array_map('trim', explode('_', $key));
			if(is_string($value)) $value = trim($value);
			$tab = $k[0];
			$field = $k[1];
			$id = new MongoId($k[2]);
			$set = $k[3];
			if($this->draft['targetID'] != $id){
				$message[$key] = array(
					'status' => 'err',
					'desc' => 'id mismatch',
				);
				$success = false;
				continue;
			}
			/*$resp = $this->setField($tab, $field, $value);
			$set = $xFrame->getAPIParamter('set');
			if($set) $set = $set[0];*/
			if($this->setField($tab, $field, $value, $set)){
				$message[$key] = array(
					'status' => 'ok',
				);
				if($tab == 'main' && $field == 'type'){
					$return = array_merge($return, $xFrame->parse('function.Editor'));
				}
			} else {
				$message[$key] = array(
					'status' => 'err',
					'desc' => 'missing',
				);
				$success = false;
				continue;
			}
		}
		$this->saveDraft();
		$return['success'] = $success;
		$return['message'] = $message;
		return $return;
	}
	
	function save(){
		global $xFrame;
		$this->discard(false);
		$class = $this->draft['class'];
		unset($this->draft['class']);
		$id = $this->draft['targetID'];
		unset($this->draft['targetID']);
		$new = $this->draft['new'];
		unset($this->draft['new']);
		$this->draft['_id'] = $id;
		
		$table = $xFrame->getDBTable($class);
		if($new){
			$table->insert($this->draft);
		} else {
			$table->update(array(
				'_id' => $this->draft['_id'],
			), $this->draft);
		}
		$drafts = $xFrame->getDBTable('draft');
		$drafts->update(array(
			'_id' => $this->draft['_id'],
		), $this->draft);
		$this->init();
	}
	
	function discard($init = true){
		global $xFrame;
		$drafts = $xFrame->getDBTable('draft');
		$drafts->remove(array(
			'_id' => $this->draft['_id'],
		));
		if($init){
			$this->init();
			return $xFrame->parse('function.Editor');
		}
	}
	
	function delete(){
		global $xFrame;
		$this->discard(false);
		$class = $this->draft['class'];
		unset($this->draft['class']);
		$id = $this->draft['targetID'];
		unset($this->draft['targetID']);
		$new = $this->draft['new'];
		unset($this->draft['new']);
		
		if($new){
			
		} else {
			$table = $xFrame->getDBTable($class);
			$table->remove(array(
				'_id' => $id,
			));
		}
	}
	
}

function fieldComparator($a, $b){
	return $a["ordering"] - $b["ordering"];
}