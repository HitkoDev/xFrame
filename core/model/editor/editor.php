<?php

class Editor {
		
	private $loaded = false;
	
	function __construct(){
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
	
	function getFields($tab){
		if(isset($this->fields[$tab])) return $this->fields[$tab];
		global $xFrame;
		if(isset($this->editor[$tab])){
			$fl = array();
			$fields = $this->editor[$tab];
			uasort($fields, "fieldComparator");
			foreach($fields as $key => $field){
				$req = '';
				if(isset($field['required']) && $field['required']) $req = 'required="required"';
				$type = '';
				if(isset($field['type'])) $type = $field['type'];
				$value = '';
				if($tab == 'main' && isset($this->draft[$key])) $value = $this->draft[$key];
				$id = (string) $this->draft['targetID'];
				$editor = $xFrame->parse($field['element'], array(
					'name' => htmlspecialchars($key),
					'key' => htmlspecialchars($tab . '_' . $key . '_' . $id),
					'required' => $req,
					'type' => htmlspecialchars($type),
					'value' => str_replace(array('[', ']', '<', '>'), array('&#91;', '&#93;', '&lt;', '&gt;'), htmlspecialchars($value)),
					'_id' => htmlspecialchars($id),
				));
				if(!isset($fl[ $field['group'] ])) $fl[ $field['group'] ] = array();
				if($editor) $fl[ $field['group'] ][$key] = $editor;
			}
			$this->fields[$tab] = $fl;
			return $fl;
		}
		return false;
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
		foreach($_POST as $key => $value){
			$k = array_map('trim', explode('_', $key));
			if(is_string($value)) $value = trim($value);
			$tab = $k[0];
			$field = $k[1];
			$id = new MongoId($k[2]);
			if($this->draft['targetID'] != $id){
				$message[$key] = array(
					'status' => 'err',
					'desc' => 'id mismatch',
				);
				$success = false;
				continue;
			}
			if($tab == 'main'){
				$this->draft[$field] = $value;
				$message[$key] = array(
					'status' => 'ok',
				);
			}
		}
		$drafts = $xFrame->getDBTable('draft');
		$drafts->update(array(
			'_id' => $this->draft['_id'],
		), $this->draft);
		return array(
			'success' => $success,
			'message' => array(
				
			),
		);
	}
	
	function save(){
		global $xFrame;
		$this->discard(false);
		$class = $this->draft['class'];
		unset($this->draft['class']);
		$id = $this->draft['targetID'];
		unset($this->draft['targetID']);
		$this->draft['_id'] = $id;
		
		$table = $xFrame->getDBTable($class);
		$table->update(array(
			'_id' => $this->draft['_id'],
		), $this->draft);
		$drafts = $xFrame->getDBTable('draft');
		$drafts->update(array(
			'_id' => $this->draft['_id'],
		), $this->draft);
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
	
}

function fieldComparator($a, $b){
	return $a["ordering"] - $b["ordering"];
}