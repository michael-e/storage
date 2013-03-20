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

        public static function allowEditorToParse() {
            return false;
        }

        public static function documentation() {
            return '<p>This is a custom event for the Storage extension. <br />For details see the README file.</p>';
        }

        public function load() {
            if(isset($_REQUEST['storage-action'])) return $this->__trigger();
        }

        protected function __trigger() {
            $action_keys = is_array($_REQUEST['storage-action']) ? array_keys($_REQUEST['storage-action']) : array();
            $action = end($action_keys);

            $drop_request = $_REQUEST['storage-action']['drop'];

            $items = (array)$_REQUEST['storage'];

            $s = new Storage();
            $errors = array();
            switch($action) {
                case 'set':
                    $s->set($items);
                    $errors = $s->getErrors();
                    break;
                case 'set-count':
                    $s->setCount($items);
                    $errors = $s->getErrors();
                    break;
                case 'drop':
                    if(is_array($drop_request)) {
                        $s->drop($drop_request);
                    }
                    else {
                        $s->drop($items);
                    }
                    $errors = $s->getErrors();
                    break;
                case 'drop-all':
                    $s->dropAll();
                    $errors = $s->getErrors();
                    break;
                default:
                    $errors[] = "'$action' is not a valid storage action.";
                    break;
            }

            $result = new XMLElement($this->ROOTELEMENT);
            $result->setAttribute('type', $action);

            if(!empty($errors)) {
                $result->setAttribute('result', 'error');
                foreach($errors as $error) {
                    $result->appendChild(new XMLElement('message', $error));
                }
            }
            else {
                if(isset($_REQUEST['redirect'])) redirect($_REQUEST['redirect']);
                $result->setAttribute('result', 'success');
            }

            $request = new XMLElement('request-values');
            $result->appendChild($request);
            Storage::buildXML($request, $items, false);

            return $result;
        }

    }
