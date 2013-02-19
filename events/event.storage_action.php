<?php

	require_once(TOOLKIT . '/class.event.php');
	require_once(EXTENSIONS . '/storage/lib/class.storage.php');

	Class eventstorage_action extends Event {

		public $ROOTELEMENT = 'storage-action';

		public static function about() {
			return array(
				'name' => 'Storage Action',
				'author' => array(
					'name' => 'Büro für Web- und Textgestaltung',
					'website' => 'http://hananils.de',
					'email' => 'buero@hananils.de'),
				'trigger-condition' => 'storage-action'
			);
		}

		public static function getSource() {
			return 'Storage';
		}

		public static function allowEditorToParse() {
			return false;
		}

		public static function documentation() {
			if(array_key_exists('markdown', TextformatterManager::listAll())) {
				$markdown = TextformatterManager::create('markdown');
				$readme = file_get_contents(EXTENSIONS . '/storage/readme.md');
				$readme = $markdown->run($readme);
				return preg_replace('/<h[1-2]>(.*?)<\/h[1-2]>/', '<h3>$1</h3>', $readme);
			} 
		}

		public function load() {
			if(isset($_REQUEST['storage-action'])) return $this->__trigger();
		}
		
		protected function __trigger() {
			$action = key($_REQUEST['storage-action']);
			$items = (array)$_REQUEST['storage']; 
			array_walk_recursive($items, 'General::sanitize');
			
			$storage = new Storage();
			
			// Trigger action
			switch($action) {
			    case 'set':
			    	$storage->set($items);
			        break;
			    case 'set-count':
			    	$storage->setCount($items);
			        break;
			    case 'drop':
			    	$storage->drop($items);
			        break;
			}
			
			// Execute event
			return $this->execute($action, $items, $storage->getError());
		}
		
		public function execute($action, $items, $error) {
			$result = new XMLElement($this->ROOTELEMENT);
			$result->setAttribute('type', $action);
			
			// Error
			if(!empty($error)) {
				$result->setAttribute('result', 'error');
				$result->appendChild(new XMLElement('message', $error));
			}
			
			// Success
			else {
				$result->setAttribute('result', 'success');
				$request = new XMLElement('request-values');
				$result->appendChild($request);
				Storage::buildXML($request, $items, true);
			}
			
			// Return result
			return $result;
		}
		
	}
