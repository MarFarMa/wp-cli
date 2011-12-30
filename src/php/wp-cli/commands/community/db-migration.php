<?php
WP_CLI::addCommand('db-migrate', 'DbMigrationCommand');

/**
 * The WP Database Migration
 *
 * @package wp-cli
 * @subpackage commands/community
 * @maintainer Pauli Price
 */
class DbMigrationCommand extends WP_CLI_Command {

public function __construct( $args, $assoc_args ) {
    $this->generate['core']   = array();
    $this->generate['plugin'] = array();
    $this->generate['theme']  = array();
    
    $this->apply['core']      = array();
    $this->apply['plugin']    = array();
    $this->apply['theme']     = array();
    
    // -- define and create default migration data directories
    //
    define('WP_CLI_DB_MIGRATION_DIR_NAME'
     , WP_CONTENT_DIR_NAME . '/migrations');
    define('WP_CLI_DB_MIGRATION_DIR'
     , WP_CONTENT_DIR_PATH . '/' . WP_CLI_DB_MIGRATION_DIR_NAME);
    define('WP_CLI_DB_MIGRATION_CORE_DIR'
     , WP_CLI_DB_MIGRATION_DIR . '/' . 'core');
    define('WP_CLI_DB_MIGRATION_PLUGIN_DIR'
     , WP_CLI_DB_MIGRATION_DIR . '/' . 'plugins');
    define('WP_CLI_DB_MIGRATION_THEME_DIR'
     , WP_CLI_DB_MIGRATION_DIR . '/' . 'themes');
     
    if (!@wp_mkdir_p(WP_CLI_DB_MIGRATION_CORE_DIR)) {
      WP_CLI::error(WP_CLI_DB_MIGRATION_CORE_DIR);
    }
    if (!@wp_mkdir_p(WP_CLI_DB_MIGRATION_PLUGIN_DIR)) {
      WP_CLI::error(WP_CLI_DB_MIGRATION_PLUGIN_DIR);
    }
    if (!@wp_mkdir_p(WP_CLI_DB_MIGRATION_THEME_DIR)) {
      WP_CLI::error(WP_CLI_DB_MIGRATION_THEME_DIR);
    }
     
    $this->ouput_dirs['core']   = WP_CLI_DB_MIGRATION_CORE_DIR;
    $this->ouput_dirs['plugin'] = WP_CLI_DB_MIGRATION_PLUGIN_DIR;
    $this->ouput_dirs['theme']  = WP_CLI_DB_MIGRATION_THEME_DIR;
    
    // Load all migration classes
    //
    foreach ( glob(WP_CLI_ROOT.'/commands/community/db-migrations/*.php') as $filename ) {
    	include $filename;
    }
		parent::__construct( $args, $assoc_args );
  
  }

	// TODO: dry out versus sql commands file
	/**
	 * return a string to connecting to the DB.
	 *
	 * @param void
	 * @return string $connect
	 */
	function connect_string() {
		$connect = sprintf( 'mysql --database=%s --user=%s --password=%s',
			DB_NAME, DB_USER, DB_PASSWORD);
		return $connect;
	}

	// TODO: dry out versus sql commands file
	/**
	 * A string for connecting to the DB.
	 *
	 * @param string $args
	 * @return void
	 */
	protected function connect( $args = array() ) {
		$connect = $this->connect_string();
	}
    
	/**
	 * Regester migration template generation hooks
	 *
	 * @param string $type
	 *        string $name
	 *        function $func
	 * @return void
	 */
  function add_migration_generator($type, $name, $func) {
    if (!is_array($this->generate[$type][$name]))
      $this->generate[$type][$name] = array();
    
    array_push($this->generate[$type][$name], $func);  
  }
  
	/**
	 * Regester migration hooks
	 *
	 * @param string $type
	 *        string $name
	 *        function $func
	 * @return void
	 */
  function add_migration($type, $name, $func) {  
    if (!is_array($this->apply[$type][$name]))
      $this->apply[$type][$name] = array();
    
    array_push($this->apply[$type][$name], $func);  
  }
  
	/**
	 * List registered migration hooks by type or by type and name
	 *
	 * @param array $args
	 *        array $assoc_args
	 * @return void
	 */
  function support($args, $assoc_args) { 
 
    if ( array_key_exists('type', $assoc_args) )
     $type = $assoc_args['type'];
    else
     $type = 'core';    
   
    // Print the header
    WP_CLI::line();     
    WP_CLI::line('Installed migration support:');  
    WP_CLI::line(); 
     
    if ( array_key_exists( 'name', $assoc_args ) ) {
         
      $name = $assoc_args['name'];
      $generators_found = true;
      $migrations_found = true;
      
      if (array_key_exists($type, $this->generate)) {         
        $generators_found = $this->list_hooks('generate',$type,$name);
      } else {
        $generators_found = false;
      } 
      if ($generators_found == false) {
    	  WP_CLI::line('No Generations found for type: '.$type.' and name: '.$name);
      }
      
      if (array_key_exists($type, $this->apply)) {
        $migrations_found = $this->list_hooks('migrate',$type,$name);
      } else {
        $migrations_found = false;
      }
      if ($migrations_found == false) {
      	 WP_CLI::line('No Migrations found for type: '.$type.' and name: '.$name);
      }

     } else {
       
       if (array_key_exists($type, $this->generate)) {
         if (count($this->generate[$type]) != 0 ) {
           foreach(array_keys($this->generate[$type]) as $name) {       
             $this->list_hooks("generate",$type,$name);
           }
         }
       } else {
         	 WP_CLI::line('No Generations found for type: '.$type);
       } 
          
       if (array_key_exists($type, $this->apply)) {
         if (count($this->apply[$type]) != 0 ) {
           foreach(array_keys($this->apply[$type]) as $name) {       
             $this->list_hooks("migrate",$type,$name);
           } 
         } 
       } else {
       	 WP_CLI::line('No Migrations found for type: '.$type);
       }
        
     }
   	 // Print the footer
   	 WP_CLI::line();
  }
  
