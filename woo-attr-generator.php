<?php
/*
Plugin Name: Dummy Product Attributes Generator
Description: Generates dummy product attributes in WooCommerce.
Version: 1.0
Author: WP Army
*/

// Hook function to WordPress admin menu
add_action('admin_menu', 'dummy_attributes_menu');

// Function to create admin menu item
function dummy_attributes_menu() {
    add_menu_page(
        'Dummy Attributes Generator', // Page title
        'Dummy Attributes',           // Menu title
        'manage_options',             // Capability
        'dummy-attributes',           // Menu slug
        'generate_dummy_attributes'   // Callback function
    );
}

// Function to generate dummy attributes
function generate_dummy_attributes() {
    // Check if WooCommerce is active
    if ( ! class_exists( 'WooCommerce' ) ) {
        echo '<div class="error"><p>WooCommerce is not active. Please activate WooCommerce to use this plugin.</p></div>';
        return;
    }

    // Check if form submitted
    if (isset($_POST['generate_attributes'])) {
        $num_attributes = isset($_POST['num_attributes']) ? intval($_POST['num_attributes']) : 0;
        $num_values = isset($_POST['num_values']) ? intval($_POST['num_values']) : 0;
        $generate_variations = isset($_POST['generate_variations']) ? intval($_POST['generate_variations']) : 0;

        if ($num_attributes <= 0 || $num_values <= 0) {
            echo '<div class="error"><p>Please enter valid values for number of attributes and number of values.</p></div>';
            return;
        }

        // Generate dummy attributes
        for ($i = 1; $i <= $num_attributes; $i++) {
            $attribute_name = 'Attribute ' . $i;
            $terms = array();
            for ($j = 1; $j <= $num_values; $j++) {
                $terms[] = 'Value ' . $i . '-' . $j;
            }
            $attribute_data = array(
                'name' => $attribute_name,
                'type' => 'select',
                'order_by' => 'menu_order',
                'has_archives' => false,
                'terms' => $terms
            );
            $attribute = wc_create_attribute($attribute_data);
            if (!is_wp_error($attribute)) {
                echo '<p>Attribute created: ' . $attribute_name . '</p>';
                if ($generate_variations) {
                    generate_variations_for_attribute($attribute);
                }
            } else {
                echo '<div class="error"><p>Error creating attribute: ' . $attribute_name . '</p></div>';
            }
        }
    }

    // Check if form submitted for deletion
    if (isset($_POST['delete_attributes'])) {
        global $wpdb;
        $wpdb->query("TRUNCATE TABLE {$wpdb->prefix}woocommerce_attribute_taxonomies");
        echo '<div class="updated"><p>All attributes deleted successfully.</p></div>';
    }

    // Display form
    ?>
    <div class="wrap">
        <h1>Generate Dummy Attributes</h1>
        <form method="post" action="">
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">Number of Attributes:</th>
                    <td><input type="number" name="num_attributes" min="1" value="1" required></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Number of Values per Attribute:</th>
                    <td><input type="number" name="num_values" min="1" value="1" required></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Generate Variations:</th>
                    <td><input type="checkbox" name="generate_variations" value="1"> (Check to generate variations)</td>
                </tr>
            </table>
            <p class="submit">
                <input type="submit" name="generate_attributes" class="button-primary" value="Generate Attributes">
            </p>
        </form>
        <form method="post" action="">
            <p class="submit">
                <input type="submit" name="delete_attributes" class="button" value="Delete All Attributes">
            </p>
        </form>
    </div>
    <?php
}

// Function to generate variations for attribute
function generate_variations_for_attribute($attribute) {
    $args = array(
        'post_type'     => 'product',
        'post_status'   => 'publish',
        'numberposts'   => -1
    );
    $products = get_posts($args);

    foreach ($products as $product) {
        $product_id = $product->ID;
        $product_variation = new WC_Product_Variable($product_id);
        $variation_data = array(
            'attributes' => array(
                $attribute->attribute_name => ''
            ),
            'manage_stock' => true
        );
        $variation_id = $product_variation->add_variation($variation_data);
        $product_variation->save();
    }
}
