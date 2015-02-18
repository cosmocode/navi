<?php
/**
 * General tests for the navi plugin
 *
 * @group plugin_navi
 * @group plugins
 */
class general_plugin_navi_test extends DokuWikiTest {

    /**
     * Simple test to make sure the plugin.info.txt is in correct format
     */
    public function test_plugininfo() {
        $file = __DIR__.'/../plugin.info.txt';
        $this->assertFileExists($file);

        $info = confToHash($file);

        $this->assertArrayHasKey('base', $info);
        $this->assertArrayHasKey('author', $info);
        $this->assertArrayHasKey('email', $info);
        $this->assertArrayHasKey('date', $info);
        $this->assertArrayHasKey('name', $info);
        $this->assertArrayHasKey('desc', $info);
        $this->assertArrayHasKey('url', $info);

        $this->assertEquals('navi', $info['base']);
        $this->assertRegExp('/^https?:\/\//', $info['url']);
        $this->assertTrue(mail_isvalid($info['email']));
        $this->assertRegExp('/^\d\d\d\d-\d\d-\d\d$/', $info['date']);
        $this->assertTrue(false !== strtotime($info['date']));
    }

    /**
     * Test to ensure that every conf['...'] entry in conf/default.php has a corresponding meta['...'] entry in
     * conf/metadata.php.
     */
    public function test_plugin_conf() {
        $conf_file = __DIR__.'/../conf/default.php';
        if (file_exists($conf_file)){
            include($conf_file);
        }
        $meta_file = __DIR__.'/../conf/metadata.php';
        if (file_exists($meta_file)) {
            include($meta_file);
        }

        $this->assertEquals(gettype($conf), gettype($meta),'Both ' . DOKU_PLUGIN . 'navi/conf/default.php and ' . DOKU_PLUGIN . 'navi/conf/metadata.php have to exist and contain the same keys.');

        if (gettype($conf) != 'NULL' && gettype($meta) != 'NULL') {
            foreach($conf as $key => $value) {
                $this->assertArrayHasKey($key, $meta, 'Key $meta[\'' . $key . '\'] missing in ' . DOKU_PLUGIN . 'navi/conf/metadata.php');
            }

            foreach($meta as $key => $value) {
                $this->assertArrayHasKey($key, $conf, 'Key $conf[\'' . $key . '\'] missing in ' . DOKU_PLUGIN . 'navi/conf/default.php');
            }
        }

    }



    /*Test that the levels have the right classes
    public function render_test() {
        $data = array(
            0 => '/home/michael/public_html/dokuwiki/data/pages/plugins/navi.txt',
            1 => array(
                'lvl1' => array(
                    'parents' => array(),
                    'page' => 'lvl1:start',
                    'title' => '',
                    'lvl' => 1,
                ),
                'lvl2' => array(
                    'parents' => Array(
                        0 => 'lvl1',
                    ),
                    'page' => 'lvl2:start',
                    'title' => '',
                    'lvl' => 2,
                ),
                'lvl3' => array(
                    'parents' => Array(
                        0 => 'lvl1',
                        1 => 'lvl2',
                    ),
                    'page' => 'lvl3:start',
                    'title' => '',
                    'lvl' => 3,
                ),
                'lvl4' => array(
                    'parents' => Array(
                        0 => 'lvl1',
                        1 => 'lvl2',
                        2 => 'lvl3',
                    ),
                    'page' => 'lvl4:start',
                    'title' => '',
                    'lvl' => 4,
                ),

            ),
            2 => '',
        );
        render('xhtml',$data);
    }*/
}
