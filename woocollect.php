<?php 
/*
Plugin Name: Woo Collect
Description: Collection time plugin for WooCommerce orders
Version: 1.0.1
Author: Darren Kandekore
Author URI: tbc
*/

// Plugin Activation and Deactivation
register_activation_hook(__FILE__, 'collection_time_booking_activate');
register_deactivation_hook(__FILE__, 'collection_time_booking_deactivate');

function collection_time_booking_activate()
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
add_action('admin_menu', 'add_custom_admin_menu');

function add_custom_admin_menu() {
    add_menu_page(
        'Woo Click & Collect', 
        'Woo Click & Collect', 
        'manage_options', 
        'woo-click-collect', 
        'display_main_menu_content', 
        'dashicons-cart', 
        30
    );
    
    add_submenu_page(
        'woo-click-collect', 
        'Booking Window', 
        'Booking Window', 
        'manage_options', 
        'booking-window', 
        'display_booking_window'
    );

    add_submenu_page(
        'woo-click-collect', 
        'Collection Time', 
        'Collection Time', 
        'manage_options', 
        'collection-time-settings', 
        'display_collection_time_settings'
    );
}

// Main menu page content
function display_main_menu_content() {
    // Display content for the main menu page here
    echo '<div class="wrap">';
    echo '<h1>Main Menu Page Content</h1>';
	echo '<p>Yooooo</p>';
    echo '</div>';
}

// Booking Window admin settings page
function display_booking_window() {
    // check user capabilities
    if (!current_user_can('manage_options')) {
        return;
    }
    
    // Update 'booking_window_hours' if form submitted
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        update_option('booking_window_hours', $_POST['booking_window_hours']);
    }

    $booking_window_hours = get_option('booking_window_hours', 2);  // set default value as 2
    ?>
    <div class="wrap">
        <h1><?= esc_html(get_admin_page_title()); ?></h1>
        <form action="options.php" method="post">
            <?php
            settings_fields('booking_window_settings');
            do_settings_sections('booking_window_settings');
            ?>
            <table class="form-table" role="presentation">
                <tbody>
                <tr>
                    <th scope="row"><label for="minimum_hours">Minimum Hours in Advance:</label></th>
                    <td><input name="booking_window_hours" type="number" id="minimum_hours" value="<?= $booking_window_hours ?>" class="regular-text"></td>
                </tr>
                </tbody>
            </table>
            <?php
            submit_button('Save Changes');
            ?>
        </form>
    </div>
    <?php
}


// Admin settings page
function display_collection_time_settings()
{
    // Save settings if form submitted
    if (isset($_POST['collection_time_booking_submit'])) {
        $opening_hours = array();
        
        // Loop through days of the week
        foreach (['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'] as $day) {
            $opening_hours[$day] = array(
                'start_time' => sanitize_text_field($_POST[$day . '_start_time']),
                'end_time'   => sanitize_text_field($_POST[$day . '_end_time'])
            );
        }

        // Save opening hours to database
        update_option('collection_time_booking_opening_hours', $opening_hours);
        
        echo '<div class="notice notice-success"><p>Settings saved successfully.</p></div>';
    }

    // Retrieve opening hours from database
    $opening_hours = get_option('collection_time_booking_opening_hours', array());

    ?>
    <div class="wrap">
        <h1>Collection Time Settings</h1>

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
                        <th scope="row"><?php echo ucfirst($day); ?></th>
                        <td>
                            <input type="text" name="<?php echo $day; ?>_start_time" value="<?php echo $start_time; ?>" placeholder="Opening Time">
                            <input type="text" name="<?php echo $day; ?>_end_time" value="<?php echo $end_time; ?>" placeholder="Closing Time">
                        </td>
                    </tr>
                    <?php
                }
                ?>
            </table>

            <p class="submit">
                <input type="submit" name="collection_time_booking_submit" class="button-primary" value="Save Changes">
            </p>
        </form>
    </div>
    <?php
}

// Add custom meta box to checkout page
add_action('woocommerce_before_order_notes', 'collection_time_booking_add_meta_box');

function collection_time_booking_add_meta_box($checkout)
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

    $time_slots[''] = "Select Collection Time";//anuj
    // Generate time slots based on the opening hours
    
    //anuj
    // for ($time = $start_time; $time < $end_time; $time += $minimum_interval) {
    //     $time_slots[date('H:i', $time)] = date('h:i A', $time);
    // }
    //anuj

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
add_action('woocommerce_checkout_process', 'collection_time_booking_validate_collection_datetime');

