CakePHP-Cakedesktop
==================

##### Table of Contents  
* [Intro](#intro)  
* [Requirements](#requirements)  
* [Installation and setup](#installation)  
* [Usage](#usage) 

<a name="intro"/>
## Intro

Download full CakePHP webapplication as a full standalone Windows desktop application. Configure the packaged application to your needs before creating it.

<a name="requirements"/>
## Requirements

 * CakePHP >= 2.3
 * php-sqlite3
 * MySQL datasource as

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


