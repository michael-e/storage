<?php

    /**
     * Storage class
     *
     * @author Michael Eichelsdoerfer
     */
    class Storage {

        /**
         * This will act as a key.
         *
         * @var string
         */
        private $_index;

        /**
         * The error log.
         *
         * @var array
         */
        private $_errors = array();

        /**
         * Initialise storage
         */
        public function __construct($index = 'storage') {
            $this->_index = $index;
            if (session_id() == "") session_start();
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
                return $_SESSION[$this->_index][$group];
            }

            // Return complete storage
            else {
                return $_SESSION[$this->_index];
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
            $this->setStorage($_SESSION[$this->_index], $items, false);
        }

        /**
         * Set storage data and recalculate counts.
         *
         * @param array $items
         *  Data
         */
        public function setCount($items = array()) {
            $this->setStorage($_SESSION[$this->_index], $items, true);
        }

        /**
         * Drop given items from the storage. If the storage array
         * becomes empty, unset it.
         *
         * @param array $items
         *  The items that should be dropped
         **/
        public function drop($items = array()) {
            $this->dropFromArray($_SESSION[$this->_index], $items);
            if (empty($_SESSION[$this->_index])) {
                unset($_SESSION[$this->_index]);
            }
        }

        /**
         * Drop all items from the storage.
         */
        public function dropAll() {
            unset($_SESSION[$this->_index]);
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
            if(is_array($_SESSION[$this->_index])) {
                return array_keys($_SESSION[$this->_index]);
            }
            else {
                return array();
            }
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
                            // The request value is an array, so drop the storage value if it's not.
                            if(!is_array($storage[$key])) {
                                $storage[$key] = NULL;
                            }
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