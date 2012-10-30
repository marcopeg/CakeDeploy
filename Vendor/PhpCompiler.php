<?php
/**
 * PhpCompiler Class
 * =================
 *
 * An utility to copy a project source folder with these benefits:
 * - solve symlinks dependencies to create a fully FTP uploadable folder
 * - skip files and directory based on rules
 * - remove comments from PHP files
 * - uglify PHP files
 *
 * 
 * 
 */


/*
Copyright (c) 2012 Marco Pegoraro - marco(dot)pegoraro(at)gmail(dot)com - MovableAPP.com

Permission is hereby granted, free of charge, to any person
obtaining a copy of this software and associated documentation
files (the "Software"), to deal in the Software without
restriction, including without limitation the rights to use,
copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the
Software is furnished to do so, subject to the following
conditions:

The above copyright notice and this permission notice shall be
included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES
OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT
HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY,
WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR
OTHER DEALINGS IN THE SOFTWARE.
*/




/**
 * Automagicaly compiler for actual folder
 */
if ( isset($_GET['compile']) ) {
	
	$src 	= __FolderCompiler_Utils___MovableappCom__::folderFixPath(dirname(__FILE__));
	$dest 	= __FolderCompiler_Utils___MovableappCom__::folderFixPath( __FolderCompiler_Utils___MovableappCom__::folderFixPath(dirname(dirname(__FILE__))) . basename($src) . '-compiled' );
	
	$c = new PhpCompiler( $src, $dest, array(
		'sync' 				=> 'sync',
		'skipFileNames' 	=> array( 'c.php' ),
	));
	
}


/**
 * Wrapper to simplify compiler
 */
class PhpCompiler {
	
	public function __construct( $source, $dest, $config = array() ) {
		
		set_time_limit(99999);
		
		$compiler = new __FolderCompiler___MovableappCom__( $source, $dest, $config );
		$compiler->run();
		
	}
	
}





// ------------------------------------------------- //
// ---[[   P R I V A T E   U T I L I T I E S   ]]--- //	
// ------------------------------------------------- //

class __FolderCompiler_Utils___MovableappCom__ {
	
	public function exists( $path ) {
		
		return file_exists($path);
		
	}
	
	public function isFolder( $path ) {
		
		if ( !$this->exists($path) ) return false;
		
		return is_dir($path);
		
	}
	
	public function isFile( $path ) {
		
		if ( $this->isFolder($path) ) 	return false;
		
		return is_file($path);
		
	}
	
	public function isLink( $path ) {
		
		if ( !$this->exists($path) ) return false;
		
		return is_link($path);
		
	}
	
	public function isWritable( $path ) {
		
		if ( !$this->exists($path) ) return false;
		
		return is_writable($path);
		
	}
	
	public function isWritableFolder( $path ) {
		
		if ( !$this->isFolder($path) ) return false;
		
		return $this->isWritable($path);
		
	}
	
	public function createFolder( $path ) {
		
		if ( mkdir($path, 0755) ) return $this->exists($path);
		
		return false;
		
	}
	
	public function folderFixPath( $path ) {
		
		if ( substr(strrev($path),0,1) != '/' ) $path .= '/';
		
		return $path;
		
	}
	
	public function getFileExtension( $path ) {
		
		$path = strrev($path);
		
		if ( strpos( $path, '.' ) === false ) return '';
		
		return strrev( substr( $path, 0, strpos($path,'.') ) );
		
	}
	
	public function fileRead( $path ) {
		
		return file_get_contents($path);
		
	}
	
	public function fileWrite( $path, $content ) {
		
		return file_put_contents( $path, $content );
		
	}
	
	/**
	 * Nice smart intelligent string matching utility.
	 * @TODO: "*foo", "foo*", "*foo*"
	 */
	public function matchStr( $test, $target ) {
		
		if ( $test === $target ) return true;
			
		if ( substr($test,0,1) === '*' ) {
			
			$test = substr($test,1,strlen($test));
			
			if ( strpos($target,$test) !== false ) return true;
			
		}
		
		return false;
		
	}
	
	
	public static function unlink( $path ) {
		
		return @unlink($path);
		
	}
	
