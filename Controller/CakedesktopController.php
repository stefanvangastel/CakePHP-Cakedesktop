<?php
/**
 * Class DesktopController
 */
App::uses('CakedesktopAppController', 'Cakedesktop.Controller');
App::uses('Folder', 'Utility');
App::uses('File', 'Utility');

class CakedesktopController extends AppController {
	
	public $uses = array('Cakedesktop.Database');

	//Variables
	private $settings		= array();

	private $applicationname= 'cakedesktop_application';
	
	private $job_id 		= '';

	private $job_directory 	= '';

	private $sql 			= '';

	private $databasename 	= '';

	private $zipfile 		= '';

	/**
	 * Index function, redirect to avoid using routes
	 */
	public function index(){
		$this->redirect(array('plugin'=>'cakedesktop','controller'=>'cakedesktop','action'=>'options'));
	}

	/**
	 * [options description]
	 * @return [type] [description]
	 */
	public function options(){	
		
		//Check deps:
		if( ! extension_loaded('sqlite3') ){
			$this->Session->setFlash(__('Cakedesktop requires Sqlite3 to be installed.'));
			$this->redirect($this->referer());
		}

		if( is_readable(CAKE.'version.txt') ){
			$versionfile = file(CAKE.'version.txt');
			$version = $versionfile[count($versionfile)-1];

			list($major,$minor,$patch) = explode('.', $version);

			if(! ("$major.$minor" >= 2.3 ) ){
				$this->Session->setFlash(__('Warning: You appear to be using CakePHP %s.%s.%s . CakePHP >= 2.3.x is required.',$major,$minor,$patch));
			}

		}else{
			$this->Session->setFlash(__('Warning: Could not determine CakePHP version. CakePHP >= 2.3.x is required.'));
		}

	}

	/**
	 * Creates a desktop app of the current CakePHP application
	 */
	public function createdesktopapp(){

		/**
		 * Steps:
		 *
		 * 0. Retrieve options and verify post 
		 * 
		 * 1. Copy phpdesktop skeleton dir to /tmp/<rand>
		 * 2. Apply options for phpdesktop 
		 * 3. Copy entire CakePHP directory to /tmp/<rand>/www/
		 * 4. Remove .htaccess files
		 * 5. Edit core.php and bootstrap.php to disable url rewrite and remove this plugin
		 *
		 * 6. Dump MySQL database
		 * 7. Convert SQL to Sqlite compatible SQL
		 * 8. Edit database.php to activate Sqlite
		 * 9. Import database structure in Sqlite
		 *
		 * 10. Zip package
		 * 11. Cleanup job dir
		 * 12. Serve package
		 */
		
		//Validate method
		if( ! $this->request->is('post') ){
			$this->Session->setFlash(__('Invalid request'));
			$this->redirect($this->referer());
		}
		
		//Check formdata present
		if(empty($this->request->data["Cakedesktop"])){
			$this->Session->setFlash(__('No form data found'));
			$this->redirect($this->referer());
		}

		$this->settings = $this->request->data["Cakedesktop"];
		if( ! empty($this->settings['main_window']['title'])){
			$this->applicationname = $this->settings['main_window']['title'];
		}
		
		//Set values:
		ini_set('max_execution_time', 120);
		ini_set('memory_limit', '256M');

		//Create job_id
		$this->job_id = time().'_'.rand(1000,9999);
		//$this->job_id = '1234'; //DEV TMP

		//Create job directory
		$this->job_directory = CakePlugin::path('Cakedesktop').'tmp'.DS.$this->job_id;		
		mkdir($this->job_directory);

		$this->copyskeletondir();

		$this->applysettings();

		$this->copycakedir();

		$this->removehtaccess();

		$this->editcore();

		$this->createmysqldump();

		$this->converttosqlite();

		$this->editdatabaseconfig();

		$this->createsqlitedb();

		$this->zipapplication();

		$this->cleanup();

		//Final step:
		return $this->servezipfile();
	}

	/**
	 * STEPS:
	 */

