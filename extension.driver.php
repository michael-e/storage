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
            $context['options'][] = array(
                'storage-add', in_array('storage-add', $context['selected']), __('Add to Storage')
            );
            $context['options'][] = array(
                'storage-drop', in_array('storage-drop', $context['selected']), __('Drop from Storage')
            );
        }

        public function eventFinalSaveFilter($context) {
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