	/**
	 * Recursive Directory Deletion.
	 * It delete all files and subdirectories of specified folder.
	 */
	public static function rrmdir( $dirname ) {
	
		// Sanity check.                                                                          #      
		if (!file_exists($dirname)) {
			return false;
		}
		
		// Simple delete for a file.                                                              #
		if (is_file($dirname) || is_link($dirname)) {
			return unlink($dirname);
		}
	
		// Loop through the folder.                                                               #
		$dir = dir($dirname);
		while (false !== $entry = $dir->read()) {
			// Skip pointers
			if ($entry == '.' || $entry == '..') {
				continue;
			}
			
			// Recurse                                                                            #
			self::rrmdir($dirname . DIRECTORY_SEPARATOR . $entry);
		}
		
		// Clean up and remove this directory.                                                    #
		$dir->close();
		return rmdir($dirname);
	
	}
	
}





/**
 * Debugging Utilities
 */


if ( !function_exists('debug') ) {

	function debug($var = false) {
		
		echo '<pre>';
		print_r($var);
		echo '</pre>';
		
	}

}

if ( !function_exists('ddebug') ) {

	function ddebug($var = false) {
		debug($var);
		exit;
	}

}






class __FolderCompiler___MovableappCom__ extends __FolderCompiler_Utils___MovableappCom__ {
	
	protected $sync		= 'clear';
	
	private $source 	= '';
	private $dest 		= '';
	
	private $files		= array();
	private $subs		= array();
	
	// syncronization utilities
	private static $sourceBase 	= '';
	private static $destBase	= '';
	private static $rel 		= '';
	
	
	private $skipFolderNames = null;
	
	
	// Dependency injection for file processing object
	public $FileProcessor = '__FileCompiler___MovableappCom__';
	
	
	
	
	public function __construct( $source, $dest, $config = array() ) {
		
		$this->source 	= $source;
		$this->dest		= $dest;
		$this->config	= $config;
		
		// Build local configuration from configuration array
		$this->make_config();
		
	}
	
	/**
	 * Compiler initialization method.
	 * Use this method to startup a new folder compilation.
	 * ( it will reset static properties to calculate relative job path )
	 */
	public function run() {
		
		// setup syncronization base path
		self::$sourceBase 	= $this->source;
		self::$destBase 	= $this->dest;
		
		$this->do_job();
		
	}
	
	/**
	 * Compile worker method
	 * use this method to propagate compilation to sub-folders
	 */
	public function do_job() {
		
		// setup syncronization base path
		if ( empty(self::$sourceBase) ) self::$sourceBase 	= $this->source;
		if ( empty(self::$destBase) ) 	self::$destBase 	= $this->dest;
		
		// Build relative path to test files in "sync" mode
		self::$rel = substr( $this->dest, strlen(self::$destBase), strlen($this->dest) );
		
		// Tun tests to define if folder can be copied.
		// here folders are checked up, deleted or created.
		if ( !$this->do_tests() ) return false;
		
		// Generates list of entries to be copied
		$this->list_folder();
		
		// If syncronization mode try to delete destination unnecessary files
		if ( $this->sync === 'sync' ) $this->sync_clear();
		
		// Run copy
		echo "<p>"; flush();
		$this->process_files();
		$this->process_subs();
		echo "</p>"; flush();
		
	}
	
	
	
	
	
	
	/**
	 * Create local configuration for running behaviors like skipping folders
	 * or define uglification levels!
	 */
	private function make_config() {
		
		// ----
		// Extends local configuration with a build file from target path
		if ( file_exists($this->source.'build.fcp') ) {
			
			$fcp = null;
			include($this->source.'build.fcp');
			if ( is_array($fcp) ) $this->config = array_merge_recursive( $fcp, $this->config );
			unset( $fcp );
			
		}
		
		
		// ----
		// Syncronization method
		if ( isset($this->config['sync']) ) $this->sync = $this->config['sync'];
		
		
		
		
		// ----
		// Skip folders by name list:
		if ( empty($this->skipFolderNames) ) $this->skipFolderNames = array(
			'.',
			'..',
		);
		
		if ( empty($this->config['skipFolderNames']) ) $this->config['skipFolderNames'] = array(
			'.git',
			'.svn',
			'.settings'
		);
		
		$this->skipFolderNames = array_merge( $this->skipFolderNames, $this->config['skipFolderNames'] );
		
		
		// ----
		// Skip files by name list:
		if ( empty($this->skipFileNames) ) $this->skipFileNames = array(
			'build.fcp', // FolderCompiler build configuration
			'.ds_store',
			'.gitignore',
			'.buildpath',
			'.project',
			'.travis.yml',
		);
		
		if ( empty($this->config['skipFileNames']) ) $this->config['skipFileNames'] = array();
		
		$this->skipFileNames = array_merge( $this->skipFileNames, $this->config['skipFileNames'] );
		
		
		
		// ----
		// Skip folders by part of their path
		if ( empty($this->skipFolderPaths) ) 			$this->skipFolderPaths 				= array();
		if ( empty($this->config['skipFolderPaths']) ) 	$this->config['skipFolderPaths'] 	= array();
		$this->skipFolderPaths = array_merge( $this->skipFolderPaths, $this->config['skipFolderPaths'] );
		
		// ----
		// Skip files by part of their path
		if ( empty($this->skipFilePaths) ) 				$this->skipFilePaths 				= array();
		if ( empty($this->config['skipFilePaths']) ) 		$this->config['skipFilePaths'] 	= array();
		$this->skipFilePaths = array_merge( $this->skipFilePaths, $this->config['skipFilePaths'] );
		
		
		// ----
		// Uglification level
		if ( !isset($this->uglifyLevels) ) $this->uglifyLevels = array();
		
		if ( empty($this->config['uglifyLevels']) ) 	$this->config['uglifyLevels'] 	= array(
			'B' => array( '.php' )
		);
		
		$this->uglifyLevels = array_merge_recursive( $this->uglifyLevels, $this->config['uglifyLevels'] );
		
	}
	
	
	/**
	 * Check source and destination folders to exists and to be writable
	 * to allow copy to continue
	 */
	private function do_tests() {
		
		// Test source folder to exists and to be a folder
		if ( !$this->exists($this->source) || !$this->isFolder($this->source) ) return false;
		
		// "clear" sync method will destroy dest folder
		if ( $this->sync === 'clear' && $this->exists($this->dest) ) {
			@self::rrmdir( $this->dest );
			if ( $this->exists($this->dest) ) return false;
		}
		
		// Optional creates dest folder
		if ( !$this->exists($this->dest) ) $this->createFolder($this->dest);
		
		// Test dest folder to exists and to be a writeable folder
		if ( !$this->exists($this->dest) || !$this->isWritableFolder($this->dest) ) return false;
		
		return true;
		
	}
	
	
	
