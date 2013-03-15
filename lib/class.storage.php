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
            if(!is_array($this->storage)) $this->storage = (array)$this->storage;
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
         * @param array $storage
         *  The storage context
         * @return array
         *  Returns items with recalculated counts
         */
        function recalculateCount($items, $storage) {
            if(is_array($storage)) {
                foreach($items as $key => $value) {
                    $isInt = ctype_digit((string)$value);

                    if(is_array($value)) {
                        $items[$key] = $this->recalculateCount($value, $storage[$key]);
                    }
                    elseif($key == 'count' && $isInt === true) {
                        $items[$key] = intval($storage[$key]) + intval($value);
                    }
                    elseif($key == 'count-positive' && $isInt === true) {
                        $items[$key] = $this->noNegativeCounts(intval($storage[$key]) + intval($value));
                    }
                    elseif(($key == 'count' || $key == 'count-positive') && $isInt === false) {
                        $this->errors[] = "Invalid count: $items[$key] is not an integer, ignoring value.";
                    }
                }
            }
            return $items;
        }

        /**
         * No negative counts.
         *
         * @param int $count
         *  The count
         */
        private function noNegativeCounts($count) {
            if($count < 0) {
                return 0;
            }
            else {
                return $count;
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
        public static function itemsToXML($parent, $items, $count_as_attribute = false){
            if(!is_array($items)) return;

            foreach($items as $key => $value){
                $item = new XMLElement('item');
                $item->setAttribute('id', $key);

                // Nested items
                if(is_array($value)) {
                    Storage::itemsToXML($item, $value, $count_as_attribute);
                    $parent->appendChild($item);
                }

                // Count as attribute
                elseif( ($key == 'count' || $key == 'count-positive') && $count_as_attribute === true ) {
                    if(empty($value)) $value = 0;
                    $parent->setAttribute('count', $value);
                }

                // Other values
                else {
                    $item->setValue($value);
                    $parent->appendChild($item);
                }
            }
        }

    }