<?php
namespace Hoborg\Dashboard;

class Widget {

	protected $kernel = null;

	private $defaults = array(
		'name' => '',
		'body' => '',
	);

	protected $data = array();

	public function __construct(Kernel $kernel, array $data = array()) {
		$this->kernel = $kernel;
		$this->setData($data);
	}

	/**
	 * Sets widget data.
	 * Data array is automatically extended with default values.
	 *
	 * @param array $data
	 *
	 * @return Hoborg\Dashboard\Widget
	 */
	public function setData(array $data) {
		$this->data = $this->arrayMergeRecursive($this->defaults, $data);

		return $this;
	}

	/**
	 * Returns widget data array.
	 * You can specify data key and default value.
	 *
	 * @param string $key
	 * @param mixed $default
	 *
	 * @return mixed
	 */
	public function getData($key = null, $default = null) {
		if (null !== $key) {
			return isset($this->data[$key]) ?
					$this->data[$key] : $default;
		}

		return $this->data;
	}

	/**
	 * Sets widget default values.
	 *
	 * @param array $data
	 */
	public function setDefaults(array $data) {
		$this->defaults = $data;
		$this->applyDefaults();
	}

	public function addDefaults(array $data) {
		$this->defaults = $this->arrayMergeRecursive($data, $this->defaults);
		$this->applyDefaults();
	}

	public function getJson() {
		return json_encode($this->data);
	}

	public function bootstrap() {
		return $this;
	}

	public function getAssetFiles($type) {

		if (empty($this->data['assets'][$type])) {
			return array();
		}

		$assets = $this->data['assets'][$type];
		if (!is_array($assets)) {
			$assets = array($assets);
		}

		return $assets;
	}

	public function hasJS() {
		return !empty($this->data['js']);
	}

	public function getJS() {
		$js = $this->getData('js', array());
		if (!is_array($js)) {
			$js = array($js);
		}

		return $js;
	}

	public function hasCSS() {
		return !empty($this->data['css']);
	}

	public function getCSS() {
		$css = $this->getData('css', array());
		if (!is_array($css)) {
			$css = array($css);
		}

		return $css;
	}

	public function getJsClassName() {
		if (empty($this->data['class'])) {
			return 'HoborgWidget';
		}

		return $this->data['class'];
	}

	public function hasHead() {
		return !empty($this->data['head']);
	}

	private function arrayMergeRecursive($arrayA, $arrayB) {
		// merge arrays if both variables are arrays
		if (is_array($arrayA) && is_array($arrayB)) {
			// loop through each right array's entry and merge it into $arrayA
			foreach ($arrayB as $key => $value) {
				if (isset($arrayA[$key])) {
					$arrayA[$key] = $this->arrayMergeRecursive($arrayA[$key], $value);
				} else {
					if ($key === 0) {
						$arrayA= array(0 => $this->arrayMergeRecursive($arrayA, $value));
					} else {
						$arrayA[$key] = $value;
					}
				}
			}
		} else {
			// one of values is not an array
			$arrayA = $arrayB;
		}

		return $arrayA;
	}

	private function applyDefaults() {
		$this->data = $this->arrayMergeRecursive($this->defaults, $this->data);
	}
}