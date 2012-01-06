<?php
class TestMigration extends WP_CLI_Migration {
	
	public static $type = 'theme';
	public static $name = 'corporate';
	
	/**
	 * Generate current settings of a plugin
	 *
	 * @param array $args
	 */
	function generate() {
		$exec = DbMigrationCommand::connect_string();
		$query = "SELECT * FROM wp_rg_form";
		$result = mysql_query($query);
	
		while ($line = mysql_fetch_array($result))
		{
			$data[]=$line;
		}
		return $data;
	}


	/**
	 * Import settings of a plugin
	 *
	 * @param array $args
	 */
	function migrate(){
		list( $file, $name ) = $this->parse_name( $args, __FUNCTION__ );

		if ( is_plugin_active( $file ) ) {
			$this->deactivate( $args );
			// import YAML data
			$this->activate( $args );
		}

	}
}

DbMigrationCommand::add_migration_generator(
	TestMigration::$type, TestMigration::$name, array( 'TestMigration', 'generate' ));
				
DbMigrationCommand::add_migration(
	TestMigration::$type, TestMigration::$name, array( 'TestMigration', 'migrate' ));	 
