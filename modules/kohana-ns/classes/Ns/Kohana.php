<?php

class Ns_Kohana extends Kohana_Core {

	/**
	 * Changes the currently enabled modules. Module paths may be relative
	 * or absolute, but must point to a directory:
	 *
	 *     Kohana::modules(array('modules/foo', MODPATH.'bar'));
	 *
	 * @param   array   $modules    list of module paths
	 * @param   bool    $append     append mobule path to exisintg list instead of initializing it from scratch
	 * @return  array   enabled modules
	 */
	public static function modules(array $modules = NULL, $append = FALSE)
	{
		if ($modules === NULL)
		{
			// Not changing modules, just return the current set
			return Kohana::$_modules;
		}

		// Start a new list of include paths, APPPATH first
		if(!$append){
			$paths = array(APPPATH);
		} else {
			$paths = self::$_paths;
		}

		foreach ($modules as $name => $path)
		{
			if (is_dir($path))
			{
				if( ($key = array_search(rtrim($path , '/') . '/', $paths)) !== FALSE ){
					unset($paths[$key]);
				}

				// Add the module to include paths
				$paths[] = $modules[$name] = realpath($path).DIRECTORY_SEPARATOR;
			}
			else
			{
				// This module is invalid, remove it
				throw new \Kohana_Exception('Attempted to load an invalid or missing module \':module\' at \':path\'', array(
					':module' => $name,
					':path'   => Debug::path($path),
				));
			}
		}

		// Finish the include paths by adding SYSPATH
		if($append){
			// to prevent of double inserting SYSPATH in list
			if( ($key = array_search(rtrim(SYSPATH , '/') . '/', $paths)) !== FALSE ){
				unset($paths[$key]);
			}
		}
		$paths[] = SYSPATH;

		// Set the new include paths
		Kohana::$_paths = $paths;

		// Set the current module list
		Kohana::$_modules = $modules;

		foreach (Kohana::$_modules as $path)
		{
			$init = $path.'init'.EXT;

			if (is_file($init))
			{
				// Include the module initialization file once
				require_once $init;
			}
		}

		return Kohana::$_modules;
	}

/**
	 * Provides auto-loading support of classes that follow Kohana's [class
	 * naming conventions](kohana/conventions#class-names-and-file-location).
	 * See [Loading Classes](kohana/autoloading) for more information.
	 *
	 *     // Loads classes/My/Class/Name.php
	 *     Kohana::auto_load('My_Class_Name');
	 *
	 * or with a custom directory:
	 *
	 *     // Loads vendor/My/Class/Name.php
	 *     Kohana::auto_load('My_Class_Name', 'vendor');
	 *
	 * You should never have to call this function, as simply calling a class
	 * will cause it to be called.
	 *
	 * This function must be enabled as an autoloader in the bootstrap:
	 *
	 *     spl_autoload_register(array('Kohana', 'auto_load'));
	 *
	 * @param   string  $class      Class name
	 * @param   string  $directory  Directory to load from
	 * @return  boolean
	 */
	public static function auto_load_extended($class, $directory = 'classes')
	{
		// Transform the class name according to PSR-0
		$class     = ltrim($class, '\\');
		$file      = '';
		$namespace = '';

		if ($last_namespace_position = strripos($class, '\\'))
		{
			$module_position = strpos($class, '\\');
			$force_module = substr($class, 0, $module_position);
			$namespace = substr($class, 0, $last_namespace_position);
			$class     = substr($class, $module_position + 1);
			$force_module = strtolower($force_module);
			//$file      = str_replace('\\', DIRECTORY_SEPARATOR, $namespace).DIRECTORY_SEPARATOR;

		} else {
			$force_module = NULL;
		}

		$file .= str_replace('\\', '/', str_replace('_', DIRECTORY_SEPARATOR, $class));

		if ($path = Kohana::find_file_module($directory, $file, NULL, FALSE, $force_module))
		{
			// Load the class file
			require $path;
			// Class has been found
			return TRUE;
		}

		// Class is not in the filesystem
		return FALSE;
	}

