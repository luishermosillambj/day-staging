<?php
if (!defined('THEME_FRAMEWORK'))
{
	exit('No direct script access allowed');
}

/**
 * Helper functions for logic part of control panel
 *
 * @author Reza Marandi <ross@artbees.net>
 * @copyright   Artbees LTD (c)
 * @link        http://artbees.net
 * @since       Version 5.1
 * @package     artbees
 */

/**
 * method that is resposible to unzip compress files .
 * it used native wordpress functions.
 *
 * @since       1.0.0
 * @author Reza Marandi <ross@artbees.net>
 *
 * @param str $zip_path compress file absolute path.
 * @param str $dest_path Where should it be uncompressed.
 *
 * @return bool will return boolean status of action
 */
if (!function_exists('mk_unzip_file'))
{
	function mk_unzip_file($zip_path, $dest_path)
	{
		$zip_path  = realpath($zip_path);
		$dest_path = realpath($dest_path);

		if (file_exists($zip_path) == false)
		{
			throw new Exception("Zip file that you are looking for is not exist");
			return false;
		}

		require_once ABSPATH . '/wp-admin/includes/file.php';
		global $wp_filesystem;
		if (!$wp_filesystem)
		{
			WP_Filesystem();
			if (!$wp_filesystem)
			{
				throw new Exception("unzipPlugin , System Error 100x001");
				return false;
			}
		}

		$unzipfile = unzip_file($zip_path, $dest_path);
		if (is_wp_error($unzipfile))
		{
			throw new Exception($unzipfile->get_error_message(), 1);
			return false;
		}
		else
		{
			return true;
		}
	}
}
/**
 * You can create a directory using this helper , it will check the dest directory for if its writable or not then
 * try to create new one
 *
 * @since       1.0.0
 * @author Reza Marandi <ross@artbees.net>
 *
 * @param str $path path of directory that need to be created
 * @param int $perm permission of new directory , default is : 0775
 *
 * @return bool will return boolean status of action , all message is setted to $this->message()
 */
if (!function_exists('mk_check_perm_and_create_dir'))
{
	function mk_check_perm_and_create_dir($path, $perm = 0775)
	{
        $path = realpath($path);

		if (file_exists($path) == true)
		{
			if (mk_is_writable($path) == false)
			{
				throw new Exception($path . ' directory is not writable');
				return false;

			}
			else
			{
				return true;
			}
		}
		else
		{
			if (@mkdir($path, $perm, true) == false)
			{
				throw new Exception("Can't create $path directory");
				return false;
			}
			else
			{
				return true;
			}
		}
	}
}

/**
 * this method is resposible to download file from url and save it on server.
 * it will check if curl is available or not and then decide to use curl or file_get_content
 *
 * @since       1.0.0
 * @author Reza Marandi <ross@artbees.net>
 *
 * @param str $upload_dir absolute path of directory that file save on it.
 * @param str $url url of file (http://yahoo.com/test-plugin.zip).
 *
 * @return bool will return action status
 */
if (!function_exists('mk_upload'))
{
	function mk_upload($upload_dir, $url)
	{
        $upload_dir = realpath($upload_dir);
		if (mk_check_remote_file_existence($url) == false)
		{
			throw new Exception("Can't download source file.");
			return false;
		}

		set_time_limit(0);
		$name = basename($url);

		if (mk_check_perm_and_create_dir($upload_dir) == false)
		{
			throw new Exception(sprintf("Destination directory is not ready for upload . {%s}", $upload_dir));
			return false;
		}

		if (function_exists('curl_version'))
		{
			$fp = @fopen($upload_dir . $name, 'w+');
			if ($fp == false)
			{
				throw new Exception(sprintf("Can't open destination file {%s}", $upload_dir . $name));
				return false;
			}

			$ch = curl_init($url);

			curl_setopt($ch, CURLOPT_TIMEOUT, 50);
			curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
			// curl_setopt($ch, CURLOPT_USERPWD, "user:password");
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
			curl_setopt($ch, CURLOPT_FILE, $fp);

			$data = curl_exec($ch);
			if (curl_error($ch))
			{
				throw new Exception(curl_error($ch));
				return false;
			}
			else
			{
				curl_close($ch);
				fclose($fp);
				return true;
			}
		}
		else
		{
			$response = @file_put_contents($upload_dir . $name, file_get_contents($url));
			if ($response == false)
			{
				throw new Exception("Can't download file using put contents , Call webmaster.");
				return false;
			}
			else
			{
				return true;
			}
		}
	}
}
/**
 * this method is resposible to check a directory for see if its writebale or not
 *
 * @since       1.0.0
 * @author Reza Marandi <ross@artbees.net>
 *
 * @param str $path for example (/var/www/jupiter/wp-content/plugins)
 *
 * @return bool true or false
 */
