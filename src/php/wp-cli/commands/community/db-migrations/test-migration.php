<?php

/**
 * Generate current settings of a plugin
 *
 * @param array $args
 */
function test_generate() {
	list( $file, $name ) = $this->parse_name( $args, __FUNCTION__ );
	$dumper = new sfYamlDumper();
	$exec = $this->connect_string();
	$query = "SELECT * FROM wp_rg_form";
  $result = mysql_query($query);
	
  while ($line = mysql_fetch_array($result))
  {
	  $yaml = $dumper->dump($line);
    print "$yaml\n";
  }
	// return YAML data
}


/**
 * Import settings of a plugin
 *
 * @param array $args
 */
function test_migrate(){
	list( $file, $name ) = $this->parse_name( $args, __FUNCTION__ );

	if ( is_plugin_active( $file ) ) {
		$this->deactivate( $args );
		// import YAML data
		$this->activate( $args );
	}

}

DbMigrationCommand::add_migration_generator('theme', 'corporate', 'test_generate');  
DbMigrationCommand::add_migration('theme', 'corporate', 'test_migrate');  
  
