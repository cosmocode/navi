<?php

/**
 * Tests for functionality of the navi plugin
 *
 * @group plugin_navi
 * @group plugins
 *
 */
class external_plugin_navi_test extends DokuWikiTest {

    protected $pluginsEnabled = array('navi');

    public function setUp() {
        parent::setUp();
    }

    public function tearDown() {
        parent::tearDown();
    }

    public function test_controlpage_with_external_link() {
        // arrange
        $controlpage = "
  * [[en:products:a:start|BasePage]]
    * [[en:products:b:d:start|2nd-level Page with hidden child]]
      * [[en:products:c:projects|hidden 3rd-level page]]
    * [[en:products:b:archive:start|2nd-level pape]]
    * [[en:products:c:start|current 2nd-level page with visible child]]
      * [[https://www.example.org|Example Page]]
";
        saveWikiText('controlpage', $controlpage, '');
        saveWikiText('navi', '{{navi>controlpage}}', '');
        global $ID, $INFO;

        // act
        $info = array();
        $ID = 'en:products:c:start';
        $INFO['id'] = 'en:products:c:start';
        $actualHTML = p_render('xhtml', p_get_instructions('{{navi>controlpage}}'), $info);
//        print_r($actualHTML);

        $pq = phpQuery::newDocumentXHTML($actualHTML);

        $actualPages = array();
        foreach ($pq->find('a') as $page) {
            $actualPages[] = $page->getAttribute('title');
        }

        $actualLiOpen = array();
        foreach ($pq->find('li.open > div > a, li.open > div > span > a') as $page) {
            $actualLiOpen[] = $page->getAttribute('title');
        }

        $actualLiClose = array();
        foreach ($pq->find('li.close > div > a, li.close > div > span > a') as $page) {
            $actualLiClose[] = $page->getAttribute('title');
        }

        $this->assertEquals(array(
            0 => 'en:products:a:start',
            1 => 'en:products:b:d:start',
            2 => 'en:products:b:archive:start',
            3 => 'en:products:c:start',
            4 => 'https://www.example.org',
        ), $actualPages, 'the correct pages in the correct order');
        $this->assertEquals(array(
            0 => 'en:products:a:start',
            1 => 'en:products:c:start',
        ), $actualLiOpen, 'the pages which have have children and are open should have the "open" class');
        $this->assertEquals(array(
            0 => 'en:products:b:d:start',
        ), $actualLiClose, 'the pages which have have children, but are closed should have the "close" class');

    }
}
