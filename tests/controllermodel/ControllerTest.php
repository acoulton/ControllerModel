<?php defined('SYSPATH') OR die('Kohana bootstrap needs to be included before tests run');
/**
 * Tests for Controller_Model
 *
 * @group controllermodel
 *
 * @package    Controller_Model
 * @category   Tests
 * @author     Andrew Coulton <andrewnfcoulton@gmail.com>
 * @copyright  (c) 2008-2011 Andrew Coulton
 * @license    http://kohanaframework.org/license
 */
class ControllerModel_ControllerTest extends Kohana_Unittest_TestCase
{
    protected static $_old_base_url = null;
    protected static $_old_index_file = null;

    /**
     * Set up the environment including route and base URL / index.php settings
     */
    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();

        // Place kohana settings in expected state
        self::$_old_base_url = Kohana::$base_url;
        Kohana::$base_url = '/cmftest/';
        self::$_old_index_file = Kohana::$index_file;
        Kohana::$index_file = 'index.php';

        // Set routes for testing
        Route::set('cm-foobar-1', 'cmf(/<directory>)/c/(<controller>(/<id>(/<action>(/<token>))))'
                , array('directory'=>'.+?'))
                ->defaults(array('action'=>'view'));

        Route::set('cm-foobar-2', 'cmf2(/<directory>)/c/(<controller>(/<title>(/<action>)))'
                , array('directory'=>'.+?'))
                ->defaults(array('action'=>'view'));                

        Controller_Model::$_routing_default_route = 'cm-foobar-1';
    }

    /**
     * Restore the index file and base URL settings
     */
    public static function tearDownAfterClass()
    {
        Kohana::$base_url = self::$_old_base_url;
        Kohana::$index_file = self::$_old_index_file;
        parent::tearDownAfterClass();
    }

    /**
     * Test that an exception is thrown if there is no controller defined for a
     * given model.
     *
     * @expectedException CM_Exception_Missing_Class
     */
    public function test_missing_controller_exception()
    {
        $model = new Model_CM_FooBar_NotValid();
        Controller_Model::uri($model);
    }

    /**
     * Data provider for the URI tests
     * @return array
     */
    public function provider_uri()
    {
        return array (
            array('Model_CM_FooBar', Controller_Model::ACTION_VIEW, 
                array(), false, 'cmf/cm/c/foobar'),
            array(new Model_CMFooBar, Controller_Model::ACTION_VIEW, 
                array('token'=>'123'), false, 'cmf/c/cmfoobar/5/view/123'),
            array(new Model_CM_FooBar, Controller_Model::ACTION_INDEX,
                array(), false, 'cmf/cm/c/foobar/5/index'),
            //@todo: The URI defs here are wrong, dependent on KO Bug http://dev.kohanaframework.org/issues/4116
            array(new Model_CM_FooBar_Inherited, Controller_Model::ACTION_VIEW,
                array(), false, 'cmf2/cm/foobar/c/inherited/my-model/view'),
            array(new Model_CM_FooBar, Controller_Model::ACTION_VIEW,
                array(), true, '/cmftest/index.php/cmf/cm/c/foobar/5/view'),
        );
    }

    /**
     * Test that URIs are generated correctly
     * @dataProvider provider_uri
     */
    public function test_uri($model, $action, $params, $external, $uri_expected)
    {
        $uri_actual = Controller_Model::uri($model, $action, $params, $external);
        $this->assertEquals($uri_expected, $uri_actual);
    }

    /**
     * Test that the controller throws an exception if _authorised is not explicitly set
     * @return void
     */
    public function test_requires_authorisation()
    {        
        $response = Request::factory('cmf/c/cmfoobar/5/authtestok')
                    ->execute();
        $this->assertEquals('ok', $response->body());
        
        try
        {
            $response = Request::factory('cmf/c/cmfoobar/5/authtestbad')
                    ->execute();
        }
        catch (CM_Exception_No_Auth $e)
        {
            return;
        }
        $this->fail('A CM_Exception_No_Auth was expected but not thrown');
    }

    public function test_actions_protected()
    {

    }
    
}

class Model_CM_FooBar_NotValid
{
}

class Model_CM_FooBar
{
    public $id = '5';
}

class Model_CMFooBar
{
    public $id = '5';
}

class Model_CM_FooBar_Inherited
{
    public $title = 'my-model';
}

class Controller_CMFooBar extends Controller_Model
{
    public function action_authtestok()
    {
        $this->authorised();
        $this->response->body('ok');
    }
    public function action_authtestbad()
    {
        return true;
    }
}

class Controller_CM_FooBar extends Controller_Model
{

}

class Controller_CM_FooBar_Inherited extends Controller_Model
{
    public static $_routing_model_id = 'title';
    public static $_routing_default_route = 'cm-foobar-2';
}