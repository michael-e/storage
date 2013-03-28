<?php

    /**
     * Storage class
     */
    class Storage {

        /**
         * The storage.
         *
         * @var array
         */
        private $_storage = array();

        /**
         * The error log.
         *
         * @var array
         */
        private $_errors = array();

        /**
         * Initialise storage, retaining existing data.
         */
        public function __construct() {
            session_start();
            $this->_storage = &$_SESSION['storage'];
            if(!is_array($this->_storage)) $this->_storage = (array)$this->_storage;
        }

        /**
         * Get storage data, optionally filtered by group.
         *
         * @param mixed $group
         *  Storage namespace, optional
         * @return array
         *  Associative storage array
         */
        public function get($group = null) {

            // Return filtered storage
            if(isset($group)) {
                return $this->_storage[$group];
            }

            // Return complete storage
            else {
                return $this->_storage;
            }
        }

        /**
         * Set storage data.
         *
         * @param array $items
         *  New data
         * @param boolean $recalculate
         *  If set to true, item counts will be recalculated
         */
        public function set($items = array()) {
            $this->setStorage($this->_storage, $items, false);
        }

        /**
         * Set storage data and recalculate counts.
         *
         * @param array $items
         *  Data
         */
        public function setCount($items = array()) {
            $this->setStorage($this->_storage, $items, true);
        }

        /**
         * Drop given items from the storage.
         *
         * @param array $items
         *  The items that should be dropped
         **/
        public function drop($items = array()) {
            $this->dropFromArray($this->_storage, $items);
        }

        /**
         * Drop all items from the storage.
         */
        public function dropAll() {
            $this->_storage = array();
        }

        /**
         * Get error.
         */
        public function getErrors() {
            return $this->_errors;
        }

        /**
         * Get groups.
         *
         * @return array
         *  Return all existing groups as array
         */
        public function getGroups() {
            return array_keys($this->_storage);
        }

        /**
         * Update the existing storage array with values from the request
         * array if keys match. Also perform processing for 'count' and
         * 'count-positive' keys and unset items if the result of
         * 'count-positive' is not positive.
         *
         * @param array $storage
         *  The storage array, passed by reference
         * @param array $request
         *  The request data array
         * @param boolean $recalculate
         *  If set to true, item counts will be recalculated
         **/
        public function setStorage(&$storage = array(), $request = array(), $recalculate = false) {
            if(is_array($request)) {
                foreach($request as $key => $request_value) {

                    if(is_array($request_value)) {
                        if($key == 'count' || $key == 'count-positive') {
                            $this->_errors[] = "Invalid count: Value of '$key' is not an integer, ignoring it.";
                        }
                        // Look ahead. Drop items based on the resulting 'count-positive' value.
                        elseif(
                            isset($request_value['count-positive'])
                            && $this->isInteger($request_value['count-positive'])
                            && intval($request_value['count-positive']) + ($recalculate ? intval($storage[$key]['count-positive']) : 0) <= 0
                        ) {
                            $this->_errors[] = "Resulting count-positive of '$key' is not positive. Dropping item.";
                            unset($storage[$key]);
                        }
                        else {
                            $this->setStorage($storage[$key], $request_value, $recalculate);
                        }
                    }
                    // 'count' type keys. There is no need at this point to care for negative
                    // result values of 'count-positive' keys; the corresponding items have
                    // been dropped already.
                    elseif($key == 'count' || $key == 'count-positive') {
                        if($this->isInteger($request_value)) {
                            $storage[$key] = intval($request_value) + ($recalculate ? intval($storage[$key]) : 0);
                        }
                        else {
                            $this->_errors[] = "Invalid count: Value of '$key' is not an integer, ignoring it.";
                        }
                    }
                    else {
                        $storage[$key] = $request_value;
                    }
                }
            }
        }

        /**
         * Drop key/value pairs from an existing array based on the keys
         * of a second array.
         *
         * @param array $array1
         *  The existing (e.g. session) array, passed by reference
         * @param array $array2
         *  The second (e.g. request data) array
         **/
        function dropFromArray(&$array1 = array(), $array2 = array()) {
            if(is_array($array2)) {
                foreach($array2 as $key => $value) {
                    if(is_array($value) && array_key_exists($key, $array1)) {
                        $this->dropFromArray($array1[$key], $value);
                    }
                    else{
                        unset($array1[$key]);
                    }
                }
            }
        }

        /**
         * Build groups based on an array of items and add all nested items.
         *
         * @param XMLElement $parent
         *  The element the items should be added to
         * @param array $items
         *  The items array
         * @param boolean $count_as_attribute
         *  If set to true, counts will be added as attributes
         */
        public static function buildXML($parent, $items, $count_as_attribute = false) {
            if(!is_array($items)) return;

            // Create groups
            foreach($items as $key => $values) {
                $group = new XMLElement('group');
                $group->setAttribute('id', $key);
                $parent->appendChild($group);

                // Append items
                Storage::itemsToXML($group, $values, $count_as_attribute);
            }
        }

        /**
         * Convert an array of items to XML, setting all counts as variables.
         *
         * @param XMLElement $parent
         *  The element the items should be added to
         * @param array $items
         *  The items array
         * @param boolean $count_as_attribute
         *  If set to true, counts will be added as attributes
         */
        public static function itemsToXML($parent, $items, $count_as_attribute = false) {
            if(!is_array($items)) return;

            foreach($items as $key => $value) {
                $item = new XMLElement('item');
                $item->setAttribute('id', General::sanitize($key));

                // Nested items
                if(is_array($value)) {
                    Storage::itemsToXML($item, $value, $count_as_attribute);
                    $parent->appendChild($item);
                }

                // Count as attribute
                elseif(($key == 'count' || $key == 'count-positive') && $count_as_attribute === true) {
                    if(empty($value)) $value = 0;
                    $parent->setAttribute($key, General::sanitize($value));
                }

                // Other values
                else {
                    $item->setValue(General::sanitize($value));
                    $parent->appendChild($item);
                }
            }
        }

        /**
         * Test if a number is an integer, including string integers
         * @param mixed var
         * @return boolean
         */
        public function isInteger($var) {
            if(preg_match('/^-?\d+$/', (string)$var)) {
                return true;
            }
            else {
                return false;
            }
        }
    }