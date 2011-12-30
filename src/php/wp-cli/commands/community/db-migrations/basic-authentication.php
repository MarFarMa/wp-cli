<?php 

DbMigrationCommand::add_migration_generator(
  BasicAuthMigration::$type, BasicAuthMigration::$name, array( 'BasicAuthMigration', 'generate' ));
        
DbMigrationCommand::add_migration(
  BasicAuthMigration::$type, BasicAuthMigration::$name, array( 'BasicAuthMigration', 'migrate' ));  

class BasicAuthMigration extends WP_CLI_Migration {
 
  public static $type = 'plugin';
  public static $name = 'basic-authentication';
  
  /**
   * Generate current settings of a plugin
   *
   * @param array $args
   */
  function generate() {
    $data['options'] = array();
    $data['options']['basic_authentication_enabled'] =
      get_option( 'basic_authentication_enabled' )?"on":"off";
    
    $data['options']['basic_authentication_method'] =
      get_option( 'basic_authentication_method' );
    
    $data['options']['basic_authentication_password'] =
      get_option( 'basic_authentication_password' );
    
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