<?php defined('SYSPATH') or die('No direct script access.');

// -- Environment setup --------------------------------------------------------

// PSR-0
require VENDORPATH.'autoload'.EXT;

// Load the core Kohana class from kohanaext module :)
require SYSPATH.'classes/Kohana/Core'.EXT;

require MODPATH.'kohana-ns/classes/Ns/Kohana'.EXT;
require MODPATH.'kohana-ns/classes/Kohana'.EXT;

/**
 * Set the default time zone.
 *
 * @link http://kohanaframework.org/guide/using.configuration
 * @link http://www.php.net/manual/timezones
 */
date_default_timezone_set('Europe/Moscow');

/**
 * Set the default locale.
 *
 * @link http://kohanaframework.org/guide/using.configuration
 * @link http://www.php.net/manual/function.setlocale
 */
setlocale(LC_ALL, 'en_US.utf-8');

/**
 * Enable modules. Modules are referenced by a relative or absolute path.
 */
Kohana::modules(array(
	// application modules
	'kohana-ext' => MODPATH . 'kohana-ext',
	'kohana-ns' => MODPATH . 'kohana-ns',
	));

/**
 * Enable the Kohana auto-loader.
 *
 * @link http://kohanaframework.org/guide/using.autoloading
 * @link http://www.php.net/manual/function.spl-autoload-register
 */

// autoloading with extended support of namespaces in controllers + modules
spl_autoload_register(array('Kohana', 'auto_load_extended'));

/**
 * Set the default language
 */
I18n::lang('en-us');

Kohana::modules(array(
	'common' => MODPATH . 'common',
	'frontend' => MODPATH . 'frontend',
	'cli' => MODPATH . 'cli',
	'auth'       => MODPATH.'auth',       // Basic authentication
	'database'   => MODPATH.'database',   // Database access
	'orm'        => MODPATH.'orm',        // Object Relationship Mapping
	'formo'      => MODPATH.'kohana-formo',
	'pagination' => MODPATH.'kohana-pagination',
	'datatools'   => MODPATH.'kohana-datatools',
	'email'   => MODPATH.'kohana-email',
  'minion'     => MODPATH.'minion',     // CLI Tasks
  'acl'      => MODPATH.'kohana-acl',
	'csv'      => MODPATH.'CSV',

	// 'cache'      => MODPATH.'cache',      // Caching with multiple backends
	// 'codebench'  => MODPATH.'codebench',  // Benchmarking tool

	// 'image'      => MODPATH.'image',      // Image manipulation
	// 'minion'     => MODPATH.'minion',     // CLI Tasks
	// 'unittest'   => MODPATH.'unittest',   // Unit testing
	// 'userguide'  => MODPATH.'userguide',  // User guide and API documentation
), TRUE);


/**
 * Optionally, you can enable a compatibility auto-loader for use with
 * older modules that have not been updated for PSR-0.
 *
 * It is recommended to not enable this unless absolutely necessary.
 */
//spl_autoload_register(array('Kohana', 'auto_load_lowercase'));

/**
 * Enable the Kohana auto-loader for unserialization.
 *
 * @link http://www.php.net/manual/function.spl-autoload-call
 * @link http://www.php.net/manual/var.configuration#unserialize-callback-func
 */
ini_set('unserialize_callback_func', 'spl_autoload_call');


/**
 * Set the mb_substitute_character to "none"
 *
 * @link http://www.php.net/manual/function.mb-substitute-character.php
 */
mb_substitute_character('none');

// -- Configuration and initialization -----------------------------------------

if (isset($_SERVER['SERVER_PROTOCOL']))
{
	// Replace the default protocol.
	HTTP::$protocol = $_SERVER['SERVER_PROTOCOL'];
}

/**
 * Set Kohana::$environment if a 'KOHANA_ENV' environment variable has been supplied.
 *
 * Note: If you supply an invalid environment name, a PHP warning will be thrown
 * saying "Couldn't find constant Kohana::<INVALID_ENV_NAME>"
 */
if (getenv('ENVIRONMENT'))
{
	Kohana::$environment = constant('Kohana::'.strtoupper(getenv('ENVIRONMENT')));
}

switch (getenv('LOCAL_ENVIRONMENT'))
{
	case ('manro_sandbox'):
		Database::$default = 'manro_sandbox';
	break;
	case ('staging'):
		Database::$default = 'staging';
	break;
	default:
		Database::$default = 'production';
	break;
}


/**
 * Initialize Kohana, setting the default options.
 *
 * The following options are available:
 *
 * - string   base_url    path, and optionally domain, of your application   NULL
 * - string   index_file  name of your index file, usually "index.php"       index.php
 * - string   charset     internal character set used for input and output   utf-8
 * - string   cache_dir   set the internal cache directory                   APPPATH/cache
 * - integer  cache_life  lifetime, in seconds, of items cached              60
 * - boolean  errors      enable or disable error handling                   TRUE
 * - boolean  profile     enable or disable internal profiling               TRUE
 * - boolean  caching     enable or disable internal caching                 FALSE
 * - boolean  expose      set the X-Powered-By header                        FALSE
 */
Kohana::init(array(
	'base_url'   => '/',
	'index_file' => false,
	'profile' => (Kohana::$environment != Kohana::PRODUCTION)
));


/**
 * Attach the file write to logging. Multiple writers are supported.
 */
Kohana::$log->attach(new Log_File(APPPATH.'logs'));

/**
 * Attach a file reader to config. Multiple readers are supported.
 */
Kohana::$config->attach(new Config_File);
Kohana::$config->attach(new Config_Database());

/**
 * Cookie Salt
 *
 * @see http://kohanaframework.org/3.3/guide/kohana/cookies If you have not defined a cookie salt in your Cookie class
 * then
 * uncomment the line below and define a preferrably long salt.
 */
	Cookie::$salt = 'shochuodoophai4oheeweeceYovafupoh7Lievig';

// routes configuration
require (MODPATH.'common/config/routes'.EXT);
//Kohana::$config->load('routes');

/**
 * Set the routes. Each route must have a minimum of a name, a URI and a set of
 * defaults for the URI.
 */
define('APP_BUILD', file_exists(ROOTPATH . 'version.txt') && is_readable(ROOTPATH . 'version.txt')? file_get_contents(ROOTPATH . 'version.txt') : 'undefined');
