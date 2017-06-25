<?php

namespace Detain\MyAdminVestaCP;

/**
 * Class VestaCP
 */
class VestaCP {
	/**
	 * @var string
	 */
	public $hostname = '';
	/**
	 * @var string
	 */
	public $username = '';
	/**
	 * @var string
	 */
	public $password = '';
	/**
	 * @var string
	 */
	public $response = '';

	/**
	 * @param string $hostname server hostname or ip address (must resolve)
	 * @param string $username administrative account to connect with
	 * @param string $password password to administrative account
	 */
	public function __construct($hostname = '', $username = '', $password = '') {
		$this->hostname = $hostname;
		$this->username = $username;
		$this->password = $password;
	}

	/**
	 * @param string $username
	 * @param string $password
	 * @param string $email
	 * @param string $name
	 * @param string $package
	 * @return bool|mixed|string
	 */
	public function create_account($username, $password, $email, $name, $package = 'default') {
		$first_name = trim(mb_substr($name, 0, mb_strpos(' ', $name)));
		$last_name = trim(mb_substr($name, mb_strpos(' ', $name) + 1));
		$vst_returncode = 'yes';
		$vst_command = 'v-add-user';
		// Prepare POST query
		$postvars = array(
			'user' => $this->username,
			'password' => $this->password,
			'returncode' => $vst_returncode,
			'cmd' => $vst_command,
			'arg1' => $username,
			'arg2' => $password,
			'arg3' => $email,
			'arg4' => $package,
			'arg5' => $first_name,
			'arg6' => $last_name
		);
		$postdata = http_build_query($postvars);

		// Send POST query via cURL
		$postdata = http_build_query($postvars);
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, 'https://'.$this->hostname.':8083/api/');
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
		curl_setopt($curl, CURLOPT_POST, TRUE);
		curl_setopt($curl, CURLOPT_POSTFIELDS, $postdata);
		$this->response = curl_exec($curl);

