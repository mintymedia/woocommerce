<?php
/**
 * Create & render settings page
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( !class_exists( 'WooCommerce_MyParcel_Settings' ) ) :

class WooCommerce_MyParcel_Settings {

	public $options_page_hook;

	public function __construct() {
		$this->callbacks = include( 'class-wcmp-settings-callbacks.php' );
		add_action( 'admin_menu', array( $this, 'menu' ) );
		add_filter( 'plugin_action_links_'.WooCommerce_MyParcel()->plugin_basename, array( $this, 'add_settings_link' ) );

		add_action( 'admin_init', array( $this, 'general_settings' ) );
		add_action( 'admin_init', array( $this, 'export_defaults_settings' ) );
	}

	/**
	 * Add settings item to WooCommerce menu
	 */
	public function menu() {
		add_submenu_page(
			'woocommerce',
			__( 'MyParcel', 'woocommerce-myparcel' ),
			__( 'MyParcel', 'woocommerce-myparcel' ),
			'manage_options',
			'woocommerce_myparcel_settings',
			array( $this, 'settings_page' )
		);	
	}
	
	/**
	 * Add settings link to plugins page
	 */
	public function add_settings_link( $links ) {
		$settings_link = '<a href="admin.php?page=woocommerce_myparcel_settings">'. __( 'Settings', 'woocommerce-myparcel' ) . '</a>';
		array_push( $links, $settings_link );
		return $links;
	}

	public function settings_page() {
		$settings_tabs = apply_filters( 'woocommerce_myparcel_settings_tabs', array (
				'general'			=> __( 'General', 'woocommerce-myparcel' ),
				'export_defaults'	=> __( 'Default export settings', 'woocommerce-myparcel' ),
				'checkout'			=> __( 'Checkout', 'woocommerce-myparcel' ),
			)
		);

		$active_tab = isset( $_GET[ 'tab' ] ) ? $_GET[ 'tab' ] : 'general';
		?>
		<script type="text/javascript">
		jQuery(function($) {
			if ( $( 'input.insured_amount' ).val() > 499 ) {
				$( 'select.insured_amount' ).val('');
			}
			// hide insurance options if unsured not checked
			$('input.insured').change(function () {
				if (this.checked) {
					$( 'select.insured_amount' ).prop('disabled', false);
					$( 'select.insured_amount' ).closest('tr').show();
					$( 'select.insured_amount' ).change();
				} else {
					$( 'select.insured_amount' ).prop('disabled', true);
					$( 'select.insured_amount' ).closest('tr').hide();
					$( 'input.insured_amount' ).closest('tr').hide();
				}
			}).change(); //ensure visible state matches initially

			// hide & disable insured amount input if not needed
			$('select.insured_amount').change(function () {
				if ( $( 'select.insured_amount' ).val() || !$('input.insured').prop('checked') ) {
					$( 'input.insured_amount' ).val('');
					$( 'input.insured_amount' ).prop('disabled', true);
					$( 'input.insured_amount' ).closest('tr').hide();
				} else {
					$( 'input.insured_amount' ).prop('disabled', false);
					$( 'input.insured_amount' ).closest('tr').show();
				}
			}).change(); //ensure visible state matches initially
		});
		</script>

		<div class="wrap">
			<h1><?php _e( 'WooCommerce MyParcel Settings', 'woocommerce-myparcel' ); ?></h1>
			<h2 class="nav-tab-wrapper">
			<?php
			foreach ($settings_tabs as $tab_slug => $tab_title ) {
				printf('<a href="?page=woocommerce_myparcel_settings&tab=%1$s" class="nav-tab nav-tab-%1$s %2$s">%3$s</a>', $tab_slug, (($active_tab == $tab_slug) ? 'nav-tab-active' : ''), $tab_title);
			}
			?>
			</h2>

			<?php do_action( 'woocommerce_myparcel_before_settings_page', $active_tab ); ?>
				
			<form method="post" action="options.php" id="woocommerce-myparcel-settings">
				<?php
					do_action( 'woocommerce_myparcel_before_settings', $active_tab );
					settings_fields( 'woocommerce_myparcel_'.$active_tab.'_settings' );
					do_settings_sections( 'woocommerce_myparcel_'.$active_tab.'_settings' );
					do_action( 'woocommerce_myparcel_after_settings', $active_tab );

					submit_button();
				?>
			</form>

			<?php do_action( 'woocommerce_myparcel_after_settings_page', $active_tab ); ?>

		</div>
		<?php
	}
	
	/**
	 * Register General settings
	 */
	public function general_settings() {
		$option_group = 'woocommerce_myparcel_general_settings';

		// Register settings.
		$option_name = 'woocommerce_myparcel_general_settings';
		register_setting( $option_group, $option_name, array( $this->callbacks, 'validate' ) );

		// Create option in wp_options.
		if ( false === get_option( $option_name ) ) {
			$this->default_settings( $option_name );
		}

		// API section.
		add_settings_section(
			'api',
			__( 'API settings', 'woocommerce-myparcel' ),
			array( $this->callbacks, 'section' ),
			$option_group
		);

		add_settings_field(
			'api_username',
			__( 'Username', 'woocommerce-myparcel' ),
			array( $this->callbacks, 'text_input' ),
			$option_group,
			'api',
			array(
				'option_name'	=> $option_name,
				'id'			=> 'api_username',
				'size'			=> 50,
			)
		);

		add_settings_field(
			'api_key',
			__( 'Key', 'woocommerce-myparcel' ),
			array( $this->callbacks, 'text_input' ),
			$option_group,
			'api',
			array(
				'option_name'	=> $option_name,
				'id'			=> 'api_key',
				'size'			=> 50,
			)
		);

		// General section.
		add_settings_section(
			'general',
			__( 'General settings', 'woocommerce-myparcel' ),
			array( $this->callbacks, 'section' ),
			$option_group
		);

		add_settings_field(
			'download_display',
			__( 'Label display', 'wpo_wcpdf' ),
			array( $this->callbacks, 'radio_button' ),
			$option_group,
			'general',
			array(
				'option_name'	=> $option_name,
				'id'			=> 'download_display',
				'options' 		=> array(
					'download'	=> __( 'Download PDF' , 'woocommerce-myparcel' ),
					'display'	=> __( 'Open de PDF in a new tab' , 'woocommerce-myparcel' ),
				),
			)
		);

		add_settings_field(
			'email_tracktrace',
			__( 'Track&trace email', 'woocommerce-myparcel' ),
			array( $this->callbacks, 'checkbox' ),
			$option_group,
			'general',
			array(
				'option_name'	=> $option_name,
				'id'			=> 'email_tracktrace',
				'description'	=> __( 'Add the track&trace code to emails to the customer.<br/><strong>Note!</strong> When you select this option, make sure you have not enabled the track & trace email in your MyParcel backend.', 'woocommerce-myparcel' )
			)
		);

		add_settings_field(
			'myaccount_tracktrace',
			__( 'Track&trace in My Account', 'woocommerce-myparcel' ),
			array( $this->callbacks, 'checkbox' ),
			$option_group,
			'general',
			array(
				'option_name'	=> $option_name,
				'id'			=> 'myaccount_tracktrace',
				'description'	=> __( 'Show track&trace trace code & link in My Account.', 'woocommerce-myparcel' )
			)
		);

		add_settings_field(
			'process_directly',
			__( 'Process shipments directly', 'woocommerce-myparcel' ),
			array( $this->callbacks, 'checkbox' ),
			$option_group,
			'general',
			array(
				'option_name'	=> $option_name,
				'id'			=> 'process_directly',
				'description'	=> __( 'When you enable this option, shipments will be directly processed when sent to myparcel.', 'woocommerce-myparcel' )
			)
		);

		add_settings_field(
			'auto_complete',
			__( 'Auto complete orders', 'woocommerce-myparcel' ),
			array( $this->callbacks, 'checkbox' ),
			$option_group,
			'general',
			array(
				'option_name'	=> $option_name,
				'id'			=> 'auto_complete',
				'description'	=> __( 'Automatically set order status to complete after succesfull MyParcel export.<br/>Make sure <strong>Process shipments directly</strong> is enabled when you use this option together with the <strong>Email track&trace code</strong> option, otherwise the track&trace code will not be included in the customer email.', 'woocommerce-myparcel' )
			)
		);		

		add_settings_field(
			'keep_consignments',
			__( 'Keep old shipments', 'woocommerce-myparcel' ),
			array( $this->callbacks, 'checkbox' ),
			$option_group,
			'general',
			array(
				'option_name'	=> $option_name,
				'id'			=> 'keep_consignments',
				'default'		=> 0,
				'description'	=> __( 'With this option enaled, data from previous shipments (track & trace links) will be kept in the order when you export more than once.', 'woocommerce-myparcel' )
			)
		);

		// Diagnostics section.
		add_settings_section(
			'diagnostics',
			__( 'Diagnostic tools', 'woocommerce-myparcel' ),
			array( $this->callbacks, 'section' ),
			$option_group
		);

		$log_file_url = WooCommerce_MyParcel()->plugin_url() . '/myparcel_log.txt';
		$log_file_path = WooCommerce_MyParcel()->plugin_path() . '/myparcel_log.txt';
		$debugging_only = __( 'Only enable this option when debugging!', 'woocommerce-myparcel' );
		$download_link = sprintf('%s<br/><a href="%s" target="_blank">%s</a>', $debugging_only, $log_file_url, __( 'Download log file', 'woocommerce-myparcel' ) );

		add_settings_field(
			'error_logging',
			__( 'Log API communication', 'woocommerce-myparcel' ),
			array( $this->callbacks, 'checkbox' ),
			$option_group,
			'diagnostics',
			array(
				'option_name'	=> $option_name,
				'id'			=> 'error_logging',
				'description'	=> file_exists($log_file_path) ? $download_link : $debugging_only,
			)
		);

	}

	/**
	 * Register General settings
	 */
	public function export_defaults_settings() {
		$option_group = 'woocommerce_myparcel_export_defaults_settings';

		// Register settings.
		$option_name = 'woocommerce_myparcel_export_defaults_settings';
		register_setting( $option_group, $option_name, array( $this->callbacks, 'validate' ) );

		// Create option in wp_options.
		if ( false === get_option( $option_name ) ) {
			$this->default_settings( $option_name );
		}

		// API section.
		add_settings_section(
			'defaults',
			__( 'Default export settings', 'woocommerce-myparcel' ),
			array( $this->callbacks, 'section' ),
			$option_group
		);

		add_settings_field(
			'shipment_type',
			__( 'Shipment type', 'woocommerce-myparcel' ),
			array( $this->callbacks, 'select' ),
			$option_group,
			'defaults',
			array(
				'option_name'	=> $option_name,
				'id'			=> 'shipment_type',
				'default'		=> 'standard',
				'options' 		=> array(
					'standard'		=> __( 'Parcel' , 'woocommerce-myparcel' ),
					'letterbox'		=> __( 'Letterbox' , 'woocommerce-myparcel' ),
					'unpaid_letter'	=> __( 'Unpaid letter' , 'woocommerce-myparcel' ),
				),
			)
		);

		add_settings_field(
			'connect_email',
			__( 'Connect customer email', 'woocommerce-myparcel' ),
			array( $this->callbacks, 'checkbox' ),
			$option_group,
			'defaults',
			array(
				'option_name'	=> $option_name,
				'id'			=> 'connect_email',
				'description'	=> __( 'When you connect the customer email, MyParcel can send a Track&Trace email to this address. In your <a href="http://www.myparcel.nl/backend/instellingen/tracktrace">MyParcel backend</a> you can enable or disable this email and format it in your own style.', 'woocommerce-myparcel' )
			)
		);
		
		add_settings_field(
			'connect_phone',
			__( 'Connect customer phone', 'woocommerce-myparcel' ),
			array( $this->callbacks, 'checkbox' ),
			$option_group,
			'defaults',
			array(
				'option_name'	=> $option_name,
				'id'			=> 'connect_phone',
				'description'	=> __( "When you connect the customer's phone number, the courier can use this for the delivery of the parcel. This greatly increases the delivery success rate for foreign shipments.", 'woocommerce-myparcel' )
			)
		);
		
		add_settings_field(
			'extra_size',
			__( 'Extra large size', 'woocommerce-myparcel' ).' (+ &euro;2.19)',
			array( $this->callbacks, 'checkbox' ),
			$option_group,
			'defaults',
			array(
				'option_name'	=> $option_name,
				'id'			=> 'extra_size',
				'description'	=> __( 'Enable this option when your shipment is bigger than 100 x 70 x 50 cm, but smaller than 175 x 78 x 58 cm. An extra fee of &euro;&nbsp;2,00 will be charged.<br/><strong>Note!</strong> If the parcel is bigger than 175 x 78 x 58 of or heavier than 30 kg, the pallet rate of &euro;&nbsp;70,00 will be charged.', 'woocommerce-myparcel' )
			)
		);
		
		add_settings_field(
			'home_address_only',
			__( 'Home address only', 'woocommerce-myparcel' ).' (+ &euro;0.26)',
			array( $this->callbacks, 'checkbox' ),
			$option_group,
			'defaults',
			array(
				'option_name'	=> $option_name,
				'id'			=> 'home_address_only',
			)
		);
		
		add_settings_field(
			'signature_on_receipt',
			__( 'Signature on delivery', 'woocommerce-myparcel' ).' (+ &euro;0.33)',
			array( $this->callbacks, 'checkbox' ),
			$option_group,
			'defaults',
			array(
				'option_name'	=> $option_name,
				'id'			=> 'signature_on_receipt',
				'description'	=> __( 'The parcel will be offered at the delivery address. If the recipient is not at home, the parcel will be delivered to the neighbours. In both cases, a signuture will be required.', 'woocommerce-myparcel' )
			)
		);
		
		add_settings_field(
			'home_address_signature',
			__( 'Home address only + signature on delivery', 'woocommerce-myparcel' ).' (+ &euro;0.40)',
			array( $this->callbacks, 'checkbox' ),
			$option_group,
			'defaults',
			array(
				'option_name'	=> $option_name,
				'id'			=> 'home_address_signature',
				'description'	=> __( 'This is the secure option. The parcel will only be delivered at the recipient address, who has to sign for delivery. This way you can be certain the parcel will be handed to the recipient.', 'woocommerce-myparcel' )
			)
		);
		
		add_settings_field(
			'return_if_no_answer',
			__( 'Return if no answer', 'woocommerce-myparcel' ),
			array( $this->callbacks, 'checkbox' ),
			$option_group,
			'defaults',
			array(
				'option_name'	=> $option_name,
				'id'			=> 'return_if_no_answer',
				'description'	=> __( 'By default, a parcel will be offered twice. After two unsuccessful delivery attempts, the parcel will be available at the nearest pickup point for three weeks. There it can be picked up by the recipient with the note that was left by the courier. If you want to receive the parcel back directly and NOT forward it to the pickup point, enable this option. Note: the parcel will be returned sooner than normal, for which we have to charge a fee.', 'woocommerce-myparcel' )
			)
		);
		
		add_settings_field(
			'insured',
			__( 'Insured shipment', 'woocommerce-myparcel' ).' (from + &euro;0.50)',
			array( $this->callbacks, 'checkbox' ),
			$option_group,
			'defaults',
			array(
				'option_name'	=> $option_name,
				'id'			=> 'insured',
				'description'	=> __( 'By default, there is no insurance on the shipments. If you still want to insure the shipment, you can do that from &euro;0.50. We insure the purchase value of the shipment, with a maximum insured value of &euro; 5.000. Insured parcels always contain the options "Home address only" en "Signature for delivery"', 'woocommerce-myparcel' ),
				'class'			=> 'insured',
			)
		);

		add_settings_field(
			'insured_amount',
			__( 'Insured amount', 'woocommerce-myparcel' ),
			array( $this->callbacks, 'select' ),
			$option_group,
			'defaults',
			array(
				'option_name'	=> $option_name,
				'id'			=> 'insured_amount',
				'default'		=> 'standard',
				'class'			=> 'insured_amount',
				'options' 		=> array(
					'49'		=> __( 'Insured up to &euro; 50 (+ &euro; 0.50)' , 'woocommerce-myparcel' ),
					'249'		=> __( 'Insured up to  &euro; 250 (+ &euro; 1.00)' , 'woocommerce-myparcel' ),
					'499'		=> __( 'Insured up to  &euro; 500 (+ &euro; 1.50)' , 'woocommerce-myparcel' ),
					''			=> __( '> &euro; 500 insured (+ &euro; 1.50)' , 'woocommerce-myparcel' ),
				),
			)
		);

		add_settings_field(
			'insured_amount_custom',
			__( 'Insured amount (in euro)', 'woocommerce-myparcel' ),
			array( $this->callbacks, 'text_input' ),
			$option_group,
			'defaults',
			array(
				'option_name'	=> $option_name,
				'id'			=> 'insured_amount_custom',
				'size'			=> '5',
				'class'			=> 'insured_amount',
			)
		);

		add_settings_field(
			'custom_id',
			__( 'Custom ID', 'woocommerce-myparcel' ),
			array( $this->callbacks, 'text_input' ),
			$option_group,
			'defaults',
			array(
				'option_name'	=> $option_name,
				'id'			=> 'custom_id',
				'size'			=> '25',
				'description'	=> __( "With this option, you can add a custom ID to the shipment. This will be printed on the top left of the label, and you can use this to search or sort shipments in the MyParcel Backend. Use <strong>[ORDER_NR]</strong> to include the order number.", 'woocommerce-myparcel' ),
			)
		);

		add_settings_field(
			'empty_parcel_weight',
			__( 'Empty parcel weight (grams)', 'woocommerce-myparcel' ),
			array( $this->callbacks, 'text_input' ),
			$option_group,
			'defaults',
			array(
				'option_name'	=> $option_name,
				'id'			=> 'empty_parcel_weight',
				'size'			=> '5',
				'description'	=> __( 'Default weight of your empty parcel, rounded to grams.', 'woocommerce-myparcel' ),
			)
		);

	}
	
	/**
	 * Set default settings.
	 * 
	 * @return void.
	 */
	public function default_settings( $option ) {
		// $default = array(
		// 	'process'			=> '1',
		// 	'keep_consignments'	=> '0',
		// 	'download_display'	=> 'download',
		// 	'email'				=> '1',
		// 	'telefoon'			=> '1',
		// 	'extragroot'		=> '0',
		// 	'huisadres'			=> '0',
		// 	'handtekening'		=> '0',
		// 	'huishand'			=> '0',
		// 	'retourbgg'			=> '0',
		// 	'verzekerd'			=> '0',
		// 	'verzekerdbedrag'	=> '0',
		// 	'kenmerk'			=> '',
		// 	'verpakkingsgewicht'=> '0',
		// );
	
		// add_option( 'wcmyparcel_settings', $default );

		switch ( $option ) {
			case 'woocommerce_myparcel_general_settings':
				$default = array(

				);
				break;
			case 'woocommerce_myparcel_export_defaults_settings':
			case 'woocommerce_myparcel_checkout_settings':
			default:
				$default = array();
				break;
		}

		if ( false === get_option( $option ) ) {
			add_option( $option, $default );
		} else {
			update_option( $option, $default );
		}
	}
}

endif; // class_exists

return new WooCommerce_MyParcel_Settings();