function collection_time_booking_validate_collection_datetime()
{
    if (isset($_POST['collection_date']) && empty($_POST['collection_date'])) {
        wc_add_notice(__('Please select a collection date.'), 'error');
    } elseif (isset($_POST['collection_time']) && empty($_POST['collection_time'])) {
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
add_action('woocommerce_checkout_create_order', 'collection_time_booking_save_collection_datetime');

function collection_time_booking_save_collection_datetime($order)
{
    $collection_date = isset($_POST['collection_date']) ? sanitize_text_field($_POST['collection_date']) : '';
    $collection_time = isset($_POST['collection_time']) ? sanitize_text_field($_POST['collection_time']) : '';
    $collection_datetime = strtotime($collection_date . ' ' . $collection_time);

    if (!empty($collection_date)) {
        $order->update_meta_data('Collection Date', $collection_date);
    }

    if (!empty($collection_time)) {
        $order->update_meta_data('Collection Time', $collection_time);
    }

    if (!empty($collection_datetime)) {
        $order->set_date_created(date('d-m-Y H:i:s', $collection_datetime));
        $order->update_meta_data('_collection_datetime', $collection_datetime);
    }
}


/*** Anuj  */

function  collection_time_booking_order_email( $fields ) {
    $fields['Collection Date'] = __('Collection Date', 'your-domain');
    $fields['Collection Time'] = __('Collection Time', 'your-domain');
    return $fields;
}
add_filter( 'woocommerce_email_order_meta_fields', 'collection_time_booking_order_email' );

/*** Anuj  */



// Display the selected collection date and time in the admin order page
add_action('woocommerce_admin_order_data_after_billing_address', 'collection_time_booking_display_admin_order_meta', 10, 1);

function collection_time_booking_display_admin_order_meta($order)
{
    $collection_date = $order->get_meta('Collection Date');
    $collection_time = $order->get_meta('Collection Time');
    $collection_datetime = $order->get_meta('Collection DateTime');
    
    if (!empty($collection_date)) {
        echo '<p><strong>Collection Date:</strong> ' . esc_html($collection_date) . '</p>';
    }

    if (!empty($collection_time)) {
        echo '<p><strong>Collection Time:</strong> ' . esc_html($collection_time) . '</p>';
    }

    if (!empty($collection_datetime)) {
        echo '<p><strong>Collection DateTime:</strong> ' . esc_html(date('l jS F H:i', $collection_datetime)) . '</p>';
    }
}

add_action('woocommerce_email_order_details', 'collection_time_booking_add_collection_datetime_to_email', 10, 4);

function collection_time_booking_add_collection_datetime_to_email($order, $sent_to_admin, $plain_text, $email)
{
    // Check if the order has the 'Collection Date' and 'Collection Time' meta data.
    if ($order->get_meta('Collection Date') && $order->get_meta('Collection Time')) {
        $collection_date = $order->get_meta('Collection Date');
        $collection_time = $order->get_meta('Collection Time');
        $collection_datetime = date('d-m-Y H:i', $order->get_meta('Collection DateTime'));
        
        echo '<p><strong>Collection Date:</strong> ' . esc_html($collection_date) . '</p>';
        echo '<p><strong>Collection Time:</strong> ' . esc_html($collection_time) . '</p>';
        echo '<p><strong>Collection DateTime:</strong> ' . esc_html($collection_datetime) . '</p>';
    }
}



// Add admin dashboard widget
add_action( 'wp_dashboard_setup', 'collection_time_booking_dashboard_widget' );

function collection_time_booking_dashboard_widget() {
    wp_add_dashboard_widget(
        'future_collection_orders_widget',  // Widget slug
        'Future Collection Orders',         // Title
        'display_future_collection_orders'  // Display function
    );
}

function display_future_collection_orders() {
    // Get current date and time
    $current_datetime = current_time('mysql');
    
    // Get upcoming collections from orders
    $orders = wc_get_orders(array(
        'meta_query' => array(
            'relation' => 'AND',
            array(
                'key' => 'Collection Date',
                'value' => date("d-m-Y"),
                'compare' => '>=',
                'type' => 'DATE',
            ),
        ),
        'meta_key' => 'Collection Date',
        'orderby' => 'meta_value',
        'order' => 'ASC',
        'status' => 'any', // changed from 'wc-completed'
        'limit' => -1,
    ));

    // Display upcoming collections
    if (!empty($orders)) {
        echo '<ul>';
        foreach ($orders as $order) {
            
            $collection_date = $order->get_meta('Collection Date');            

            if($collection_date!='' && date("d-m-Y",strtotime($collection_date)) >=date("d-m-Y")){    
            
                //anuj
                $collection_time = $order->get_meta('Collection Time');
                $formatted_date = date_i18n('l jS F ', strtotime($collection_date));
                //anuj

                $customer_name = $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();
                $order_link = admin_url('post.php?post=' . $order->get_id() . '&action=edit');
                echo '<li><strong>' . esc_html($customer_name) . '</strong> - ' . esc_html($formatted_date) . ' '.$collection_time.' - <a href="' . esc_url($order_link) . '">View Order</a></li>';
            }
        }
        echo '</ul>';
    } else {
        echo '<p>No upcoming collections found.</p>';
    }
}


function enqueue_my_script() {
    $selected_shipping_methods = get_option('click_collect_shipping_methods', array());
    wp_enqueue_script('collection-time-booking-script', plugin_dir_url(__FILE__) . 'js/collection-time-booking.js', array('jquery'), '1.0.0', true);
    wp_enqueue_script('my-shipping-methods-script', plugin_dir_url(__FILE__) . 'js/shipping-methods.js', array('jquery', 'collection-time-booking-script'), '1.1.1', true);
    wp_localize_script('collection-time-booking-script', 'my_script_vars', array(
        'selected_shipping_methods' => implode(" , ",$selected_shipping_methods),
    ));
    
    // Localize script with the collection time options
$booking_window_hours = get_option('booking_window_hours', 2); // Get booking window hours from settings, default to 2 if not set

$collection_time_options = array(
    'curdate' => date("d-m-Y"),
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

// Enqueue jQuery UI
wp_enqueue_script('jquery-ui-core');
wp_enqueue_script('jquery-ui-datepicker');

// Enqueue jQuery UI CSS
wp_enqueue_style('jquery-ui-datepicker-css', 'https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css');

// Enqueue time picker JavaScript
wp_enqueue_script('jquery-ui-timepicker-addon', 'https://cdnjs.cloudflare.com/ajax/libs/jquery-ui-timepicker-addon/1.6.3/jquery-ui-timepicker-addon.min.js', array('jquery-ui-datepicker'), '1.6.3', true);

// Enqueue time picker CSS
wp_enqueue_style('jquery-ui-timepicker-css', 'https://cdnjs.cloudflare.com/ajax/libs/jquery-ui-timepicker-addon/1.6.3/jquery-ui-timepicker-addon.min.css');

// Enqueue custom JavaScript for initializing date and time pickers
wp_enqueue_script('collection-time-booking-script', plugin_dir_url(__FILE__) . 'js/collection-time-booking.js', array('jquery-ui-datepicker', 'jquery-ui-timepicker-addon'),'1.19', true);//

// Enqueue css
wp_enqueue_style( 'plugin-styles', plugin_dir_url( __FILE__ ) . 'plugin-styles.css' );
}
add_action('wp_enqueue_scripts', 'enqueue_my_script');
add_action('admin_init', 'register_booking_window_settings');

function register_booking_window_settings() {
    register_setting('booking_window_settings', 'booking_window_hours');
}

function register_collection_time_settings() {
    register_setting('collection_time_settings', 'booking_window_hours');
    register_setting('collection_time_booking_settings', 'collection_time_booking_opening_hours');
}
add_action('admin_init', 'register_collection_time_settings');


add_action('woocommerce_thankyou', 'display_collection_time_on_order_confirmation', 10, 1);

function display_collection_time_on_order_confirmation($order_id) {
    // Get the order object
    $order = wc_get_order($order_id);

    $collection_date = $order->get_meta('Collection Date');
    $collection_time = $order->get_meta('Collection Time');
    $collection_datetime = $order->get_meta('Collection DateTime');

    echo '<div class="order-collection-details">';
    
    if (!empty($collection_date)) {
        echo '<p><strong>Collection Date:</strong> ' . esc_html($collection_date) . '</p>';
    }

    if (!empty($collection_time)) {
        echo '<p><strong>Collection Time:</strong> ' . esc_html($collection_time) . '</p>';
    }

    if (!empty($collection_datetime)) {
        echo '<p><strong>Collection DateTime:</strong> ' . esc_html(date('l jS F H:i', $collection_datetime)) . '</p>';
    }

    echo '</div>';
}


