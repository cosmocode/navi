<?php

/**
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Andreas Gohr <gohr@cosmocode.de>
 */
class action_plugin_navi extends DokuWiki_Action_Plugin
{

    /** @inheritDoc */
    function register(Doku_Event_Handler $controller)
    {
        $controller->register_hook('PARSER_CACHE_USE', 'BEFORE', $this, 'handle_cache_prepare');
    }

    /**
     * prepare the cache object for default _useCache action
     */
    public function handle_cache_prepare(Doku_Event $event)
    {
        $cache =& $event->data;

        // we're only interested in wiki pages
        if (!isset($cache->page)) return;
        if ($cache->mode != 'i') return;

        // get meta data
        $depends = p_get_metadata($cache->page, 'relation naviplugin');
        if (!is_array($depends) || !count($depends)) return; // nothing to do
        $cache->depends['files'] = !empty($cache->depends['files']) ? array_merge($cache->depends['files'],
            $depends) : $depends;
    }
}
