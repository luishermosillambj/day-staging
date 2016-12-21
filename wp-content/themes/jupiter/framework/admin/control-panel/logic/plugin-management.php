<?php
/**
 * This class is responsible to manage all jupiters plugin.
 * it will communicate with artbees API and get list of plugins , install them or remove them
 *
 * @author       Reza Marandi <ross@artbees.net>
 * @copyright    Artbees LTD (c)
 * @link         http://artbees.net
 * @version      1.0
 * @package      jupiter
 */
class mk_plugin_management {

	private $plugins_dir;

	public function setPluginsDir( $plugins_dir ) {
		$this->plugins_dir = $plugins_dir;
	}

	public function getPluginsDir() {
		return $this->plugins_dir;
	}

	private $plugin_name;

	public function setPluginName( $plugin_name ) {
		$this->plugin_name = $plugin_name;
	}

	public function getPluginName() {
		return $this->plugin_name;
	}

	private $plugin_slug;

	public function setPluginSlug( $plugin_slug ) {
		$this->plugin_slug = $plugin_slug;
	}

	public function getPluginSlug() {
		return $this->plugin_slug;
	}

	private $plugin_head_file_name;

	public function setPluginHeadFileName( $plugin_head_file_name ) {
		$this->plugin_head_file_name = $plugin_head_file_name;
	}

	public function getPluginHeadFileName() {
		return $this->plugin_head_file_name;
	}

	private $plugin_remote_file_name;

	public function setPluginRemoteFileName( $plugin_remote_file_name ) {
		$this->plugin_remote_file_name = $plugin_remote_file_name;
	}

	public function getPluginRemoteFileName() {
		return $this->plugin_remote_file_name;
	}

	private $plugin_remote_url;

	public function setPluginRemoteURL( $plugin_remote_url ) {
		$this->plugin_remote_url = $plugin_remote_url;
	}

	public function getPluginRemoteURL() {
		return $this->plugin_remote_url;
	}

	private $plugin_full_path;

	public function setPluginFullPath( $plugin_full_path ) {
		$this->plugin_full_path = $plugin_full_path;
	}

	public function getPluginFullPath() {
		return $this->plugin_full_path;
	}

	private $message;

	public function setMessage( $message ) {
		$this->message = $message;
	}

	public function getMessage() {
		return $this->message;
	}

	private $system_test_env;

	public function setSystemTestEnv( $system_test_env ) {
		$this->system_test_env = $system_test_env;
	}

	public function getSystemTestEnv() {
		return $this->system_test_env;
	}

	private $ajax_mode;

	public function setAjaxMode( $ajax_mode ) {
		$this->ajax_mode = $ajax_mode;
	}

