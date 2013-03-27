<?php
/**
 * CakeDeploy
 * =============
 *
 * adds an hard configured routing rule to ensure cake deploy is
 * launched by it's url even if other strange router rules exists.
 */

if (strpos($_SERVER['REQUEST_URI'], '/cake_deploy') === 0) {
	Router::connect(
		'/cake_deploy',
		array(
			'plugin' => 'cake_deploy',
			'controller' => 'cake_deploy', 
			'action' => 'index'
		)
	);
}