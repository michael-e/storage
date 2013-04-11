<?php

    require_once EXTENSIONS . '/storage/data-sources/datasource.storage.php';

    /**
     * Storage Extension
     */
    Class extension_Storage extends Extension {

        private static $provides = array();

        /**
         * Register Data Source providers
         */
        public static function registerProviders() {
            self::$provides = array(
                'data-sources' => array(
                    'StorageDatasource' => StorageDatasource::getName()
                )
            );

            return true;
        }

        /**
         * Reveal providers
         */
        public static function providerOf($type = null) {
            self::registerProviders();
            $providers = array();

            if(is_null($type)) {
                $providers = self::$provides;
            }
            elseif(isset(self::$provides[$type])) {
                $providers = self::$provides[$type];
            }

            return $providers;
        }

        /**
         * Storage delegates
         */
        public function getSubscribedDelegates() {
            return array(

                // Append event filters
                array(
                    'page' => '/blueprints/events/edit/',
                    'delegate' => 'AppendEventFilter',
                    'callback' => 'appendEventFilter'
                ),
                array(
                    'page' => '/blueprints/events/new/',
                    'delegate' => 'AppendEventFilter',
                    'callback' => 'appendEventFilter'
                ),

                // Execute event filters
                array(
                    'page' => '/frontend/',
                    'delegate' => 'EventFinalSaveFilter',
                    'callback' => 'executeEventFilter'
                )
            );
        }

        /**
         * Append filters to add to or drop from the storage to the event editor.
         */
        public function appendEventFilter($context) {

            // Add to storage
            $context['options'][] = array(
                'storage-add', in_array('storage-add', $context['selected']), __('Storage: Add Event Data')
            );

            // Drop from storage
            $context['options'][] = array(
                'storage-drop', in_array('storage-drop', $context['selected']), __('Storage: Drop All Event Data')
            );
        }

        /**
         * If an event has passed successfully, check attached filters and
         * either add the current event data to the storage or drop all event data.
         */
        public function executeEventFilter($context) {
            $storage = new Storage();
            $filters = (array)$context['event']->eParamFILTERS;
            $name = $context['event']->ROOTELEMENT;
            $events = array('events' => null);

            // Add to storage
            if(in_array('storage-add', $filters)) {
                $events['events'][$name] = $context['fields'];
                $storage->set($events);
            }

            // Drop from storage
            elseif(in_array('storage-drop', $filters)) {
                $storage->drop($events);
            }
        }

    }
