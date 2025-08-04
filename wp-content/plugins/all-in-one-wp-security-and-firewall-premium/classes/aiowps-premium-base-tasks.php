<?php
if (!defined('ABSPATH')) {
	exit;
}

require_once AIOWPS_PREMIUM_PATH . '/vendor/autoload.php';

use GeoIp2\Database\Reader;
use GeoIp2\Exception\AddressNotFoundException;
use MaxMind\Db\Reader\InvalidDatabaseException;

/**
 * Base Tasks class
 */
class AIOWPS_Premium_Base_Tasks {

	/**
	 * Country codes
	 *
	 * @var array
	 */
	public $country_codes;

	/**
	 * User roles
	 *
	 * @var array
	 */
	public $user_roles = array();

	/**
	 * GeoIp2\Database\Reader instance.
	 *
	 * @var object
	 */
	private $geo_reader;

	/**
	 * Base Task constructor to setup class variable and include required PHP file.
	 *
	 * @return void
	 * @throws InvalidDatabaseException - if database is invalid.
	 */
	public function __construct() {
		global $aio_wp_security_premium;
		if (!AIOWPS_Premium_Utilities::woocommerce_maxmind_db_exists()) {
			$aiowps_premium_geodb_path = $aio_wp_security_premium->get_aiowps_premium_geodb_dir_path().'/'.AIOWPS_MAXMIND_DATABASE;
			if (file_exists($aiowps_premium_geodb_path) && is_readable($aiowps_premium_geodb_path)) {
				$this->geo_reader = new Reader($aiowps_premium_geodb_path);
			}
		}

		if (!class_exists('AIOWPSecurity_Utility_IP')) {
			if (file_exists(WP_PLUGIN_DIR.'/all-in-one-wp-security-and-firewall/classes/wp-security-utility-ip-address.php')) require_once(WP_PLUGIN_DIR.'/all-in-one-wp-security-and-firewall/classes/wp-security-utility-ip-address.php');
		}
		if (!class_exists('AIOWPSecurity_Utility')) {
			if (file_exists(WP_PLUGIN_DIR.'/all-in-one-wp-security-and-firewall/classes/wp-security-utility.php')) require_once(WP_PLUGIN_DIR.'/all-in-one-wp-security-and-firewall/classes/wp-security-utility.php');
		}

		if (!function_exists('get_editable_roles')) {
			require_once ABSPATH . 'wp-admin/includes/user.php';
		}
		
		$editable_roles = get_editable_roles();
		foreach ($editable_roles as $role => $details) {
			$this->user_roles[$role] = $details['name'];
		}
		
		$this->country_codes = array(
			"A1" => "Anonymous Proxy",
			"A2" => "Satellite Provider",
			"O1" => "Other Country",
			"AD" => "Andorra",
			"AE" => "United Arab Emirates",
			"AF" => "Afghanistan",
			"AG" => "Antigua and Barbuda",
			"AI" => "Anguilla",
			"AL" => "Albania",
			"AM" => "Armenia",
			"AO" => "Angola",
			"AP" => "Asia/Pacific Region",
			"AQ" => "Antarctica",
			"AR" => "Argentina",
			"AS" => "American Samoa",
			"AT" => "Austria",
			"AU" => "Australia",
			"AW" => "Aruba",
			"AX" => "Aland Islands",
			"AZ" => "Azerbaijan",
			"BA" => "Bosnia and Herzegovina",
			"BB" => "Barbados",
			"BD" => "Bangladesh",
			"BE" => "Belgium",
			"BF" => "Burkina Faso",
			"BG" => "Bulgaria",
			"BH" => "Bahrain",
			"BI" => "Burundi",
			"BJ" => "Benin",
			"BL" => "Saint Bartelemey",
			"BM" => "Bermuda",
			"BN" => "Brunei Darussalam",
			"BO" => "Bolivia",
			"BQ" => "Bonaire, Saint Eustatius and Saba",
			"BR" => "Brazil",
			"BS" => "Bahamas",
			"BT" => "Bhutan",
			"BV" => "Bouvet Island",
			"BW" => "Botswana",
			"BY" => "Belarus",
			"BZ" => "Belize",
			"CA" => "Canada",
			"CC" => "Cocos (Keeling) Islands",
			"CD" => "Congo, The Democratic Republic of the",
			"CF" => "Central African Republic",
			"CG" => "Congo",
			"CH" => "Switzerland",
			"CI" => "Cote d'Ivoire",
			"CK" => "Cook Islands",
			"CL" => "Chile",
			"CM" => "Cameroon",
			"CN" => "China",
			"CO" => "Colombia",
			"CR" => "Costa Rica",
			"CU" => "Cuba",
			"CV" => "Cape Verde",
			"CW" => "Curacao",
			"CX" => "Christmas Island",
			"CY" => "Cyprus",
			"CZ" => "Czech Republic",
			"DE" => "Germany",
			"DJ" => "Djibouti",
			"DK" => "Denmark",
			"DM" => "Dominica",
			"DO" => "Dominican Republic",
			"DZ" => "Algeria",
			"EC" => "Ecuador",
			"EE" => "Estonia",
			"EG" => "Egypt",
			"EH" => "Western Sahara",
			"ER" => "Eritrea",
			"ES" => "Spain",
			"ET" => "Ethiopia",
			"EU" => "Europe",
			"FI" => "Finland",
			"FJ" => "Fiji",
			"FK" => "Falkland Islands (Malvinas)",
			"FM" => "Micronesia, Federated States of",
			"FO" => "Faroe Islands",
			"FR" => "France",
			"GA" => "Gabon",
			"GB" => "United Kingdom",
			"GD" => "Grenada",
			"GE" => "Georgia",
			"GF" => "French Guiana",
			"GG" => "Guernsey",
			"GH" => "Ghana",
			"GI" => "Gibraltar",
			"GL" => "Greenland",
			"GM" => "Gambia",
			"GN" => "Guinea",
			"GP" => "Guadeloupe",
			"GQ" => "Equatorial Guinea",
			"GR" => "Greece",
			"GS" => "South Georgia and the South Sandwich Islands",
			"GT" => "Guatemala",
			"GU" => "Guam",
			"GW" => "Guinea-Bissau",
			"GY" => "Guyana",
			"HK" => "Hong Kong",
			"HM" => "Heard Island and McDonald Islands",
			"HN" => "Honduras",
			"HR" => "Croatia",
			"HT" => "Haiti",
			"HU" => "Hungary",
			"ID" => "Indonesia",
			"IE" => "Ireland",
			"IL" => "Israel",
			"IM" => "Isle of Man",
			"IN" => "India",
			"IO" => "British Indian Ocean Territory",
			"IQ" => "Iraq",
			"IR" => "Iran, Islamic Republic of",
			"IS" => "Iceland",
			"IT" => "Italy",
			"JE" => "Jersey",
			"JM" => "Jamaica",
			"JO" => "Jordan",
			"JP" => "Japan",
			"KE" => "Kenya",
			"KG" => "Kyrgyzstan",
			"KH" => "Cambodia",
			"KI" => "Kiribati",
			"KM" => "Comoros",
			"KN" => "Saint Kitts and Nevis",
			"KP" => "Korea, Democratic People's Republic of",
			"KR" => "Korea, Republic of",
			"KW" => "Kuwait",
			"KY" => "Cayman Islands",
			"KZ" => "Kazakhstan",
			"LA" => "Lao People's Democratic Republic",
			"LB" => "Lebanon",
			"LC" => "Saint Lucia",
			"LI" => "Liechtenstein",
			"LK" => "Sri Lanka",
			"LR" => "Liberia",
			"LS" => "Lesotho",
			"LT" => "Lithuania",
			"LU" => "Luxembourg",
			"LV" => "Latvia",
			"LY" => "Libyan Arab Jamahiriya",
			"MA" => "Morocco",
			"MC" => "Monaco",
			"MD" => "Moldova, Republic of",
			"ME" => "Montenegro",
			"MF" => "Saint Martin",
			"MG" => "Madagascar",
			"MH" => "Marshall Islands",
			"MK" => "Macedonia",
			"ML" => "Mali",
			"MM" => "Myanmar",
			"MN" => "Mongolia",
			"MO" => "Macao",
			"MP" => "Northern Mariana Islands",
			"MQ" => "Martinique",
			"MR" => "Mauritania",
			"MS" => "Montserrat",
			"MT" => "Malta",
			"MU" => "Mauritius",
			"MV" => "Maldives",
			"MW" => "Malawi",
			"MX" => "Mexico",
			"MY" => "Malaysia",
			"MZ" => "Mozambique",
			"NA" => "Namibia",
			"NC" => "New Caledonia",
			"NE" => "Niger",
			"NF" => "Norfolk Island",
			"NG" => "Nigeria",
			"NI" => "Nicaragua",
			"NL" => "Netherlands",
			"NO" => "Norway",
			"NP" => "Nepal",
			"NR" => "Nauru",
			"NU" => "Niue",
			"NZ" => "New Zealand",
			"OM" => "Oman",
			"PA" => "Panama",
			"PE" => "Peru",
			"PF" => "French Polynesia",
			"PG" => "Papua New Guinea",
			"PH" => "Philippines",
			"PK" => "Pakistan",
			"PL" => "Poland",
			"PM" => "Saint Pierre and Miquelon",
			"PN" => "Pitcairn",
			"PR" => "Puerto Rico",
			"PS" => "Palestinian Territory",
			"PT" => "Portugal",
			"PW" => "Palau",
			"PY" => "Paraguay",
			"QA" => "Qatar",
			"RE" => "Reunion",
			"RO" => "Romania",
			"RS" => "Serbia",
			"RU" => "Russian Federation",
			"RW" => "Rwanda",
			"SA" => "Saudi Arabia",
			"SB" => "Solomon Islands",
			"SC" => "Seychelles",
			"SD" => "Sudan",
			"SE" => "Sweden",
			"SG" => "Singapore",
			"SH" => "Saint Helena",
			"SI" => "Slovenia",
			"SJ" => "Svalbard and Jan Mayen",
			"SK" => "Slovakia",
			"SL" => "Sierra Leone",
			"SM" => "San Marino",
			"SN" => "Senegal",
			"SO" => "Somalia",
			"SR" => "Suriname",
			"SS" => "South Sudan",
			"ST" => "Sao Tome and Principe",
			"SV" => "El Salvador",
			"SX" => "Sint Maarten",
			"SY" => "Syrian Arab Republic",
			"SZ" => "Swaziland",
			"TC" => "Turks and Caicos Islands",
			"TD" => "Chad",
			"TF" => "French Southern Territories",
			"TG" => "Togo",
			"TH" => "Thailand",
			"TJ" => "Tajikistan",
			"TK" => "Tokelau",
			"TL" => "Timor-Leste",
			"TM" => "Turkmenistan",
			"TN" => "Tunisia",
			"TO" => "Tonga",
			"TR" => "Turkey",
			"TT" => "Trinidad and Tobago",
			"TV" => "Tuvalu",
			"TW" => "Taiwan",
			"TZ" => "Tanzania, United Republic of",
			"UA" => "Ukraine",
			"UG" => "Uganda",
			"UM" => "United States Minor Outlying Islands",
			"US" => "United States",
			"UY" => "Uruguay",
			"UZ" => "Uzbekistan",
			"VA" => "Holy See (Vatican City State)",
			"VC" => "Saint Vincent and the Grenadines",
			"VE" => "Venezuela",
			"VG" => "Virgin Islands, British",
			"VI" => "Virgin Islands, U.S.",
			"VN" => "Vietnam",
			"VU" => "Vanuatu",
			"WF" => "Wallis and Futuna",
			"WS" => "Samoa",
			"YE" => "Yemen",
			"YT" => "Mayotte",
			"ZA" => "South Africa",
			"ZM" => "Zambia",
			"ZW" => "Zimbabwe"
		);
	}

