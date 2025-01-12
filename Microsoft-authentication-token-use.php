<?php
/*
 * Plugin Name:       Dynamics Contact Form
 * Description:       Connects CF7 to CRM API
 * Version:           1.0
 * Requires at least: 6.7
 * Requires PHP:      8.1
 * Author:            Md Sayem Iftekar
 * Text Domain:       dynamics-contact-form
 * Requires Plugins:  contact-form-7
 */
if ( !defined('WPINC') ) {
	die;
}

if ( !class_exists('dynamicsContactForm') ) {
	class dynamicsContactForm {
		private $client_id = ' '; // use your client_id
		private $client_secret = '" "'; //client_secret
		private $resource = 'https://trialsandbox.api.crm4.dynamics.com/';
		private $login_url = 'https://login.microsoftonline.com/common/oauth2/token';
		private $callback_url = 'http://localhost';
		private $auth_url;

		public function __construct() {
			$this->auth_url = 'https://login.microsoftonline.com/common/oauth2/authorize?resource=' . $this->resource;

			// Is run with "wp dynamics:auth"
			if ( class_exists('WP_CLI') ) {
				WP_CLI::add_command('dynamics:auth', [$this, 'getAuth']);
			}

			add_action('wpcf7_before_send_mail', [$this, 'pushToCRM']);
		}

		public function pushToCRM($data) : void {
			if ( isset($_POST['yourName']) && $_POST['yourName'] ) {
				$your_name = $_POST['yourName'];
			} else {
				$error = 'Your Name is required';
			}

			if ( isset($_POST['yourSurname']) && $_POST['yourSurname'] ) {
				$your_surname = $_POST['yourSurname'];
			} else {
				$error = 'Your Surname is required';
			}

			if ( isset($_POST['yourCompany']) && $_POST['yourCompany'] ) {
				$your_company = $_POST['yourCompany'];
			} else {
				$error = 'Your Company is required';
			}

			if ( isset($_POST['yourJobTitle']) && $_POST['yourJobTitle'] ) {
				$your_job_title = $_POST['yourJobTitle'];
			} else {
				$error = 'Your Job Title is required';
			}

			if ( isset($_POST['yourEmail']) && $_POST['yourEmail'] ) {
				$your_email = $_POST['yourEmail'];
			} else {
				$error = 'Your Email is required';
			}

			if ( isset($_POST['yourTel']) && $_POST['yourTel'] ) {
				$your_tel = $_POST['yourTel'];
			} else {
				$error = 'Your Telephone is required';
			}

			if ( isset($_POST['yourCountry']) && $_POST['yourCountry'] ) {
				$your_country = $_POST['yourCountry'];
			} else {
				$error = 'Your Country is required';
			}

			if ( isset($_POST['yourPostcode']) && $_POST['yourPostcode'] ) {
				$your_postcode = $_POST['yourPostcode'];
			} else {
				$error = 'Your Postcode is required';
			}

			if ( isset($_POST['enquiryWhere']) && $_POST['enquiryWhere'] ) {
				$enquiry_where = $_POST['enquiryWhere'];

				if ( $enquiry_where == 'Search Engine' ) {
					$crm_lead = '281490001';
				} else if ( $enquiry_where == 'Social Media' ) {
					$crm_lead = '289230000';
				} else if ( $enquiry_where == 'Online Advert' ) {
					$crm_lead = '289230001';
				} else if ( $enquiry_where == 'Print Advertisement' ) {
					$crm_lead = '1';
				} else if ( $enquiry_where == 'Existing Customer' ) {
					$crm_lead = '281490000';
				} else if ( $enquiry_where == 'Word of Mouth/Enva Employee' ) {
					$crm_lead = '2';
				} else if ( $enquiry_where == 'Event/Conference' ) {
					$crm_lead = '6';
				} else {
					$crm_lead = '10';
				}
			} else {
				$error = 'Enquiry Where is required';
			}

			if ( isset($_POST['yourMessage']) && $_POST['yourMessage'] ) {
				$your_message = $_POST['yourMessage'];
			} else {
				$error = 'Your Message is required';
			}

			if ( isset($_POST['accept-this-1']) && $_POST['accept-this-1'] ) {
				$accept_this = $_POST['accept-this-1'];
			} else {
				$error = 'Please ensure you agree to the privacy policy';
			}

			if ( !isset($error) ) {
				$crm_data = array(
					'firstName' => $your_name,
					'lastName' => $your_surname,
					'createdon' => date('Y-m-d\TH:i:s\Z'),
					'hex_leadchannel' => 281490002,
					'hex_pagedetailname' => get_site_url(),
					'companyname' => $your_company,
					'jobtitle' => $your_job_title,
					'telephone1' => $your_tel,
					'emailaddress1' => $your_email,
					'address1_country' => $your_country,
					'address1_postalcode' => $your_postcode,
					'leadsourcecode' => $crm_lead,
					'description' => "\r\nUTM Src: easibedding",
					'hex_consent' => 1,
					'hex_easibed' => 1
				);

				$this->sendRequest($crm_data);
			} else {
				var_dump($error);
			}
		}

		private function sendRequest($data) : void {
			$auth_token = $this->authenticate();
			$data_string = json_encode($data);

			$ch = curl_init();
			curl_setopt_array($ch, array(
				CURLOPT_URL => $this->resource . 'api/data/v9.1/leads',
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_ENCODING => "",
				CURLOPT_MAXREDIRS => 10,
				CURLOPT_TIMEOUT => 30,
				CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
				CURLOPT_CUSTOMREQUEST => "POST",
				CURLOPT_POSTFIELDS => $data_string,
				CURLOPT_HTTPHEADER => array(
					"Authorization: Bearer " . $auth_token->access_token,
					"Content-Type: application/json",
					'Content-Length: ' . strlen($data_string),
					"cache-control: no-cache"
				)
			));

			$response = curl_exec($ch);

			if (curl_error($ch)) {
				$error_msg = curl_error($ch);
			}

			$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

			curl_close($ch);

			if (isset($error_msg) || $code != 204) {
				var_dump($error_msg, $data_string, $code);
			} else {
				var_dump($error_msg, $data_string, $code);
			}

			exit();
		}

		public function getAuth() : void {
			$folder_path = __DIR__ . '/';
			$token_file = 'token.json';

			// does token exist
			if (file_exists($folder_path . $token_file)) {
				// has it expired
				$auth_token = json_decode(file_get_contents($folder_path . $token_file));

				if ( !$this->isAccessTokenExpired($auth_token) ) {
					WP_CLI::log('Token is still valid, you are authenticated!.');
				} else {
					$refreshed_token = $this->refreshToken($auth_token);
					$json_token = json_decode($refreshed_token);

					if ( !property_exists($json_token, 'access_token') ) {
						var_dump($json_token);
						exit;		
					}

					if ($json_token->access_token) {
						$json_token->expire_time = $this->setTokenExpires();

						file_put_contents($folder_path . $token_file, json_encode($json_token));

						WP_CLI::log('Token has been refreshed, you are now authenticated!.');
					}
				}
			} else {
				WP_CLI::log("Open the following link in your browser:\n" . $this->getAuthUrl());
				WP_CLI::log('You will need to login with User: crmintegrations@enva.com and Password: Welcome123');

				$auth_code = $this->ask('Enter verification code:');

				$auth_token = $this->getAuthToken($auth_code);

				$json_token = json_decode($auth_token);

				if ( isset($json_token->access_token) && $json_token->access_token ) {
					$json_token->expireTime = $this->setTokenExpires();

					file_put_contents($folder_path . $token_file, json_encode($json_token));
					WP_CLI::log('You are now authenticated!.');
				} else {
					// There has been an error authenticating
					var_dump($auth_token);
					exit();
				}
			}
		}

		private function ask($question) : Mixed {
			fwrite(STDOUT, $question . ' ');

			return strtolower(trim(fgets(STDIN)));
		}

		private function isAccessTokenExpired($auth_token) : bool {
			$token_expire_time = new \Datetime($auth_token->expireTime->date);
			$now = new \Datetime();

			if ($now > $token_expire_time) {
				return true;
			} else {
				return false;
			}
		}

		private function refreshToken($auth_token) : Mixed {
			$payload =
			'client_id=' . $this->client_id .
			'&refresh_token=' . $auth_token->refresh_token .
			'&grant_type=refresh_token' .
			'&resource=' . $this->resource .
			'&client_secret=' . $this->client_secret;

			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $this->login_url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
			curl_setopt(
				$ch,
				CURLOPT_HTTPHEADER,
				array(
					'Content-Type: application/x-www-form-urlencoded'
				)
			);

			$response_data = curl_exec($ch);

			return $response_data;
		}

		private function setTokenExpires() : DateTime {
			$date_time = new \DateTime();
			$date_time->modify('+ 1 hour');
			$date_time->format('H:i:s');

			return $date_time;
		}

		public function getAuthToken($auth_code) : Mixed {
			$payload =
			'grant_type=authorization_code' .
			'&client_id=' . $this->client_id .
			'&code=' . trim($auth_code) .
			'&redirect_uri=' . $this->callback_url .
			'&resource=' . $this->resource .
			'&client_secret=' . $this->client_secret;

			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $this->login_url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
			curl_setopt(
				$ch,
				CURLOPT_HTTPHEADER,
				array(
					'Content-Type: application/x-www-form-urlencoded'
				)
			);

			$response_data = curl_exec($ch);

			return $response_data;
		}

		public function getAuthUrl() : String {
			return $this->auth_url . '&client_id=' . $this->client_id . '&response_type=code' . '&redirect_uri=' . $this->callback_url;
		}
	}
}
$dynamics_contact_form = new dynamicsContactForm;
