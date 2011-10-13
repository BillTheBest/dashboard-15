<?php
error_reporting(E_ALL & ~(E_STRICT|E_NOTICE));
require_once __DIR__ . '/../src/core.php';

$confDir = realpath(__DIR__ . '/../conf');
$config = $_GET['conf'];
$configFile = realpath($confDir .'/' . $config . '.js');

if (empty($configFile)) {
	echo "erorr - no config";
	// redirect to error page;
	return;
}

if (strpos($confDir, $configFile)) {
	echo "erorr :(";
	// redirect to error page;
	return;
}

// get widgets
$config = json_decode(file_get_contents($configFile), true);

$widgets = $config['widgets'];

// now we bootstrap each widget
foreach ($widgets as & $widget) {
	bootstrap_widget($widget);
}
unset($widget);

// ... collect `head` data


$head = '';
$onceOnly = array();
$onLoad = array();
foreach ($widgets as & $widget) {
    if (is_callable($widget['head'])) {
        $widget['head'] = $widget['head']();
    }

	foreach ($widget['head'] as $key => $values) {
		if ('onceOnly' === $key) {
			foreach ($values as $k => $v) { $onceOnly[$k] = $v; }
		}
		if ('onLoad' === $key) {
			foreach ($values as $k => $v) { $onLoad[$k] = $v; }
		}
	}
}
$head .= join("\n", $onceOnly);

foreach ($data as $widget) {
	foreach ($widget['head'] as $key => $values) {
		if ('always' === $key) {
			foreach ($values as $k => $v) {
				$head .= "\n" . $v;
			}
		}
	}
}

$head .= '<script type="text/javascript">
window.onload = function () {' . join("\n\n", $onLoad) . '};</script>';

include __DIR__ . '/../templates/' . $config['template'] . '.phtml';