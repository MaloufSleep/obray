# Obray

Obray is lightweight PHP object oriented MVC framework designed to help you write less code and do more quickly.

## Installation

### Setup Apache

To install Obray prototype demo application on a typical Apache configuration create a site and use this example to create your configuration file:

	<VirtualHost *:80>
        ServerAdmin yoursupportemail@example.com
        ServerName yourservername.com
        DocumentRoot /yourpath/obray/prototype
        
        <Directory /yourpath/obray/prototype >
                
                Options Indexes FollowSymLinks MultiViews
                AllowOverride None
                #Order allow,deny
                #allow from all

                DirectoryIndex obray.php index.php

                <IfModule mod_rewrite.c>
                        RewriteEngine On
                        RewriteBase /
                        RewriteCond %{REQUEST_FILENAME} !-f
                        RewriteCond %{REQUEST_FILENAME} !-d
                        RewriteRule ^.+$ obray.php [QSA,L]
                </IfModule>

        </Directory>

    </VirtualHost>

Restart Apache.

### Configuration

Next you'll want to modify your settings file to accomodate your server settings.  Here's breif explaination of each:

#### Basic Settings

	define('__APP__','prototype');							// The name of your application
	define('__SELF__', dirname(__FILE__).'/');              // This should contain the path to your application
	define('__PATH_TO_CORE__','/yourpath/obray/core/');		// The path to obray's core files
	define('__DebugMode__',TRUE);							// Enable or Disable debug mode.  In debug mode things like ?refresh will write tables and rebuild resources
	
#### Route Settings	

This is where you are going to define valid routes in your application.  A route is a shortcut to a path that contains the classes you would like to make available to your application.  In general you should always disable system routes and extend the classes you need.  This will allow you to add or remove functionality from the core that your application needs.  For security it's recommended you only place here paths to classes you'd like to give access and not the whole application directory __SELF__.

	define('__ROUTES__',serialize( array( 
	
		// Custom Routes
		"lib" => __SELF__ . "/lib/"
		
		// System Routes - generally only uncomment these if you are going to debug obray core files
		// "cmd" => __SELF__,
		// "core" => __PATH_TO_CORE__
	) ));

#### Database settings	

Place your basic database settings here

	define('__DBHost__','localhost');						// database server host
	define('__DBPort__','3306');							// database server port
	define('__DBUserName__','yourusername');				// database username
	define('__DBPassword__','yourpassword');				// database password
	define('__DB__','dbname');								// database name
	define('__DBEngine__','MyISAM');						// database engine
	define('__DBCharSet__','utf8');							// database characterset (default: utf8)
	
Below are the definitions of the basic datatypes available when creating a table definition in and ODBO class.  You can add more below, remove ones you don't need, or make changes you do need.

	define ("__DATATYPES__", serialize (array (
	//	data_type	    SQL TO SCRIPT TABLE										My SQL Datatypes for verification	Regex to validate values
	    "varchar"   =>  array("sql"=>" VARCHAR(size) COLLATE utf8_general_ci ",	"my_sql_type"=>"varchar(size)",		"validation_regex"=>""),
	    "text"      =>  array("sql"=>" TEXT COLLATE utf8_general_ci ",			"my_sql_type"=>"text",				"validation_regex"=>""),
	    "integer"   =>  array("sql"=>" int ",									"my_sql_type"=>"int(11)",			"validation_regex"=>"/^([0-9])*$/"),
	    "float"     =>  array("sql"=>" float ",									"my_sql_type"=>"float",				"validation_regex"=>"/[0-9\.]*/"),
	    "boolean"   =>  array("sql"=>" boolean ",								"my_sql_type"=>"boolean",			"validation_regex"=>""),
	    "datetime"  =>  array("sql"=>" datetime ",								"my_sql_type"=>"datetime",			"validation_regex"=>""),
	    "password"  =>  array("sql"=>" varchar(255) ",							"my_sql_type"=>"varchar(255)",		"validation_regex"=>"")
	)));

## Introduction

