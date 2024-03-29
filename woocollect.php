<?php
/*
Plugin Name: Simple click & Collect for WooCommerce
Description: Collection time plugin for WooCommerce orders
Version: 1.0
Author: Kandeshop
Author URI: https://darrenk.uk
License: GPL v2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
*/

if ( ! defined( 'ABSPATH' ) ) exit;    

// Plugin Activation and Deactivation

register_activation_hook(__FILE__, 'scwc_collection_time_booking_activate');
register_deactivation_hook(__FILE__, 'scwc_collection_time_booking_deactivate');

function scwc_collection_time_booking_activate()
{
    // Check if WooCommerce is active
    if (!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
        // WooCommerce is not active, display an error message and deactivate the plugin
        deactivate_plugins(plugin_basename(__FILE__));
        wp_die('Sorry, but this plugin requires WooCommerce to be installed and activated.');
    }

    // Add default opening hours
    $default_opening_hours = array(
        'monday' => array('start_time' => '10:00', 'end_time' => '18:00'),
        'tuesday' => array('start_time' => '10:00', 'end_time' => '18:00'),
        'wednesday' => array('start_time' => '10:00', 'end_time' => '18:00'),
        'thursday' => array('start_time' => '10:00', 'end_time' => '18:00'),
        'friday' => array('start_time' => '10:00', 'end_time' => '18:00'),
        'saturday' => array('start_time' => '10:00', 'end_time' => '18:00'),
        'sunday' => array('start_time' => '10:00', 'end_time' => '18:00')
    );
    update_option('collection_time_booking_opening_hours', $default_opening_hours);
}

// Add admin settings page
add_action('admin_menu', 'scwc_add_custom_admin_menu');

function scwc_add_custom_admin_menu() {
    add_menu_page(
        'Woo Click & Collect', 
        'Woo Click & Collect', 
        'manage_options', 
        'woo-click-collect', 
        'scwc_display_main_menu_content', 
        'dashicons-cart', 
        30
    );
    

    add_submenu_page(
        'woo-click-collect', 
        'Collection Time', 
        'Collection Time', 
        'manage_options', 
        'collection-time-settings', 
        'scwc_display_collection_time_settings'
    );
    
}


function scwc_display_main_menu_content() {
    echo '<div class="wrap">';
    echo '<h1>' . esc_html__('Simple Click & Collect for WooCommerce', 'collection-time-booking') . '</h1>';
    echo '</div>';

    echo '<h2>' . esc_html__('Collection Time Settings', 'collection-time-booking') . '</h2>';
    echo '<ul>';
    echo '<li>' . esc_html__('The Collection Time Settings allow you to define the opening and closing times for collection on each day of the week. This ensures accurate scheduling of collection times based on your business\'s availability.', 'collection-time-booking') . '</li>';
    echo '<li>' . esc_html__('Follow these steps to set the opening and closing times:', 'collection-time-booking') . '</li>';
    echo '<ol>';
    echo '<li>' . esc_html__('On the main menu page, click on the "Collection Time Settings" option.', 'collection-time-booking') . '</li>';
    echo '<li>' . esc_html__('You will see a form with a table displaying the days of the week and corresponding input fields for start and end times.', 'collection-time-booking') . '</li>';
    echo '<li>' . esc_html__('For each day of the week, enter the opening and closing times in the respective input fields. This defines the available collection times for each day.', 'collection-time-booking') . '</li>';
    echo '<li>' . esc_html__('For days that collection times are not available, remove the start and end times.', 'collection-time-booking') . '</li>';
    echo '<li>' . esc_html__('After entering the times for all the days, click the "Save Changes" button to save your settings.', 'collection-time-booking') . '</li>';
    echo '<li>' . esc_html__('Collection times by default are on the hour every hour and allow a 1-hour window.', 'collection-time-booking') . '</li>';
    echo '</ol>';
    echo '</ul>';
}