	/**
	 * Get country code from user IP Address
	 *
	 * @param string $user_ip - user IP address.
	 *
	 * @return string|boolean User country code or false on getting country code.
	 */
	protected function get_country_code_from_ip($user_ip) {
		global $aio_wp_security, $aio_wp_security_premium;
		$geodb_path = $aio_wp_security_premium->get_aiowps_premium_geodb_dir_path().'/'.AIOWPS_MAXMIND_DATABASE;
		if (class_exists('WC_Geolocation')) {
			$country_code = $this->get_woocommerce_geolocate_country_code($user_ip);
		} elseif (file_exists($geodb_path) && is_readable($geodb_path)) {
			try {
				$reader = $this->geo_reader;
				$record = $reader->country($user_ip);
				$country_code = $record->country->isoCode;
			} catch (AddressNotFoundException $e) {
				$aio_wp_security->debug_logger->log_debug("Error AddressNotFoundException. Error Message:" . $e->getMessage(), 3);
				return false; // allow visitor through if there is an exception
			} catch (InvalidDatabaseException $e) {
				$aio_wp_security->debug_logger->log_debug("Error InvalidDatabaseException. Error Message:" . $e->getMessage(), 4);
				return false;
			}
		} else {
			$country_code = $this->geolocate_via_api($user_ip);
		}

		$aio_wp_security->debug_logger->log_debug("IP: ".$user_ip." Country Code: ".$country_code, 1);

		return $country_code;
	}

