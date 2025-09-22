<?php
/**
 * Register all actions and filters for the plugin
 *
 * @package    GuduleLapointe/w4os
 * @subpackage w4os/includes
 */

/**
 * Register all actions and filters for the plugin.
 *
 * Maintain a list of all hooks that are registered throughout
 * the plugin, and register them with the WordPress API. Call the
 * run function to execute the list of actions and filters.
 */
class W4OS_Economy extends W4OS_Loader {
	protected $actions;
	protected $filters;

	public function __construct() {
		$this->gloebit_url = '<a href=http://dev.gloebit.com/opensim/configuration-instructions/ target=_blank>gloebit.com</a>';
	}

	public function init() {
		if ( empty( get_option( 'w4os_economy_slug' ) ) ) {
			update_option( 'w4os_economy_slug', 'economy' );
		}

		$this->actions = array(
			array(
				'hook'     => 'init',
				'callback' => 'sanitize_options',
			),
			array(
				'hook'     => 'admin_menu',
				'callback' => 'register_settings_sidebar',
			),
		);

		$this->filters = array(
			array(
				'hook'     => 'rwmb_meta_boxes',
				'callback' => 'register_settings_fields',
			),
			array(
				'hook'     => 'mb_settings_pages',
				'callback' => 'register_settings_pages',
			),
		);
	}

	function register_settings_pages( $settings_pages ) {
		$settings_pages[] = array(
			'menu_title' => __( 'Economy', 'w4os' ),
			'page_title' => __( 'Economy Settings', 'w4os' ),
			'id'         => 'w4os-economy',
			'position'   => 25,
			'parent'     => 'w4os_settings',
			'capability' => 'manage_options',
			'class'      => 'w4os-settings',
			'style'      => 'no-boxes',
			'columns'    => 2,
			'icon_url'   => 'dashicons-admin-generic',
		);

		return $settings_pages;
	}