	/**
	 * Creates a list of resources to be copied later.
	 * handles symlinks to be copied from their sources!
	 */
	private function list_folder() {
		
		$this->files 	= array();
		$this->subs		= array();
		
		$fp = opendir($this->source);
		if ( !$fp ) return;
		
		while ( ( $entry = readdir($fp) ) !== false ) {
			
			$entry_path = $this->source . $entry;
			
			if ( $this->isFolder( $entry_path ) ) {
				
				if ( !$this->is_skip_folder($entry) ) {
					
					if ( $this->isLink($entry_path) ) {
						$this->subs[] = array( $this->folderFixPath($entry) => $this->folderFixPath(readlink($entry_path)) );
						
					} else {
						$this->subs[] = $this->folderFixPath( $entry );
						
					}
					
					
						
				}
				
			} else {
				
				if ( !$this->is_skip_file($entry) ) {
					
					if ( $this->isLink($entry_path) ) {
						
						$entry = array( $entry => readlink($entry_path) );
						
					}
					
					$this->files[] = $entry;
						
				}
				
			}
			
		}
		
		
	}
	
	
	
	
	
	
	
	/**
	 * Processing methods for files adn sub-folders
	 */
	
	private function process_files() {
		
		foreach ( $this->files as $i=>$entry ) {
			
			if ( is_array($entry) ) {
				$dest 	= array_keys($entry);
				$dest	= $dest[0];
				
				$source = $entry[$dest];
				$dest	= $this->dest . $dest;
				
			} else {
				
				$source = $this->source . $entry;
				$dest	= $this->dest . $entry;
				
			}
			
			// Exclude a file by part of it's detination path
			if ( $this->is_skip_file_path($dest) ) continue;
			
			// SYNCRONIZATION MODE:
			// does not override files who didn't changed since last syncronization
			if ( $this->sync === 'sync' && $this->exists($dest) ) {
				
				if ( !( filemtime($source) > filemtime($dest) ) ) {				
					debug("SKIP SYNC: " . $dest );
					continue;
				}
				
			}
			
			// Process file throught proper object
			echo '. '; flush();
			$action = new $this->FileProcessor( $source, $dest, $this->file_uglify_level( $dest ) );
			
		}
		
	}
	
