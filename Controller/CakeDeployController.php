<?php
/**
 * Deploy
 *
 * deploy running applciation following rules found in ROOT/build.fcp
 */

App::uses( 'PhpCompiler', 'CakeDeploy.Vendor' );

class CakeDeployController extends CakeDeployAppController {
	
	public function index() {
		
		$source = ROOT . DS;
		
		$dest 	= dirname(ROOT) . DS . basename(ROOT) . '-deploy' . DS ;
		
		$c = new PhpCompiler( $source, $dest );
		
	}
	
}