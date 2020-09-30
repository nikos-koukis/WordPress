<?php
/*Plugin Name: WooCommerce Product Wishlist
	Plugin URI: http://woothemes.com/woocommerce
	Description: To manage customers favourite product for later.
	Author: Acespritech Solutions Pvt. Ltd.
	Author URI: https://acespritech.com/
	Version: 1.2.0
	Domain Path: /languages/
*/

include_once( ABSPATH . 'wp-admin/includes/plugin.php' );

$woocommerce_wishlist = get_option('woocommerce_wishlist', 1);
if(is_plugin_active( 'woocommerce/woocommerce.php' ))
{
    if ($woocommerce_wishlist == "yes") {
        add_action('admin_menu', 'aceww_wishlist_menu');
        add_action('wp_enqueue_scripts', 'aceww_wishlist_script');
        add_filter('woocommerce_account_menu_items', 'aceww_account_menu_items', 10, 1);
        add_action('init', 'aceww_wishlist_endpoint');
        add_filter('the_title', 'aceww_wishlist_endpoint_title');
        add_action('woocommerce_account_wishlist_endpoint', 'aceww_wishlist_frontend_content');
        add_action('woocommerce_after_shop_loop_item', 'aceww_show_wishlist_icon', 20);
        add_action('woocommerce_product_meta_start', 'aceww_show_wishlist_icon', 20);
        add_action('woocommerce_before_shop_loop','aceww_add_login_page_url');
        add_action('isa_add_every_three_minutes', 'aceww_cron_notification');
        add_filter('wp_nav_menu_items', 'aceww_add_tab', 10, 2);
    }
}
else{ 
    deactivate_plugins(plugin_basename(__FILE__));
    add_action( 'admin_notices', 'aceww_woocommerce_not_installed' );
}

function aceww_woocommerce_not_installed()
{
    ?>
    <div class="error notice">
      <p><?php _e( 'You need to install and activate WooCommerce to use WooCommerce Product Whishlist!', 'WooCommerce-Whishlist' ); ?></p>
    </div>
    <?php
}

function aceww_wishlist_menu()
{
    
    wp_enqueue_style('wishlist_style', plugins_url('/css/wishlist_style.css', __FILE__));
    $woocommerce_wishlist = get_option('woocommerce_wishlist', 1);
    if ($woocommerce_wishlist == "yes") {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        $table_name = $wpdb->prefix . "wishlist";
        $table_name1 = $wpdb->prefix . "wishlist_notification";
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
            $sql = "CREATE TABLE $table_name (
	                wishlist_id mediumint(9) NOT NULL AUTO_INCREMENT,
	                time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
	                user_id varchar(5) NOT NULL,
	                product_id varchar(5) NOT NULL,
	                PRIMARY KEY  (wishlist_id)
	                ) $charset_collate;";

            dbDelta($sql);
        } else {
            
        }
        
        $sql1 = "CREATE TABLE $table_name1 (
	                wishlist_notification_id mediumint(9) NOT NULL AUTO_INCREMENT,
	                user_id varchar(5) NOT NULL,
	                get_notification tinyint(1) NOT NULL,
	                cart tinyint(1) NOT NULL,
	                PRIMARY KEY  (wishlist_notification_id)
	                ) $charset_collate;";

        dbDelta($sql1);
        
    }
}

//ajax function
add_action('wp_ajax_aceww_add_wishlist', 'aceww_add_wishlist');
add_action('wp_ajax_nopriv_aceww_add_wishlist', 'aceww_add_wishlist');

add_action('wp_ajax_aceww_remove_wishlist', 'aceww_remove_wishlist');
add_action('wp_ajax_nopriv_aceww_remove_wishlist', 'aceww_remove_wishlist');

add_action('wp_ajax_aceww_remove_all', 'aceww_remove_all');
add_action('wp_ajax_nopriv_aceww_remove_all', 'aceww_remove_all');

add_action('wp_ajax_aceww_wishlist_frontend_content', 'aceww_wishlist_frontend_content');
add_action('wp_ajax_nopriv_aceww_wishlist_frontend_content', 'aceww_wishlist_frontend_content');

add_action('wp_ajax_aceww_get_notification', 'aceww_get_notification');
add_action('wp_ajax_nopriv_aceww_get_notification', 'aceww_get_notification');


add_action('wp_ajax_aceww_cart_checkbox', 'aceww_cart_checkbox');
add_action('wp_ajax_nopriv_aceww_cart_checkbox', 'aceww_cart_checkbox');

add_action('wp_ajax_aceww_update_menu_item', 'aceww_update_menu_item');
add_action('wp_ajax_nopriv_aceww_update_menu_item', 'aceww_update_menu_item');
//---- ajax function

// Register scripts


