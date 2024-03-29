<?php

namespace dokuwiki\plugin\navi\test;

use DokuWikiTest;
use phpQuery;


/**
 * Tests for functionality of the navi plugin
 *
 * @group plugin_navi
 * @group plugins
 *
 */
class BasicListTest extends DokuWikiTest
{

    protected $pluginsEnabled = ['navi'];

    public function test_controlpage_simple()
    {
        // arrange
        $controlpage = "  * [[a]]\n  * [[b]]\n    * [[c]]";
        saveWikiText('controlpage', $controlpage, '');
        saveWikiText('navi', '{{navi>controlpage}}', '');

        // act
        global $INFO;
        $INFO['id'] = 'egal';

        $info = [];
        $actualHTML = p_render('xhtml', p_get_instructions('{{navi>controlpage}}'), $info);

        // assert
        $expectedHTML = '<div class="plugin__navi "><ul>
<li class="level1 "><div class="li"><a href="/./doku.php?id=a" class="wikilink2" title="a" rel="nofollow" data-wiki-id="a">a</a></div>
</li>
<li class="level1 close"><div class="li"><a href="/./doku.php?id=b" class="wikilink2" title="b" rel="nofollow" data-wiki-id="b">b</a></div>
</li>
</ul>
</div>';
        $this->assertEquals($expectedHTML, $actualHTML);

    }

    public function test_controlpage_complex()
    {
        // arrange
        $controlpage = "
  * [[en:products:a:start|BasePage]]
    * [[en:products:b:d:start|2nd-level Page with hidden child]]
      * [[en:products:c:projects|hidden 3rd-level page]]
    * [[en:products:b:archive:start|2nd-level pape]]
    * [[en:products:c:start|current 2nd-level page with visible child]]
      * [[en:products:d:start|visible 3rd-level page]]
";
        saveWikiText('controlpage', $controlpage, '');
        saveWikiText('navi', '{{navi>controlpage}}', '');
        global $ID, $INFO;

        // act
        $info = [];
        $ID = 'en:products:c:start';
        $INFO['id'] = 'en:products:c:start';
        $actualHTML = p_render('xhtml', p_get_instructions('{{navi>controlpage}}'), $info);

        if(class_exists('DOMWrap\Document')) {
            $pq = (new \DOMWrap\Document())->html($actualHTML);
        } else {
            // deprecated
            $pq = \phpQuery::newDocumentHTML($actualHTML);
        }

        $actualPages = [];
        foreach ($pq->find('a') as $page) {
            $actualPages[] = $page->getAttribute('title');
        }

        $actualLiOpen = [];
        foreach ($pq->find('li.open > div > a, li.open > div > span > a') as $page) {
            $actualLiOpen[] = $page->getAttribute('title');
        }

        $actualLiClose = [];
        foreach ($pq->find('li.close > div > a, li.close > div > span > a') as $page) {
            $actualLiClose[] = $page->getAttribute('title');
        }

        $this->assertEquals([
            0 => 'en:products:a:start',
            1 => 'en:products:b:d:start',
            2 => 'en:products:b:archive:start',
            3 => 'en:products:c:start',
            4 => 'en:products:d:start',
        ], $actualPages, 'the correct pages in the correct order');
        $this->assertEquals([
            0 => 'en:products:a:start',
            1 => 'en:products:c:start',
        ], $actualLiOpen, 'the pages which have have children and are open should have the "open" class');
        $this->assertEquals([
            0 => 'en:products:b:d:start',
        ], $actualLiClose, 'the pages which have have children, but are closed should have the "close" class');

    }
}