	function register_settings_fields( $meta_boxes ) {
		$prefix = 'w4os_';

		$economy_url    = ( ! empty( W4OS_GRID_INFO['economy'] ) ) ? W4OS_GRID_INFO['economy'] : get_home_url( null, '/economy/' );
		$use_default_db = get_option( 'w4os_economy_use_default_db', true );

		$meta_boxes[] = array(
			'title'          => __( 'Economy Settings', 'w4os' ),
			'id'             => 'economy-settings',
			'settings_pages' => array( 'w4os-economy' ),
			'fields'         => array(
				array(
					'name'       => __( 'Provide Economy Helper', 'w4os' ),
					'id'         => $prefix . 'provide_economy',
					'type'       => 'switch',
					'style'      => 'rounded',
					'std'        => get_option( 'w4os_provide_economy_helpers', true ),
					'save_field' => false,
				),
				array(
					'name'        => __( 'Economy Helper URI', 'w4os' ),
					'id'          => $prefix . 'economy_helper_uri',
					'type'        => 'url',
					'placeholder' => $economy_url,
					'readonly'    => true,
					'save_field'  => false,
					'class'       => 'copyable',
					'std'         => $economy_url,
					'visible'     => array(
						'when'     => array( array( 'provide_economy', '=', 1 ) ),
						'relation' => 'or',
					),
					'desc'        => '<p>'
					. __( 'The URL must be set in Robust configuration.', 'w4os' )
					. w4os_format_ini(
						array(
							'Robust.HG.ini' => array(
								'[GridInfoService]' => array(
									'economy' => ( ! empty( W4OS_GRID_INFO['economy'] ) ) ? W4OS_GRID_INFO['economy'] : get_home_url( null, '/economy/' ),
								),
								'[LoginService]'    => array(
									'; Currency' => 'YC$ ;; Your Currency symbol, optional',
								),
							),
						)
					) . '</p>',
				),
				
				// MoneyServer Proxy Settings Group
				array(
					'name'       => __( 'MoneyServer Proxy Settings', 'w4os' ),
					'id'         => $prefix . 'moneyserver_proxy',
					'type'       => 'group',
					'visible'    => array(
						'when'     => array( array( 'provide_economy', '=', 1 ) ),
						'relation' => 'or',
					),
					'save_field' => false,
					'fields'     => array(
						array(
							'name'       => __( 'Use MoneyServer Proxy', 'w4os' ),
							'id'         => $prefix . 'use_moneyserver',
							'type'       => 'switch',
							'style'      => 'rounded',
							'std'        => get_option( 'w4os_use_moneyserver', false ),
							'desc'       => __( 'Enable to forward economy calls to an external MoneyServer via XML-RPC', 'w4os' ),
						),
						array(
							'name'        => __( 'MoneyServer URL', 'w4os' ),
							'id'          => $prefix . 'moneyserver_url',
							'type'        => 'url',
							'std'         => get_option( 'w4os_moneyserver_url', 'http://localhost:8008/' ),
							'placeholder' => 'https://your.moneyserver:8008/',
							'visible'     => array(
								'when'     => array( array( 'use_moneyserver', '=', 1 ) ),
								'relation' => 'or',
							),
							'desc'       => __( 'URL of the external MoneyServer (e.g., http://moneyserver.example.com:8008/)', 'w4os' ),
						),
						array(
							'name'        => __( 'MoneyServer Script Key', 'w4os' ),
							'id'          => $prefix . 'money_script_access_key',
							'type'        => 'text',
							'std'         => get_option( 'w4os_money_script_access_key', '123456789' ),
							'visible'     => array(
								'when'     => array( array( 'use_moneyserver', '=', 1 ) ),
								'relation' => 'or',
							),
							'desc'       => __( 'The MoneyScriptAccessKey configured in MoneyServer.ini', 'w4os' ),
						),
						array(
							'name'        => __( 'CA Certificate Path', 'w4os' ),
							'id'          => $prefix . 'moneyserver_cainfo',
							'type'        => 'text',
							'std'         => get_option( 'w4os_moneyserver_cainfo', '' ),
							'placeholder' => '/etc/ssl/certs/moneyserver-cert.pem',
							'visible'     => array(
								'when'     => array( array( 'use_moneyserver', '=', 1 ) ),
								'relation' => 'or',
							),
							'desc'       => __( 'Optional: path to PEM file for self-signed TLS certificates', 'w4os' ),
						),
					),
				),

				// Currency Provider Selection
				array(
					'name'       => __( 'Currency Provider', 'w4os' ),
					'id'         => $prefix . 'currency_provider',
					'type'       => 'radio',
					'std'        => empty( get_option( 'w4os_currency_provider' ) ) ? 'none' : get_option( 'w4os_currency_provider' ),
					'save_field' => false,
					'visible'    => array(
						'when'     => array( array( 'provide_economy', '=', 1 ) ),
						'relation' => 'or',
					),
					'options'    => array(
						'gloebit' => 'Gloebit (<a href=http://dev.gloebit.com/opensim/configuration-instructions/ target=_blank>www.gloebit.com</a>)',
						'podex'   => 'Podex (<a href=http://www.podex.info/p/info-for-grid-owners.html target=_blank>www.podex.info</a>)',
						'none'    => __( 'Generic MoneyServer (DTL/NSL compatible)', 'w4os' ),
					),
					'inline'     => false,
				),

				// Currency Rate Settings
				array(
					'name'        => __( 'Currency Conversion Rate', 'w4os' ),
					'id'          => $prefix . 'currency_rate',
					'type'        => 'number',
					'desc'        => __( 'Amount to pay in US$ for 1000 in-world money units. Used for cost estimation.', 'w4os' ),
					'step'        => 'any',
					'placeholder' => 10,
					'size'        => 5,
					'std'         => get_option( 'w4os_currency_rate', 10 ),
					'save_field'  => false,
					'visible'     => array(
						'when'     => array( array( 'provide_economy', '=', 1 ) ),
						'relation' => 'or',
					),
				),
				array(
					'name'        => __( 'Currency Rate Per', 'w4os' ),
					'id'          => $prefix . 'currency_rate_per',
					'type'        => 'number',
					'desc'        => __( 'Number of in-world money units for the conversion rate above.', 'w4os' ),
					'placeholder' => 1000,
					'size'        => 5,
					'std'         => get_option( 'w4os_currency_rate_per', 1000 ),
					'save_field'  => false,
					'visible'     => array(
						'when'     => array( array( 'provide_economy', '=', 1 ) ),
						'relation' => 'or',
					),
				),

				// Provider-specific configurations
				array(
					'name'       => __( 'Gloebit Configuration', 'w4os' ),
					'id'         => $prefix . 'gloebit_config',
					'type'       => 'custom_html',
					'std'        => $this->get_gloebit_config_html(),
					'save_field' => false,
					'visible'    => array(
						'when'     => array( array( 'currency_provider', '=', 'gloebit' ) ),
						'relation' => 'or',
					),
				),

				array(
					'name'       => __( 'Generic MoneyServer Configuration', 'w4os' ),
					'id'         => $prefix . 'generic_moneyserver_config',
					'type'       => 'custom_html',
					'std'        => $this->get_generic_moneyserver_config_html(),
					'save_field' => false,
					'visible'    => array(
						'when'     => array( array( 'currency_provider', '=', 'none' ) ),
						'relation' => 'or',
					),
				),

				array(
					'name'       => __( 'Podex Options', 'w4os' ),
					'id'         => $prefix . 'podex_options',
					'type'       => 'group',
					'visible'    => array(
						'when'     => array( array( 'currency_provider', '=', 'podex' ) ),
						'relation' => 'or',
					),
					'save_field' => false,
					'fields'     => array(
						array(
							'name'        => __( 'Podex Redirect Message', 'w4os' ),
							'id'          => $prefix . 'podex_error_message',
							'type'        => 'text',
							'std'         => get_option( 'w4os_podex_error_message' ),
							'placeholder' => __( 'Please use our terminals in-world to proceed. Click OK to teleport to Podex Exchange area.', 'w4os' ),
						),
						array(
							'name'        => __( 'Exchange Teleport URL', 'w4os' ),
							'id'          => $prefix . 'podex_teleport_url',
							'type'        => 'text',
							'required'    => true,
							'std'         => get_option( 'w4os_podex_redirect_url' ),
							'placeholder' => 'secondlife://Podex Exchange/128/128/21',
						),
					),
				),
			),
		);

		return $meta_boxes;
	}

