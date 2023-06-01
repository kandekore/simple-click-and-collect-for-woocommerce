<?php 
/*
Plugin Name: Woo Collect
Description: Collection time plugin for WooCommerce orders
Version: 1.0
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

function collection_time_booking_deactivate()
{
    // Perform any cleanup tasks upon deactivation
    // ...
}

// Add admin settings page
add_action('admin_menu', 'collection_time_booking_admin_menu');

function collection_time_booking_admin_menu()
{
    add_menu_page(
        'Collection Time Settings',
        'Collection Time',
        'manage_options',
        'collection-time-settings',
        'collection_time_booking_settings_page',
        'dashicons-clock',
        30
    );
}

// Admin settings page
function collection_time_booking_settings_page()
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
    for ($time = $start_time; $time < $end_time; $time += $minimum_interval) {
        $time_slots[date('H:i', $time)] = date('h:i A', $time);
    }

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
        $opening_hours = get_option('collection_time_booking_opening_hours', array());
        $selected_datetime = strtotime($selected_date . ' ' . $selected_time);
        $minimum_interval = 2 * 60 * 60;
        $current_datetime = strtotime('now');
        
        // Calculate the minimum allowed collection datetime
        $minimum_collection_datetime = $current_datetime + $minimum_interval;
        
        // Validate collection time
        if ($selected_datetime < $minimum_collection_datetime) {
            wc_add_notice(__('Please select a collection time that is at least 2 hours into the future.'), 'error');
        } else {
            WC()->session->set('selected_collection_date', $selected_date);
            WC()->session->set('selected_collection_time', $selected_time);
        }
    }
}


// Save the selected collection date and time to the order
add_action('woocommerce_checkout_create_order', 'collection_time_booking_save_collection_datetime');

function collection_time_booking_save_collection_datetime($order)
{
    if (WC()->session->get('selected_collection_date') && WC()->session->get('selected_collection_time')) {
        $collection_date = WC()->session->get('selected_collection_date');
        $collection_time = WC()->session->get('selected_collection_time');
        $collection_datetime = strtotime($collection_date . ' ' . $collection_time);
        $order->update_meta_data('Collection Date', $collection_date);
        $order->update_meta_data('Collection Time', $collection_time);
        $order->update_meta_data('Collection DateTime', $collection_datetime);
    }
}

// Display the selected collection date and time in the admin order page
add_action('woocommerce_admin_order_data_after_billing_address', 'collection_time_booking_display_admin_order_meta', 10, 1);

function collection_time_booking_display_admin_order_meta($order)
{
    $collection_date = $order->get_meta('Collection Date');
    $collection_time = $order->get_meta('Collection Time');
    if (!empty($collection_date) && !empty($collection_time)) {
        $collection_datetime = date('Y-m-d H:i', $order->get_meta('Collection DateTime'));
        echo '<p><strong>Collection Date:</strong> ' . esc_html($collection_date) . '</p>';
        echo '<p><strong>Collection Time:</strong> ' . esc_html($collection_time) . '</p>';
        echo '<p><strong>Collection DateTime:</strong> ' . esc_html($collection_datetime) . '</p>';
    }
}

// Attach collection date and time to the order confirmation email sent to the admin
add_action('woocommerce_email_order_details', 'collection_time_booking_add_collection_datetime_to_email', 10, 4);

function collection_time_booking_add_collection_datetime_to_email($order, $sent_to_admin, $plain_text, $email)
{
    if ($sent_to_admin && $order->get_meta('Collection Date') && $order->get_meta('Collection Time')) {
        $collection_date = $order->get_meta('Collection Date');
        $collection_time = $order->get_meta('Collection Time');
        $collection_datetime = date('Y-m-d H:i', $order->get_meta('Collection DateTime'));
        echo '<p><strong>Collection Date:</strong> ' . esc_html($collection_date) . '</p>';
        echo '<p><strong>Collection Time:</strong> ' . esc_html($collection_time) . '</p>';
        echo '<p><strong>Collection DateTime:</strong> ' . esc_html($collection_datetime) . '</p>';
    }
}

// Add custom dashboard widget
add_action('wp_dashboard_setup', 'collection_time_booking_add_dashboard_widget');

function collection_time_booking_add_dashboard_widget()
{
    wp_add_dashboard_widget(
        'collection_time_booking_widget',
        'Upcoming Collections',
        'collection_time_booking_dashboard_widget_content'
    );
}

// Custom dashboard widget content
function collection_time_booking_dashboard_widget_content()
{
    // Get upcoming collection times from custom post type
    $collection_times = get_posts(array(
        'post_type' => 'collection-time',
        'meta_key' => 'Collection Time',
        'meta_value' => date('Y-m-d'),
        'meta_compare' => '>=',
        'orderby' => 'meta_value',
        'order' => 'ASC',
        'posts_per_page' => -1
    ));

    // Get upcoming collections from orders
    $orders = wc_get_orders(array(
        'meta_key' => 'Collection DateTime',
        'meta_value' => date('Y-m-d H:i:s'),
        'meta_compare' => '>=',
        'orderby' => 'meta_value',
        'order' => 'ASC',
        'status' => 'wc-completed',
        'limit' => -1
    ));

    // Combine collection times and orders into a single array
    $upcoming_collections = array_merge($collection_times, $orders);

    // Display upcoming collections
    if (!empty($upcoming_collections)) {
        echo '<ul>';
        foreach ($upcoming_collections as $collection) {
            $collection_datetime = '';
            if ($collection->post_type === 'collection-time') {
                $collection_datetime = get_post_meta($collection->ID, 'Collection Time', true);
            } elseif ($collection->get_meta('Collection DateTime')) {
                $collection_datetime = $collection->get_meta('Collection DateTime');
            }
            
            echo '<li>' . esc_html($collection_datetime) . '</li>';
        }
        echo '</ul>';
    } else {
        echo '<p>No upcoming collections found.</p>';
    }
}

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
wp_enqueue_script('collection-time-booking-script', plugin_dir_url(__FILE__) . 'js/collection-time-booking.js', array('jquery-ui-datepicker', 'jquery-ui-timepicker-addon'),'1.14', true);

// Localize script with the collection time options
$collection_time_options = array(
    'curdate' => date("Y-m-d"),   //anuj
    'timeFormat' => get_option('time_format', 'g:i A'),
    'minDate' => 0, // Minimum date is today
    'minTime' => date('H:i', strtotime('+2 hours')), // Minimum time is 2 hours from now
    'maxTime' => '' // Placeholder for the maximum time based on opening hours
);
$opening_hours = get_option('collection_time_booking_opening_hours', array());
if (!empty($opening_hours)) {
    $collection_time_options['openingHours'] = $opening_hours;
}
wp_localize_script('collection-time-booking-script', 'collectionTimeOptions', $collection_time_options);