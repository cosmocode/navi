<?php
/**
 * Build a navigation menu from a list
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Andreas Gohr <gohr@cosmocode.de>
 */
// must be run within Dokuwiki
if(!defined('DOKU_INC')) die();

if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
require_once(DOKU_PLUGIN.'syntax.php');

class syntax_plugin_navi extends DokuWiki_Syntax_Plugin {

    /**
     * What kind of syntax are we?
     */
    function getType(){
        return 'substition';
    }

    /**
     * What about paragraphs?
     */
    function getPType(){
        return 'block';
    }

    /**
     * Where to sort in?
     */
    function getSort(){
        return 155;
    }

    /**
     * Connect pattern to lexer
     */
    function connectTo($mode) {
        $this->Lexer->addSpecialPattern('{{navi>[^}]+}}',$mode,'plugin_navi');
    }

    /**
     * Handle the match
     */
    function handle($match, $state, $pos, Doku_Handler $handler){
        global $ID;

        $id = substr($match,7,-2);
        list($id,$opt) = explode('?',$id,2);
        $id = cleanID($id);

        // fetch the instructions of the control page
        $instructions = p_cached_instructions(wikiFN($id),false,$id);

        // prepare some vars
        $max = count($instructions);
        $pre = true;
        $lvl = 0;
        $parents = array();
        $page = '';
        $cnt  = 0;

        // build a lookup table
        for($i=0; $i<$max; $i++){
            if($instructions[$i][0] == 'listu_open'){
                $pre = false;
                $lvl++;
                if($page) array_push($parents,$page);
            }elseif($instructions[$i][0] == 'listu_close'){
                $lvl--;
                array_pop($parents);
            }elseif($pre || $lvl == 0){
                unset($instructions[$i]);
            }elseif($instructions[$i][0] == 'listitem_close'){
                $cnt++;
            }elseif($instructions[$i][0] == 'internallink'){
                $foo = true;
                $page = $instructions[$i][1][0];
                resolve_pageid(getNS($ID),$page,$foo); // resolve relative to sidebar ID
                $list[$page] = array(
                                     'parents' => $parents,
                                     'page'    => $page,
                                     'title'   => $instructions[$i][1][1],
                                     'lvl'     => $lvl
                                    );
            } elseif ($instructions[$i][0] == 'externallink') {
                $url = $instructions[$i][1][0];
                $list['_'.$page] = array(
                    'parents' => $parents,
                    'page'    => $url,
                    'title'   => $instructions[$i][1][1],
                    'lvl'     => $lvl
                );
            }
        }
        return array(wikiFN($id),$list,$opt);
    }

    /**
     * Create output
     *
     * We handle all modes (except meta) because we pass all output creation back to the parent
     */
    function render($format, Doku_Renderer $R, $data) {
        $fn   = $data[0];
        $opt  = $data[2];
        $data = $data[1];

        if($format == 'metadata'){
            $R->meta['relation']['naviplugin'][] = $fn;
            return true;
        }

        $R->info['cache'] = false; // no cache please

        $path = $this->getOpenPath($data, $opt);
        $arrowLocation = $this->getConf('arrow');

        $R->doc .= '<div class="plugin__navi ' . $arrowLocation . '">';
        $this->renderTree($data, $path, $R);
        $R->doc .= '</div>';

        return true;
    }

    public function getOpenPath($data, $opt) {
        global $INFO;
        $openPath = array();
        if(isset($data[$INFO['id']])){
            $openPath = (array) $data[$INFO['id']]['parents']; // get the "path" of the page we're on currently
            array_push($openPath,$INFO['id']);
        }elseif($opt == 'ns'){
            $ns   = $INFO['id'];

            // traverse up for matching namespaces
            if($data) do {
                $ns = getNS($ns);
                $try = "$ns:";
                resolve_pageid('',$try,$foo);
                if(isset($data[$try])){
                    // got a start page
                    $openPath = (array) $data[$try]['parents'];
                    array_push($openPath,$try);
                    break;
                }else{
                    // search for the first page matching the namespace
                    foreach($data as $key => $junk){
                        if(getNS($key) == $ns){
                            $openPath = (array) $data[$key]['parents'];
                            array_push($openPath,$key);
                            break 2;
                        }
                    }
                }

            }while($ns);
        }
        return $openPath;
    }

    /**
     * @param $data
     * @param $parent
     * @param Doku_Renderer $R
     */
    public function renderTree($data, $parent, Doku_Renderer $R) {
// create a correctly nested list (or so I hope)
        $open = false;
        $lvl = 1;
        $R->listu_open();

        // read if item has childs and if it is open or closed
        $upper = array();
        foreach ((array)$data as $pid => $info) {
            $state = (array_diff($info['parents'], $parent)) ? 'close' : '';
            $countparents = count($info['parents']);
            if ($countparents > '0') {
                for ($i = 0; $i < $countparents; $i++) {
                    $upperlevel = $countparents - 1;
                    $upper[$info['parents'][$upperlevel]] = ($state == 'close') ? 'close' : 'open';
                }
            }
        }
        unset($pid);

        foreach ((array)$data as $pid => $info) {
            // only show if we are in the "path"
            if (array_diff($info['parents'], $parent)) {
                continue;
            }
            if ($upper[$pid]) {
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
                for ($lvl; $lvl > $info['lvl']; --$lvl) {
                    $R->listitem_close();
                    $R->listu_close();
                }
                $R->listitem_close();
                $R->listitem_open($lvl . ' ' . $menuitem);
            } elseif ($lvl < $info['lvl']) {
                // more than one run is bad nesting!
                for ($lvl; $lvl < $info['lvl']; ++$lvl) {
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
}
