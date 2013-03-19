<?php

    require_once EXTENSIONS . '/storage/lib/class.storage.php';

    Class Extension_Storage extends Extension {

        /**
         * About function
         *
         * @return array
         */
        public function about() {
            return array(
                'name' => 'Storage',
                'version' => '1.0',
                'release-date' => '2013-03-19',
                'author'       => array(
                    'name'    => 'Michael Eichelsdoerfer',
                    'website' => 'http://www.michael-eichelsdoerfer.de',
                    'email'   => 'info@michael-eichelsdoerfer.de'
                )
            );
        }

        /**
         * Delegates and callbacks
         *
         * @return array
         */
        public function getSubscribedDelegates(){

            return array(
                array(
                    'page'     => '/blueprints/events/new/',
                    'delegate' => 'AppendEventFilter',
                    'callback' => 'appendEventFilter'
                ),
                array(
                    'page'     => '/blueprints/events/edit/',
                    'delegate' => 'AppendEventFilter',
                    'callback' => 'appendEventFilter'
                ),
                array(
                    'page'     => '/frontend/',
                    'delegate' => 'EventFinalSaveFilter',
                    'callback' => 'eventFinalSaveFilter'
                ),
            );
        }

        /**
         * Append event filters to event pages
         *
         * @param string $context
         * @return void
         */
        public function appendEventFilter($context){
            $selected = !is_array($context['selected']) ? array() : $context['selected'];

            $context['options'][] = array(
                'storage-drop',
                in_array('storage-drop', $selected),
                __('Storage: Drop')
            );
        }

        /**
         * Drop storage item if request data is found
         *
         * @uses EventFinalSaveFilter
         * @param string $context
         * @return void
         */
        public function eventFinalSaveFilter($context){
            if(in_array('storage-drop', $context['event']->eParamFILTERS)){

                $drop_request = $_REQUEST['storage-action']['drop'];

                if(is_array($drop_request)) {
                    $s = new Storage();
                    $s->drop($drop_request);
                }
            }
        }

    }
