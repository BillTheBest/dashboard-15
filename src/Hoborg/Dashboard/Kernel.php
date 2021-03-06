<?php
namespace Hoborg\Dashboard;

use Seld\JsonLint\JsonParser;

class Kernel {

	protected $config = null;

	protected $properties = array();

	protected $params = array();

	protected $defaultParams = array();

	protected $paths = array(
		'templates' => array(),
		'widgets' => array(),
		'data' => array(),
		'config' => array(),
	);

	public function __construct($rootFolder) {
		if (is_readable($rootFolder . '/dashboard.properties')) {
			$this->properties = parse_ini_file($rootFolder . '/dashboard.properties');
		}

		$this->addExtensionPath($rootFolder);
		$this->paths['templates'][] = __DIR__ . '/Resources/templates';
	}

	/**
	 * Handles dashboard request.
	 *
	 * @param array $params
	 * @param Hoborg\Dashboard\Dashboard $dashboard
	 * 			Optional dashboard object for handling
	 * @param Hoborg\Dashboard\WidgetProvider $widgetProvider
	 */
	public function handle(array $params, Dashboard $dashboard = null, WidgetProvider $widgetProvider = null) {
		try {
			$this->setParams($params);

			if ($this->hasStaticParam()) {
				$proxy = new StaticAssetsProxy($this);
				$proxy->output($this->getParam('static', ''));

				return;
			}

			if ($this->hasWidgetParam()) {
				// return selected widget in JSONP format
				if (null == $widgetProvider) {
					$widgetProvider = new WidgetProvider($this);
				}

				$widget = $widgetProvider->createWidget(json_decode($this->getParam('widget'), true));
				$this->send($widget->getJson());
			} else if ($this->hasWidgetDataParam()) {
				// return selected widget data in JSONP format
				if (null == $widgetProvider) {
					$widgetProvider = new WidgetProvider($this);
				}
				$widget = $widgetProvider->createWidget($this->getParam('widget'));
				$this->send($widget->getJson());
			} else {
				// render whole dashboard
				if (null == $dashboard) {
					$dashboard = new Dashboard($this);
				}
				$this->send($dashboard->render());
			}
		} catch (Exception $e) {
			$this->send($this->handleException($e));
		}
	}

	public function handleCli(array $params) {
		$cli = new Cli($this);
		$cli->handle($params);
	}

	public function hasWidgetParam() {
		return array_key_exists('widget', $this->params);
	}

	public function hasWidgetDataParam() {
		return array_key_exists('data', $this->params);
	}

	public function hasStaticParam() {
		return array_key_exists('static', $this->params);
	}

	public function setDefaultParam($key, $value) {
		$this->defaultParams[$key] = $value;

		return $this;
	}

	public function addDefaultParams(array $defaults) {
		foreach ($defaults as $key => $value) {
			$this->defaultParams[$key] = $value;
		}

		return $this;
	}

	public function setParams(array $params) {
		$params = $params + $this->defaultParams;

		if (empty($params['conf'])) {
			throw new Exception('Missing `conf` parameter', 500);
		}

		$this->params = $params;
	}

	public function getParam($key, $default = null) {
		return isset($this->params[$key]) ? $this->params[$key] : $default;
	}

	public function setPath($key, array $paths) {
		$this->paths[$key] = $paths;

		return $this;
	}

	public function addPath($key, array $paths) {
		if (is_array($this->paths[$key])) {
			$this->paths[$key] = array_merge($this->paths[$key], $paths);
		}

		return $this;
	}

	/**
	 * Register folder with your widgets, templates, config and data.
	 *
	 * @param string $path
	 */
	public function addExtensionPath($path) {
		$this->paths['config'][] = "{$path}/config";
		$this->paths['templates'][] = "{$path}/templates";
		$this->paths['widgets'][] = "{$path}/widgets";
		$this->paths['data'][] = "{$path}/data";
	}

	public function getTemplatesPath() {
		return $this->paths['templates'];
	}

	public function setTemplatesPath(array $paths) {
		$this->paths['templates'] = $paths;

		return $this;
	}

	public function getConfigPath() {
		return $this->paths['config'];
	}

	public function getWidgetsPath() {
		return $this->paths['widgets'];
	}

	public function setWidgetsPath(array $paths) {
		$this->paths['widgets'] = $paths;

		return $this;
	}

	public function getDataPath() {
		return $this->paths['data'];
	}

	public function getConfig() {
		if (!empty($this->config)) {
			return $this->config;
		}

		if (empty($this->params['conf'])) {
			throw new Exception('no configuration specified', 500);
		}

		$configName = $this->params['conf'];
		$configFile = $this->findFileOnPath($configName . '.js', $this->getConfigPath());
		if (!is_file($configFile)) {
			$configFile = $this->findFileOnPath($configName . '.json', $this->getConfigPath());
		}
		if (!is_file($configFile)) {
			$error = "Configuration file `{$this->params['conf']}` not found.";
			$code = '404';

			return $this->handleError($error, $code);
		}

		// get configuration
		$configJson = file_get_contents($configFile);
		$this->config = json_decode($configJson, true);

		if (empty($this->config)) {
			$parser = new JsonParser();
			$jsonError = $parser->lint($configJson);

			$error = "You have an error in your configuration. {$jsonError->getMessage()}";
			$code = '500';

			return $this->handleError($error, $code);
		}

		return $this->config;
	}

	public function findFileOnPath($file, array $paths = array()) {
		foreach ($paths as $path) {
			if (is_readable($path . DIRECTORY_SEPARATOR . $file)) {
				return $path . DIRECTORY_SEPARATOR . $file;
			}
		}

		// fallback to php include path
		if (is_readable($file)) {
			return $file;
		}

		return false;
	}

	public function log($msg) {
		echo $msg;
	}

	public function shutDown($exitCode = 0) {
		exit($exitCode);
	}

	protected function send($content) {
		echo $content;
	}

	protected function handleException(Exception $e) {
		return "Application Error :( <br /> {$e->getMessage()}";
	}

	protected function handleError($error, $code) {
		$errorTpl = $this->findFileOnPath('error.phtml', $this->getTemplatesPath());
		include $errorTpl;
		$this->shutDown(1);
	}
}