function aceww_wishlist_script()
{
    wp_enqueue_style('dashicons');
    wp_enqueue_script('wishlist_script1', plugins_url('/js/script.js', __FILE__), array('jquery'));
    wp_enqueue_style('wishlist_style', plugins_url('/css/whishlist_style.css', __FILE__));
}

// add menu in account page and create end point

function aceww_account_menu_items($items)
{
    $my_items = array(
        'wishlist' => __('Wishlist', 'wish'),
    );
    $my_items = array_slice($items, 1, true) + $my_items + array_slice($items, 1, count($items), true);
    return $my_items;
}


function aceww_wishlist_endpoint()
{
    add_rewrite_endpoint('wishlist', EP_PAGES);
}


function aceww_wishlist_endpoint_title($title)
{
    global $wp_query;

    $is_endpoint = isset($wp_query->query_vars['wishlist']);

    if ($is_endpoint && !is_admin() && is_main_query() && in_the_loop() && is_account_page()) {
        // New page title.
        $title = __('Wishlist', 'woocommerce');

        remove_filter('the_title', 'aceww_wishlist_endpoint_title');
    }

    return $title;
}

// frontend content user side


function aceww_wishlist_frontend_content()
{
    ?>
    <span class="admin-url" hidden><?php echo $admin_url = admin_url('admin-ajax.php'); ?></span>
    <?php
    $user = wp_get_current_user();
    $user_id = get_current_user_id();
    global $wpdb;
    // $cart = '';
    $table_name = $wpdb->prefix . "wishlist";
    $table_name1 = $wpdb->prefix . "wishlist_notification";
    $result = $wpdb->get_results("SELECT * FROM $table_name where user_id = $user_id");
    $result_count = count($result);
    if ($result_count == 0) {
        echo 'Your wishlist is currently empty.';
    } else {
        $result1 = $wpdb->get_results("SELECT * FROM $table_name1 where user_id = $user_id");
        foreach ($result1 as $print) {
            $cart = $print->cart;
        }
        ?>

        <input type="checkbox" name="add-to-cart" class="cart_check"
            <?php
            if ($cart == 1) {
                echo 'checked';
            }
            ?> >Items after add to cart remaining in Wishlist
        <table>
            <tr>
                <th>Product</th>
                <th></th>
                <th>Price</th>
                <th></th>
            </tr>
            <?php
            $user = wp_get_current_user();
            $user_id = get_current_user_id();
            global $wpdb;
            
            $table_name = $wpdb->prefix . "wishlist";
            $result = $wpdb->get_results("SELECT * FROM $table_name where user_id = $user_id ORDER BY wishlist_id DESC  LIMIT 10 ");
            foreach ($result as $print) {
                $product_id = $print->product_id;
                $product = wc_get_product($product_id);
                $product->get_image_id();
                ?>
                <tr>
                    <td>
                        <img width="60px" src="<?php echo get_the_post_thumbnail_url($product->get_id(), 'full') ?>">
                    </td>
                    <td style="width: 50%;">
                        <a href="<?php echo get_permalink($product_id); ?>"><?php echo $product->get_title(); ?></a>
                        <p class="single_remove" data-product_id='<?php echo $product_id; ?>'>Remove</p>
                    </td>
                    <td>
                        <?php echo $product->get_price_html(); ?>
                    </td>
                    <td>

                        <a class="add-to-cart button product_type_simple add_to_cart_button ajax_add_to_cart"
                           href="<?php echo get_site_url(); ?>/my-account/wishlist/?add-to-cart=<?php echo $product_id; ?>"
                           data-product_id="<?php echo $product_id; ?>">Add to Cart</a>

                    </td>
                </tr>
            <?php } ?>
        </table>
        <button class="remove-all">Remove All</button>
        <button class="get_notification">
            <?php
            $table_name1 = $wpdb->prefix . "wishlist_notification";
            $result = $wpdb->get_results("SELECT * FROM $table_name1 where user_id = $user_id && get_notification = 1");
            $result_count = count($result);
            if ($result_count == 1) {
                echo 'Stop Notification';
            } else {
                echo 'Get Notification';
            }
            ?>
        </button>
        <?php
    }
    die();
}