	public function getAjaxMode() {
		return $this->ajax_mode;
	}
	/**
	 * Construct.
	 * it will add_actions if class created on ajax mode
	 *
	 * @author Reza Marandi <ross@artbees.net>
	 * @param bool $system_text_env if you want to create an instance of this method for phpunit it should be true
	 * @param bool $ajax_mode if you need this method as ajax mode set true
	 *
	 * @return      void
	 */
	public function __construct( $system_test_env = false, $ajax_mode = true ) {

		$this->setSystemTestEnv( $system_test_env );
		$this->setAjaxMode( $ajax_mode );

		if ( $this->getSystemTestEnv() == false ) {
			$this->setPluginsDir( ABSPATH . 'wp-content/plugins/' );
		}
		if ( $ajax_mode == true ) {
			add_action( 'wp_ajax_abb_lazy_load_plugin_list', array( &$this, 'abbLazyPluginLoad' ) );
			add_action( 'wp_ajax_abb_install_plugin', array( &$this, 'abbInstallPlugin' ) );
			add_action( 'wp_ajax_abb_remove_plugin', array( &$this, 'abbRemovePlugin' ) );
			add_action( 'wp_ajax_abb_update_plugin', array( &$this, 'abbUpdatePlugin' ) );
		}
	}
	/**
	 * method that is resposible to get data from wordpress ajax and pass it to install() method for installing plugin.
	 *
	 * @author Reza Marandi <ross@artbees.net>
	 * @param str $abb_controlpanel_plugin_name should be posted to this method
	 *
	 * @return bool will return boolean status of action , all message is setted to $this->message()
	 */
	public function abbInstallPlugin() {
		$this->setPluginName( $_POST['abb_controlpanel_plugin_name'] );
		$this->install();
	}
	/**
	 * method that is resposible to get data from wordpress ajax and remove specific plugin.
	 * it will deactive plugin first and check if the plugin is one file stand or a directory and then will remove it
	 *
	 * @author Reza Marandi <ross@artbees.net>
	 * @param str $abb_controlpanel_plugin_name plugin name that should be posted to this method ex (artbees-cap)
	 * @param str $abb_controlpanel_plugin_index_name plugin base name that should be posted to this method ex (artbees-captcha/captcha.php)
	 *
	 * @return bool will return boolean status of action , all message is setted to $this->message()
	 */
	public function abbRemovePlugin() {
		try {
			$this->setPluginName( $_POST['abb_controlpanel_plugin_name'] );
			$this->setPluginSlug( $_POST['abb_controlpanel_plugin_slug'] );

			$plugin_head_file_name = $this->findPluginHeadFileName( $this->getPluginName() );
			if ( $plugin_head_file_name == false ) {
				throw new Exception( "Can't find plugin head file. {abb_remove}" );
			}

			$this->setPluginHeadFileName( $plugin_head_file_name );
			$this->setPluginFullPath( $this->getPluginsDir() . $plugin_head_file_name );

			$this->deactivatePlugin( $this->getPluginName() );
			if ( $this->removePlugin( $this->getPluginFullPath(), $this->getPluginsDir() ) == false ) {
				throw new Exception( 'Cant delete directory' );
			}

			if ( $this->getActivePlugins( $this->getPluginHeadFileName() ) == false ) {
				$this->message( 'Successfull', true );
				return true;
			} else {
				throw new Exception( 'Failure in removing' );
			}
		} catch (Exception $e) {
			$this->message( $e->getMessage(), false );
			return false;
		}
	}
	/**
	 * method that is resposible to get data from wordpress ajax and update specific plugin.
	 * it will deactive plugin first and check if the plugin is one file stand or a directory and then will remove it
	 * after removing plugin it will send the plugin name to install() method to install renew plugin
	 *
	 * @author Reza Marandi <ross@artbees.net>
	 *
	 * @param str $abb_controlpanel_plugin_name plugin name that should be posted to this method ex (artbees-cap)
	 * @param str $abb_controlpanel_plugin_index_name plugin base name that should be posted to this method ex (artbees-captcha/captcha.php)
	 *
	 * @return bool will return boolean status of action , all message is setted to $this->message()
	 */
	public function abbUpdatePlugin() {
		try {
			$this->setPluginName( $_POST['abb_controlpanel_plugin_name'] );
			$this->setPluginSlug( $_POST['abb_controlpanel_plugin_slug'] );
			$plugin_head_file_name = $this->findPluginHeadFileName( $this->getPluginName() );
			if ( $plugin_head_file_name == false ) {
				throw new Exception( "Can't find plugin head file. {abb_remove}" );
			}
			$this->setPluginHeadFileName( $plugin_head_file_name );
			$this->setPluginFullPath( $this->getPluginsDir() . $plugin_head_file_name );

			$this->deactivatePlugin( $this->getPluginName() );
			if ( $this->removePlugin( $this->getPluginFullPath(), $this->getPluginsDir() ) == false ) {
				throw new Exception( "Can't delete plugin directory" );
			}
			if ( $this->getActivePlugins( $this->getPluginHeadFileName() ) == false ) {
				return $this->install();
			} else {
				throw new Exception( 'Failure in removing' );
			}
		} catch (Exception $e) {
			$this->message( $e->getMessage(), false );
		}
	}
	/**
	 * method that is resposible to pass plugin list to UI base on lazy load condition.
	 *
	 * @author Reza Marandi <ross@artbees.net>
	 *
	 * @param str $_POST[from] from number
	 * @param str $_POST[count] how many ?
	 *
	 * @return bool will return boolean status of action , all message is setted to $this->message()
	 */
	public function abbLazyPluginLoad() {
		$from  = (isset( $_POST['from'] ) ? $_POST['from'] : null);
		$count = (isset( $_POST['count'] ) ? $_POST['count'] : null);
		if ( is_null( $from ) || is_null( $count ) ) {
			$this->message( 'System problem , please call support', false );
			return false;
		}
		return $this->getListOfPluginsLazy( $from, $count );
	}
	/**
	 * method that is resposible to pass plugin list to UI base on lazy load condition.
	 *
	 * @author Reza Marandi <ross@artbees.net>
	 *
	 * @param str $from from number
	 * @param str $count how many ?
	 *
	 * @return bool will return boolean status of action , all message is setted to $this->message()
	 */
	public function getListOfPluginsLazy( $from, $count ) {
		try {
			$list_of_plugins = $this->getPluginListFromApi();
			if ( is_array( $list_of_plugins ) && count( $list_of_plugins ) > 0 ) {
				foreach ( $list_of_plugins as $key => $plugin_info ) {
					if ( $this->getActivePlugins( $plugin_info['slug'] ) ) {
						if ( ($current_plugin_version = $this->getPluginVersion( $plugin_info['name'] )) != false ) {
							if ( version_compare( $current_plugin_version, $plugin_info['version'], '<' ) ) {
								$list_of_plugins[ $key ]['installed']   = true;
								$list_of_plugins[ $key ]['need_update'] = true;
							} else {
								$list_of_plugins[ $key ]['installed']   = true;
								$list_of_plugins[ $key ]['need_update'] = false;
							}
						}
					} else {
						$list_of_plugins[ $key ]['installed']   = false;
						$list_of_plugins[ $key ]['need_update'] = false;
					}
				}
				$list_of_plugins = array_slice( $list_of_plugins, $from, $count );
				$this->message( 'successfull', true, $list_of_plugins );
				return true;
			} else {
				$this->message( 'Plugin list is empty', false );
				throw new Exception( 'Plugin list is empty', 1 );
			}
		} catch (Exception $e) {
			return false;
		}// End try().
	}
	/**
	 * method that is resposible to get all plugins list and compare version with exist plugin and add some info to the array list
	 * such as installed or not , need and update or not ?
	 *
	 * @author Reza Marandi <ross@artbees.net>
	 *
	 * @return bool will return boolean status of action , all message is setted to $this->message()
	 */
	public function getListOfAllPlugins() {
		try {
			$list_of_plugins = $this->getPluginListFromApi();
			if ( is_array( $list_of_plugins ) && count( $list_of_plugins ) > 0 ) {
				foreach ( $list_of_plugins as $key => $plugin_info ) {
					if ( ($current_plugin_version = $this->getPluginVersion( $plugin_info['name'] )) != false ) {
						if ( version_compare( $current_plugin_version, $plugin_info['version'], '<' ) ) {
							$list_of_plugins[ $key ]['installed']   = true;
							$list_of_plugins[ $key ]['need_update'] = true;
						} else {
							$list_of_plugins[ $key ]['installed']   = true;
							$list_of_plugins[ $key ]['need_update'] = false;
						}
					} else {
						$list_of_plugins[ $key ]['installed']   = false;
						$list_of_plugins[ $key ]['need_update'] = false;
					}
				}

				$this->message( 'successfull', true, $list_of_plugins );
				return true;
			} else {
				$this->message( 'Plugin list is empty', false );
				throw new Exception( 'Plugin list is empty', 1 );
			}
		} catch (Exception $e) {
			return false;
		}// End try().
	}
	/**
	 * method that is resposible to download plugin from api and install it on wordpress then activate it on last step.
	 * it will get an array of plugins name.
	 * @author Reza Marandi <ross@artbees.net>
	 *
	 * @param str $this->getPluginName plugin name
	 *
	 * @return bool will return boolean status of action , all message is setted to $this->message()
	 */
	public function installBatch() {
		try {
			$plugins_list = $this->getPluginName();
			if(empty($plugins_list) || is_array($plugins_list) == false || count($plugins_list) == 0)
			{
				throw new Exception('Plugin list is not an array , use install method instead');
			}
			foreach ($plugins_list as $key => $plugin_name) {
				$this->setPluginName($plugin_name);
				$response = $this->install();
				if($response == false)
				{
					$response = $this->getMessage();
					throw new Exception($response['message']);
				}
			}
			$this->message(count($plugins_list) .' plugins installed successfully');
			return true;

		} catch (Exception $e) {
			$this->message( $e->getMessage(), false );
			return false;
		}
	}
	/**
	 * method that is resposible to download plugin from api and install it on wordpress then activate it on last step.
	 *
	 * @author Reza Marandi <ross@artbees.net>
	 *
	 * @param str $this->getPluginName plugin name
	 *
	 * @return bool will return boolean status of action , all message is setted to $this->message()
	 */
	public function install() {
		try {
			// Validate if plugin name is setted
			if ( $this->getPluginName() == '' ) {
				throw new Exception( 'Choose a plugin first.' );
			}
			// Check if plugin already exist
			if ( ($head_file_response = $this->findPluginHeadFileName( $this->getPluginName() )) !== false ) {
				if ( $this->getActivePlugins( $head_file_response ) == false ) {
					$this->setPluginFullPath( $this->getPluginsDir() . $head_file_response );
					$this->activatePlugin( $this->getPluginFullPath() );
					$this->message( 'Plugin successfully added and activated.', true );
					return true;
				} else {
					$this->message( 'Plugin successfully added and activated.', true );
					return true;
				}
			}
			// Validate plugins and uploads dir is writable
			if ( $this->isWritable( $this->getPluginsDir() ) == false ) {
				throw new Exception( 'Plugin directory is not writable.' );
			}

			// Get plugin url (address)
			$api_response = $this->getPluginListFromApi( $this->getPluginName() )[0];
			if ( filter_var( $api_response['source'], FILTER_VALIDATE_URL ) === false ) {
				throw new Exception( 'Plugin source not found' );
			}

			$this->setPluginRemoteURL( $api_response['source'] );
			$this->setPluginRemoteFileName( basename( $this->getPluginRemoteURL() ) );
			$this->setPluginSlug( $api_response['slug'] );

			// Upload plugin from address to wordpress upload folder
			$this->uploadPluginFromURL( $this->getPluginRemoteURL(), $this->getPluginRemoteFileName() );
			// Unzip IT
			$zip_path = $this->getPluginsDir() . $this->getPluginRemoteFileName();
			$this->unzipPlugin( $zip_path );
			// Find if the plugin have a directory or one stand php file and set full address of it
			$found_file = $this->findPluginHeadFileName( $this->getPluginName() );
			if ( $found_file === false ) {
				throw new Exception( "Can't find " . $this->getPluginName() . ' plugin head file name.' );
			}

			$this->setPluginHeadFileName( $found_file );
			$this->setPluginFullPath( $this->getPluginsDir() . $found_file );

			// Remove ZIP file
			unlink( $zip_path );

			// Activate Plugin
			$this->activatePlugin( $this->getPluginFullPath() );

			$this->message( 'Plugin successfully added and activated.', true );
			return true;
		} catch (Exception $e) {
			$this->message( $e->getMessage(), false );
			return false;
		}// End try().
	}
	/**
	 * method that is resposible to update specific plugin.
	 * it will deactive plugin first and check if the plugin is one file stand or a directory and then will remove it
	 * after removing plugin it will send the plugin name to install() method to install renew plugin
	 *
	 * @author Reza Marandi <ross@artbees.net>
	 *
	 * @param str $this->getPluginName() plugin name. ex (artbees-cap)
	 * @param str $this->getPluginIndexName() plugin base name. ex (artbees-captcha/captcha.php)
	 *
	 * @return bool will return boolean status of action , all message is setted to $this->message()
	 */
	public function updatePlugin() {
		try {
			// Validate if plugin name is setted
			if ( $this->getPluginName() == '' ) {
				throw new Exception( 'Plguin information data format is not right.' );
			}

			$found_file = $this->findPluginHeadFileName( $this->getPluginName() );
			if ( $found_file == false ) {
				throw new Exception( "Can't find plugin head file name.", false );
			}

			$this->setPluginHeadFileName( $found_file );
			$this->setPluginFullPath( $this->getPluginsDir() . $found_file );

			// Check if plugin is active or not
			if ( $this->getActivePlugins( $this->getPluginHeadFileName() ) != false ) {
				$this->deactivatePlugin( $this->getPluginName() );
			}

			$response = $this->removePlugin( $this->getPluginFullPath(), $this->getPluginsDir() );
			if ( $response == false ) {
				return false;
			}

			$response = $this->install();
			if ( $response == false ) {
				return false;
			}
			$this->message( 'Update completed', true );
			return true;
		} catch (Exception $e) {
			$this->message( $e->getMessage(), false );
			return false;
		}// End try().
	}
	/**
	 * method that is resposible to unzip compress files .
	 * it used native wordpress functions.
	 *
	 * @author Reza Marandi <ross@artbees.net>
	 *
	 * @param str $zip_path compress file absolute path.
	 *
	 * @return bool will return boolean status of action , all message is setted to $this->message()
	 */
	public function unzipPlugin( $zip_path ) {
		require_once ABSPATH . '/wp-admin/includes/file.php';

		global $wp_filesystem;
		if ( ! $wp_filesystem ) {
			WP_Filesystem();
			if ( ! $wp_filesystem ) {
				throw new Exception( 'unzipPlugin , System Error 100x001' );
				return false;
			}
		}

		$unzipfile = unzip_file( $zip_path, $this->getPluginsDir() );
		if ( is_wp_error( $unzipfile ) ) {
			throw new Exception( 'unzipPlugin , ' . $unzipfile->get_error_message() );
			return false;
		} else {
			return true;
		}
	}
	/**
	 * this method is resposible to activate upladed plugin .
	 * it used native wordpress functions.
	 *
	 * @author Reza Marandi <ross@artbees.net>
	 *
	 * @param str $plugin_index such as (artbees-captcha/captcha.php)
	 *
	 * @return bool will return boolean status of action , all message is setted to $this->message()
	 */
	public function activatePlugin( $plugin_full_path ) {
		$current = get_option( 'active_plugins' );
		$plugin  = plugin_basename( trim( $plugin_full_path ) );
		if ( ! in_array( $plugin, $current ) ) {
			$current[] = $plugin;
			sort( $current );
			do_action( 'activate_plugin', trim( $plugin ) );
			update_option( 'active_plugins', $current );
			do_action( 'activate_' . trim( $plugin ) );
			do_action( 'activated_plugin', trim( $plugin ) );
			return true;
		} else {
			return true;
		}
	}
	/**
	 * this method is resposible to get plugin version .
	 * it used native wordpress functions.
	 *
	 * @author Reza Marandi <ross@artbees.net>
	 *
	 * @param str $plugin_index such as (artbees-captcha/captcha.php)
	 *
	 * @return bool|int will return version of plugin or false
	 */
	public function getPluginVersion( $plugin_name ) {
		if ( ($plugin_head_file_name = $this->findPluginHeadFileName( $plugin_name )) == false ) {
			return false;
		}
		$plugin_full_path = $this->getPluginsDir() . $plugin_head_file_name;
		if ( file_exists( $plugin_full_path ) == false ) {
			return false;
		}
		$get_plugin_data = get_plugin_data( $plugin_full_path );
		$version_response = $get_plugin_data['Version'] ;
		if ( empty($version_response) == false ) {
			return $version_response;
		} else {
			return false;
		}
	}
	/**
	 * this method is resposible to deactivate active plugin .
	 * it used native wordpress functions.
	 *
	 * @author Reza Marandi <ross@artbees.net>
	 *
	 * @param str $plugin_index for example : (artbees-captcha/captcha.php)
	 * @param str $full_path_with_name plugin full path to the
	 *                                 head file for example :
	 *                                 (/var/www/jupiter/wp-content/plugin/artbees-captcha/captcha.php)
	 *
	 * @return bool will return boolean status of action , all message is setted to $this->message()
	 */
	public function deactivatePlugin( $plugin_name ) {
		$plugin_head_file_name = $this->findPluginHeadFileName( $plugin_name );
		$plugin_full_path      = $this->getPluginsDir() . $plugin_head_file_name;
		if ( is_plugin_active( $plugin_head_file_name ) ) {
			$response = deactivate_plugins( $plugin_full_path );
			if ( is_wp_error( $response ) ) {
				throw new Exception( 'deactivatePlugin , ' . $response->get_error_message(), 1 );
				return false;
			} else {
				return true;
			}
		} else {
			return true;
		}
	}
	/**
	 * this method is resposible to remove deactive plugin .
	 * it used native wordpress functions.
	 *
	 * @author Reza Marandi <ross@artbees.net>
	 *
	 * @param str $full_path_with_name plugin full path to the
	 *                                 head file for example :
	 *                                 (/var/www/jupiter/wp-content/plugin/artbees-captcha/captcha.php)
	 * @param str $plugins_dir for example : (/var/www/jupiter/wp-content/plugin/)
	 *
	 * @return bool will return boolean status of action , all message is setted to $this->message()
	 */
	public function removePlugin( $plugin_full_path, $plugins_dir ) {
		// Check wether parent directory is writable or not
		try {
			if ( $this->isWritable( $plugins_dir ) == false ) {
				throw new Exception( 'Plugin parent directory is not writable {remove Plugin}.' );
			}

			// Check if the plugin is one file or a directory
			$plugin_base_directory = str_replace( basename( $plugin_full_path ), '', $plugin_full_path );
			if ( strlen( str_replace( $plugins_dir, '', $plugin_full_path ) ) > 2 ) {
				if ( $this->isWritable( $plugin_base_directory ) == false ) {
					throw new Exception( 'Plugin directory is not writable {remove Plugin}.' );
				}

				if ( $this->deleteDirectory( $plugin_base_directory ) ) {
					return true;
				} else {
					throw new Exception( "Can't remove directory of plugin - Directory" );
				}
			} else {
				if ( $this->deleteDirectory( $plugin_full_path ) ) {
					return true;
				} else {
					throw new Exception( "Can't remove directory of plugin - File" );
				}
			}
		} catch (Exception $e) {
			$this->message( $e->getMessage(), false );
			return false;
		}// End try().
	}
	/**
	 * this method is resposible to check if input plugin name is active or not.
	 * it used native wordpress functions.
	 *
	 * @author Reza Marandi <ross@artbees.net>
	 *
	 * @param str $plugin_name plugin name for exampme (artbees-cap).
	 *
	 * @return bool will return boolean status of action , all message is setted to $this->message()
	 */
	public function getActivePlugins( $plugin_head_file_name ) {
		$response = get_option( 'active_plugins' );
		if ( is_array( $response ) && count( $response ) > 0 ) {
			foreach ( $response as $index => $string ) {
				if ( strpos( $string, $plugin_head_file_name ) !== false ) {
					return true;
				}
			}
			return false;
		} else {
			return false;
		}
	}
	/**
	 * this method is resposible to find plugin head file and return full path of it.
	 *
	 * @author Reza Marandi <ross@artbees.net>
	 *
	 * @param str $plugin_index plugin name for exampme (artbees-captcha/captcha.php).
	 *
	 * @return string will return absolute address of plugin head file
	 */
	public function findPluginHeadFileName( $plugin_name ) {
		wp_clean_plugins_cache();
		$plugins = get_plugins();
		foreach ( $plugins as $plugin_file => $plugin_info ) {
			if ( $plugin_info['Name'] == $plugin_name ) {
				return $plugin_file;
			}
		}
		return false;
	}
	/**
	 * this method is resposible to download plugin file from url and save it on server.
	 * it will check if curl is available or not and then decide to use curl or file_get_content
	 *
	 * @author Reza Marandi <ross@artbees.net>
	 *
	 * @param str $url url of plugin (http://yahoo.com/test-plugin.zip).
	 * @param str $name plugin name that should save on server for exampme (test-plugin.zip).
	 *
	 * @return bool will return action status
	 */
	public function uploadPluginFromURL( $url, $name ) {
		if ( $this->checkRemoteFileExistence( $url ) == false ) {
			throw new Exception( "Can't download plugin source file." );
			return false;
		}

		set_time_limit( 0 );
		if ( function_exists( 'curl_version' ) ) {
			$fp = @fopen( $this->getPluginsDir() . $name, 'w+' );
			if ( $fp == false ) {
				throw new Exception( 'Can\'t open destination file' );
				return false;
			}
			$ch = curl_init( $url );

			curl_setopt( $ch, CURLOPT_TIMEOUT, 50 );
			curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
			curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, 1 );
			curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );
			curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, false );
			curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, 0 );
			curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, 0 );
			curl_setopt( $ch, CURLOPT_FILE, $fp );

			$data = curl_exec( $ch );
			if ( curl_error( $ch ) ) {
				throw new Exception( curl_error( $ch ) );
				return false;
			} else {
				curl_close( $ch );
				fclose( $fp );
				return true;
			}
		} else {
			$response = @file_put_contents( $this->getPluginsDir() . $name, file_get_contents( $url ) );
			if ( $response == false ) {
				throw new Exception( "Can't download file using put contents , call webmaster." );
				return false;
			} else {
				return true;
			}
		}// End if().
	}
	/**
	 * This method is resposible to get plugin list from api and create download link if plugin need to extract from wordpress repo.
	 *
	 * @author Reza Marandi <ross@artbees.net>
	 *
	 * @param str $plugin_name if plugin name is valued it will return array of information about the this plugin
	 *                         but if plugin is valued as false it will return all plugins information
	 *
	 * @return array will return array of plugins
	 */
	public function getPluginListFromApi( $plugin_name = false ) {
		// Begin Response from api server
		$api_response = [
			[
				'name'    => 'Hello Dolly',
				'img_url' => '#',
				'slug'    => 'hello-dolly',
				'source'  => 'wp-repo',
				'version' => 'wp-repo',
			],
			[
				'name'    => 'WPBakery Visual Composer (Artbees Modified Version)',
				'img_url' => 'https://dummyimage.com/200x100/000/ffffff&text=' . 'Visual Composer',
				'slug'    => 'js_composer_theme',
				'version' => '4.12.1',
				'source'  => 'http://localhost:8888/plugins/js_composer_theme.zip',
			],
			[
				'name'    => 'LayerSlider WP',
				'img_url' => 'https://dummyimage.com/200x100/000/ffffff&text=' . 'Layer Slider',
				'slug'    => 'layerslider',
				'version' => '5.6.5',
				'source'  => 'http://localhost:8888/plugins/LayerSlider-v5.6.5.zip',
			]
		];
		$result = '';
		if ( $plugin_name != false ) {
			foreach ( $api_response as $key => $value ) {
				if ( $value['name'] == $plugin_name ) {
					$result = array( $api_response[ $key ] );
					break;
				}
			}
		} else {
			$result = $api_response;
		}
		// End Response from api server
		if ( is_array( $result ) && count( $result ) > 0 ) {
			foreach ( $result as $key => $value ) {
				if ( $value['source'] == 'wp-repo' && $value['version'] == 'wp-repo' ) {
					$response = $this->getPluginInfoFromWPRepo( $value['slug'], array( 'download_link' => 'source', 'version' => 'version' ) );
					if ( $response != false ) {
						$result[ $key ] = array_replace( $result[ $key ], $response );
					}
				} else if ( $value['source'] == 'wp-repo' ) {
					$response = $this->getPluginInfoFromWPRepo( $value['slug'], array( 'download_link' => 'source' ) );
					if ( $response != false ) {
						$result[ $key ] = array_replace( $result[ $key ], $response );
					}
				} else if ( $value['version'] == 'wp-repo' ) {
					$response = $this->getPluginInfoFromWPRepo( $value['slug'], array( 'version' => 'version' ) );
					if ( $response != false ) {
						$result[ $key ] = array_replace( $result[ $key ], $response );
					}
				}
			}
			return $result;
		} else {
			throw new Exception( 'The plugin you are looking for is not exist.' );
			return false;
		}
	}
	/**
	 * Try to grab information from WordPress API.
	 *
	 * @param string $slug Plugin slug.
	 * @param array  $info_array it should be valued if you want to extract specific data from wordpress info
	 *                           for example : array('download_link' => 'source' , 'version' => 'version')
	 *                           array key : the info name from wordpress repo
	 *                           array value : the name of info that you need to return
	 *
	 * @return object Plugins_api response object on success, WP_Error on failure.
	 */
	public function getPluginInfoFromWPRepo( $slug, $info_array = array() ) {
		static $api = array();

		if ( ! isset( $api[ $slug ] ) ) {
			if ( ! function_exists( 'plugins_api' ) ) {
				require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
			}

			$response   = plugins_api( 'plugin_information', array( 'slug' => $slug, 'fields' => array( 'sections' => false ) ) );
			$api[ $slug ] = false;

			if ( is_wp_error( $response ) ) {
				throw new Exception( $response->get_error_message() );
				return false;
			} else {
				$api[ $slug ] = $response;
			}
		}
		if ( is_array( $info_array ) && count( $info_array ) > 0 ) {
			$final_response = [];
			foreach ( $info_array as $key => $value ) {
				if ( empty( $api[ $slug ]->$key ) == false ) {
					$final_response[ $value ] = $api[ $slug ]->$key;
				}
			}
			return $final_response;
		} else {
			return $api[ $slug ];
		}
	}
	/*====================== Helpers ============================*/
	/**
	 * this method is resposible to manage all the classes messages and act different on ajax mode or test mode
	 *
	 * @author Reza Marandi <ross@artbees.net>
	 *
	 * @param str   $message for example ("Successfull")
	 * @param bool  $status true or false
	 * @param mixed $data its for when ever you want to result back an array of data or anything else
	 */
	public function message( $message, $status, $data = null ) {
		$response = array(
			'status'  => $status,
			'message' => $message,
			'data'    => $data,
		);
		if ( $this->getAjaxMode() == true ) {
			header( 'Content-Type: application/json' );
			wp_die( json_encode($response) );
		} else {
			$this->setMessage( $response );
		}
	}
	/**
	 * this method is resposible to check a directory for see if its writebale or not
	 *
	 * @author Reza Marandi <ross@artbees.net>
	 *
	 * @param str $path for example (/var/www/jupiter/wp-content/plugins)
	 *
	 * @return bool true or false
	 */
	public function isWritable( $path ) {
		return is_writable( $path );
	}
	/**
	 * this method is resposible to delete a directory or file
	 * if the path is pointing to a directory it will remove all the includes file recursivly and then remove directory at last step
	 * if the path is pointing to a file it will remove it
	 *
	 * @author Reza Marandi <ross@artbees.net>
	 *
	 * @param str $dir for example (/var/www/jupiter/wp-content/plugins)
	 *
	 * @return bool true or false
	 */
	public function deleteDirectory( $dir ) {
		if ( ! file_exists( $dir ) ) {
			return true;
		}

		if ( ! is_dir( $dir ) ) {
			return unlink( $dir );
		}

		foreach ( scandir( $dir ) as $item ) {
			if ( $item == '.' || $item == '..' ) {
				continue;
			}

			if ( ! $this->deleteDirectory( $dir . DIRECTORY_SEPARATOR . $item ) ) {
				return false;
			}
		}
		return rmdir( $dir );
	}
	public function checkRemoteFileExistence( $url ) {
		if ( @get_headers( $url )[0] == 'HTTP/1.1 404 Not Found' ) {
			return false;
		} else {
			return true;
		}
	}
}
global $abb_phpunit;
if ( empty( $abb_phpunit ) || $abb_phpunit == false ) {
	new mk_plugin_management();
}
