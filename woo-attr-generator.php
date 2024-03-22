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

    // Delete terms associated with attributes
    $attribute_taxonomies = wc_get_attribute_taxonomies();
    foreach ($attribute_taxonomies as $attribute_taxonomy) {
        $taxonomy = 'pa_' . $attribute_taxonomy->attribute_name;
        $wpdb->query("DELETE FROM {$wpdb->term_taxonomy} WHERE taxonomy = '{$taxonomy}'");
    }

    // Delete all attribute taxonomies
    $wpdb->query("TRUNCATE TABLE {$wpdb->prefix}woocommerce_attribute_taxonomies");

    // Clear WooCommerce transients
    wc_delete_product_transients();

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
    $terms = get_terms(array(
        'taxonomy' => 'pa_' . $attribute->attribute_name,
        'hide_empty' => false,
    ));

    if (!empty($terms)) {
        foreach ($terms as $term) {
            $term_name = $term->name;
            $variation_data = array(
                'attributes' => array(
                    $attribute->attribute_name => $term_name,
                ),
                'manage_stock' => true,
            );

            $product_id = wp_insert_post(array(
                'post_title' => 'Variation #' . $term->term_id . ' of Product #' . $attribute->attribute_id,
                'post_status' => 'publish',
                'post_type' => 'product_variation',
                'post_parent' => 0,
                'post_content' => '',
            ));

            if ($product_id) {
                foreach ($variation_data['attributes'] as $key => $value) {
                    update_post_meta($product_id, 'attribute_' . $key, $value);
                }
                update_post_meta($product_id, '_price', '');
                update_post_meta($product_id, '_regular_price', '');
                update_post_meta($product_id, '_stock_status', 'instock');
            }
        }
    }
}

