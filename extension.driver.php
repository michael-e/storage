<?php

require_once EXTENSIONS . '/storage/data-sources/datasource.storage.php';
require_once EXTENSIONS . '/storage/lib/class.storage.php';

class extension_Storage extends Extension
{
    protected static $provides = array();

    public static function registerProviders()
    {
        self::$provides = array(
            'data-sources' => array(
                'StorageDatasource' => StorageDatasource::getName()
            )
        );

        return true;
    }

    public static function providerOf($type = null)
    {
        self::registerProviders();

        if (is_null($type)) {
            return self::$provides;
        }

        if (!isset(self::$provides[$type])) {
            return array();
        }

        return self::$provides[$type];
    }

    /**
     * Delegates and callbacks
     *
     * @return array
     */
    public function getSubscribedDelegates()
    {
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
    public function appendEventFilter($context)
    {
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
    public function eventFinalSaveFilter($context)
    {
        if (in_array('storage-drop', $context['event']->eParamFILTERS)) {
            $drop_request = $_REQUEST['storage-action']['drop'];

            if (is_array($drop_request)) {
                $s = new Storage();
                $s->drop($drop_request);
            }
        }
    }
}
