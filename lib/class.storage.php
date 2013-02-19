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
	 * @var string
	 */
	private $error = '';
	
	/**
	 * This variable determines, if the storage counts can become negative or not.
	 * If negative counts are not allowed, those values will be set to 0.
	 *
	 * @var boolean
	 */
	private $allowNegativeCounts = false;
	
	/**
	 * Initialise storage, retaining existing data.
     */
	public function __construct($allowNegativeCounts = false) {
		$this->allowNegativeCounts = $allowNegativeCounts;
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
	 * @param boolean $update
	 *  If the flag is set to true, counts will be recalculated
	 */
	public function set($items = array()) {
		$storage = array_replace_recursive($this->storage, (array)$items);
		
		// Merge complete
		if($storage !== null) {
		
			// Zero negative counts
			if($this->allowNegativeCounts === false) {
				array_walk_recursive($storage, 'Storage::zeroNegativeCounts');
			}
			
			// Update storage
			$this->storage = $storage;
		}
		
		// Log error
		else {
			$this->error = 'Storage could not be updated.';
		}
	}
	
	/**
	 * Update storage data and recalculate counts.
	 *
	 * @param array $items
	 *  Updated data
	 */	
	public function update($items = array()) {
		$items = $this->recalculateCount($items, $this->storage);
		$this->set($items);
	}
		
	/**
	 * Delete given keys from the storage
	 *
	 * @param array $items
	 *  The items that should be deleted.
	 */
	public function delete($items = array()) {
	
		// Delete specified keys
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
	public function getError() {
		return $this->error;
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
	 * @param array $existing
	 *  The array existing amounts
	 * @param array $additional
	 *  The array with additional amounts
	 * @return array $additional
	 *  Return the second array with summed counts 
	 */
	function recalculateCount($existing, $additional){
		if(is_array($additional)){
			foreach($additional as $key => $value) {
				if(is_array($value)) {
					$additional[$key] = $this->recalculateCount($value, $existing[$key]);
				}
				elseif($key == 'count') {
					$additional[$key] = intval($existing[$key]) + intval($value);
				}
			}
		}
		return $additional;
	}	 
	
	/**
	 * Zero negative counts.
	 *
	 * @param mixed $item
	 *  The value, passed by reference
	 * @param mixed $key
	 *  The key
	 */
	private static function zeroNegativeCounts(&$item, $key) {
		if($key == 'count' && intval($item) < 0) {
			$item = 0;
		}
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
		foreach($items as $key => $value){
			$item = new XMLElement('item');
			$item->setAttribute('id', $key);
			
			// Nested items
			if(is_array($value)) {
				Storage::itemsToXML($item, $value, $algebraic);
				$parent->appendChild($item);
			}
			
			// Count
			elseif($key == 'count') {
				if($algebraic === true) {
					$count = ($value > 0) ? '+' . $value : $value;
				}
				$parent->setAttribute('count', $count);
			}
			
			// Final value
			else {
				$item->setValue($value);
				$parent->appendChild($item);
			}
		}
	}

}