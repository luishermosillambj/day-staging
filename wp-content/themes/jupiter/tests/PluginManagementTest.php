<?php

require_once __DIR__ . '/../framework/admin/control-panel/logic/plugin-management.php';

use org\bovigo\vfs\vfsStream;

class PluginManagementTest extends PHPUnit_Framework_TestCase
{
    protected $stub;
    protected $plugin_name = 'Hello Dolly';
    protected $plugin_slug = 'hello-dolly';
    public function setUp()
    {
        $csc = $this->getMockBuilder('mk_plugin_management')
            ->setConstructorArgs(array(true, false))
            ->setMethods(array('getPluginInfoFromWPRepo'))
            ->getMock();

        // Tell the `handleValue` method to return 'bla'
        //

        $map = array(
            array(
                $this->plugin_slug,
                array('download_link' => 'source', 'version' => 'version'),
                array('source' => 'http://static-cdn.artbees.net/phpunit/hello-dolly.1.6.zip', 'version' => '1.6'),
            ),
            array(
                $this->plugin_slug,
                array('download_link' => 'source'),
                array('source' => 'http://static-cdn.artbees.net/phpunit/hello-dolly.1.6.zip'),
            ),
            array(
                $this->plugin_slug,
                array('version' => 'version'),
                array('version' => '1.6'),
            ),
        );
        $csc->method('getPluginInfoFromWPRepo')
            ->will($this->returnValueMap($map));
        $this->plm = $csc;
        $this->plm->setPluginsDir(ABSPATH . '/wp-content/plugins/');

        // $this->plm = new mk_plugin_management(true, false);
    }
    public function testItCanDownloadAndActivatePlugin()
    {
        $plugin_name = 'Hello Dolly';
        if (!is_writeable(ABSPATH . 'wp-content/plugins/'))
        {
            $this->markTestSkipped('Plugin directory is not writable , this test skipped.');
        }
        $this->plm->setPluginName($plugin_name);
        $response = $this->plm->install();
        $this->assertTrue($response);
        $this->assertTrue($this->plm->getActivePlugins($this->plm->findPluginHeadFileName($plugin_name)));
        $plugin_full_path = $this->plm->getPluginsDir() . $this->plm->findPluginHeadFileName($plugin_name);
        $this->assertTrue($this->plm->deactivatePlugin($plugin_name));
        $this->assertTrue($this->plm->removePlugin($plugin_full_path, $this->plm->getPluginsDir()));
        $this->assertFalse($this->plm->getActivePlugins($this->plm->getPluginName()));
    }
    public function testItCanGetVersionOfPluginFromWPRepository()
    {
        $plugin_slug = 'hello-dolly';
        $response    = $this->plm->getPluginInfoFromWPRepo($plugin_slug, array('version' => 'version'));
        $this->assertTrue(version_compare($response['version'], 1, '>'));
    }
    public function testItCanGetDownloadURLOfPluginFromWPRepository()
    {
        $plugin_slug = 'hello-dolly';
        $response    = $this->plm->getPluginInfoFromWPRepo($plugin_slug, array('download_link' => 'source'));
        $this->assertInternalType('int', strpos($response['source'], "http://"));
        $this->assertInternalType('int', strpos($response['source'], $plugin_slug));
    }
    public function testItCanUpdatePlugins()
    {
        if (!is_writeable(ABSPATH . '/wp-content/plugins/'))
        {
            $this->markTestSkipped('Plugin directory is not writable , this test skipped.');
        }

        // Install Plugin
        $plugin_name = 'Hello Dolly';
        $this->plm->setPluginName($plugin_name);
        $response = $this->plm->install();
        $this->assertTrue($response);
        $this->assertTrue($this->plm->getActivePlugins($this->plm->getPluginHeadFileName()));

        // Update Plugin
        $response = $this->plm->updatePlugin();
        $this->assertTrue($response);

        // Remove Plugins
        $plugin_full_path = $this->plm->getPluginsDir() . $this->plm->findPluginHeadFileName($plugin_name);
        $this->assertTrue($this->plm->deactivatePlugin($plugin_name));
        $this->assertTrue($this->plm->removePlugin($plugin_full_path, $this->plm->getPluginsDir()));
        $this->assertFalse($this->plm->getActivePlugins($this->plm->getPluginName()));

    }
    public function testItCanGetListOfPluginsFromApi()
    {
        // Install Plugin
        $plugin_name = 'Hello Dolly';
        $this->plm->setPluginName($plugin_name);
        $response = $this->plm->install();
        $this->assertTrue($response);
        $this->assertTrue($this->plm->getActivePlugins($this->plm->getPluginHeadFileName()));

        // Check list of Plugins
        $response = $this->plm->getListOfAllPlugins();

        $this->assertTrue($response);
        $list_of_plugins = $this->plm->getMessage();
        foreach ($list_of_plugins['data'] as $key => $value)
        {
            if ($value['name'] == "Hello Dolly")
            {
                $this->assertTrue($value['installed']);
            }
            $this->assertArrayHasKey('installed', $value);
            $this->assertArrayHasKey('need_update', $value);
        }
        // Remove Plugins
        //
        $plugin_name      = 'Hello Dolly';
        $plugin_full_path = $this->plm->getPluginsDir() . $this->plm->findPluginHeadFileName($plugin_name);
        $this->assertTrue($this->plm->deactivatePlugin($plugin_name));
        $this->assertTrue($this->plm->removePlugin($plugin_full_path, $this->plm->getPluginsDir()));
        $this->assertFalse($this->plm->getActivePlugins($plugin_name));
    }

    public function testValidateDirectoryPermission()
    {

        vfsStream::setup("upload-notwritable", 0444);
        $path = vfsStream::url('upload-notwritable');
        $this->plm->setPluginsDir($path);
        $this->plm->setPluginName('wp-cleanup-optimizer');
        $this->assertFalse($this->plm->install());
        $exp_response = $this->message('Plugin directory is not writable.', false);
        $this->assertJson($exp_response, $this->plm->getMessage());
    }

    public function testValidatePluginNotExistOnServer()
    {

        $this->plm->setPluginName('testtest-test');
        $this->assertFalse($this->plm->install());
        $exp_response = $this->message('The plugin you are looking for is not exist.', false);
        $this->assertJson($exp_response, $this->plm->getMessage());
    }

    // /* ------  Helpers ------ */
    public function message($message, $status, $data = null)
    {
        $response = json_encode(array(
            'status'  => $status,
            'message' => $message,
            'data'    => $data,
        ));
        return $response;
    }
    public function getPerm($path)
    {
        return substr(sprintf('%o', fileperms(ABSPATH . $path)), -4);
    }
}