	private function process_subs() {
		
		foreach ( $this->subs as $i=>$entry ) {
			
			if ( is_array($entry) ) {
				$dest 	= array_keys($entry);
				$dest	= $dest[0];
				
				$source = $entry[$dest];
				$dest	= $this->dest . $dest;
				
			} else {
				
				$source = $this->source . $entry;
				$dest	= $this->dest . $entry;
				
			}
			
			// Exclude a folder by part of it's detination path
			if ( $this->is_skip_folder_path($dest) ) continue;
			
			// Process file throught proper object
			// uses "get_called_class" to allow to change or extends this class!
			$folder_compiler_class = get_called_class();
			$action = new $folder_compiler_class( $source, $dest, $this->config );
			$action->do_job();
			
		}
		
	}
	
	
	
	
	
	
	
	
	/**
	 * Remove from destination folder all files who are targeted as "unnecessary"
	 * these files does not exists anymore into source folder or doesn't match
	 * next compiler rules.
	 */
	private function sync_clear() {
		
		
		// Compose a list of source files and folders to delete
		// files who does not exists into destination folder
		$sourceFiles	= array();
		$sourceSubs		= array();
		
		$fp = opendir($this->source);
		if ( !$fp ) return;
		
		while ( ( $entry = readdir($fp) ) !== false ) {
			
			$entry_path = $this->dest . $entry;
			
			if ( $this->isFolder( $entry_path ) ) {
				
				$sourceSubs[] = $entry;
				
			} else {
				
				$sourceFiles[] = $entry;
				
			}
			
		}
		
		// Clear hard deletion from source folder
		// I need this step to prevent system folder deletion (., ..)
		
		$destFiles 	= array();
		$destSubs	= array();
		
		$fp = opendir($this->dest);
		if ( !$fp ) return;
		
		while ( ( $entry = readdir($fp) ) !== false ) {
			
			$entry_path = $this->dest . $entry;
			
			if ( $this->isFolder( $entry_path ) ) {
				
				if ( !in_array($entry,$sourceSubs) ) {
					self::rrmdir( $entry_path );
				} elseif ( !in_array($entry,array('.','..')) ) {
					$destSubs[] = $this->folderFixPath($entry);
				}
				
			} else {
				
				if ( !in_array($entry,$sourceFiles) ) {
					self::unlink( $entry_path );
				} else {
					$destFiles[] = $entry;
				}
				
			}
			
		}
		
		// Remove destination folders by path rules
		foreach ( $destSubs as $entry ) {
		
			$entry_path = $this->dest . $entry;
			
			if ( $this->is_skip_folder_path($entry_path) ) self::rrmdir( $entry_path );
			
			if ( !in_array($entry, $this->subs) && !in_array($entry,array_keys($this->subs)) ) self::rrmdir( $entry_path );
			
		}
		
		// Remove destination files by path rules
		foreach ( $destFiles as $entry ) {
		
			$entry_path = $this->dest . $entry;
			
			if ( $this->is_skip_file_path($entry_path) ) self::unlink( $entry_path );
			
			if ( !in_array($entry, $this->files) ) self::unlink( $entry_path );
			
		}
		
	}

	
	
	
	
	
	/**
	 * Allows to skip entries based on entry name
	 * list of names to skip is defined by FolderCompile configuration
	 * maker.
	 *
	 * This filter is made while listing resources to be copied.
	 * only the name of resource (withouth full path) is given to these methods
	 *
	 * "matchStr" utility allow to configure names like "*.php", ".*" etc..
	 * (some advanced behaviors of wild char needs to be implemented!)
	 */
	
	protected function is_skip_folder( $entry ) {
		
		foreach ( $this->skipFolderNames as $skip ) {
			
			if ( $this->matchStr( $skip, $entry ) ) return true;
			
		}
		
	}
	
	protected function is_skip_file( $entry ) {
		
		foreach ( $this->skipFileNames as $skip ) {
			
			if ( $this->matchStr( $skip, $entry ) ) return true;
			
		}
		
	}
	
	
	/**
	 * Allows to skip entries based on a part of entry destination full path.
	 */
	
	protected function is_skip_folder_path( $entry ) {
		
		foreach ( $this->skipFolderPaths as $path ) {
			
			if ( strpos($entry,$path) !== false ) {
				
				debug("SKIP: " . $entry);
				return 1;
				
			}
		}
		
	}
	
	protected function is_skip_file_path( $entry ) {
		
		foreach ( $this->skipFilePaths as $path ) {
			
			if ( strpos($entry,$path) !== false ) {
				
				debug("SKIP: " . $entry);
				return 1;
				
			}
		}
		
	}
	
