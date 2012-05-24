<?php 
/*
Plugin Name: File Un-Attach
Plugin URI: http://www.xparkmedia.com
Description: Attach multiple file to a post and unattch them also.
Author: Hafid R. Trujillo Huizar
Version: 1.0.0
Author URI: http://www.xparkmedia.com
Requires at least: 3.1.0
Tested up to: 3.4.0

Copyright 2010-2011 by Hafid Trujillo http://www.xparkmedia.com

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License,or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not,write to the Free Software
Foundation,Inc.,51 Franklin St,Fifth Floor,Boston,MA 02110-1301 USA
*/ 


// Stop direct access of the file
if(preg_match( '#'.basename(__FILE__) . '#',$_SERVER['PHP_SELF'])) die();
	
if(!class_exists( 'FileUnattach')){

class FileUnattach{
	
	/**
	*Variables
	*
	*@param $domain plugin Gallery IDentifier
	*Make sure that new language(.mo) files have 'fua-' as base name
	*/
	var $domain	= 'fun';
	var $version	= '1.0.0';
	
	/**
	*Constructor
	*
	*@return void
	*@since 0.5.0 
	*/
	function __construct(){
		$this->load_text_domain();
		$this->define_constant();
		$this->load_dependencies();
	}
	
	/**
	*Define contant variables
	*
	*@return void
	*@since 0.5.0 
	*/
	function define_constant(){
		ob_start(); //fix redirection problems
		define( 'FUNATTACH_FILE_NAME',plugin_basename(__FILE__));
		define( 'FUNATTACH_FOLDER',plugin_basename(dirname(__FILE__)));
		define( 'FUNATTACH_ABSPATH',str_replace("\\","/",dirname(__FILE__)));
		define( 'FUNATTACH_URL',WP_PLUGIN_URL."/".FUNATTACH_FOLDER."/");
	}
	
	/**
	*Register localization/language file
	*
	*@return void
	*@since 0.5.0 
	*/
	function load_text_domain(){
		$locale 	= get_locale( );
		$filedir 	= WP_CONTENT_DIR . '/languages/_fun/'. $this->domain . '-' . $locale . '.mo';
		if( function_exists( 'load_plugin_textdomain' ) )
			load_plugin_textdomain( $this->domain , false, '../languages/_fun/' );
		elseif( function_exists( 'load_textdomain' ) )
			load_textdomain( $this->domain, $filedir );
	}
	
	/**
	*Load what is needed where is needed
	*
	*@return void
	*@since 0.5.0 
	*/
	function load_dependencies( ){
		if( is_admin() && !class_exists( 'FunAdmin' ))
			require_once( FUNATTACH_ABSPATH . '/admin.php');
		elseif( !is_admin() && !class_exists( 'FunFront'))
			require_once( FUNATTACH_ABSPATH . '/front.php');
	}
		
}

// Do that thing you do!!!
global $FileUnattach;
$FileUnattach = new FileUnattach();
}
?>