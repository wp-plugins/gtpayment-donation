<?php
/*
Plugin Name: GTPayment Donations
Description: Easy and simple setup and insertion of GTPayment donate buttons with a shortcode or through a sidebar Widget. Donation purpose can be set for each button. A few other customization options are available as well.
Version: 1.0.0
Author: Harry He
Email: harry.he@gtpayment.com
Author URI: https://gtpayment.com/
Text Domain: GTPayment-donations 

Copyright 2010 Harry He  (email : harry.he [at] gtpayment [dot] com)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/


class GTPayment_donations {
	var $plugin_options = 'GTPayment_donations_options';
	var $donate_buttons = array('small' => 'https://gtpayment.com/images/buttons/donations/01.gif',
						  		'large' => 'https://gtpayment.com/images/buttons/donations/02.gif',
						  		'cards' => 'https://gtpayment.com/images/buttons/donations/05.gif');
	var $currency_codes = array('EUR' => 'Euros (&euro;)',
						   		'GBP' => 'Pounds Sterling (&pound;)',
						   		'USD' => 'U.S. Dollars ($)',
						   		'HKD' => 'Hong Kong Dollar ($)',
						   		'SGD' => 'Singapore Dollar ($)',
								'CNY' => 'China Yuan',
								'THB' => 'Thailand Baht',
								'MYR' => 'Malaysian Ringgit'
						   		);
	// Languages that GTPayment default to en_US has been commented out for the time being.
	var $localized_buttons = array(
								   'zh-CN' => 'Simplified Chinese',	
								   'en-US' => 'U.S. English');
	/**
	* Constructor
	*
	*/
	function GTPayment_donations()
	{
		// define URL
		define('GTPayment_donations_ABSPATH', WP_PLUGIN_DIR.'/'.plugin_basename( dirname(__FILE__) ).'/' );
		define('GTPayment_donations_URLPATH', WP_PLUGIN_URL.'/'.plugin_basename( dirname(__FILE__) ).'/' );

		// Define the domain for translations
		//load_plugin_textdomain(	'GTPayment-donations', false, dirname(plugin_basename(__FILE__)) . '/languages/');

		// Check installed Wordpress version.
		global $wp_version;
		if ( version_compare($wp_version, '2.7', '>=') ) {
//			include_once (dirname (__FILE__)."/tinymce/tinymce.php");
			$this->init_hooks();
		} else {
			$this->version_warning();
		}
	}

	/**
	* Initializes the hooks for the plugin
	*
	* @returns	Nothing
	*/
	function init_hooks() {
		add_action('admin_menu', array(&$this,'wp_admin'));
		add_shortcode('GTPayment-donation', array(&$this,'GTPayment_shortcode'));
		global $wp_version;
		if ( version_compare($wp_version, '2.8', '>=') )
			add_action( 'widgets_init',  array(&$this,'load_widget') );
	}
	
	/**
	* Displays a warning when installed in an old Wordpress Version
	*
	* @returns	Nothing
	*/
	function version_warning() {
		echo '<div class="updated fade"><p><strong>'.__('GTPayment Donations requires WordPress version 2.7 or later!', 'GTPayment-donations').'</strong></p></div>';
	}
	
	/**
	* Register the Widget
	*
	*/
	function load_widget() {
		register_widget( 'GTPayment_donations_Widget' );
	}

	/**
	* Create and register the GTPayment shortcode
	*
	*/
	function GTPayment_shortcode($atts) {
		extract(shortcode_atts(array(
			'purpose' => '',
			'reference' => '',
			'amount' => '',
		), $atts));

		return $this->generate_html($purpose, $reference, $amount);
	}
	
	/**
	* Generate the GTPayment button HTML code
	*
	*/
	function generate_html($purpose = null, $reference = null, $amount = null) {
		$pd_options = get_option($this->plugin_options);

		// Set overrides for purpose and reference if defined
		$purpose = (!$purpose) ? $pd_options['purpose'] : $purpose;
		$reference = (!$reference) ? $pd_options['reference'] : $reference;
		$amount = (!$amount) ? $pd_options['amount'] : $amount;
		
		# Build the button
		$GTPayment_btn =	'<form action="https://www.gtpayment.com/paymentype.do" method="post">';
		$GTPayment_btn .=	'<div class="GTPayment-donations">';
		$GTPayment_btn .=	'<input type="hidden" name="member" value="' .$pd_options['GTPayment_account']. '" />';

		// Optional Settings
		if ($pd_options['cancel_page'])
			$GTPayment_btn .=	'<input type="hidden" name="ucancel" value="' .$pd_options['cancel_page']. '" />';
		if ($pd_options['return_page'])
			$GTPayment_btn .=	'<input type="hidden" name="ureturn" value="' .$pd_options['return_page']. '" />'; // Return Page
		if ($purpose)
			$GTPayment_btn .=	'<input type="hidden" name="product" value="' .$purpose. '" />';	// Purpose
		if ($reference)
			$GTPayment_btn .=	'<input type="hidden" name="productid" value="' .$reference. '" />';	// LightWave Plugin
		if ($amount)
			$GTPayment_btn .=     '<input type="hidden" name="price" value="' .$amount. '" />';

		// More Settings
		if (isset($pd_options['currency_code']))
			$GTPayment_btn .=     '<input type="hidden" name="membercurrency" value="' .$pd_options['currency_code']. '" />';
		if (isset($pd_options['button_localized']))
			{ $button_localized = $pd_options['button_localized']; } else { $button_localized = 'en-US'; }
			
		$GTPayment_btn .=     '<input type="hidden" name="lang" value="' .$button_localized. '" />';
		// Settings not implemented yet
		//		$GTPayment_btn .=     '<input type="hidden" name="amount" value="20" />';

		// Get the button URL
		if ( $pd_options['button'] == "custom" )
			$button_url = $pd_options['button_url'];
		else
			$button_url = str_replace('en_US', $button_localized, $this->donate_buttons[$pd_options['button']]);

		$GTPayment_btn .=	'<input type="image" src="' .$button_url. '" name="submit" alt="GTPayment - Global Trading Payment" />';
		$GTPayment_btn .=	'</div>';
		$GTPayment_btn .=	'</form>';
		
		return $GTPayment_btn;
	}

	/**
	* The Admin Page and all it's functions
	*
	*/
	function wp_admin()	{
		if (function_exists('add_options_page')) {
			add_options_page( 'GTPayment Donations Options', 'GTPayment Donations', 10, __FILE__, array(&$this, 'options_page') );
		}
	}

	function admin_message($message) {
		if ( $message ) {
			?>
			<div class="updated"><p><strong><?php echo $message; ?></strong></p></div>
			<?php	
		}
	}

	function options_page() {
		// Update Options
		if (isset($_POST['Submit'])) {
			$pd_options['GTPayment_account'] = trim( $_POST['GTPayment_account'] );
			$pd_options['cancel_page'] = trim( $_POST['cancel_page'] );
			$pd_options['return_page'] = trim( $_POST['return_page'] );
			$pd_options['purpose'] = trim( $_POST['purpose'] );
			$pd_options['reference'] = trim( $_POST['reference'] );
			$pd_options['button'] = trim( $_POST['button'] );
			$pd_options['button_url'] = trim( $_POST['button_url'] );
			$pd_options['currency_code'] = trim( $_POST['currency_code'] );
			$pd_options['amount'] = trim( $_POST['amount'] );
			$pd_options['button_localized'] = trim( $_POST['button_localized'] );
			update_option($this->plugin_options, $pd_options);
			$this->admin_message( __( 'The GTPayment Donations settings have been updated.', 'GTPayment-donations' ) );
		}
?>
<div class=wrap>
    <h2>GTPayment Donations</h2>

	<form method="post" action="">
	<?php wp_nonce_field('update-options'); ?>
	<?php $pd_options = get_option($this->plugin_options); ?>
    <table class="form-table">
    <tr valign="top">
    <th scope="row"><label for="GTPayment_account"><?php _e( 'GTPayment Account', 'GTPayment-donations' ) ?></label></th>
    <td><input name="GTPayment_account" type="text" id="GTPayment_account" value="<?php echo $pd_options['GTPayment_account']; ?>" class="regular-text" /><span class="setting-description"><br/><?php _e( 'Your GTPayment email address or your GTPayment secure merchant account ID.', 'GTPayment-donations' ) ?></span></td>
    </tr>
    <tr valign="top">
    <th scope="row"><label for="currency_code"><?php _e( 'Currency', 'GTPayment-donations' ) ?></label></th>
    <td><select name="currency_code" id="currency_code">
<?php   if (isset($pd_options['currency_code'])) { $current_currency = $pd_options['currency_code']; } else { $current_currency = 'USD'; }
		foreach ( $this->currency_codes as $key => $code ) {
	        echo '<option value="'.$key.'"';
			if ($current_currency == $key) { echo ' selected="selected"'; }
			echo '>'.$code.'</option>';
		}?></select>
        <span class="setting-description"><br/><?php _e( 'The currency to use for the donations.', 'GTPayment-donations' ) ?></span></td>
    </tr>
   <!-- </table>

	<h3><?php _e( 'Settings', 'GTPayment-donations' ) ?></h3>
    <table class="form-table">-->
    <tr valign="top">
    <th scope="row"><label for="cancel_page"><?php _e( 'Cancel Page', 'GTPayment-donations' ) ?></label></th>
    <td><input name="cancel_page" type="text" id="cancel_page" value="<?php echo $pd_options['cancel_page']; ?>" class="regular-text" /><span class="setting-description"><br/><?php _e( 'URL to which the donator comes to if cancelling the donation; for example, a URL on your site that displays a "The donation has been cancelled".', 'GTPayment-donations' ) ?></span></td>
    </tr>
    <tr valign="top">
    <th scope="row"><label for="return_page"><?php _e( 'Return Page', 'GTPayment-donations' ) ?></label></th>
    <td><input name="return_page" type="text" id="return_page" value="<?php echo $pd_options['return_page']; ?>" class="regular-text" /><span class="setting-description"><br/><?php _e( 'URL to which the donator comes to after completing the donation; for example, a URL on your site that displays a "Thank you for your donation".', 'GTPayment-donations' ) ?></span></td>
    </tr>   
	<tr valign="top">
    <th scope="row"><label for="amount"><?php _e( 'Amount', 'GTPayment-donations' ) ?></label></th>
    <td><input name="amount" type="text" id="amount" value="<?php echo $pd_options['amount']; ?>" class="regular-text" /><span class="setting-description"><br/><?php _e( 'The default amount for a donation.', 'GTPayment-donations' ) ?></span></td>
    </tr>
    <tr valign="top">
    <th scope="row"><label for="purpose"><?php _e( 'Purpose', 'GTPayment-donations' ) ?></label></th>
    <td><input name="purpose" type="text" id="purpose" value="<?php echo $pd_options['purpose']; ?>" class="regular-text" /><span class="setting-description"><br/><?php _e( 'The default purpose of a donation (Optional).', 'GTPayment-donations' ) ?></span></td>
    </tr>
    <tr valign="top">
    <th scope="row"><label for="reference"><?php _e( 'Reference', 'GTPayment-donations' ) ?></label></th>
    <td><input name="reference" type="text" id="reference" value="<?php echo $pd_options['reference']; ?>" class="regular-text" /><span class="setting-description"><br/><?php _e( 'Default reference for the donation.', 'GTPayment-donations' ) ?></span></td>
    </tr> 	
    <tr valign="top">
    <th scope="row"><label for="button_localized"><?php _e( 'GTPayment Language', 'GTPayment-donations' ) ?></label></th>
    <td><select name="button_localized" id="button_localized">
<?php   foreach ( $this->localized_buttons as $key => $localize ) {
	        echo '<option value="'.$key.'"';
			if ($button_localized == $key) { echo ' selected="selected"'; }
			echo '>'.$localize.'</option>';
		}?></select>
        <span class="setting-description"><br/><?php _e( 'Localize the language to the GTPayment system.', 'GTPayment-donations' ) ?></span></td>
    </tr>      
    </table>

	<!--<h3><?php _e( 'Defaults', 'GTPayment-donations' ) ?></h3>
    <table class="form-table">
    <tr valign="top">
    <th scope="row"><label for="amount"><?php _e( 'Amount', 'GTPayment-donations' ) ?></label></th>
    <td><input name="amount" type="text" id="amount" value="<?php echo $pd_options['amount']; ?>" class="regular-text" /><span class="setting-description"><br/><?php _e( 'The default amount for a donation (Optional).', 'GTPayment-donations' ) ?></span></td>
    </tr>
    <tr valign="top">
    <th scope="row"><label for="purpose"><?php _e( 'Purpose', 'GTPayment-donations' ) ?></label></th>
    <td><input name="purpose" type="text" id="purpose" value="<?php echo $pd_options['purpose']; ?>" class="regular-text" /><span class="setting-description"><br/><?php _e( 'The default purpose of a donation (Optional).', 'GTPayment-donations' ) ?></span></td>
    </tr>
    <tr valign="top">
    <th scope="row"><label for="reference"><?php _e( 'Reference', 'GTPayment-donations' ) ?></label></th>
    <td><input name="reference" type="text" id="reference" value="<?php echo $pd_options['reference']; ?>" class="regular-text" /><span class="setting-description"><br/><?php _e( 'Default reference for the donation (Optional).', 'GTPayment-donations' ) ?></span></td>
    </tr>    
    </table>
-->
	<h3><?php _e( 'Donation Button', 'GTPayment-donations' ) ?></h3>
    <table class="form-table">
    <tr>
	<th scope="row"><?php _e( 'Select Button', 'GTPayment-donations' ) ?></th>
	<td>
	<fieldset><legend class="hidden">GTPayment Button</legend>
<?php
	$custom = TRUE;
	if (isset($pd_options['button_localized'])) { $button_localized = $pd_options['button_localized']; } else { $button_localized = 'en_US'; }
	if (isset($pd_options['button'])) { $current_button = $pd_options['button']; } else { $current_button = 'large'; }
	foreach ( $this->donate_buttons as $key => $button ) {
		echo "\t<label title='" . attribute_escape($key) . "'><input style='padding: 10px 0 10px 0;' type='radio' name='button' value='" . attribute_escape($key) . "'";
		if ( $current_button === $key ) { // checked() uses "==" rather than "==="
			echo " checked='checked'";
			$custom = FALSE;
		}
		echo " /> <img src='" . str_replace('en_US', $button_localized, $button) . "' alt='" . $key  . "' style='vertical-align: middle;' /></label><br /><br />\n";
	}

	echo '	<label><input type="radio" name="button" value="custom"';
	checked( $custom, TRUE );
	echo '/> ' . __('Custom Button:', 'GTPayment-donations') . ' </label>';
?>
	<input type="text" name="button_url" value="<?php echo $pd_options['button_url']; ?>" class="regular-text" /><br/>
	<span class="setting-description"><?php _e( 'Enter a URL to a custom donation button.', 'GTPayment-donations' ) ?></span>
	</fieldset>
	</td>
	</tr>  
    </table>

    <p class="submit">
    <input type="submit" name="Submit" class="button-primary" value="<?php _e( 'Save Changes', 'GTPayment-donations' ) ?>" />
    </p>
</div>
<?php
	}
}


