<?php
/**
 * the FTP authenticator
 *
 * To work properly this plugin has to know where to submit the FTP request.
 * This is configured by adding the server name as a parameter.
 *
 * Example configuration:
 * [snippet]
 * ftp ftp.mydomain.com
 * [/snippet]
 *
 * @author Bernard Paques
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

Class Ftp_Authenticator extends Authenticator {

	/**
	 * login
	 *
	 * The script checks provided name and password against remote server.
	 *
	 * This is done by transmitting the user name and the password
	 * while opening a FTP session to the server.
	 *
	 * @param string the nickname of the user
	 * @param string the submitted password
	 * @return TRUE on succesful authentication, FALSE othewise
	 */
	function login($name, $password) {
		global $context;

		// we need some parameters
		if(!isset($this->attributes['authenticator_parameters']) || !$this->attributes['authenticator_parameters']) {
			Logger::error(i18n::s('Please provide parameters to the authenticator.'));
			return FALSE;
		}

		// prepare network parameters
		$server = $this->attributes['authenticator_parameters'];
		if(strstr($server, ':'))
			list($server, $port) = explode(':', $server, 2);
		else
			$port = 21;

		// open network socket
		if(!$handle = Safe::fsockopen($server, $port)) {
			Logger::error(sprintf(i18n::s('Impossible to connect to %.'), $this->attributes['authenticator_parameters']));
			return FALSE;
		}

		// read welcome banner
		if((!$line = fgets($handle, 256)) || !strstr($line, '2')) {
			fclose($handle);
			Logger::error(sprintf(i18n::s('Invalid banner message from %s.'), $this->attributes['authenticator_parameters']));
			return FALSE;
		}

		// submit name
		fputs($handle, "USER $username\r\n");
		if((!$line = fgets($handle, 256)) || !strstr($line, '3')) {
			fclose($handle);
			Logger::error(sprintf(i18n::s('Impossible to submit name to %s.'), $this->attributes['authenticator_parameters']));
			return FALSE;
		}

		// submit password
		fputs($handle, "PASS $password\r\n");
		if((!$line = fgets($handle, 256)) || !strstr($line, '2')) {
			fclose($handle);
			Logger::error(sprintf(i18n::s('Impossible to submit password to %s.'), $this->attributes['authenticator_parameters']));
			return FALSE;
		}

		// close ftp session
		fputs($handle, "QUIT\r\n");
		fclose($handle);

		// this is a valid user
		return TRUE;
	}

}

?>