// --------------------------------------------------
function aceww_get_notification()
{
    $user = wp_get_current_user();
    $user_id = get_current_user_id();
    global $wpdb;
    $table_name1 = $wpdb->prefix . "wishlist_notification";
    $result = $wpdb->get_results("SELECT * FROM $table_name1 where user_id = $user_id");
    $result_count = count($result);
    if ($result_count == 0) {
        $insert = $wpdb->insert($table_name1, array(
            'user_id' => $user_id,
            'get_notification' => 1
        ));
    } else {
        foreach ($result as $print) {
            $value = $print->get_notification;
            if ($value == 1) {
                $value = 0;
            } else {
                $value = 1;
            }
            $update = $wpdb->query($wpdb->prepare("UPDATE $table_name1 
                						 				SET get_notification = %s 
            		                      				WHERE user_id = %s", $value, $user_id));

        }
    }

    die();
}

function aceww_cart_checkbox()
{
    $user = wp_get_current_user();
    $user_id = get_current_user_id();
    global $wpdb;
    
    $table_name1 = $wpdb->prefix . "wishlist_notification";
    $result = $wpdb->get_results("SELECT * FROM $table_name1 where user_id = $user_id");
    // var_dump($result);
    if (count($result) == 0) {
        $insert = $wpdb->insert($table_name1, array(
            'cart' => 1,
            'user_id' => $user_id
        ));
        echo "insert";
    } else {
        foreach ($result as $print) {
            $cart = $print->cart;
            if ($cart == 0) {
                $cart = 1;
            } else {
                $cart = 0;
            }
            $wpdb->query($wpdb->prepare("UPDATE $table_name1 
                						 				SET cart = %s 
            		                      				WHERE user_id = %s", $cart, $user_id));
        }
        echo "update";
    }
    die();

}

function aceww_set_html_content_type()
{
    return 'text/html';
}

add_filter('cron_schedules', 'aceww_isa_add_every_three_minutes');
function aceww_isa_add_every_three_minutes($schedules)
{
    $schedules['every_three_minutes'] = array(
        'interval' => get_option('woocommerce_wishlist_schedule_time', 1) * 24 * 60 * 60,
        'display' => __('Every 3 Minutes', 'textdomain')
    );
    return $schedules;
}

// Schedule an action if it's not already scheduled
if (!wp_next_scheduled('isa_add_every_three_minutes')) {
    wp_schedule_event(time(), 'every_three_minutes', 'isa_add_every_three_minutes');
}

// Hook into that action that'll fire every three minutes

function aceww_cron_notification()
{
    add_filter('wp_mail_content_type', 'aceww_set_html_content_type');
    $headers = array('Content-Type: text/html; charset=UTF-8');
    $url = get_site_url();
    global $wpdb;
    $table_name1 = $wpdb->prefix . "wishlist_notification";
    $table_name = $wpdb->prefix . "wishlist";
    $result = $wpdb->get_results("SELECT * FROM $table_name1 where get_notification = 1");
    $custom_logo_id = get_theme_mod('custom_logo');
    $image = wp_get_attachment_image_src($custom_logo_id, 'full');

    foreach ($result as $print) {
        $user_id = $print->user_id;
        $user_info = get_userdata($user_id);
        echo $email = $user_info->user_email;
        echo $first_name = $user_info->first_name;
        include('mail.php');
        wp_mail('email', 'Wishlist Notification', $message, '$headers', '');
    }
}


function aceww_add_login_page_url()
{ ?>
    <div class="login-page-url" hidden>
    <?php echo get_site_url(); ?>/my-account/
    </div>
<?php }

function aceww_show_wishlist_icon()
{
    $admin_url = admin_url('admin-ajax.php');

    global $product;
    $product_id = $product->get_id();
    $user = wp_get_current_user();
    $user_id = get_current_user_id();

    global $wpdb;
    $table_name = $wpdb->prefix . "wishlist";


    $result = $wpdb->get_results("SELECT * FROM $table_name where user_id = $user_id && product_id = $product_id");
    $result_count = count($result);

    if ($result_count >= 1) {
        echo '<p class="wishlist active" data-url=' . $admin_url . ' data-product-id=' . $product_id . '><span class="dashicons dashicons-heart"></span></p>';
    } else {
        echo '<p class="wishlist" data-url=' . $admin_url . ' data-product-id=' . $product_id . '><span class="dashicons dashicons-heart"></span></p>';
    }

}


function aceww_add_wishlist()
{
    $user = wp_get_current_user();
    echo $user_id = get_current_user_id();
    if ($user_id == 0) {
        echo 0;
    } else {
        $product_id = sanitize_text_field($_POST['product_id']);
        global $wpdb;
        
        $table_name = $wpdb->prefix . "wishlist";

        $insert = $wpdb->insert($table_name, array(
            'product_id' => $product_id,
            'user_id' => $user_id
        ));


    }

    die();
}

function aceww_remove_wishlist()
{
    $user = wp_get_current_user();
    $user_id = get_current_user_id();
    $product_id = sanitize_text_field($_POST['product_id']);
    global $wpdb;
    $table_name = $wpdb->prefix . "wishlist";


    $delete = $wpdb->delete($table_name, ['user_id' => $user_id, 'product_id' => $product_id], ['%d']);
    var_dump($delete);
    echo 'delete';
    die();
}

function aceww_remove_all()
{
    $user = wp_get_current_user();
    $user_id = get_current_user_id();
    global $wpdb;
    $table_name = $wpdb->prefix . "wishlist";

    $delete = $wpdb->delete($table_name, ['user_id' => $user_id], ['%d']);
    var_dump($delete);
    echo 'delete all';
    die();

}

add_filter('woocommerce_general_settings', 'aceww_add_wishlist_enable');

function aceww_add_wishlist_enable($settings)
{
    $updated_settings = array();
    foreach ($settings as $section) {
        // at the bottom of the General Options section

        if (isset($section['id']) && 'general_options' == $section['id'] &&

            isset($section['type']) && 'sectionend' == $section['type']) {
            $updated_settings[] = array(

                'name' => __('Wishlist', 'Wishlist'),
                // 'desc_tip' => __( 'The starting number for the incrementing portion of the order numbers, unless there is an existing order with a higher number.', 'wc_seq_order_numbers' ),
                'id' => 'woocommerce_wishlist',
                'type' => 'checkbox',
                'css' => 'min-width:300px;',
                'desc' => __('Enable Add to wishlist options for Customers', 'wishlist'),
            );
            $updated_settings[] = array(

                'name' => __('Wishlist Mail Schedule', 'wishlist'),
                // 'desc_tip' => __( 'The starting number for the incrementing portion of the order numbers, unless there is an existing order with a higher number.', 'wc_seq_order_numbers' ),
                'id' => 'woocommerce_wishlist_schedule_time',
                'type' => 'select',
                'options' => array(
                    '1' => __('Daily', 'woocommerce'),
                    '7' => __('Weekly', 'woocommerce')
                ),
                'css' => 'min-width:300px;'
            );

        }
        $updated_settings[] = $section;
    }
    return $updated_settings;
}


function aceww_add_tab($menu)
{
    // check if it is the 'primary' navigation menu



    // add the search form
    ob_start();
    get_search_form();
    $search = ob_get_clean();
    $user = wp_get_current_user();
    $user_id = get_current_user_id();
    $menu .= '<li class="menu-item-wishlist menu-item menu-item-type-post_type menu-item-object-page">
      				<div class="dropdown">
  						<a><span>My Wishlist</span></a>
  						<div class="dropdown-content">
  						<table>';
    global $wpdb;
    $table_name = $wpdb->prefix . "wishlist";
    $result = $wpdb->get_results("SELECT * FROM $table_name where user_id = $user_id ORDER BY wishlist_id DESC  LIMIT 3 ");
    $result_count = count($result);
    if ($result_count == 0) {
        $menu .= 'Your wishlist is currently empty';
    } else {

        foreach ($result as $print) {
            $product_id = $print->product_id;
            $product = wc_get_product($product_id);
            $product->get_image_id();
            $product_img = get_the_post_thumbnail_url($product->get_id(), "full");
            $product_title = $product->get_title();
            $price = $product->get_price_html();
            $url = get_permalink($product_id);
            $menu .= '<tr>
					<td class="product-img">
						<img src ="' . $product_img . '">
					</td>
					<td class="product-content">
						<a href="' . $url . '"><p>' . $product_title . '</p></a>
						<p>' . $price . '</p>
					</td>
				</tr>';
        }
    }
    $menu .= '</table><div class="footer"><a href="' . get_site_url() . '/my-account/wishlist/"><button>View wishlist</button></a></div>';


    $menu .= '</div></div></li>';
    //	die();

   // echo $menu;

    //die();
    return $menu;
    //die();

}

function aceww_update_menu_item(){
    
    $user = wp_get_current_user();
    $user_id = get_current_user_id();
    
    global $wpdb;
    $table_name = $wpdb->prefix . "wishlist";
    $result = $wpdb->get_results("SELECT * FROM $table_name where user_id = $user_id ORDER BY wishlist_id DESC  LIMIT 3 ");
    $result_count = count($result);
    if ($result_count == 0) {
        $menu .= 'Your wishlist is currently empty';
    } else {

        foreach ($result as $print) {
            $product_id = $print->product_id;
            $product = wc_get_product($product_id);
            $product->get_image_id();
            $product_img = get_the_post_thumbnail_url($product->get_id(), "full");
            $product_title = $product->get_title();
            $price = $product->get_price_html();
            $url = get_permalink($product_id);
            $menu .= '<tr>
                    <td class="product-img">
                        <img src ="' . $product_img . '">
                    </td>
                    <td class="product-content">
                        <a href="' . $url . '"><p>' . $product_title . '</p></a>
                        <p>' . $price . '</p>
                    </td>
                </tr>';
        }
    }
   
   echo $menu;
}

function aceww_clear_notices_on_cart_update()
{
    wc_clear_notices();
}

;

// add the filter 
add_filter('woocommerce_update_cart_action_cart_updated', 'aceww_clear_notices_on_cart_update', 10, 1);