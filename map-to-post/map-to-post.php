<?php
/**
 * Plugin Name: Map to Post
 * Plugin URI: https://example.com/
 * Description: A custom plugin to add location fields to posts and show all locations on map using a shortcode.
 * Version: 1.0
 * Author: drashti soni
 * Author URI: https://drashti96.wordpress.com/
 * License: GPL2
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}
// Function to run on activation
function my_plugin_activate()
{
    // Code to execute on activation (e.g., creating a table)
    error_log('My Custom Plugin Activated');
}
register_activation_hook(__FILE__, 'my_plugin_activate');

// Function to run on deactivation
function my_plugin_deactivate()
{
    // Cleanup tasks (e.g., removing options)
    error_log('My Custom Plugin Deactivated');
}
register_deactivation_hook(__FILE__, 'my_plugin_deactivate');

function my_plugin_menu()
{
    add_menu_page(
        'Map for Posts',   
        'Map for Posts',          
        'manage_options',     
        'my-plugin-settings',
        'my_plugin_settings_page', 
        );
}
add_action('admin_menu', 'my_plugin_menu');

// Callback function for the menu page
function my_plugin_settings_page()
{
    echo '<div class="wrap"><h1>My Plugin Settings</h1>';
    echo '<p>Here is the shortcode you can use to show posts with map! Before that please add locations to Posts.</p>';
    echo '<p>[map-with-posts][/map-with-posts]</p>';
    echo '</div>';
}

function show_map($atts){
    ?>
   
    <?php
    ob_start();

    $meta_key = '_clp_post_location'; 

    $posts = get_posts(array(
        'post_type' => 'post', 
        'posts_per_page' => 15,     
    ));
   
    foreach ($posts as $post) {
        $meta_value = get_post_meta($post->ID, $meta_key, true);
        if($meta_value != ''){
            
            $address[] = $meta_value;
            $titles[] = $post->post_title;
            $links[] = get_the_permalink($post->ID);
        }
    }

    if(!empty($address) && !empty($titles)){
        $addresses = json_encode($address);
        $titles = json_encode($titles);
        $links = json_encode($links);
    }


    if (empty($address)) {
        
        echo '<div class="container" >';
        echo '<H3>NO ARTICLE LOCATIONS AT THE MOMENT!</H3>';
        echo '</div>';
    }
    else{
        echo '<div class="container" >';
        echo '<H3>CHECK OUT OUR ARTICLES</H3>';
        echo '<div id="map"></div>';
        echo '</div>';
    }
    ?>
        <script>
            document.addEventListener("DOMContentLoaded", function() {
            <?php if(!empty($addresses)){ ?>
            var addresses = <?php echo $addresses; ?>;
            <?php } ?>
            <?php if(!empty($titles)) {?>
             var titles = <?php echo $titles; ?>;
             <?php } ?>
            <?php if(!empty($links)) {?>
             var links = <?php echo $links; ?>;
             <?php } ?>
            
            
            if(typeof addresses !== "undefined"){
            var map = L.map('map').setView([20.5937, 78.9629], 5); 
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; OpenStreetMap contributors'
                }).addTo(map);
            }
            
            function geocodeAddress(address, title, link) {
                
                var url = 'https://nominatim.openstreetmap.org/search?format=json&q='+address+'';
                // var url = 'https://api.opencagedata.com/geocode/v1/json?q='+address+'&key=c97eb41e8d2a40aa93f5424abf46ce83';

                    fetch(url, {
                        headers: { "User-Agent": "MyLeafletApp/1.0 (dras.rajpura@gmail.com)" } 
                    })
                    .then(response => response.json())
                    .then(data => {
                        // console.log(data);
                        if (data.length > 0) {
                            var lat = data[0].lat;
                            var lon = data[0].lon;

                            // Update map position
                            map.setView([lat, lon], 7);

                            // marker and popup
                            L.marker([lat, lon]).addTo(map).bindPopup('<a href="'+link+'" >'+title+'</a>')
                            .openPopup();
                    
                        } else {
                            console.log("Address not found!");
                        }
                    })
                    .catch(error => console.error("Error fetching geocode:", error));
            }
               
            if(typeof addresses !== "undefined"){
                  addresses.forEach((address, index) => {
                    var title = titles[index]; 
                    var link = links[index]; 
                    // console.log(title);
                    geocodeAddress(address, title, link);
                });
            }
              
            });
            
    </script>
    <?php
    return ob_get_clean();
}
add_shortcode('map-with-posts', 'show_map');

function my_plugin_enqueue_scripts()
{
    wp_enqueue_style('stylecss', plugin_dir_url(__FILE__).'/assets/style.css');
    wp_enqueue_style('leafletcss', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css');
    wp_enqueue_script('leafletjs', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js', ['jquery'], null, true);
    wp_enqueue_script('scriptjs', plugin_dir_url(__FILE__) . '/assets/script.js', ['jquery'], null, false);
}
add_action('wp_enqueue_scripts', 'my_plugin_enqueue_scripts');
//  add meta box
add_action('add_meta_boxes', 'clp_add_location_meta_box');
add_action('save_post', 'clp_save_location_meta');

// Add Location Field Meta Box
function clp_add_location_meta_box()
{
    add_meta_box(
        'clp_location_meta',        
        'Post Location',            // Meta Box Title
        'clp_render_location_meta_box',  // Callback function
        'post',                    
        'side',                     
        'default'                    
    );
}

function clp_render_location_meta_box($post)
{
    $location = get_post_meta($post->ID, '_clp_post_location', true);
    wp_nonce_field('clp_location_nonce_action', 'clp_location_nonce');
    ?>
        <label for="clp_location">Enter Location:</label>
        <input type="text" name="clp_location" value="<?php echo esc_attr($location); ?>" placeholder="City, Country" style="width:100%;" />
        <?php
}

//Save Location Field
function clp_save_location_meta($post_id)
{
    if (!isset($_POST['clp_location_nonce']) || !wp_verify_nonce($_POST['clp_location_nonce'], 'clp_location_nonce_action')) {
        return;
    }

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    if (isset($_POST['clp_location'])) {
        update_post_meta($post_id, '_clp_post_location', sanitize_text_field($_POST['clp_location']));
    }
}