<?php
/**
 * Build a navigation menu from a list
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Andreas Gohr <gohr@cosmocode.de>
 */

class syntax_plugin_navi extends DokuWiki_Syntax_Plugin
{
    protected $defaultOptions = [
        'ns' => false,
        'full' => false,
        'js' => false,
    ];

    /** * @inheritDoc */
    function getType()
    {
        return 'substition';
    }

    /** * @inheritDoc */
    function getPType()
    {
        return 'block';
    }

    /** * @inheritDoc */
    function getSort()
    {
        return 155;
    }

    /** * @inheritDoc */
    function connectTo($mode)
    {
        $this->Lexer->addSpecialPattern('{{navi>[^}]+}}', $mode, 'plugin_navi');
    }

    /** * @inheritDoc */
    function handle($match, $state, $pos, Doku_Handler $handler)
    {
        $id = substr($match, 7, -2);
        $opts = '';
        if (strpos($id, '?') !== false) {
            list($id, $opts) = explode('?', $id, 2);
        }
        $options = $this->parseOptions($opts);
        $list = $this->parseNavigationControlPage(cleanID($id));

        return [wikiFN($id), $list, $options];
    }

    /**
     * @inheritDoc
     * We handle all modes (except meta) because we pass all output creation back to the parent
     */
    function render($format, Doku_Renderer $R, $data)
    {
        $fn = $data[0];
        $navItems = $data[1];
        $options = $data[2];

        if ($format == 'metadata') {
            $R->meta['relation']['naviplugin'][] = $fn;
            return true;
        }

        $R->info['cache'] = false; // no cache please

        $parentPath = $this->getOpenPath($navItems, $options);

        $R->doc .= '<div class="plugin__navi ' . ($options['js'] ? 'js' : '') . '">';
        $this->renderTree($navItems, $parentPath, $R, $options['full']);
        $R->doc .= '</div>';

        return true;
    }

    /**
     * Simple accessor to call the plugin from templates
     *
     * @param string $controlPage
     * @param array $options
     * @return string the HTML tree
     */
    public function tpl($controlPage, $options = [])
    {
        // resolve relative to the controlpage because we have no sidebar context
        global $ID;
        $oldid = $ID;
        $ID = $controlPage;

        $options = array_merge($this->defaultOptions, $options);
        $R = new \Doku_Renderer_xhtml();
        $this->render('xhtml', $R, [
            wikiFN($controlPage),
            $this->parseNavigationControlPage($controlPage),
            $options,
        ]);

        $ID = $oldid;
        return $R->doc;
    }

    /**
     * Parses the items from the control page
     *
     * @param string $controlPage ID of the control page
     * @return array list of navigational items
     */
    public function parseNavigationControlPage($controlPage)
    {
        global $ID;

        // fetch the instructions of the control page
        $instructions = p_cached_instructions(wikiFN($controlPage), false, $controlPage);
        if (!$instructions) return [];

        // prepare some vars
        $max = count($instructions);
        $pre = true;
        $lvl = 0;
        $parents = array();
        $page = '';
        $cnt = 0;

        // build a lookup table
        $list = [];
        for ($i = 0; $i < $max; $i++) {
            if ($instructions[$i][0] == 'listu_open') {
                $pre = false;
                $lvl++;
                if ($page) array_push($parents, $page);
            } elseif ($instructions[$i][0] == 'listu_close') {
                $lvl--;
                array_pop($parents);
            } elseif ($pre || $lvl == 0) {
                unset($instructions[$i]);
            } elseif ($instructions[$i][0] == 'listitem_close') {
                $cnt++;
            } elseif ($instructions[$i][0] == 'internallink') {
                $foo = true;
                $page = $instructions[$i][1][0];
                resolve_pageid(getNS($ID), $page, $foo); // resolve relative to sidebar ID
                $list[$page] = array(
                    'parents' => $parents,
                    'page' => $page,
                    'title' => $instructions[$i][1][1],
                    'lvl' => $lvl,
                );
            } elseif ($instructions[$i][0] == 'externallink') {
                $url = $instructions[$i][1][0];
                $list['_' . $page] = array(
                    'parents' => $parents,
                    'page' => $url,
                    'title' => $instructions[$i][1][1],
                    'lvl' => $lvl,
                );
            }
        }
        return $list;
    }

