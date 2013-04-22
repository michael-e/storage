<?php

    require_once TOOLKIT . '/class.datasource.php';
    require_once EXTENSIONS . '/storage/lib/class.storage.php';

    Class datasourceStorage extends DataSource {

        public $dsParamROOTELEMENT = 'storage';

        function about() {
            return array(
                'name' => 'Storage',
                'description' => 'This is a custom datasource for the Storage extension. <br /><br />For details see the README file.',
                'author' => array(
                    'name' => 'Büro für Web- und Textgestaltung',
                    'website' => 'http://hananils.de',
                    'email' => 'buero@hananils.de',
                ),
                'version' => '1.0',
                'release-date' => '2013-03-19',
            );
        }

        public function allowEditorToParse(){
            return false;
        }

        public function grab(&$param_pool = null) {
            $result = new XMLElement($this->dsParamROOTELEMENT);
            $storage = new Storage();
            $groups = array();

            // Get groups
            if(!empty($this->dsParamGROUPS)) {
                foreach($this->dsParamGROUPS as $id) {
                    $groups[$id] = $storage->get($id);
                }
            }
            else {
                $groups = $storage->get();
            }

            // Build XML
            Storage::buildXML($result, $storage->get(), true);

            // Add output parameters
            if(is_array($groups)) {
                foreach($groups as $name => $values) {
                    $param_pool['ds-' . $this->dsParamROOTELEMENT . '-' . $name] = array_keys((array)$values);
                }
            }

            return $result;
        }
    }
