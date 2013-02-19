<?
/**
 * Storage class
 */
class Storage {
	
	/**
	 * The storage.
	 *
	 * @var array
	 */
	private $storage = array();
	
	/**
	 * The error log.
	 *
	 * @var array
	 */
	private $errors = array();
	
	/**
	 * Initialise storage, retaining existing data.
     */
	public function __construct() {
		session_start();
		$this->storage = &$_SESSION['storage'];
	}
	
	/**
	 * Get storage data, optionally filtered by group.
	 *
	 * @param mixed $namespace
	 *  Storage namespace, optional
	 * @return array
	 *  Associative storage array 
	 */
	public function get($group = null) {
	
		// Return filtered storage
		if(isset($group)) {
			return $this->storage[$group];
		}
		
		// Return complete storage
		else {
			return $this->storage;
		}
	}
	
	/**
	 * Set storage data.
	 *
	 * @param array $items
	 *  New data
	 */
	public function set($items = array()) {
		$storage = array_replace_recursive($this->storage, (array)$items);
		
		// Set storage
		if($storage !== null) {
			$this->storage = $storage;
		}
		
		// Log error
		else {
			$this->errors[] = 'Storage could not be updated.';
		}
	}
	
	/**
	 * Set storage data and recalculate counts.
	 *
	 * @param array $items
	 *  Updated data
	 */	
	public function setCount($items = array()) {
		$items = $this->recalculateCount($items, $this->storage);
		$this->set($items);
	}
		
	/**
	 * Drop given keys from the storage
	 *
	 * @param array $items
	 *  The items that should be dropped.
	 */
	public function drop($items = array()) {
	
		// Drop specified keys
		if(isset($items)) {
			$this->storage = array_diff_key($this->storage, (array)$items);
		}
		
		// Empty storage
		else {
			$this->storage = array();
		}
	}
	
	/**
	 * Get error.
	 */	
	public function getErrors() {
		return $this->errors;
	}
	
	/**
	 * Get groups.
	 *
	 * @return array
	 *  Return all existing groups as array
	 */
	public function getGroups() {
		return array_keys($this->storage);
	}
	
	/**
	 * Recalculate storage counts
	 *
	 * @param array $items
	 *  The items array
	 * @param array $additional
	 *  The array with additional amounts
	 * @return array $additional
	 *  Return the second array with summed counts 
	 */
	function recalculateCount($items, $additional){
		if(is_array($additional)){
			foreach($additional as $key => $value) {
				$isInt = ctype_digit((string)$items[$key]);
			
				if(is_array($value)) {
					$additional[$key] = $this->recalculateCount($value, $items[$key]);
				}
				elseif($key == 'count' && $isInt === true) {
					$additional[$key] = intval($items[$key]) + intval($value);
				}
				elseif($key == 'count-positive' && $isInt === true) {
					$additional[$key] = $this->zeroNegativeCounts(intval($items[$key]) + intval($value));
				}
				elseif(($key == 'count' || $key == 'count-positive') && $isInt === false) {
					$this->errors[] = "Invalid count: $items[$key] is not an integer, ignoring value.";
				}
			}
		}
		return $additional;
	}
	
	/**
	 * Zero negative counts.
	 *
	 * @param int $count
	 *  The count
	 */
	private function zeroNegativeCounts($count) {
		if($count < 0) return 0;
	}

	/**
	 * Build groups based on an array of items and add all nested items.
	 *
	 * @param XMLElement $parent
	 *  The element the items should be added to
	 * @param array $items
	 *  The items array
	 * @param boolean $algebraic
	 *  If set to true, all counts will be returned with algebraic sign
	 */
	public static function buildXML($parent, $items, $algebraic = false) {
		if(!is_array($items)) return;
	
		// Create groups
		foreach($items as $key => $values) {
			$group = new XMLElement('group');
			$group->setAttribute('id', $key);
			$parent->appendChild($group);
			
			// Append items
			Storage::itemsToXML($group, $values, $algebraic);
		}
	} 

	/**
	 * Convert an array of items to XML, setting all counts as variables.
	 *
	 * @param XMLElement $parent
	 *  The element the items should be added to
	 * @param array $items
	 *  The items array
	 * @param boolean $algebraic
	 *  If set to true, all counts will be returned with algebraic sign
	 */
	public static function itemsToXML($parent, $items, $algebraic = false){
		if(!is_array($items)) return;
		
		foreach($items as $key => $value){
			$item = new XMLElement('item');
			$item->setAttribute('id', $key);
			
			// Nested items
			if(is_array($value)) {
				Storage::itemsToXML($item, $value, $algebraic);
				$parent->appendChild($item);
			}
			
			// Count
			elseif($key == 'count' || $key == 'count-positive') {
				$count = 'count';
				
				if($algebraic === true) {
					$count = 'difference';
					if($value > 0) $value = '+' . $value;
				}
				
				if(empty($value)) $value = 0;
				
				$parent->setAttribute($count, $value);
			}
			
			// Final value
			else {
				$item->setValue($value);
				$parent->appendChild($item);
			}
		}
	}

}