	/**
	 * Searches for a file in the [Cascading Filesystem](kohana/files), and
	 * returns the path to the file that has the highest precedence, so that it
	 * can be included.
	 *
	 * When searching the "config", "messages", or "i18n" directories, or when
	 * the `$array` flag is set to true, an array of all the files that match
	 * that path in the [Cascading Filesystem](kohana/files) will be returned.
	 * These files will return arrays which must be merged together.
	 *
	 * If no extension is given, the default extension (`EXT` set in
	 * `index.php`) will be used.
	 *
	 *     // Returns an absolute path to views/template.php
	 *     Kohana::find_file('views', 'template');
	 *
	 *     // Returns an absolute path to media/css/style.css
	 *     Kohana::find_file('media', 'css/style', 'css');
	 *
	 *     // Returns an array of all the "mimes" configuration files
	 *     Kohana::find_file('config', 'mimes');
	 *
	 * @param   string  $dir    directory name (views, i18n, classes, extensions, etc.)
	 * @param   string  $file   filename with subdirectory
	 * @param   string  $ext    extension to search for
	 * @param   boolean $array  return an array of files?
	 * @param   boolean $force_module breaks cascade loading and force some module (using for namespaces)
	 * @return  array   a list of files when $array is TRUE
	 * @return  string  single file path
	 */
	public static function find_file_module($dir, $file, $ext = NULL, $array = FALSE, $force_module = NULL)
	{
		if ($ext === NULL)
		{
			// Use the default extension
			$ext = EXT;
		}
		elseif ($ext)
		{
			// Prefix the extension with a period
			$ext = ".{$ext}";
		}
		else
		{
			// Use no extension
			$ext = '';
		}

		// Create a partial path of the filename
		$path = $dir.DIRECTORY_SEPARATOR.$file.$ext;

		if($force_module) {
			$check_path = $force_module . '/' . $path;
		} else {
			$check_path = $path;
		}

		if($force_module) {
			if(isset(Kohana::$_modules[$force_module])) {
				$paths = array(Kohana::$_modules[$force_module]);
			} else {
					throw new \Kohana_Exception('Could not find namespace\'s required module :module',
						array(':module' => $force_module));
			}
		} else {
			$paths = Kohana::$_paths;
		}


		if (Kohana::$caching === TRUE AND isset(Kohana::$_files[$check_path.($array ? '_array' : '_path')]))
		{
			// This path has been cached
			return Kohana::$_files[$check_path.($array ? '_array' : '_path')];
		}

		if (Kohana::$profiling === TRUE AND class_exists('Profiler', FALSE))
		{
			// Start a new benchmark
			$benchmark = Profiler::start('Kohana', __FUNCTION__);
		}

		if ($array OR $dir === 'config' OR $dir === 'i18n' OR $dir === 'messages')
		{

			// Include paths must be searched in reverse
			$paths = array_reverse($paths);

			// Array of files that have been found
			$found = array();

			foreach ($paths as $dir)
			{

				if (is_file($dir.$path))
				{
					// This path has a file, add it to the list
					$found[] = $dir.$path;
				}
			}
		}
		else
		{
			// The file has not been found yet
			$found = FALSE;

			foreach ($paths as $dir)
			{
				if (is_file($dir.$path))
				{
					// A path has been found
					$found = $dir.$path;
					// Stop searching
					break;
				}
			}
		}

		if (Kohana::$caching === TRUE)
		{
			// Add the path to the cache
			Kohana::$_files[$check_path.($array ? '_array' : '_path')] = $found;

			// Files have been changed
			Kohana::$_files_changed = TRUE;
		}

		if (isset($benchmark))
		{
			// Stop the benchmark
			Profiler::stop($benchmark);
		}

		/*
					if(strpos($file, 'validation') !== FALSE ){
						var_dump($found);
					}
*/
		return $found;
	}

	/**
	 * Get a message from a file. Messages are arbitrary strings that are stored
	 * in the `messages/` directory and reference by a key. Translation is not
	 * performed on the returned values.  See [message files](kohana/files/messages)
	 * for more information.
	 *
	 * Extended version, to use find_file_module
	 *
	 *     // Get "username" from messages/text.php
	 *     $username = Kohana::message('text', 'username');
	 *
	 * @param   string  $file       file name
	 * @param   string  $path       key path to get
	 * @param   mixed   $default    default value if the path does not exist
	 * @return  string  message string for the given path
	 * @return  array   complete message list, when no path is specified
	 * @uses    Arr::merge
	 * @uses    Arr::path
	 */
	public static function message($file, $path = NULL, $default = NULL, $force_module = NULL)
	{
		if(!is_null($force_module)){
			$force_module = strtolower($force_module);
		}

		static $messages;

		if ( ! isset($messages[$file]))
		{
			// Create a new message list
			$messages[$file] = array();

			if ($files = Kohana::find_file_module('messages', $file, NULL, FALSE, $force_module))
			{
				foreach ($files as $f)
				{
					// Combine all the messages recursively
					$messages[$file] = Arr::merge($messages[$file], Kohana::load($f));
				}
			}
		}

		if ($path === NULL)
		{
			// Return all of the messages
			return $messages[$file];
		}
		else
		{
			// Get a message using the path
			return Arr::path($messages[$file], $path, $default);
		}
	}
}