	/**
	 * Get country name from WooCommerce function.
	 *
	 * @param string $user_ip - user IP address.
	 *
	 * @return string|boolean country code or false.
	 */
	private function get_woocommerce_geolocate_country_code($user_ip) {
		$gelocation = new WC_Geolocation(); // Get WC_Geolocation instance object
		$user_geo = $gelocation->geolocate_ip($user_ip, true, true); // Get geolocated user data based on AIOS detected IP address.
		if (!empty($user_geo['country'])) {
			return $user_geo['country'];
		} else {
			return false;
		}
	}

	/**
	 * Use APIs to Geolocate the user.
	 *
	 * @param  string $ip_address IP address.
	 * @return string
	 */
	private function geolocate_via_api($ip_address) {
		$country_code = get_transient('aiowps_premium_geoip_' . $ip_address);
		if (false !== $country_code) return $country_code;
		$geoip_apis = array(
			'ipinfo.io'  => 'https://ipinfo.io/%s/json',
			'ip-api.com' => 'http://ip-api.com/json/%s',
		);

		$geoip_services_keys = array_keys($geoip_apis);
		shuffle($geoip_services_keys);
		foreach ($geoip_services_keys as $service_name) {
			$service_endpoint = $geoip_apis[$service_name];
			$response = wp_safe_remote_get(sprintf($service_endpoint, $ip_address), array('timeout' => 2 ));
			if (!is_wp_error($response) && $response['body']) {
				switch ($service_name) {
					case 'ipinfo.io':
						$data = json_decode($response['body']);
						$country_code = isset($data->country) ? $data->country : '';
						break;
					case 'ip-api.com':
						$data = json_decode($response['body']);
						$country_code = isset($data->countryCode) ? $data->countryCode : ''; // @codingStandardsIgnoreLine
						break;
					default:
						break;
				}

				$country_code = sanitize_text_field(strtoupper($country_code));
				if ($country_code) {
					break;
				}
			}
		}
		// set_transient('aiowps_premium_geoip_' . $ip_address, $country_code, DAY_IN_SECONDS);
		return $country_code;
	}
}
