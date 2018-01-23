<?php

require_once(EXTENSIONS . '/storage/data-sources/datasource.storage.php');

Class datasource<!-- CLASS NAME --> extends StorageDatasource {

    public $dsParamROOTELEMENT = '%s';
    public $dsParamPARAMS = %d;
    public $dsParamGROUPS = array(
        <!-- GROUPS -->
    );

    public function __construct($env=NULL, $process_params=true){
        parent::__construct($env, $process_params);
        $this->_dependencies = array(<!-- DS DEPENDENCY LIST -->);
    }

    public function about(){
        return array(
            'name' => '<!-- NAME -->',
            'author' => array(
                'name' => '<!-- AUTHOR NAME -->',
                'website' => '<!-- AUTHOR WEBSITE -->',
                'email' => '<!-- AUTHOR EMAIL -->'),
            'version' => '<!-- VERSION -->',
            'release-date' => '<!-- RELEASE DATE -->'
        );
    }

    public function allowEditorToParse(){
        return true;
    }

}