		// Check result
		if (is_null($this->response)) {
			//echo "Null Response, Check Firewall Settings\n";
			return FALSE;
		} elseif ($this->response == '0' || $this->response == 0) {
			//echo "User account has been successfully created\n";
		} else {
			//echo "Query returned error code: " .$this->response. "\n";
			return FALSE;
		}
		return $this->response;
	}

	/**
	 * @param string $username
	 * @param string $domain
	 */
	public function add_web_dns_mail_domain($username, $domain) {
		$vst_returncode = 'yes';
		$vst_command = 'v-add-domain';

		// Prepare POST query
		$postvars = array(
			'user' => $this->username,
			'password' => $this->password,
			'returncode' => $vst_returncode,
			'cmd' => $vst_command,
			'arg1' => $username,
			'arg2' => $domain
		);
		$postdata = http_build_query($postvars);

		// Send POST query via cURL
		$postdata = http_build_query($postvars);
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, 'https://'.$this->hostname.':8083/api/');
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
		curl_setopt($curl, CURLOPT_POST, TRUE);
		curl_setopt($curl, CURLOPT_POSTFIELDS, $postdata);
		$this->response = curl_exec($curl);

		// Check result
		if ($this->response == '0' || $this->response == 0) {
			echo "Domain has been successfully created\n";
		} else {
			echo 'Query returned error code: '.$this->response."\n";
		}
	}

	/**
	 * @param string $username
	 * @param string $db_name
	 * @param string $db_user
	 * @param string $db_pass
	 */
	public function add_database($username, $db_name, $db_user, $db_pass) {
		$vst_returncode = 'yes';
		$vst_command = 'v-add-database';

		// Prepare POST query
		$postvars = array(
			'user' => $this->username,
			'password' => $this->password,
			'returncode' => $vst_returncode,
			'cmd' => $vst_command,
			'arg1' => $username,
			'arg2' => $db_name,
			'arg3' => $db_user,
			'arg4' => $db_pass
		);
		$postdata = http_build_query($postvars);

		// Send POST query via cURL
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, 'https://'.$this->hostname.':8083/api/');
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
		curl_setopt($curl, CURLOPT_POST, TRUE);
		curl_setopt($curl, CURLOPT_POSTFIELDS, $postdata);
		$this->response = curl_exec($curl);

		// Check result
		if ($this->response == '0' || $this->response == 0) {
			echo "Database has been successfully created\n";
		} else {
			echo 'Query returned error code: '.$this->response."\n";
		}
	}

	/**
	 * @param string $username
	 * @param string $format
	 */
	public function list_account($username, $format = 'json') {
		$vst_command = 'v-list_user';

		// Prepare POST query
		$postvars = array(
			'user' => $this->username,
			'password' => $this->password,
			'cmd' => $vst_command,
			'arg1' => $username,
			'arg2' => $format
		);
		$postdata = http_build_query($postvars);

		// Send POST query via cURL
		$postdata = http_build_query($postvars);
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, 'https://'.$this->hostname.':8083/api/');
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
		curl_setopt($curl, CURLOPT_POST, TRUE);
		curl_setopt($curl, CURLOPT_POSTFIELDS, $postdata);
		$this->response = curl_exec($curl);

		// Parse JSON output
		$data = json_decode($this->response, TRUE);

		// Print result
		print_r($data);
	}

	/**
	 * @param string $username
	 * @param string $domain
	 * @param string $format
	 */
	public function list_web_domains($username, $domain, $format = 'json') {
		$vst_command = 'v-list-web-domain';

		// Prepare POST query
		$postvars = array(
			'user' => $this->username,
			'password' => $this->password,
			'cmd' => $vst_command,
			'arg1' => $username,
			'arg2' => $domain,
			'arg3' => $format
		);
		$postdata = http_build_query($postvars);

		// Send POST query via cURL
		$postdata = http_build_query($postvars);
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, 'https://'.$this->hostname.':8083/api/');
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
		curl_setopt($curl, CURLOPT_POST, TRUE);
		curl_setopt($curl, CURLOPT_POSTFIELDS, $postdata);
		$this->response = curl_exec($curl);

		// Parse JSON output
		$data = json_decode($this->response, TRUE);

		// Print result
		print_r($data);
	}

	/**
	 * @param string $username
	 */
	public function delete_account($username) {
		$vst_returncode = 'yes';
		$vst_command = 'v-delete-user';

		// Prepare POST query
		$postvars = array(
			'user' => $this->username,
			'password' => $this->password,
			'returncode' => $vst_returncode,
			'cmd' => $vst_command,
			'arg1' => $username
		);
		$postdata = http_build_query($postvars);

		// Send POST query via cURL
		$postdata = http_build_query($postvars);
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, 'https://'.$this->hostname.':8083/api/');
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
		curl_setopt($curl, CURLOPT_POST, TRUE);
		curl_setopt($curl, CURLOPT_POSTFIELDS, $postdata);
		$this->response = curl_exec($curl);

		// Check result
		if ($this->response == '0' || $this->response == 0) {
			echo "User account has been successfully deleted\n";
		} else {
			echo 'Query returned error code: '.$this->response."\n";
		}
	}

	/**
	 * @param string $username
	 */
	public function suspend_account($username) {
		$vst_returncode = 'yes';
		$vst_command = 'v-suspend-user';

		// Prepare POST query
		$postvars = array(
			'user' => $this->username,
			'password' => $this->password,
			'returncode' => $vst_returncode,
			'cmd' => $vst_command,
			'arg1' => $username
		);
		$postdata = http_build_query($postvars);

		// Send POST query via cURL
		$postdata = http_build_query($postvars);
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, 'https://'.$this->hostname.':8083/api/');
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
		curl_setopt($curl, CURLOPT_POST, TRUE);
		curl_setopt($curl, CURLOPT_POSTFIELDS, $postdata);
		$this->response = curl_exec($curl);

		// Check result
		if ($this->response == '0') {
			echo "User account has been successfully suspended\n";
		} else {
			echo 'Query returned error code: '.$this->response."\n";
		}
	}

	/**
	 * @param string $username
	 */
	public function unsuspend_account($username) {
		$vst_returncode = 'yes';
		$vst_command = 'v-unsuspend-user';

		// Prepare POST query
		$postvars = array(
			'user' => $this->username,
			'password' => $this->password,
			'returncode' => $vst_returncode,
			'cmd' => $vst_command,
			'arg1' => $username
		);
		$postdata = http_build_query($postvars);

		// Send POST query via cURL
		$postdata = http_build_query($postvars);
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, 'https://'.$this->hostname.':8083/api/');
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
		curl_setopt($curl, CURLOPT_POST, TRUE);
		curl_setopt($curl, CURLOPT_POSTFIELDS, $postdata);
		$this->response = curl_exec($curl);

		// Check result
		if ($this->response == '0') {
			echo "User account has been successfully unsuspended\n";
		} else {
			echo 'Query returned error code: '.$this->response."\n";
		}
	}

	/**
	 * @param string $username
	 * @param string $password
	 */
	public function check_user_pass($username, $password) {
		$vst_command = 'v-check-user-password';
		$vst_returncode = 'yes';

		// Prepare POST query
		$postvars = array(
			'user' => $this->username,
			'password' => $this->password,
			'cmd' => $vst_command,
			'arg1' => $username,
			'arg2' => $password
		);
		$postdata = http_build_query($postvars);

		// Send POST query via cURL
		$postdata = http_build_query($postvars);
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, 'https://'.$this->hostname.':8083/api/');
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
		curl_setopt($curl, CURLOPT_POST, TRUE);
		curl_setopt($curl, CURLOPT_POSTFIELDS, $postdata);
		$this->response = curl_exec($curl);

		// Check result
		if ($this->response == '0' || $this->response == 0) {
			echo "OK: User can login\n";
		} else {
			echo "Error: Username or password is incorrect\n";
		}
	}

	/**
	 * @param string $hostname
	 */
	public function setHostname($hostname) {
		$this->hostname = $hostname;
	}

	/**
	 * @param string $password
	 */
	public function setPassword($password) {
		$this->password = $password;
	}

	/**
	 * @param string $username
	 */
	public function setUsername($username) {
		$this->username = $username;
	}

	/**
	 * @return string
	 */
	public function getResponse() {
		return $this->response;
	}

}