function scwc_display_collection_time_settings()
{
    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.'));
    }

    // Check if form is submitted and nonce is set
    if (isset($_POST['scwc_collection_time_booking_submit']) && isset($_POST['scwc_collection_time_booking_nonce'])) {
        // Verifying the nonce
    
        if (!isset($_POST['scwc_collection_time_booking_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['scwc_collection_time_booking_nonce'])), 'scwc_collection_time_booking_settings')) {
            die('Invalid nonce.');
        }

        $opening_hours = array();

        // Loop through days of the week
        foreach (['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'] as $day) {
            if (isset($_POST[$day . '_start_time']) && isset($_POST[$day . '_end_time'])) {
                $opening_hours[$day] = array(
                    'start_time' => sanitize_text_field($_POST[$day . '_start_time']),
                    'end_time'   => sanitize_text_field($_POST[$day . '_end_time'])
                );
            }
        }

        // Save opening hours to database
        update_option('collection_time_booking_opening_hours', $opening_hours);

        echo '<div class="notice notice-success"><p>' . esc_html('Settings saved successfully.', 'collection-time-booking') . '</p></div>';
    }

    // Retrieve opening hours from the database
    $opening_hours = get_option('collection_time_booking_opening_hours', array());

    ?>
    <div class="wrap">
        <h1><?php esc_html_e('Collection Time Settings', 'collection-time-booking'); ?></h1>

        <form method="post" action="">
            <?php wp_nonce_field('collection_time_booking_settings', 'collection_time_booking_nonce'); ?>

            <table class="form-table">
                <?php
                // Loop through days of the week
                foreach (['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'] as $day) {
                    $start_time = isset($opening_hours[$day]['start_time']) ? esc_attr($opening_hours[$day]['start_time']) : '';
                    $end_time = isset($opening_hours[$day]['end_time']) ? esc_attr($opening_hours[$day]['end_time']) : '';
                    ?>
                    <tr>
                        <th scope="row"><?php echo esc_html(ucfirst($day)); ?></th>
                        <td>
                            <input type="text" name="<?php echo esc_attr($day); ?>_start_time" value="<?php echo esc_attr($start_time); ?>" placeholder="<?php esc_attr_e('Opening Time', 'collection-time-booking'); ?>">
                            <input type="text" name="<?php echo esc_attr($day); ?>_end_time" value="<?php echo esc_attr($end_time); ?>" placeholder="<?php esc_attr_e('Closing Time', 'collection-time-booking'); ?>">
                        </td>
                    </tr>
                    <?php
                }
                ?>
            </table>

            <p class="submit">
                <input type="submit" name="collection_time_booking_submit" class="button-primary" value="<?php esc_attr_e('Save Changes', 'collection-time-booking'); ?>">
            </p>
        </form>
    </div>
    <?php
}

// Add custom meta box to checkout page
add_action('woocommerce_before_order_notes', 'scwc_collection_time_booking_add_meta_box');

function scwc_collection_time_booking_add_meta_box($checkout)
{
    $opening_hours = get_option('collection_time_booking_opening_hours', array());
    
    // Get current time
    $current_time = strtotime('now');
    
    // Minimum time slot interval in seconds (1 hour)
    $minimum_interval = 1 * 60 * 60;

    // Get available time slots for today
    $today = strtolower(date('l'));
    $start_time = strtotime($opening_hours[$today]['start_time']);
    $end_time = strtotime($opening_hours[$today]['end_time']);
    $time_slots = array();
    $selected_date = '';
    $selected_time = '';

    $time_slots[''] = "Select Collection Time";

    echo '<div id="collection-time-box">';
    woocommerce_form_field(
        'collection_date',
        array(
            'type' => 'text',
            'class' => array('form-row-wide'),
            'label' => __('Collection Date'),
            'placeholder' => __('Select date'),
            'required' => true,
            'autocomplete' => 'off',
            'custom_attributes' => array(
                'autocomplete' => 'off',
                'readonly' => 'readonly'
            )
        ),
        $selected_date
    );

    woocommerce_form_field(
        'collection_time',
        array(
            'type' => 'select',
            'class' => array('form-row-wide'),            
            'label' => __('Collection Time'),
            'options' => $time_slots,
            'required' => true,
        ),
        $selected_time
    );
    echo '</div>';

    // Set the session variables
    WC()->session->set('selected_collection_date', $selected_date);
    WC()->session->set('selected_collection_time', $selected_time);
}
// Validate collection date and time before placing the order
add_action('woocommerce_checkout_process', 'scwc_collection_time_booking_validate_collection_datetime');

function scwc_collection_time_booking_validate_collection_datetime()
{   

        if (isset($_POST['collection_date']) && empty($_POST['collection_date'])) {
            wc_add_notice(__('Please select a collection date.'), 'error');
        } if (isset($_POST['collection_time']) && empty($_POST['collection_time'])) {
            wc_add_notice(__('Please select a collection time.'), 'error');
        } else {
            $selected_date = sanitize_text_field($_POST['collection_date']);
            $selected_time = sanitize_text_field($_POST['collection_time']);
            $booking_window_hours = get_option('booking_window_hours', 2); // Get booking window hours from settings, default to 2 if not set
            $selected_datetime = strtotime($selected_date . ' ' . $selected_time);
            $minimum_interval = $booking_window_hours * 60 * 60;
            $current_datetime = strtotime('now');

            // Calculate the minimum allowed collection datetime
            $minimum_collection_datetime = $current_datetime + $minimum_interval;

        
        

    }
}


// Save the selected collection date and time to the order
add_action('woocommerce_checkout_create_order', 'scwc_collection_time_booking_save_collection_datetime');

function scwc_collection_time_booking_save_collection_datetime($order) {
    $collection_date = isset($_POST['collection_date']) ? sanitize_text_field($_POST['collection_date']) : '';
    $collection_time = isset($_POST['collection_time']) ? sanitize_text_field($_POST['collection_time']) : '';

    if (!empty($collection_date)) {
        $order->update_meta_data('Collection Date', esc_html($collection_date));
    }

    if (!empty($collection_time)) {
        $order->update_meta_data('Collection Time', esc_html($collection_time));
    }
}


// Add Meta to email

function  scwc_collection_time_booking_order_email( $fields ) {
    $fields['Collection Date'] = __('Collection Date', 'your-domain');
    $fields['Collection Time'] = __('Collection Time', 'your-domain');
    return $fields;
}
add_filter( 'woocommerce_email_order_meta_fields', 'scwc_collection_time_booking_order_email' );


// Display the selected collection date and time in the admin order page
add_action('woocommerce_admin_order_data_after_billing_address', 'scwc_collection_time_booking_display_admin_order_meta', 10, 1);

function scwc_collection_time_booking_display_admin_order_meta($order)
{
   
    if (!is_a($order, 'WC_Order')) {
        return; 
    }

    // Get metadata
    $collection_date = $order->get_meta('Collection Date');
    $collection_time = $order->get_meta('Collection Time');

    if (!empty($collection_date)) {
        echo '<p><strong>' . esc_html__('Collection Date:') . '</strong> ' . esc_html($collection_date) . '</p>';
    }

    if (!empty($collection_time)) {
        echo '<p><strong>' . esc_html__('Collection Time:') . '</strong> ' . esc_html($collection_time) . '</p>';
    }

  
}


// Attach collection date and time to the order confirmation emails
add_action('woocommerce_email_order_details', 'scwc_collection_time_booking_add_collection_datetime_to_email', 10, 4);

function scwc_collection_time_booking_add_collection_datetime_to_email($order, $sent_to_admin, $plain_text, $email)
{
    if ($order->get_meta('Collection Date') && $order->get_meta('Collection Time')) {
        $collection_date = $order->get_meta('Collection Date');
        $collection_time = $order->get_meta('Collection Time');
        echo '<p><strong>Collection Date:</strong> ' . esc_html($collection_date) . '</p>';
        echo '<p><strong>Collection Time:</strong> ' . esc_html($collection_time) . '</p>';
        
    }
}


function scwc_enqueue_my_script() {

    wp_enqueue_script('collection-time-booking-script', plugin_dir_url(__FILE__) . 'js/collection-time-booking.js', array('jquery'), '1.0.0', true);

     wp_localize_script('collection-time-booking-script', 'my_script_vars', array(
        'selected_shipping_methods' => implode(" , ",$selected_shipping_methods),
    ));
    
    // Localize script with the collection time options
$booking_window_hours = get_option('booking_window_hours', 2); // Get booking window hours from settings, default to 2 if not set

$collection_time_options = array(
    'curdate' => date("Y-m-d"),
    'timeFormat' => get_option('time_format', 'g:i A'),
    'minDate' => 0, // Minimum date is today
    'minTime' => date('H:i', strtotime('+' . $booking_window_hours . ' hours')), // Minimum time is 'booking_window_hours' hours from now
    'maxTime' => '' // Placeholder for the maximum time based on opening hours
);
$opening_hours = get_option('collection_time_booking_opening_hours', array());
if (!empty($opening_hours)) {
    $collection_time_options['openingHours'] = $opening_hours;
}
    
wp_localize_script('collection-time-booking-script', 'collectionTimeOptions', $collection_time_options);

// Enqueue 
wp_enqueue_script('jquery-ui-core');
wp_enqueue_script('jquery-ui-datepicker');

wp_enqueue_style('jquery-ui-datepicker-css', plugins_url('assets/jquery-ui.css', __FILE__));
wp_enqueue_script('jquery-ui-timepicker-addon', plugins_url('assets/jquery-ui-timepicker-addon.min.js', __FILE__), array('jquery-ui-datepicker'), '1.6.3', true);
wp_enqueue_style('jquery-ui-timepicker-css', plugins_url('assets/jquery-ui-timepicker-addon.min.css', __FILE__));
wp_enqueue_script('collection-time-booking-script', plugin_dir_url(__FILE__) . 'js/collection-time-booking.js', array('jquery-ui-datepicker', 'jquery-ui-timepicker-addon'),'1.19', true);
wp_enqueue_style( 'plugin-styles', plugin_dir_url( __FILE__ ) . 'plugin-styles.css' );
}
add_action('wp_enqueue_scripts', 'scwc_enqueue_my_script');


add_action('admin_init', 'scwc_register_booking_window_settings');

function scwc_register_booking_window_settings() {
    register_setting('booking_window_settings', 'booking_window_hours');
}

function scwc_register_collection_time_settings() {
    register_setting('scwc_collection_time_settings', 'scwc_booking_window_hours');
    register_setting('scwc_collection_time_booking_settings', 'scwc_collection_time_booking_opening_hours');
}
add_action('admin_init', 'scwc_register_collection_time_settings');