	private function get_gloebit_config_html() {
		$use_default_db = get_option( 'w4os_economy_use_default_db', true );
		
		return '<ol><li>' . join(
			'</li><li>',
			array(
				'<strong>' . __( 'Gloebit module needs to be configured before restarting the region, otherwise it could crash the simulator.', 'w4os' ) . '</strong>',
				W4OS::sprintf_safe(
					__( 'For Linux, see %s to avoid certificate-related errors.', 'w4os' ),
					'<a href=https://github.com/magicoli/opensim-helpers/blob/master/README-Gloebit.md target=_blank>README-Gloebit.md</a>'
				),
				W4OS::sprintf_safe(
					__( 'Register an account or connect on %1$s and Follow instructions on %2$s to setup an app for your grid/simulator.', 'w4os' ),
					'<a href=https://www.gloebit.com/ target=_blank>gloebit.com</a>',
					'<a href=http://dev.gloebit.com/opensim/configuration-instructions/ target=_blank>dev.gloebit.com</a>'
				),
				__( 'Add Gloebit configuration in OpenSim.ini.', 'w4os' ),
				W4OS::sprintf_safe(
					'Download the latest dll in your OpenSimulator bin/ folder (rename it Gloebit.dll), from %1$s or %2$s',
					'<a href="https://github.com/GuduleLapointe/opensim-debian" target="_blank">github.com/GuduleLapointe/opensim-debian</a>',
					'<a href="http://dev.gloebit.com/opensim/downloads/" target="_blank">dev.gloebit.com</a>'
				),
			)
		) . '</li></ol>'
		. w4os_format_ini(
			array(
				'OpenSim.ini' => array(
					'[Economy]' => array(
						'economymodule'      => 'Gloebit',
						'economy'            => ( ! empty( W4OS_GRID_INFO['economy'] ) ) ? W4OS_GRID_INFO['economy'] : get_home_url( null, '/economy/' ),
						'SellEnabled'        => 'true',
						'; PriceUpload'      => '0',
						'; PriceGroupCreate' => '0',
					),
					'[Gloebit]' => array(
						'Enabled'        => 'true',
						'GLBEnvironment' => 'production',
						'GLBKey'         => '(your Gloebit app key)',
						'GLBSecret'      => '(your Gloebit app secret)',
						'GLBOwnerName'   => 'Banker Name',
						'GLBOwnerEmail'  => 'banker@example.org',
					),
				),
			)
		) . '</p>';
	}

	private function get_generic_moneyserver_config_html() {
		$use_default_db = get_option( 'w4os_economy_use_default_db', true );
		$use_moneyserver = get_option( 'w4os_use_moneyserver', false );
		$moneyserver_url = get_option( 'w4os_moneyserver_url', 'http://localhost:8008/' );
		
		$config_instructions = array(
			__( 'The Generic MoneyServer is compatible with DTL/NSL MoneyServer and other XML-RPC based money servers.', 'w4os' ),
			__( 'You can use either direct database access or XML-RPC proxy mode.', 'w4os' ),
		);

		if ($use_moneyserver) {
			$config_instructions[] = W4OS::sprintf_safe(
				__( 'XML-RPC Proxy Mode: Economy calls will be forwarded to %s', 'w4os' ),
				'<strong>' . $moneyserver_url . '</strong>'
			);
			$config_instructions[] = __( 'Make sure the MoneyServer is running and accessible at the specified URL.', 'w4os' );
		} else {
			$config_instructions[] = __( 'Direct Database Mode: Economy calls will access the database directly.', 'w4os' );
			$config_instructions[] = __( 'Make sure the database credentials are correct and the money server tables exist.', 'w4os' );
		}

		return '<ol><li>' . join( '</li><li>', $config_instructions ) . '</li></ol>'
		. w4os_format_ini(
			array(
				'MoneyServer.ini' => array(
					'[MoneyServer]' => array(
						'EnableScriptSendMoney' => 'true',
						'MoneyScriptAccessKey' => esc_attr( get_option( 'w4os_money_script_access_key', '123456789' ) ),
					),
				),
				'OpenSim.ini' => array(
					'[Economy]' => array(
						'EconomyModule' => 'DTLMoneyModule',
						'MoneyServerURL' => ( ! empty( W4OS_GRID_INFO['economy'] ) ) ? W4OS_GRID_INFO['economy'] : get_home_url( null, '/economy/' ),
					),
				),
			)
		) . '</p>';
	}

