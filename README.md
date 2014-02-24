CakePHP-Cakedesktop
==================

##### Table of Contents  
* [Intro](#intro)  
* [Requirements](#requirements)  
* [Installation and setup](#installation)  
* [Usage](#usage) 

<a name="intro"/>
## Intro

Download a complete CakePHP webapplication as a full standalone Windows (XP, 7 or 8) desktop application. Configure the packaged application to your needs before creating it using the simple webinterface. The aim is to supply a pure PHP application packager, therefore no shellscripts or exec() functions are used.

Some key features:

 * Generated applications settings are customizable via webinterface
 * MySQL database is converted to local Sqlite database
 * Loaded Php extensions on webserver are also loaded in generated applications php.ini (if available) for maximized compatibility
 * Plugin assets (js and css) are also used correctly. You application will look, feel and work extactly like you webapplication.

This plugin is in development. Some things like the MySQL to Sqlite database conversion may need work.

The awesome [phpdesktop](https://code.google.com/p/phpdesktop/) project is used to provide the standalone Windows enviroment. The Chrome-driven variant is used for this plugin.

<a name="requirements"/>
## Requirements

 * CakePHP >= 2.3
 * php-sqlite3 extension
 * MySQL datasource for current webapplication (More datasource support comming)
 * Plugin assets are included

The packaged application is running a Mongoose webserver, PHP 5.4 and uses Chromium (Chromium Emebeded Framework) to view the app. Read more about this on the phpdesktop website. Currently using the 5.4 version to keep Windows XP compatible.

<a name="installation"/>
## Installation and Setup

1. Check out a copy of the Cakedesktop CakePHP plugin from the repository using Git :

	`git clone http://github.com/stefanvangastel/CakePHP-Cakedesktop.git`

	or download the archive from Github: 

	`https://github.com/stefanvangastel/CakePHP-Cakedesktop/archive/master.zip`

	You must place the Cakedesktop CakePHP plugin within your CakePHP 2.x app/Plugin directory.
	
	or load it with composer:
	
	`"stefanvangastel/cakedesktop": "dev-master"`

2. Make sure app/Plugin/Cakedesktop/tmp is writable by the webserver user.

3. Load the plugin in app/Config/bootstrap.php:

	`CakePlugin::load('Cakedesktop');`

<a name="usage"/>
## Usage

Open http(s)://yourapp.com/cakedesktop/ and create your application!

The application is served as a zip file containing all the stuff you need to run your application. Extract the contents and run the .exe file provided.