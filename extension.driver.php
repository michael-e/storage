<?php

    require_once EXTENSIONS . '/storage/data-sources/datasource.storage.php';

    Class extension_Storage extends Extension {

        private static $provides = array();

        public static function registerProviders() {
            self::$provides = array(
                'data-sources' => array(
                    'StorageDatasource' => StorageDatasource::getName()
                )
            );

            return true;
        }

        public static function providerOf($type = null) {
            self::registerProviders();

            if(is_null($type)) return self::$provides;

            if(!isset(self::$provides[$type])) return array();

            return self::$provides[$type];
        }

        public function getSubscribedDelegates() {
            return array(
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
                array(
                    'page' => '/frontend/',
                    'delegate' => 'EventFinalSaveFilter',
                    'callback' => 'eventFinalSaveFilter'
                )
            );
        }
        
        public function appendEventFilter($context) {
            $handle = 'storage-add';
            $selected = (in_array($handle, $context['selected']));
            $context['options'][] = Array(
                $handle, $selected, __('Add to Storage')
            );
        }

        public function eventFinalSaveFilter($context) {
            $storage = new Storage();
            $storage->set(
                array(
                    'events' => array(
                        $context['event']->ROOTELEMENT => $context['fields']
                    )
                )
            );
        }        

    }