	/**
	 * Generate migration template by type and name
	 *
	 * @param array $args
	 *        array $assoc_args
	 * @return void
	 */
  function generate($args, $assoc_args) { 
 
    if ( array_key_exists('type', $assoc_args) )
     $type = $assoc_args['type'];
    else {
      WP_CLI::line( '<type> missing.  Usage: wp db-migrate generate --type=<plugin|theme|core> --name=<name>' ); 
      exit; 
    }
   
    if ( array_key_exists('name', $assoc_args) )
     $name = $assoc_args['name'];
    else {
      WP_CLI::line( '<name> missing.  Usage: wp db-migrate generate --type=<plugin|theme|core> --name=<name>' ); 
      exit; 
    }
    
    $generators_found = false;
      
    if (array_key_exists($type, $this->generate)) {         
      if (array_key_exists($name, $this->generate[$type])) {
        $generators_found = true;
      	$dumper = new sfYamlDumper();
        
        foreach ( $this->generate[$type][$name] as $gen)
          $data['default'] = call_user_func($gen); 
      	  $default = $dumper->dump($data,7);
          $filename = $this->ouput_dirs[$type]. '/' . $gen[0].'.yaml';
          $fh = fopen($filename, 'w') or die("There was an error, accessing the requested file.");
          fwrite($fh, $default);
          fclose($fh);
          
          // print_r($filename);
          // print "\n";
          // print "$default\n";                 
      }
    }
      
    if ($generators_found == false) {
  	  WP_CLI::line('No Generations found for type: '.$type.' and name: '.$name);
    }
 
  }

	/**
	 * List registered migration hooks by type and name, called by support command
	 *
	 * @param string $kind - generate or migrate
	 *        string $type - primary array key
	 *        string $name - secondary array key
	 * @return void
	 */
  private function list_hooks($kind, $type, $name) {
    
    if ($kind == "generate") { 
      if (array_key_exists($name, $this->generate[$type])) {
        foreach($this->generate[$type][$name] as $hook) { 
          WP_CLI::line( 'Generator: '.$type.' '.$name.' '.$hook ); 
        } 
      } else {
        return false;
      }
    } else { 
      if (array_key_exists($name, $this->apply[$type])) {
        foreach($this->apply[$type][$name] as $hook) {  
          WP_CLI::line( 'Migration: '.$type.' '.$name.' '.$hook ); 
        } 
      } else {
        return false;
      }
    }
  
  }
  
	/**
	 * Help function for this command
	 */
	public static function help() {
		WP_CLI::line( <<<EOB
      usage: wp migration <sub-command> [--type <plugin|theme|core>] [--name <name>] [--path <directory-path>] [--file <filename>] [--stage <stage-name>]

      Available sub-commands:
         support        list of migration callbacks registered
           --type       category of migration, either plugin, theme, or core.  Defaults to core, if not specified
           --name       name of migration.  Defaults to all for specified type
                 
         generate       create a template migration file reflecting current settings
           --type       category of migration, either plugin, theme, or core.  Defaults to core, if not specified
           --name       name of migration to apply.  Defaults to all for specified type
           
         dry-run        list migration files to be applied, by type and stage
           --type       category of migration, either plugin, theme, or core.  Defaults to core, if not specified
           --name       name of migration to apply.  Defaults to all for specified type
           --stage      specifies the migration file section to process.  If not provided, migration will use the default section.  If no section for <stage-name> is found "nothing to do for stage='<stage-name>'" will be reported. 
                 
         apply           update the wp database according to the contents of the migration file(s) specified
           --type       category of migration, either plugin, theme, or core.  Defaults to core, if not specified
           --name       name of migration to apply.  Defaults to all for specified type
           --stage      specifies the migration file section to process.  If not provided, migration will use the default section.  If no section for <stage-name> is found "nothing to do for stage='<stage-name>'" will be reported. 
EOB
	);
	}
}

class WP_CLI_Migration {
   
  /**
   * Generate current settings of a plugin
   *
   * @param array $args
   */
  function generate() {
    // virtual function - create in subclass
  	// return array of migration data
  }


  /**
   * Import settings of a plugin
   *
   * @param array $args
   */
  function migrate($args) {
    // virtual function - create in subclass
    // receives an array of migration data
  }

}