    /**
     * Create a "path" of items to be opened above the current page
     *
     * @param array $navItems list of navigation items
     * @param array $options Configuration options
     * @return array
     */
    public function getOpenPath($navItems, $options)
    {
        global $INFO;
        $openPath = array();
        if (isset($navItems[$INFO['id']])) {
            $openPath = (array)$navItems[$INFO['id']]['parents']; // get the "path" of the page we're on currently
            array_push($openPath, $INFO['id']);
        } elseif ($options['ns']) {
            $ns = $INFO['id'];

            // traverse up for matching namespaces
            if ($navItems) {
                do {
                    $ns = getNS($ns);
                    $try = "$ns:";
                    resolve_pageid('', $try, $foo);
                    if (isset($navItems[$try])) {
                        // got a start page
                        $openPath = (array)$navItems[$try]['parents'];
                        array_push($openPath, $try);
                        break;
                    } else {
                        // search for the first page matching the namespace
                        foreach ($navItems as $key => $junk) {
                            if (getNS($key) == $ns) {
                                $openPath = (array)$navItems[$key]['parents'];
                                array_push($openPath, $key);
                                break 2;
                            }
                        }
                    }

                } while ($ns);
            }
        }
        return $openPath;
    }

    /**
     * create a correctly nested list (or so I hope)
     *
     * @param array $navItems list of navigational items
     * @param array $parentPath path of parent items
     * @param Doku_Renderer $R should closed subitems still be rendered?
     * @param bool $fullTree
     */
    public function renderTree($navItems, $parentPath, Doku_Renderer $R, $fullTree = false)
    {
        $open = false;
        $lvl = 1;
        $R->listu_open();

        // read if item has childs and if it is open or closed
        $upper = array();
        foreach ((array)$navItems as $pid => $info) {
            $state = (array_diff($info['parents'], $parentPath)) ? 'close' : '';
            $countparents = count($info['parents']);
            if ($countparents > '0') {
                for ($i = 0; $i < $countparents; $i++) {
                    $upperlevel = $countparents - 1;
                    $upper[$info['parents'][$upperlevel]] = ($state == 'close') ? 'close' : 'open';
                }
            }
        }
        unset($pid);

        foreach ((array)$navItems as $pid => $info) {
            // only show if we are in the "path"
            if (!$fullTree && array_diff($info['parents'], $parentPath)) {
                continue;
            }

            if (!empty($upper[$pid])) {
                $menuitem = ($upper[$pid] == 'open') ? 'open' : 'close';
            } else {
                $menuitem = '';
            }

            // skip every non readable page
            if (auth_quickaclcheck(cleanID($info['page'])) < AUTH_READ) {
                continue;
            }

            if ($info['lvl'] == $lvl) {
                if ($open) {
                    $R->listitem_close();
                }
                $R->listitem_open($lvl . ' ' . $menuitem);
                $open = true;
            } elseif ($lvl > $info['lvl']) {
                for (; $lvl > $info['lvl']; --$lvl) {
                    $R->listitem_close();
                    $R->listu_close();
                }
                $R->listitem_close();
                $R->listitem_open($lvl . ' ' . $menuitem);
            } elseif ($lvl < $info['lvl']) {
                // more than one run is bad nesting!
                for (; $lvl < $info['lvl']; ++$lvl) {
                    $R->listu_open();
                    $R->listitem_open($lvl + 1 . ' ' . $menuitem);
                    $open = true;
                }
            }

            $R->listcontent_open();
            if (substr($pid, 0, 1) != '_') {
                $R->internallink(':' . $info['page'], $info['title']);
            } else {
                $R->externallink($info['page'], $info['title']);
            }

            $R->listcontent_close();
        }
        while ($lvl > 0) {
            $R->listitem_close();
            $R->listu_close();
            --$lvl;
        }
    }

    /**
     * Parse the option string into an array
     *
     * @param string $opts
     * @return array
     */
    protected function parseOptions($opts)
    {
        $options = $this->defaultOptions;

        foreach (explode('&', $opts) as $opt) {
            $options[$opt] = true;
        }

        if ($options['js']) $options['full'] = true;

        return $options;
    }
}
