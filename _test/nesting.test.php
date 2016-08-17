<?php

/**
 * Tests for functionality of the navi plugin
 *
 * @group plugin_navi
 * @group plugins
 *
 */
class nesting_plugin_navi_test extends DokuWikiTest {

    protected $pluginsEnabled = array('navi');

    public function setUp() {
        parent::setUp();
    }

    public function tearDown() {
        parent::tearDown();
    }

    public function test_simple_controlpage() {
        // arrange
        $controlpage = "  * [[a]]\n  * [[b]]\n    * [[c]]";
        saveWikiText('controlpage', $controlpage, '');
        saveWikiText('navi', '{{navi>controlpage}}', '');

        // act
        $info = array();
        $actualHTML = p_render('xhtml', p_get_instructions('{{navi>controlpage}}'), $info);

        // assert
        $expectedHTML = '<ul>
<li class="level1 "><div class="li"><a href="/./doku.php?id=a" class="wikilink2" title="a" rel="nofollow">a</a></div>
</li>
<li class="level1 close"><div class="li"><a href="/./doku.php?id=b" class="wikilink2" title="b" rel="nofollow">b</a></div>
</li>
</ul>';
        $this->assertEquals($expectedHTML, $actualHTML);

    }

    public function test_controlpage_with_double_pages() {
        // arrange
        $controlpage = "
  * **[[en:products:a:start|BasePage]]**
    * **[[en:products:b:d:start|2nd-level Page with hidden child]]**
      * [[en:products:c:projects|hidden 3rd-level page]]
    * **[[en:products:b:archive:start|2nd-level pape]]**
    * [[en:products:c:start|current 2nd-level page with visible child]]
      * [[en:products:c:projects|visible 3rd-level page]]
";
        saveWikiText('controlpage', $controlpage, '');
        saveWikiText('navi', '{{navi>controlpage}}', '');
        global $ID, $INFO;

        // act
        $info = array();
        $ID = 'en:products:c:start';
        $INFO['id'] = 'en:products:c:start';
//        print_r(p_get_instructions('{{navi>controlpage}}'));
        $actualHTML = p_render('xhtml', p_get_instructions('{{navi>controlpage}}'), $info);

        // assert
        $expectedHTML = '<ul>
<li class="level1 open"><div class="li"><a href="/./doku.php?id=en:products:a:start" class="wikilink2" title="en:products:a:start" rel="nofollow">BasePage</a></div>
<ul>
<li class="level2 close"><div class="li"><a href="/./doku.php?id=en:products:b:d:start" class="wikilink2" title="en:products:b:d:start" rel="nofollow">2nd-level Page with hidden child</a></div>
</li>
<li class="level2 "><div class="li"><a href="/./doku.php?id=en:products:b:archive:start" class="wikilink2" title="en:products:b:archive:start" rel="nofollow">2nd-level pape</a></div>
</li>
<li class="level2 open"><div class="li"><span class="current"><span class="curid"><a href="/./doku.php?id=en:products:c:start" class="wikilink2" title="en:products:c:start" rel="nofollow">current 2nd-level page with visible child</a></span></span></div>
<ul>
<li class="level3 "><div class="li"><a href="/./doku.php?id=en:products:c:projects" class="wikilink2" title="en:products:c:projects" rel="nofollow">visible 3rd-level page</a></div>
</li>
</ul>
</li>
</ul>
</li>
</ul>';
        $this->assertEquals($expectedHTML, $actualHTML);

    }
}