	function register_settings_sidebar() {
		add_meta_box(
			'sidebar-content',
			'Settings Sidebar',
			array( $this, 'sidebar_content' ),
			'opensimulator_page_w4os-economy',
			'side'
		);
	}

	function sidebar_content() {
		echo '<ul><li>' . join(
			'</li><li>',
			array(
				__( 'Economy helpers are additional scripts needed if you implement economy on your grid (with real or fake currency).', 'w4os' ),
				__( 'Helper scripts allow communication between the money server and the grid: current balance update, currency cost estimation, land and object sales, payments...', 'w4os' ),
				'<strong>' . __( 'New Feature: MoneyServer Proxy Mode', 'w4os' ) . '</strong>',
				__( 'The plugin now supports XML-RPC proxy mode for DTL/NSL compatible money servers. This allows you to run the money server on a separate machine.', 'w4os' ),
				'<strong>' . __( 'Supported Currency Providers:', 'w4os' ) . '</strong>',
				'<ul><li>' . join(
					'</li><li>',
					array(
						'Gloebit (real currency)',
						'Podex (real currency)',
						'Generic MoneyServer (DTL/NSL compatible - real or fake currency)',
					)
				) . '</li></ul>',
				'&nbsp;',
				__( 'Ready to use binaries and example config files can be downloaded here:', 'w4os' )
				. '<br><a href="https://github.com/magicoli/opensim-helpers/tree/master/bin">github.com/magicoli/opensim-helpers</a>',
			)
		) . '</li></ul>';
	}

	function sanitize_options() {
		if ( empty( $_POST ) ) {
			return;
		}

		if ( isset( $_POST['nonce_economy-settings'] ) && wp_verify_nonce( $_POST['nonce_economy-settings'], 'rwmb-save-economy-settings' ) ) {
			$options = array_merge(
				array(
					'w4os_economy_helper_uri'      => null,
					'w4os_currency_rate'           => null,
					'w4os_currency_rate_per'       => null,
					'w4os_money_script_access_key' => null,
					'w4os_currency_provider'       => null,
					'w4os_podex_options'           => array(),
					'w4os_moneyserver_proxy'       => array(),
				),
				$_POST
			);
			
			$provide = isset( $_POST['w4os_provide_economy'] ) ? true : false;
			update_option( 'w4os_provide_economy_helpers', $provide );

			if ( $provide ) {
				// Save MoneyServer proxy settings
				if ( isset( $_POST['w4os_moneyserver_proxy'] ) ) {
					$proxy_settings = $_POST['w4os_moneyserver_proxy'];
					update_option( 'w4os_use_moneyserver', isset( $proxy_settings['w4os_use_moneyserver'] ) );
					update_option( 'w4os_moneyserver_url', sanitize_text_field( $proxy_settings['w4os_moneyserver_url'] ) );
					update_option( 'w4os_money_script_access_key', sanitize_text_field( $proxy_settings['w4os_money_script_access_key'] ) );
					update_option( 'w4os_moneyserver_cainfo', sanitize_text_field( $proxy_settings['w4os_moneyserver_cainfo'] ) );
				}

				// Save currency rates
				update_option( 'w4os_currency_rate', floatval( $options['w4os_currency_rate'] ) );
				update_option( 'w4os_currency_rate_per', intval( $options['w4os_currency_rate_per'] ) );

				// Save provider
				$provider = ( $options['w4os_currency_provider'] == 'none' ) ? null : $options['w4os_currency_provider'];
				update_option( 'w4os_currency_provider', $provider );

				// Save provider-specific options
				switch ( $provider ) {
					case 'podex':
						$podex = array_merge(
							array(
								'w4os_podex_error_message' => null,
								'w4os_podex_teleport_url'  => null,
							),
							$_POST['w4os_podex_options']
						);
						update_option( 'w4os_podex_error_message', $podex['w4os_podex_error_message'] );
						update_option( 'w4os_podex_redirect_url', $podex['w4os_podex_teleport_url'] );
						break;
				}
			}
		}
	}
}

$this->loaders[] = new W4OS_Economy();