/**
 * The Class for the Widget
 *
 */
if (class_exists('WP_Widget')) :
class GTPayment_donations_Widget extends WP_Widget {
	/**
	* Constructor
	*
	*/
	function GTPayment_donations_Widget() {
		// Widget settings.
		$widget_ops = array ( 'classname' => 'widget_GTPayment_donations', 'description' => __('GTPayment Donation Button', 'GTPayment-donations') );

		// Widget control settings.
		$control_ops = array( 'id_base' => 'GTPayment_donations' );

		// Create the Widget
		$this->WP_Widget( 'GTPayment_donations', 'GTPayment Donations', $widget_ops );
	}

	/**
	* Output the Widget
	*
	*/
	function widget( $args, $instance ) {
		extract( $args );
		global $GTPayment_donations;

		// Get the settings
		$title = apply_filters('widget_title', $instance['title'] );
		$text = $instance['text'];
		$purpose = $instance['purpose'];
		$reference = $instance['reference'];

		echo $before_widget;
		if ( $title )
			echo $before_title . $title . $after_title;
		if ( $text )
			echo wpautop( $text );
		echo  $GTPayment_donations->generate_html( $purpose, $reference );
		echo $after_widget;
	}
	
	/**
	  * Saves the widgets settings.
	  *
	  */
	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;