	/**
	 * [copyskeletondir description]
	 * @return bool Result of this action
	 */
	private function copyskeletondir(){

		$folder = new Folder($this->job_directory);
		return $folder->copy(array(
		    'from' => CakePlugin::path('Cakedesktop').'Vendor'.DS.'phpdesktop', // will cause a cd() to occur
		    'to' => $this->job_directory,
		    'mode' => 0755,
		    'skip' => array('Cakedesktop', '.git'),
		    'scheme' => Folder::SKIP  // Skip directories/files that already exist
		));

	}

	/**
	 * [applysettings description]
	 * @return [type] [description]
	 */
	private function applysettings(){

		/*
			Rename application exe to title if avail
		 */
			if( ! empty($this->applicationname)){
				rename($this->job_directory.DS.'phpdesktop-chrome.exe', $this->job_directory.DS.Inflector::slug($this->applicationname).'.exe');
			}

		/*
			Set favicon if any in webroot
		 */
			if(is_readable(WWW_ROOT.'favicon.ico')){
				rename(WWW_ROOT.'favicon.ico', $this->job_directory.DS.'favicon.ico');
				$this->settings['main_window']['icon'] = 'favicon.ico';
			}


		/*
			Apply settings (final step)
		 */
			//Rework settings:
			$this->settings = $this->fixboolean($this->settings);

			$settingsfile = $this->job_directory.DS.'settings.json';

			//Read settings.json
			if( ! is_readable($settingsfile)){
				return false;
			}

			$currentsettings = json_decode(file_get_contents($settingsfile),true); //Create assoc array

			//Merge the settings
			$newsettings = array_merge($currentsettings,$this->settings);

			//Prettyprint if PHP version supports it
			if( phpversion() >= 5.4 ){
				$newsettings = json_encode($newsettings,JSON_PRETTY_PRINT);
			}else{
				$newsettings = json_encode($newsettings);
			}
		
		//Write new settingsfile
		return file_put_contents($settingsfile, $newsettings);
	}

	/**
	 * [copycakedir description]
	 * @return bool Result of this action
	 */
	private function copycakedir(){

		$folder = new Folder($this->job_directory);
		return $folder->copy(array(
		    'from' => ROOT, // will cause a cd() to occur
		    'to' => $this->job_directory.DS.'www',
		    'mode' => 0755,
		    'skip' => array('Cakedesktop', '.git'),
		    'scheme' => Folder::OVERWRITE  // Skip directories/files that already exist
		));
		
	}

	/**
	 * [removehtaccess description]
	 * @return bool Result of this action
	 */
	private function removehtaccess(){

		$basedir = $this->job_directory.DS.'www'.DS;

		//Delete the 3 .htacces files:
		unlink($basedir.'.htaccess');
		unlink($basedir.'app'.DS.'.htaccess');
		unlink($basedir.'app'.DS.'webroot'.DS.'.htaccess');

		return true;
	}

	/**
	 * [removehtaccess description]
	 * @return bool Result of this action
	 */
	private function editcore(){

		//Define dirs
		$configdir   = $this->job_directory.DS.'www'.DS.'app'.DS.'Config'.DS;
		
		/*
		Core.php
		 */
		$corephpfile = file_get_contents($configdir.'core.php');

			//Replace lines TODO: need to do this better
			
			//TODO: Set debug to 0
			//Regex to set debug to 0

			//Set baseUrl
			$corephpfile = str_replace("//Configure::write('App.baseUrl', env('SCRIPT_NAME'));","Configure::write('App.baseUrl', '/index.php');",$corephpfile);

			//Set default timezone
			$corephpfile = str_replace("//date_default_timezone_set('UTC');","date_default_timezone_set('UTC');",$corephpfile);

		//Rewrite the file:
		file_put_contents($configdir.'core.php', $corephpfile);

		/*
		Bootstrap.php
		 */
		$bootstrapfile = file_get_contents($configdir.'bootstrap.php');
		
			//Remove this plugin
			$bootstrapfile = str_replace("CakePlugin::load('Cakedesktop');","",$bootstrapfile);

		//Rewrite the file:
		file_put_contents($configdir.'bootstrap.php', $bootstrapfile);

		return true;
	}


	/**
	 * [createmysqldump description]
	 * @return bool Result of this action
	 */
	private function createmysqldump(){

		$this->sql = $this->Database->createsql();
		
		return true;
	}

