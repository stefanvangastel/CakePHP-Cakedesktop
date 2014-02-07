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
		//Render options view
	}

	/**
	 * Creates a desktop app of the current CakePHP application
	 */
	public function createdesktopapp(){

		/**
		 * Steps:
		 *
		 * 0. Retrieve options
		 * 
		 * 1. Copy phpdesktop skeleton dir to /tmp/<rand>
		 * 2. Copy entire CakePHP directory to /tmp/<rand>/www/
		 * 3. Remove .htaccess files
		 * 4. Edit core.php and bootstrap.php to disable url rewrite and remove this plugin
		 *
		 * 5. Dump MySQL database
		 * 6. Convert SQL to Sqlite compatible SQL
		 * 7. Edit database.php to activate Sqlite
		 * 8. Import database structure in Sqlite
		 *
		 * 9. Zip package
		 * 10. Cleanup job dir
		 * 11. Serve package
		 */
		
		//Set values:
		ini_set('max_execution_time', 120);
		ini_set('memory_limit', '256M');

		//Create job_id
		$this->job_id = time().'_'.rand(1000,9999);
		//$this->job_id = '1234'; //DEV TMP

		//Create job directory
		$this->job_directory = CakePlugin::path('Cakedesktop').'tmp'.DS.$this->job_id;		
		mkdir($this->job_directory);

		//Step 1
		$this->timerstart();
			$this->copyskeletondir();
		$this->timerstop("1. Copy skel dir");

		//Step 2
		$this->timerstart();
			$this->copycakedir();
		$this->timerstop("2. Copy Cake dir");

		//Step 3
		$this->timerstart();
			$this->removehtaccess();
		$this->timerstop("3. Remove .htacces files");

		//Step 4
		$this->timerstart();
			$this->editcore();
		$this->timerstop("4. Edit core.php");

		//Step 5
		$this->timerstart();
			$this->createmysqldump();
		$this->timerstop("5. Create mysql dump");

		//Step 6
		$this->timerstart();
			$this->converttosqlite();
		$this->timerstop("6. Convert to sqlite"); 

		//Step 7
		$this->timerstart();
			$this->editdatabaseconfig();
		$this->timerstop("7. Edit databaseconfig"); 

		//Step 8
		$this->timerstart();
			$this->createsqlitedb();
		$this->timerstop("8. Create sqlitedb"); 

		//Step 9
		$this->timerstart();
			$this->zipapplication();
		$this->timerstop("9. Zip application"); 

		//Step 10
		$this->timerstart();
			$this->cleanup();
		$this->timerstop("10. Cleanup"); 

		//Step 11
		return $this->servezipfile();
	}

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

		$this->zipfile = CakePlugin::path('Cakedesktop').'tmp'.DS.'desktopapplication.zip';

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
		    array('download' => true, 'name' => 'desktopapplication.zip')
		);


		return $this->response;
	}






	/*
	TMP FUNCTIONS ==============================================================
	 */

	function timerstart(){
		$this->starttime = time();
	}
	
	function timerstop($msg = ''){

		$time = time() - $this->starttime;

		echo "<pre>$msg | Time: $time s</pre>";
	}
	
}
