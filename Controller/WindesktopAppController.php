<?php
/**
 * Class WindesktopAppController
 *
 * Contains some reusable functions
 */
class WindesktopAppController extends AppController {

	/**
	 * Copy a file, or recursively copy a folder and its contents
	 * @param       string   $source    Source path
	 * @param       string   $dest      Destination path
	 * @param       string   $permissions New folder creation permissions
	 * @return      bool     Returns true on success, false on failure
	 */
	public function xcopy($source, $dest, $permissions = 0755)
	{
	    // Check for symlinks
	    if (is_link($source)) {
	        return symlink(readlink($source), $dest);
	    }

	    // Simple copy for a file
	    if (is_file($source)) {
	        return copy($source, $dest);
	    }

	    // Make destination directory
	    if (!is_dir($dest)) {
	        mkdir($dest, $permissions);
	    }

	    // Loop through the folder
	    $dir = dir($source);
	    while (false !== $entry = $dir->read()) {
	        // Skip pointers
	        if ($entry == '.' || $entry == '..') {
	            continue;
	        }

	      	if(stristr($entry, 'Windesktop')){
	      		continue;
	      	}

	        // Deep copy directories
	        $this->xcopy("$source/$entry", "$dest/$entry");
	    }

	    // Clean up
	    $dir->close();
	    return true;
	}


	/**
	 * Recursive delete a dir
	 * @param       string   $dir    Source path
	 * @return      bool     Returns true on success, false on failure
	 */
	public function rrmdir($dir) { 
		if (is_dir($dir)) { 
			$objects = scandir($dir); 
			foreach ($objects as $object) { 
				if ($object != "." && $object != "..") { 
					if (filetype($dir."/".$object) == "dir"){
						$this->rrmdir($dir."/".$object);
					}else{
						unlink($dir."/".$object);
					} 
				} 
			} 
			reset($objects); 
			rmdir($dir); 
		}

		return true;
	} 

	public function createzip($source, $destination){
	    
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

}