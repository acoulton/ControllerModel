<?php defined('SYSPATH') or die('No direct script access.');

class Controller_Model extends Controller
{
	const ACTION_VIEW = 'view';
	const ACTION_INDEX = 'index';
	const ACTION_EDIT = 'edit';
	const ACTION_CREATE = 'create';
	const ACTION_DELETE = 'delete';

	protected $_authorised = null;

	public static $_routing_model_id = 'id';
	public static $_routing_default_route = 'default';

	public static function uri($model, $action = self::ACTION_VIEW, $params = array(), $external = false)
	{
		// Find the controller for this model
		$controller_class = self::_equivalent_class($model, 'Controller');

		// Call the controller's _route_uri method to get the routing array
		$route = call_user_func(array($controller_class,'_route_uri'), $controller_class, $model, $action, $params);

		// Convert the routing array to a URI
		$uri = Route::get($route['name'])->uri($route['params']);
		$external AND $uri = URL::site($uri);
		return $uri;
	}

	public function action_index()
	{
		// Show a list of all models
	}

	public function action_view()
	{
		// Show a single model
	}

	public function action_edit()
	{
		// Show a single model, rendered as an edit form
		// And save on POST
	}

	public function action_create()
	{
		// Show a "create new" form
		// And save on POST
	}

	public function action_delete()
	{
		// Show a confirmation
		// And delete if POST
	}

	/*
	 * Internal methods
	 */

	public function after()
	{
		if ($this->_authorised === null)
		{
                    throw new CM_Exception_No_Auth('No explicit authorisation for action :action in :controller',
				array(':action' => $this->request->action(),
					  ':controller' => get_class($this)));
		}
		parent::after();
	}

	protected function authorise()
	{
		// Do something to authorise the user can access this action
		if ( ! $this->_authorised)
		{
			// Throw a 403 exception
		}
		return $this->_authorised;
	}

	protected function authorised()
	{
		// Flag that we are authorised
		$this->_authorised = true;
	}

	protected function model()
	{
		// Get a single model relevant to this controller
		// Get the class name
		$class = self::_equivalent_class($this, 'Model');

		// Get the ID from the request
		$id_field = self::$_routing_model_id;
		$id = $this->request->param($id_field);

		// Load a single model
		$model = call_user_func(array($class, 'factory'), $id);
		return $model;
	}

	protected function view($action)
	{
		$class = self::_equivalent_class($this, 'View') . "_$action";
		return call_user_func(array($class,'factory'));
	}

	protected static function _equivalent_class($from_class, $to_type)
	{
		// Get the class name
		if (is_object($from_class))
		{
			$from_class = get_class($from_class);
		}

		// Split off any leading first element (View|Controller|Model)
		$equiv = preg_replace('/^(View|Controller|Model)_/','',$from_class);

		// Find and return the new class
		$equiv = $to_type . '_' . $equiv;

		if ( ! class_exists($equiv))
		{
                    throw new CM_Exception_Missing_Class('The :type class :class for :from_class did not exist',
                            array(':type'=>$to_type,
                                  ':class'=>$equiv,
                                  ':from_class'=>$from_class));
		}

		return $equiv;
	}

	protected static function _route_uri($controller_class, $model, $action, $params)
	{
		$route_params = array();

		// Get the static values with inheritance and without LSB
		$vars = get_class_vars($controller_class);

		// Get the routing name of this controller
		preg_match('/^Controller_?([a-zA-Z0-9_]*)_([a-zA-Z0-9_]+)$/i', $controller_class, $matches);
		$route_params['directory'] = strtolower(str_replace('_','/',$matches[1]));
		$route_params['controller'] = strtolower($matches[2]);

		// And the action
		$route_params['action'] = $action;

		// And the model identifier
		$id_field = $vars['_routing_model_id'];
                if (is_object($model))
                {
                    $route_params[$id_field] = $model->$id_field;
                }
                else
                {
                    $route_params[$id_field] = null;
                }

		// Merge in params
		$route_params = Arr::merge($route_params, $params);

		return array(
			'name' => $vars['_routing_default_route'],
			'params' => $route_params);
	}
}

class CM_Exception_Missing_Class extends Kohana_Exception {}
class CM_Exception_No_Auth extends Kohana_Exception {}