	/**
	 * [createmysqldump description]
	 * @return bool Result of this action
	 */
	private function converttosqlite(){

		$this->sqlite = $this->Database->converttosqlite($this->sql);

		return true;
	}

	/**
	 * [removehtaccess description]
	 * @return bool Result of this action
	 */
	private function editdatabaseconfig(){

		//Define dirs
		$configdir   = $this->job_directory.DS.'www'.DS.'app'.DS.'Config'.DS;
		
		//Get current database name:
		App::uses('ConnectionManager', 'Model');
		$dataSource = ConnectionManager::getDataSource('default');
		$databasename = $dataSource->config['database'];

		$this->databasename = $databasename; //Need this later

		/*
		Database.php
		 */		
		$newdatabasefile = <<<EOD
<?php
class DATABASE_CONFIG {

	public \$default = array(
		'datasource' => 'Database/Sqlite',
		'host' => 'localhost',
		'login' => '',
		'password' => '',
		'database' => '$databasename'
	);
}
?>
EOD;

		file_put_contents($configdir.'database.php', $newdatabasefile );

		return true;
	}


	/**
	 * [createsqlitedb description]
	 * @return bool Result of this action
	 */
	private function createsqlitedb(){

		//Create the database
		$db = new SQLite3($this->job_directory.DS.'www'.DS.$this->databasename);
		$db->exec($this->sqlite);

		//TODO: LOTS OF DEBUGGING AND ERROR CATCHING!

		return true;
	}

	/**
	 * [zipapplication description]
	 * @return [type] [description]
	 */
	private function zipapplication(){

		$this->zipfile = CakePlugin::path('Cakedesktop').'tmp'.DS.Inflector::slug($this->applicationname).'.zip';

		//Cleanup:
		if(file_exists($this->zipfile) ){
			unlink($this->zipfile);
		}

		$source 		= $this->job_directory.DS;
		$destination 	= $this->zipfile;

		 if (!extension_loaded('zip') || !file_exists($source)) {
	        return false;
	    }

	    $zip = new ZipArchive();
	    if (!$zip->open($destination, ZIPARCHIVE::CREATE)) {
	        return false;
	    }

	    //$source = str_replace('\\', '/', realpath($source));

	    if (is_dir($source) === true)
	    {
	        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($source), RecursiveIteratorIterator::SELF_FIRST);

	        foreach ($files as $file)
	        {
	            $file = str_replace('\\', '/', $file);

	            // Ignore "." and ".." folders
	            if( in_array(substr($file, strrpos($file, DS)+1), array('.', '..')) )
	                continue;

	            $file = realpath($file);

	            if (is_dir($file) === true)
	            {
	                $zip->addEmptyDir(str_replace($source, '', $file . DS));
	            	//echo str_replace($source . '/', '', $file . '/')."<br />";
	            }
	            else if (is_file($file) === true)
	            {
	                //$zip->addFromString(str_replace($source . '/', '', $file), file_get_contents($file));
	                $zip->addFile($file, str_replace($source, '', $file));
	                //echo str_replace($source . '/', '', $file)."<br />";
	            }
	        }
	    }
	    else if (is_file($source) === true)
	    {
	       $zip->addFromString(basename($source), file_get_contents($source));
	       //echo $source."<br />";
	    }

	    return $zip->close();
	}

	/**
	 * [cleanup description]
	 * @return [type] [description]
	 */
	private function cleanup(){

		$folder = new Folder($this->job_directory);
		return $folder->delete();
	}

	/**
	 * [cleanup description]
	 * @return [type] [description]
	 */
	public function servezipfile(){

		$this->response->file(
		    $this->zipfile,
		    array('download' => true, 'name' => Inflector::slug($this->applicationname).'.zip')
		);


		return $this->response;
	}


	/**
	 * Aux functions
	 */
	
	private function fixboolean($array){

		if (!is_array($array)) {
			if( $array == '1'){
				return true;
			}else if( $array == '0'){
				return false;
			}
			return $array;
		}

		$newArray = array();

		foreach ($array as $key => $value) {
			$newArray[$key] = $this->fixboolean($value);
		}

		return $newArray;
	}
}