if (!function_exists('mk_is_writable'))
{
	function mk_is_writable($path)
	{
		return is_writable(realpath($path));
	}
}

/**
 * this method is resposible to delete a directory or file
 * if the path is pointing to a directory it will remove all the includes file recursivly and then remove directory at last step
 * if the path is pointing to a file it will remove it
 *
 * @since       1.0.0
 * @author Reza Marandi <ross@artbees.net>
 *
 * @param str $dir for example (/var/www/jupiter/wp-content/plugins)
 *
 * @return bool true or false
 */
if (!function_exists('mk_delete_file_and_dir'))
{
	function mk_delete_file_and_dir($dir)
	{
        $dir = realpath($dir);
		if (!file_exists($dir))
		{
			return true;
		}

		if (!is_dir($dir))
		{
			return unlink($dir);
		}
		foreach (scandir($dir) as $item)
		{
			if ($item == '.' || $item == '..')
			{
				continue;
			}

			if (!mk_delete_file_and_dir($dir . DIRECTORY_SEPARATOR . $item))
			{
				return false;
			}

		}
		return rmdir($dir);
	}
}
/**
 * Safely and securely get file from server.
 * It attempts to read file using Wordpress native file read functions
 * If it fails, we use wp_remote_get. if the site is ssl enabled, we try to convert it http as some servers may fail to get file
 *
 * @author Reza Marandi <ross@artbees.net>
 *
 * @param $file_url         string    its directory URL
 * @param $file_dir         string    its directory Path
 *
 * @return $wp_file_body    string
 */
if (!function_exists('mk_get_file_body'))
{
	function mk_get_file_body($file_url, $file_dir)
	{
		$file_dir = realpath($file_dir);

		global $wp_filesystem;
		if (empty($wp_filesystem))
		{
			require_once ABSPATH . '/wp-admin/includes/file.php';
			WP_Filesystem();
		}
		$wp_get_file_body = $wp_filesystem->get_contents($file_dir);
		if ($wp_get_file_body == false)
		{
			$wp_remote_get_file = wp_remote_get($file_uri);

			if (is_array($wp_remote_get_file) and array_key_exists('body', $wp_remote_get_file))
			{
				$wp_remote_get_file_body = $wp_remote_get_file['body'];

			}
			else if (is_numeric(strpos($file_uri, "https://")))
			{

				$file_uri           = str_replace("https://", "http://", $file_uri);
				$wp_remote_get_file = wp_remote_get($file_uri);

				if (!is_array($wp_remote_get_file) or !array_key_exists('body', $wp_remote_get_file))
				{
					throw new Exception('SSL connection error. Code: template-assets-get');
					return false;
				}

				$wp_remote_get_file_body = $wp_remote_get_file['body'];
			}

			$wp_file_body = $wp_remote_get_file_body;

		}
		else
		{
			$wp_file_body = $wp_get_file_body;
		}
		return $wp_file_body;
	}
}
if (!function_exists('mk_check_remote_file_existence'))
{
	function mk_check_remote_file_existence($url)
	{
		if (@get_headers($url)[0] == 'HTTP/1.1 404 Not Found')
		{
			return false;
		}
		else
		{
			return true;
		}
	}
}