Obray allows you to map PHP objects directly to URIs even from within your application!  To do this every object in Obray extends the OObject class, for example:

	
	Class MyClass1 extends OObject{
		
		public permissions = array(
			"firstFunction" => "any"
		);
	
		public function firstFunction($params){
			
			$this->result = $params["a"] + $params["b"];
			
		}
		
	}

	Class MyClass2 extends OObject{
	
		public function secondFunction(){
		
			$params = array("a"=>1,"b"=>1);
			$my_instance_1 = $this->route('/lib/MyClass1/firstFunction/',$params);  // instantiate instance of MyClass1 and call firstFunction and return the object
			$this->result_1 = $my_instance_1->result;
			
			$params = array("a"=>1,"b"=>2);
			$my_instance_2 = $this->route('/lib/MyClass1/');						// just initiate an instance of MyClass1
			$my_instance_2->route('/firstFunction/',$params);						// call the firstFunction function through route
			$my_instance_2->firstFunction($params);									// call the firstFunction function direction from the object
			
			$this->result_2 = $my_instance_2->result;
			
		}
	
	}


From within an OObject class you can access any path defined as a __ROUTES__ in your settings file.  In this case the "lib" path and any of it's public methods.

## ORouter

ORouter's job is to handle an HTTP request by converting into a routable path and returning the object in an output format such JSON with appropriate status codes and HTTP headers.  For example if you put this in your browser address bar:


	http://www.myobrayapplication.com/lib/MyClass/firstFunction/?a=1&b=2


you will get the response (JSON is the default output format from ORouter):


	{
		"object":"MyClass",
		"result_1":2
		"result_2":3
	}


External HTTP requests that are handled by ORouter are restricted not only by public/private declarations, but also by the permissions array. Unless you explicitely define a functions permissions in the permissions array a request through ORouter will be blocked with 404 Not Found status code.  If an attempt is made to a URI where the permissions are not sufficient you will recieve a 403 Forbidden error.  This way unless the resource is explicity defined with permissions an object can be hidden from ORouter and completely inaccessable except from within an OObject in PHP code.

## ODBO - database access

ODBO (Obray Database Object) is a database abstraction layer that will allow you to create basic database interactions quickly.  This works by defining a tabe_definition which can be used to infur DB interactions SELECT, INSERT, UPDATE, DELETE through 4 basic functions get, add, update, delete respectively.

This looks like the following:

	Class MyDBClass extends ODBO{
	
		private $table_name = "contacts";
		private $table_definition = array(
			"id" => array( "primary_key"=>TRUE ),
			"first_name" => 	array( "data_type"=>"varchar(100)", "required"=>TRUE ),
			"last_name" => 		array( "data_type"=>"varchar(100)",	"required"=>TRUE ),
			"email_address" => 	array( "data_type"=>"varchar(100)",	"required"=>TRUE ),
			"home_phone" => 	array( "data_type"=>"varchar(100)",	"required"=>FALSE )
		);
	
	}

Once you've defined you table_definition you can now call the available public methods:

	$obj = $this->route('/lib/MyDBClass/add/?first_name=John&last_name=Smith&email=johnsmith@example.com');	// add John to the database
	$obj->update(array( "first_name"=>"Johnny" ));															// update his name to Johnny
	$obj->delete();																							// delete Johnny
	
You can also overwrite methods to enhance the default functionality while still maintaining everything existing:

	Class MyDBClass extends ODBO{
	
		private $table_name = "contacts";
		private $table_definition = array(
			"id" => array( "primary_key"=>TRUE ),
			"first_name" => 	array( "data_type"=>"varchar(100)", "required"=>TRUE ),
			"last_name" => 		array( "data_type"=>"varchar(100)",	"required"=>TRUE ),
			"email_address" => 	array( "data_type"=>"varchar(100)",	"required"=>TRUE ),
			"home_phone" => 	array( "data_type"=>"varchar(100)",	"required"=>FALSE )
		);
		
		public function add($params){
			
			// do some pre-database processing here
			
			parent::add($params);
			
			// do some post-database processing here
			
		}
	
	}
	
When you query existing data using get the data gets put into $this->data as an array:

	$this->get();	// gets all records in the table and puts them in $this->data[]
	print_r($this->data);
	
You can also use OObjects extended query string syntax to extract more precise queries:

	$this->route('/get/?first_name=John|Johnny');	// get all records with the first_name 'John' OR 'Johnny'
	print_r($this->data);
	