	/**
	 * Define uglify level for each file.
	 * $entry is destination folder!
	 */
	protected function file_uglify_level( $entry ) {
		
		$return_level = 'A';
		
		foreach ( $this->uglifyLevels as $level=>$paths ) {
			
			foreach ( $paths as $path ) {
				
				if ( strpos( $entry, $path ) !== false ) $return_level = $level;
				
			}
			
		}
		
		return $return_level;
		
	}
	
}





class __FileCompiler___MovableappCom__ extends __FolderCompiler_Utils___MovableappCom__ {
	
	private $source = '';
	private $dest	= '';
	private $level	= 0;
	
	
	public function __construct( $source, $dest, $level = 0 ) {
		
		$this->source 	= $source;
		$this->dest 	= $dest;
		$this->level	= $level;
		
		if ( !$this->do_tests() ) return false;
		
		if ( $this->is_source_file() && $this->level != 'A' ) {
			return $this->process();
			
		} else {
			return copy( $this->source, $this->dest );
			
		}
		
	}
	
	/**
	 * Check source and destination folders to exists and to be writable
	 * to allow copy to continue
	 */
	private function do_tests() {
		
		// Test source folder to exists and to be a folder
		if ( !$this->exists($this->source) || !$this->isFile($this->source) ) return false;
		
		// Try to remove existing file
		if ( $this->exists($this->dest) ) @unlink($this->dest);
		if ( $this->exists($this->dest) ) return false;
		
		// Test dest folder to exists and to be a writeable folder
		if ( !$this->isWritableFolder(dirname($this->dest)) ) return false;
		
		return true;
		
	}
	
	/**
	 * Define is a path is a file who needs to be processed or not
	 */
	protected function is_source_file() {
		
		if ( !isset($this->sourceFilesExtensions) ) $this->sourceFilesExtensions = array(
			'php'
		);
		
		if ( in_array( strtolower($this->getFileExtension($this->source)), $this->sourceFilesExtensions ) ) return true;
		
	}
	
	/**
	 * Copy file removing comments, white spaces, etc...
	 * @TODO: implement it!
	 */
	private function process() {
		
		#return copy( $this->source, $this->dest );
		
		$fileStr = $this->fileRead( $this->source );
		
		// build comment tokens code list
		$this->commentTokens = array(T_COMMENT);
		if (defined('T_DOC_COMMENT'))	$this->commentTokens[] = T_DOC_COMMENT; // PHP 5
		if (defined('T_ML_COMMENT'))	$this->commentTokens[] = T_ML_COMMENT;  // PHP 4
		
		$newStr  	= '';
		$tokens 	= token_get_all($fileStr);
		
		foreach ( $tokens as $i=>$token ) {
		
			
			// Allow processing steps to set a number of token to skip in the future!
			if ( isset($this->__skip__) && $this->__skip__ > 0 ) {
				$this->__skip__ -= 1;
				continue;
			}
			
			// remove comments
			if ( in_array($this->level,array('B','C')) ) $token = $this->process_lv1( $token, $tokens, $i );
			
			// code uglifier processing
			if ( $this->level == 'C' ) $token = $this->process_lv2( $token, $tokens, $i );
			
			
			/**
			 * Appends Token
			 */
			
			if ( !$token ) continue;
			
			if ( is_array($token) ) {
				$newStr .= $token[1];
				
			} else {
				$newStr .= $token;
				
			}
			
			
		}
		
		return $this->fileWrite( $this->dest, $newStr );
		
	}
	
	
	
	/**
	 * Remove all PHP comments
	 */
	private function process_lv1( $token, $tokens, $i ) {
		
		if ( !is_array($token) ) return $token;
		
		if ( in_array($token[0], $this->commentTokens) ) return null;
		
		return $token;
		
	}
	
	
	/**
	 * Code Uglifier!
	 */
	private function process_lv2( $token, $tokens, $i ) {
		
		if ( !is_array($token) ) {
			
			switch ( $token ) {
				
				// skip a following new line token
				// must consider EOT exception from previous block!
				case ';':
				case '{':
				case '}':
				case '(':
				case ')':
				case ',':
					if ( !empty($tokens[$i+1][0]) && $tokens[$i+1][0] === 371 ) $this->__skip__ = 1;
					if ( !empty($tokens[$i-1][0]) && $tokens[$i-1][0] === T_END_HEREDOC ) $this->__skip__ = 0;
					
					break;
					
					
				
			}
			
		}
		
		return $token;	
		
	}
	
	
	
}