	    $instance['title'] = strip_tags(stripslashes($new_instance['title']));
	    $instance['text'] = $new_instance['text'];
	    $instance['purpose'] = strip_tags(stripslashes($new_instance['purpose']));
	    $instance['reference'] = strip_tags(stripslashes($new_instance['reference']));

		return $instance;
	}

	/**
	* The Form in the Widget Admin Screen
	*
	*/
	function form( $instance ) {
		// Default Widget Settings
		$defaults = array( 'title' => __('Donate', 'GTPayment-donations'), 'text' => '', 'purpose' => '', 'reference' => '' );
		$instance = wp_parse_args( (array) $instance, $defaults ); ?>
        
        <p>
            <label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:', 'GTPayment-donations'); ?> 
            <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo esc_attr($instance['title']); ?>" />
            </label>
        </p>

        <p>
            <label for="<?php echo $this->get_field_id('text'); ?>"><?php _e('Text:', 'GTPayment-donations'); ?> 
            <textarea class="widefat" id="<?php echo $this->get_field_id('text'); ?>" name="<?php echo $this->get_field_name('text'); ?>"><?php echo esc_attr($instance['text']); ?></textarea>
            </label>
        </p>

        <p>
            <label for="<?php echo $this->get_field_id('purpose'); ?>"><?php _e('Purpose:', 'GTPayment-donations'); ?> 
            <input class="widefat" id="<?php echo $this->get_field_id('purpose'); ?>" name="<?php echo $this->get_field_name('purpose'); ?>" type="text" value="<?php echo esc_attr($instance['purpose']); ?>" />
            </label>
        </p>

        <p>
            <label for="<?php echo $this->get_field_id('reference'); ?>"><?php _e('Reference:', 'GTPayment-donations'); ?> 
            <input class="widefat" id="<?php echo $this->get_field_id('reference'); ?>" name="<?php echo $this->get_field_name('reference'); ?>" type="text" value="<?php echo esc_attr($instance['reference']); ?>" />
            </label>
        </p>
        <?php 
	}
}
endif;

/**
 * Uninstall
 * Clean up the WP DB by deleting the options created by the plugin.
 *
 */
if ( function_exists('register_uninstall_hook') )
	register_uninstall_hook(__FILE__, 'GTPayment_donations_deinstall');
 
function GTPayment_donations_deinstall() {
	delete_option('GTPayment_donations_options');
	delete_option('widget_GTPayment_donations');
}

// Start the Plugin
add_action( 'plugins_loaded', create_function( '', 'global $GTPayment_donations; $GTPayment_donations = new GTPayment_donations();' ) );

?>