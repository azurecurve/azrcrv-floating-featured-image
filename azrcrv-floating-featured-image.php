<?php
/**
 * ------------------------------------------------------------------------------
 * Plugin Name: Floating Featured Image
 * Description: Shortcode allowing a floating featured image to be placed at the top of a post.
 * Version: 1.0.1
 * Author: azurecurve
 * Author URI: https://development.azurecurve.co.uk/classicpress-plugins/
 * Plugin URI: https://development.azurecurve.co.uk/classicpress-plugins/floating-featured-image
 * Text Domain: floating-featured-image
 * Domain Path: /languages
 * ------------------------------------------------------------------------------
 * This is free software released under the terms of the General Public License,
 * version 2, or later. It is distributed WITHOUT ANY WARRANTY; without even the
 * implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. Full
 * text of the license is available at https://www.gnu.org/licenses/gpl-2.0.html.
 * ------------------------------------------------------------------------------
 */

global $azc_ffi_db_version;
$azc_ffi_db_version = '2.1.0';

// Prevent direct access.
if (!defined('ABSPATH')){
	die();
}

// include plugin menu
require_once(dirname( __FILE__).'/pluginmenu/menu.php');

/**
 * Setup registration activation hook, actions, filters and shortcodes.
 *
 * @since 1.0.0
 *
 */
// add actions
register_activation_hook(__FILE__, 'azrcrv_ffi_set_default_options');
register_activation_hook(__FILE__, 'azrcrv_ffi_install');

// add actions
add_action("admin_menu", "azrcrv_ffi_create_menus");
add_action('admin_post_azrcrv_ffi_save_options', 'azrcrv_ffi_save_options');
add_action('plugins_loaded', 'azrcrv_ffi_update_db_check');
add_action('admin_post_azrcrv_ffi_add_image', 'azrcrv_ffi_add_image');
//add_action('admin_init', 'azrcrv_ffi_admin_init_process_images');
add_action('admin_post_azrcrv_ffi_process_image', 'azrcrv_ffi_process_image');
add_action('wp_enqueue_scripts', 'azrcrv_ffi_load_css');
//add_action('the_posts', 'azrcrv_ffi_check_for_shortcode');

// add filters
add_filter('plugin_action_links', 'azrcrv_ffi_add_plugin_action_link', 10, 2);

// add shortcodes
add_shortcode('featured-image', 'azrcrv_ffi_display_image');
add_shortcode('ffi', 'azrcrv_ffi_display_image');

/**
 * Check if shortcode on current page and then load css and jqeury.
 *
 * @since 1.0.0
 *
 */
function azrcrv_ffi_check_for_shortcode($posts){
    if (empty($posts)){
        return $posts;
	}
	
	
	// array of shortcodes to search for
	$shortcodes = array(
						'ffi','featured-image'
						);
	
    // loop through posts
    $found = false;
    foreach ($posts as $post){
		// loop through shortcodes
		foreach ($shortcodes as $shortcode){
			// check the post content for the shortcode
			if (has_shortcode($post->post_content, $shortcode)){
				$found = true;
				// break loop as shortcode found in page content
				break 2;
			}
		}
	}
 
    if ($found){
		// as shortcode found call functions to load css and jquery
        azrcrv_ffi_load_css();
    }
    return $posts;
}

/**
 * Load CSS.
 *
 * @since 1.0.0
 *
 */
function azrcrv_ffi_load_css(){
	wp_enqueue_style('azrcrv-ffi', plugins_url('assets/css/style.css', __FILE__), '', '1.0.0');
}

/**
 * Set default options for plugin.
 *
 * @since 1.0.0
 *
 */
function azrcrv_ffi_set_default_options($networkwide){
	
	$option_name = 'azrcrv-ffi';
	$old_option_name = 'azc_ffi_options';
	
	$new_options = array(
				'default_path' => plugin_dir_url(__FILE__).'images/',
				'default_image' => '',
				'default_title' => '',
				'default_alt' => '',
				'default_taxonomy' => '',
				'default_taxonomy_is_tag' => 0
			);
	
	// set defaults for multi-site
	if (function_exists('is_multisite') && is_multisite()){
		// check if it is a network activation - if so, run the activation function for each blog id
		if ($networkwide){
			global $wpdb;

			$blog_ids = $wpdb->get_col("SELECT blog_id FROM $wpdb->blogs");
			$original_blog_id = get_current_blog_id();

			foreach ($blog_ids as $blog_id){
				switch_to_blog($blog_id);

				if (get_option($option_name) === false){
					if (get_option($old_option_name) === false){
						add_option($option_name, $new_options);
					}else{
						add_option($option_name, get_option($old_option_name));
					}
				}
			}

			switch_to_blog($original_blog_id);
		}else{
			if (get_option($option_name) === false){
				if (get_option($old_option_name) === false){
					add_option($option_name, $new_options);
				}else{
					add_option($option_name, get_option($old_option_name));
				}
			}
		}
		if (get_site_option($option_name) === false){
				if (get_option($old_option_name) === false){
					add_option($option_name, $new_options);
				}else{
					add_option($option_name, get_option($old_option_name));
				}
		}
	}
	//set defaults for single site
	else{
		if (get_option($option_name) === false){
				if (get_option($old_option_name) === false){
					add_option($option_name, $new_options);
				}else{
					add_option($option_name, get_option($old_option_name));
				}
		}
	}
}

/**
 * Add Floating Featured Image action link on plugins page.
 *
 * @since 1.0.0
 *
 */
function azrcrv_ffi_add_plugin_action_link($links, $file){
	static $this_plugin;

	if (!$this_plugin){
		$this_plugin = plugin_basename(__FILE__);
	}

	if ($file == $this_plugin){
		$settings_link = '<a href="'.get_bloginfo('wpurl').'/wp-admin/admin.php?page=azrcrv-ffi">'.esc_html__('Settings' ,'floating-featured-image').'</a>';
		array_unshift($links, $settings_link);
	}

	return $links;
}

/**
 * Add to menu.
 *
 * @since 1.0.0
 *
 */
function azrcrv_ffi_create_menus(){
	
	add_submenu_page("azrcrv-plugin-menu"
						,esc_html__("Floating Featured Image Settings", "floating-featured-image")
						,esc_html__("Floating Featured Image", "floating-featured-image")
						,'manage_options'
						,'azrcrv-ffi'
						,'azrcrv_ffi_display_options');
						
    add_menu_page(
			esc_html__("Floating Featured Image Settings", "floating-featured-image")
			,esc_html__("Floating Featured Image", "floating-featured-image")
			,0
			,"azrcrv-ffi"
			,"azrcrv_ffi_display_options"
			,plugins_url('/images/Favicon-16x16.png', __FILE__));
    
	add_submenu_page("azrcrv-ffi"
			,esc_html__("Images", "floating-featured-image")
			,esc_html__("Images", "floating-featured-image")
			,0
			,"azrcrv-ffi-list"
			,"azrcrv_ffi_list_images");
}



function azrcrv_ffi_display_options() {
	if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.', 'azc-ffi'));
    }
	
	// Retrieve plugin configuration options from database
	$options = get_option( 'azrcrv-ffi' );
	?>
	<div id="azrcrv-ffi-general" class="wrap">
		<fieldset>
		
			<h2><?php echo esc_html(get_admin_page_title()); ?></h2>
			
			<?php if( isset($_GET['settings-updated']) ) { ?>
				<div class="notice notice-success is-dismissible">
					<p><strong><?php esc_html_e('Settings have been saved.') ?></strong></p>
				</div>
			<?php } ?>
			
			<form method="post" action="admin-post.php">
				<input type="hidden" name="action" value="azrcrv_ffi_save_options" />
				<input name="page_options" type="hidden" value="default_path, default_image, default_title default_alt, default_taxonomy_is_tag, default_taxonomy" />
				
				<!-- Adding security through hidden referrer field -->
				<?php wp_nonce_field( 'azrcrv-ffi', 'azrcrv-ffi-nonce' ); ?>
				
				<table class="form-table">
				
				<tr><td colspan=2>
					<p><?php esc_html_e('Set the default path for where you will be storing the images; default is to the plugin/images folder.', 'floating-featured-image'); ?></p>
					
					<p><?php printf(esc_html__('Use the %s shortcode to place the image in a post or on a page. With the default stylesheet it will float to the right.', 'floating-featured-image'), '[featured-image]'); ?></p>
					
					<p><?php printf(esc_html__('Add image attribute to use an image other than the default; %1$s and %2$s attributes can also be set to override the defaults.', 'floating-featured-image'), 'title', 'alt'); ?></p>
					
					<p><?php printf(esc_html__('Add %s attribute to use the tag instead of the category taxonomy.', 'floating-featured-image'), 'is_tag=1'); ?></p>
					
					<p><?php printf(esc_html__('Add %s attribute to have the image hyperlinked (category will be used if both are supplied).', 'floating-featured-image'), 'taxonomy'); ?> </p>
					
					<p><?php printf(esc_html__('If the default featured image is to be displayed simply add the shortcode to a page or post.).', 'floating-featured-image'), '[featured-image]'); ?> </p>
					
					<p><?php printf(esc_html__('When overriding the default add the parameters to the shortcode; e.g. %s', 'floating-featured-image'), "[featured-image image='classicpress.png' title='ClassicPress' alt='ClassicPress' taxonomy='classicpress' is_tag=1]"); ?> </p>
				</td></tr>
				
				<tr><th scope="row"><label for="width"><?php esc_html_e('Default Path', 'floating-featured-image'); ?></label></th><td>
					<input type="text" name="default_path" value="<?php echo esc_html(stripslashes($options['default_path'])); ?>" class="regular-text" />
					<p class="description"><?php esc_html_e('Set default folder for images'); ?></p>
				</td></tr>
				
				<tr><th scope="row"><label for="width"><?php esc_html_e('Default Image', 'floating-featured-image'); ?></label></th><td>
					<input type="text" name="default_image" value="<?php echo esc_html( stripslashes($options['default_image']) ); ?>" class="regular-text" />
					<p class="description"><?php printf(esc_html__('Set default image used when no %s attribute set', 'floating-featured-image'), 'img'); ?> </p>
				</td></tr>
				
				<tr><th scope="row"><label for="width"><?php esc_html_e('Default Title', 'floating-featured-image'); ?></label></th><td>
					<input type="text" name="default_title" value="<?php echo esc_html( stripslashes($options['default_title']) ); ?>" class="regular-text" />
					<p class="description"><?php esc_html_e('Set default title for image', 'floating-featured-image'); ?></p>
				</td></tr>
				
				<tr><th scope="row"><label for="width"><?php esc_html_e('Default Alt', 'floating-featured-image'); ?></label></th><td>
					<input type="text" name="default_alt" value="<?php echo esc_html( stripslashes($options['default_alt']) ); ?>" class="regular-text" />
					<p class="description"><?php printf(esc_html__('Set default %s text for image', 'floating-featured-image'), 'alt'); ?></p>
				</td></tr>
				
				<tr><th scope="row"><?php esc_html_e('Default Taxonomy Is Tag', 'floating-featured-image'); ?></th><td>
					<fieldset><legend class="screen-reader-text"><span>Default Taxonomy Is Tag</span></legend>
					<label for="default_taxonomy_is_tag"><input name="default_taxonomy_is_tag" type="checkbox" id="default_taxonomy_is_tag" value="1" <?php checked( '1', $options['default_taxonomy_is_tag'] ); ?> /><?php esc_html_e('Default Taxonomy Is Tag?', 'floating-featured-image'); ?></label>
					</fieldset>
				</td></tr>
				
				<tr><th scope="row"><label for="width"><?php esc_html_e('Default Taxonomy', 'floating-featured-image'); ?></label></th><td>
					<input type="text" name="default_taxonomy" value="<?php echo esc_html( stripslashes($options['default_taxonomy']) ); ?>" class="regular-text" />
					<p class="description"><?php esc_html_e('Set default taxonomy to hyperlink image (default is to use category unless Is Tag is marked)', 'floating-featured-image'); ?></p>
				</td></tr>
				</table>
				<input type="submit" value="Submit" class="button-primary"/>
			</form>
		</fieldset>
	</div>
<?php }

function azrcrv_ffi_save_options() {
	// Check that user has proper security level
	if (!current_user_can('manage_options')){
		wp_die(esc_html__('You do not have permissions to perform this action', 'filtered-categories'));
	}

	if ( ! empty( $_POST ) && check_admin_referer( 'azrcrv-ffi', 'azrcrv-ffi-nonce' ) ) {	
		// Retrieve original plugin options array
		$options = get_option( 'azrcrv-ffi' );
		
		$option_name = 'default_path';
		if ( isset( $_POST[$option_name] ) ) {
			$options[$option_name] = sanitize_text_field($_POST[$option_name]);
		}
		
		$option_name = 'default_image';
		if ( isset( $_POST[$option_name] ) ) {
			$options[$option_name] = sanitize_text_field($_POST[$option_name]);
		}
		
		$option_name = 'default_title';
		if ( isset( $_POST[$option_name] ) ) {
			$options[$option_name] = sanitize_text_field($_POST[$option_name]);
		}
		
		$option_name = 'default_alt';
		if ( isset( $_POST[$option_name] ) ) {
			$options[$option_name] = sanitize_text_field($_POST[$option_name]);
		}
		
		$option_name = 'default_taxonomy_is_tag';
		if ( isset( $_POST[$option_name] ) ) {
			$options[$option_name] = 1;
		}else{
			$options[$option_name] = 0;
		}
		
		$option_name = 'default_taxonomy';
		if ( isset( $_POST[$option_name] ) ) {
			$options[$option_name] = sanitize_text_field($_POST[$option_name]);
		}
		
		// Store updated options array to database
		update_option( 'azrcrv-ffi', $options );
		
		// Redirect the page to the configuration form that was processed
		wp_redirect( add_query_arg( 'page', 'azrcrv-ffi&settings-updated', admin_url( 'admin.php' ) ) );
		exit;
	}
}


/**
 * Create table on install of plugin.
 *
 * @since 1.0.0
 *
 */
function azrcrv_ffi_install(){
	global $wpdb;
	global $azc_ffi_db_version;

	$table_name = $wpdb->prefix.'azc_ffi_images';
	
	$charset_collate = $wpdb->get_charset_collate();

	$sql = "CREATE TABLE $table_name (
		id mediumint(9) NOT NULL AUTO_INCREMENT,
		ffikey varchar(50) NOT NULL,
		image varchar(200) NOT NULL,
		title varchar(300) NOT NULL,
		alt varchar(300) NOT NULL,
		is_tag bit NOT NULL,
		taxonomy varchar(300) NOT NULL,
		PRIMARY KEY  (id)
	) $charset_collate;";

	require_once(ABSPATH.'wp-admin/includes/upgrade.php');
	dbDelta($sql);

	add_option('azc_ffi_db_version', $azc_ffi_db_version);
}

/**
 * on plugin update, update table.
 *
 * @since 1.0.0
 *
 */
function azrcrv_ffi_update_db_check(){
	global $wpdb;
    global $azc_ffi_db_version;
	$installed_ver = get_option("azc_ffi_images");
    if (get_site_option('azc_ffi_db_version') != $azc_ffi_db_version){
		$table_name = $wpdb->prefix.'azc_ffi_images';
		
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE $table_name (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			image varchar(200) NOT NULL,
			title varchar(300) NOT NULL,
			alt varchar(300) NOT NULL,
			is_tag bit NOT NULL,
			taxonomy varchar(300) NOT NULL,
			ffikey varchar(50) NOT NULL,
			PRIMARY KEY  (id)
		) $charset_collate;";

		require_once(ABSPATH.'wp-admin/includes/upgrade.php');
		dbDelta($sql);

		update_option("azc_ffi_db_version", $azc_ffi_db_version);
    }
}

/**
 * List available images in admin panel.
 *
 * @since 1.0.0
 *
 */
function azrcrv_ffi_list_images(){
	global $wpdb;
	if (!current_user_can('manage_options')){
        wp_die(esc_html__('You do not have sufficient permissions to access this page.', 'floating-featured-image'));
    }
	
	// Retrieve plugin configuration options from database
	$options = get_option('azrcrv-ffi');
	?>
	<div id="azc-ffi-general" class="wrap">
		<fieldset>
			<h2><?php echo esc_html(get_admin_page_title()); ?></h2>
			<?php if(isset($_GET['deleted'])){ ?>
				<div class="notice notice-success is-dismissible">
					<p><strong><?php esc_html_e('Image has been deleted.', 'floating-featured-image') ?></strong></p>
				</div>
			<?php }
			if(isset($_GET['image-added'])){ ?>
				<div class="notice notice-success is-dismissible">
					<p><strong><?php esc_html_e('Image has been added.', 'floating-featured-image') ?></strong></p>
				</div>
			<?php }
			if(isset($_GET['image-updated'])){ ?>
				<div class="notice notice-success is-dismissible">
					<p><strong><?php esc_html_e('Image has been updated.', 'floating-featured-image') ?></strong></p>
				</div>
			<?php } ?>
			<h3><?php esc_html_e('Available Images', 'floating-featured-image'); ?></h3>
			<table class="form-table">
			<tr>
			<th width="20%"><label for="Key"><?php esc_html_e('Key', 'floating-featured-image'); ?></label></th>
			<th width="30%"><label for="Image"><?php esc_html_e('Image', 'floating-featured-image'); ?></label></th>
			<th width="10%"><label for="Is_Tag"><?php esc_html_e('Is Tag', 'floating-featured-image'); ?></label></th>
			<th width="25%"><label for="Taxonomy"><?php esc_html_e('Taxonomy', 'floating-featured-image'); ?></label></th>
			<th width="15%"><label for="Delete button">&nbsp;</label></th>
			</tr>
			<?php $results = $wpdb->get_results("SELECT * FROM ".$wpdb->prefix."azc_ffi_images ORDER BY title");
			foreach($results as $image){
			?>
			<tr>
				<td><?php echo esc_html(stripslashes($image->ffikey)); ?></td>
				<td><?php echo esc_html(stripslashes($image->image)); ?></td>
				<td><?php echo esc_html(stripslashes($image->is_tag)); ?></td>
				<td><?php echo esc_html(stripslashes($image->taxonomy)); ?></td>
			<td>
				<div style='display:inline;'><form style='display:inline;' method="post" action="admin-post.php">
					<input type="hidden" name="action" value="azrcrv_ffi_process_image" />
					<input name="page_options" type="hidden" value="edit,delete" />
					<?php wp_nonce_field('azrcrv-ffi-nonce', 'azrcrv-ffi-nonce'); ?>
					<input type="hidden" name="id" value="<?php echo esc_html(stripslashes($image->id)); ?>" class="short-text" />
					<input type="hidden" name="whichbutton" value="edit" class="short-text" />
					<input style='display:inline;' type="image" src="<?php echo plugin_dir_url(__FILE__); ?>images/edit.png" name="edit" title="Edit" alt="Edit" value="Edit" class="azrcrv-ffi"/></div>
				</form>
				<div style='display:inline;'><form style='display:inline;' method="post" action="admin-post.php">
					<input type="hidden" name="action" value="azrcrv_ffi_process_image" />
					<input name="page_options" type="hidden" value="edit,delete" />
					<?php wp_nonce_field('azrcrv-ffi-nonce', 'azrcrv-ffi-nonce'); ?>
					<input type="hidden" name="id" value="<?php echo esc_html(stripslashes($image->id)); ?>" class="short-text" />
					<input type="hidden" name="whichbutton" value="delete" class="short-text" />
					<input style='display:inline;' type="image" src="<?php echo plugin_dir_url(__FILE__); ?>images/delete.png" name="delete" title="Delete" alt="Delete" value="Delete" class="azrcrv-ffi"/></div>
				</form>
			</td></tr>
			<?php
			}
			?>
			</table>
		
			<h3><?php esc_html_e('Add Image', 'floating-featured-image'); ?></h3>
			<form method="post" action="admin-post.php">
				<?php
				$id = '';
				$key = '';
				$alt = '';
				$image = '';
				$is_tag = '';
				$taxonomy = '';
				if(isset($_GET['edit'])){
					$id = $_GET['id'];
				}
				if (strlen($id) > 0){
					$results = $wpdb->get_results(
												  $wpdb->prepare(
																"SELECT * FROM ".$wpdb->prefix."azc_ffi_images WHERE id = %d LIMIT 0,1"
																,$id
																)
													);

					if ($results){
						foreach ($results as $result){
							$key = $result->ffikey;
							$image = $result->image;
							$alt = $result->alt;
							$is_tag = $result->is_tag;
							$taxonomy = $result->taxonomy;
						}
					}
				}
				
				?>
				<input type="hidden" name="action" value="azrcrv_ffi_add_image" />
				<input name="page_options" type="hidden" value="key,image,  alt, is_tag, taxonomy" />
				<!-- Adding security through hidden referrer field -->
				<?php wp_nonce_field('azrcrv-ffi-nonce', 'azrcrv-ffi-nonce'); ?>
					<input type="hidden" name="id" value="<?php echo $id; ?>" class="short-text" />
				<table class="form-table">
				<tr><th scope="row"><label for="key"><?php esc_html_e('Key', 'floating-featured-image'); ?></label></th><td>
					<input type="text" name="key" value="<?php echo esc_html(stripslashes($key)); ?>" class="short-text" />
					<p class="description"><?php esc_html_e('Enter key of image (i.e. an easy to remember set of characters)', 'floating-featured-image'); ?></p>
				</td></tr>
				<tr><th scope="row"><label for="alt"><?php esc_html_e('Alt Text', 'floating-featured-image'); ?></label></th><td>
					<input type="text" name="alt" value="<?php echo esc_html(stripslashes($alt)); ?>" class="regular-text" />
					<p class="description"><?php esc_html_e('Enter alt text of image', 'floating-featured-image'); ?> </p>
				</td></tr>
				<tr><th scope="row"><label for="image"><?php esc_html_e('Image', 'floating-featured-image'); ?></label></th><td>
					<input type="text" name="image" value="<?php echo esc_html(stripslashes($image)); ?>" class="short-text" />
					<p class="description"><?php esc_html_e('Enter name of image', 'floating-featured-image'); ?></p>
				</td></tr>
				<tr><th scope="row"><?php esc_html_e('Taxonomy Is Tag', 'floating-featured-image'); ?></th><td>
					<fieldset><legend class="screen-reader-text"><span>Taxonomy Is Tag</span></legend>
					<label for="is_tag"><input name="is_tag" type="checkbox" id="is_tag" value="1" <?php checked('1', $is_tag); ?> /><?php esc_html_e('Taxonomy Is Tag?', 'floating-featured-image'); ?></label>
					</fieldset>
				</td></tr>
				<tr><th scope="row"><label for="width"><?php esc_html_e('Taxonomy', 'floating-featured-image'); ?></label></th><td>
					<input type="text" name="taxonomy" value="<?php echo esc_html(stripslashes($taxonomy)); ?>" class="regular-text" />
					<p class="description"><?php esc_html_e('Set taxonomy to hyperlink image (default is to use category unless Is Tag is marked)', 'floating-featured-image'); ?></p>
				</td></tr>
				</table>
				<input type="submit" value="Submit" class="button-primary"/>
			</form>
		</fieldset>
	</div>
	<?php
}

/**
 * Add new image via admin panel.
 *
 * @since 1.0.0
 *
 */
function azrcrv_ffi_add_image(){
	
	global $wpdb;
	
	// Check that user has proper security level
	if (!current_user_can('manage_options')){
		wp_die(esc_html__('You do not have permissions to perform this action.'));
	}

	if (! empty($_POST) && check_admin_referer('azrcrv-ffi-nonce', 'azrcrv-ffi-nonce')){	
		// Retrieve original plugin options array
		$option_name = 'id';
		$id = '';
		if (isset($_POST[$option_name])){
			$id = sanitize_text_field($_POST[$option_name]);
		}
		
		$option_name = 'key';
		$key = '';
		if (isset($_POST[$option_name])){
			$key = sanitize_text_field($_POST[$option_name]);
		}
		
		$option_name = 'image';
		$image = '';
		if (isset($_POST[$option_name])){
			$image = sanitize_text_field($_POST[$option_name]);
		}
		
		$option_name = 'alt';
		$alt = '';
		if (isset($_POST[$option_name])){
			$alt = sanitize_text_field($_POST[$option_name]);
		}
		
		$option_name = 'is_tag';
		if (isset($_POST[$option_name])){
			$is_tag = 1;
		}else{
			$is_tag = 0;
		}
		
		$option_name = 'taxonomy';
		$taxonomy = '';
		if (isset($_POST[$option_name])){
			$taxonomy = sanitize_text_field($_POST[$option_name]);
		}
	
		$table_name = $wpdb->prefix.'azc_ffi_images';
		
		if (strlen($id) == 0){
			$wpdb->insert(
				$table_name
				,array(
					'ffikey' => $key,
					'image' => $image,
					'alt' => $alt,
					'is_tag' => $is_tag,
					'taxonomy' => $taxonomy,
				)
				,array(
					'%s'
					,'%s'
					,'%s'
					,'%d'
					,'%s'
				)
			);
			//echo "insert";
			//exit;
			// Redirect the page to the configuration form that was processed
			wp_redirect(add_query_arg('page', 'azrcrv-ffi-list&image-added', admin_url('admin.php')));
		}else{
			$wpdb->update(
				$table_name
				,array(
					'ffikey' => $key,
					'image' => $image,
					'alt' => $alt,
					'is_tag' => $is_tag,
					'taxonomy' => $taxonomy,
				)
				,array('id' => $id)
				,array(
					'%s'
					,'%s'
					,'%s'
					,'%d'
					,'%s'
				)
				,array(
					'%d'
				)
			);
			//exit(var_dump($wpdb->last_query));
			// Redirect the page to the configuration form that was processed
			wp_redirect(add_query_arg('page', 'azrcrv-ffi-list&image-updated', admin_url('admin.php')));
		}
		exit;
	}
}

/**
 * Save edit or delete image via admin panel.
 *
 * @since 1.0.0
 *
 */
function azrcrv_ffi_process_image(){
	
	global $wpdb;
	
	// Check that user has proper security level
	if (!current_user_can('manage_options')){
		wp_die(esc_html__('You do not have permissions to perform this action.', 'floating-featured-image'));
	}

	if (! empty($_POST) && check_admin_referer('azrcrv-ffi-nonce', 'azrcrv-ffi-nonce')){
		if ($_POST['whichbutton'] == 'delete'){
			// Retrieve original plugin options array
			$option_name = 'id';
			$id = '';
			if (isset($_POST[$option_name])){
				$id = ($_POST[$option_name]);
			}
				
			$table_name = $wpdb->prefix.'azc_ffi_images';
			
			$wpdb->delete(
				$table_name, 
				array(
					'id' => $id,
				) 
			);
			
			// Redirect the page to the configuration form that was processed
			wp_redirect(add_query_arg('page', 'azrcrv-ffi-list&deleted', admin_url('admin.php')));
			exit;
		}else{
			// edit
			// Redirect the page to the configuration form that was processed
			wp_redirect(add_query_arg('page', 'azrcrv-ffi-list&edit&id='.$_POST['id'], admin_url('admin.php')));
			exit;
		}
	}
}

/**
 * Display Floating Featured Image via shortcode.
 *
 * @since 1.0.0
 *
 */
function azrcrv_ffi_display_image($atts, $content = null){
	global $wpdb;
	// Retrieve plugin configuration options from database
	$options = get_option('azrcrv-ffi');
	
	$args = shortcode_atts(array(
		'key' => '',
		'path' => stripslashes($options['default_path']),
		'image' => stripslashes($options['default_image']),
		'alt' => stripslashes($options['default_alt']),
		'taxonomy' => stripslashes($options['default_taxonomy']),
		'is_tag' => 0
	), $atts);
	$key = $args['key'];
	$path = $args['path'];
	$image = $args['image'];
	$alt = $args['alt'];
	$taxonomy = $args['taxonomy'];
	$is_tag = $args['is_tag'];
	
	$key = sanitize_text_field($key);
	
	$sql = "SELECT * FROM ".$wpdb->prefix."azc_ffi_images WHERE id = %d or title = %s";
//echo $sql;
	$results = $wpdb->get_results(
								  $wpdb->prepare(
												"SELECT * FROM ".$wpdb->prefix."azc_ffi_images WHERE ffikey = %s LIMIT 0,1"
												,$key
												)
									);

	if ($results){
		foreach ($results as $result){
			$image = $result->image;
			$alt = $result->alt;
			$is_tag = $result->is_tag;
			$taxonomy = $result->taxonomy;
		}
	}
	
	$output = "<span class='azrcrv-ffi'>";
	if (strlen($taxonomy) > 0 and $is_tag == 0){
		$category_url = get_category_link(get_cat_ID($taxonomy));
		if (strlen($category_url) == 0){ // if taxonomy not name then check if slug
			$category = get_term_by('slug', $taxonomy, 'category');
			$category_url = get_category_link( $category->term_id);
		}
		$output .= "<a href='$category_url'>";
	}elseif (strlen($taxonomy) > 0){
		$tag = get_term_by('name', $taxonomy, 'post_tag');
		$tag_url = get_tag_link($tag->term_id);
		if (strlen($tag_url) == 0){ // if taxonomy not name then check if slug
			$tag = get_term_by('slug', $taxonomy, 'post_tag');
			$tag_url = get_tag_link($tag->term_id);
		}
		$output .= "<a href='$tag_url'>";
	}
	
	$output .= "<img src='".esc_html(stripslashes($path))."".esc_html(stripslashes($image))."' class='azrcrv-ffi' alt='".esc_html(stripslashes($alt))."' />";
	if (strlen($taxonomy) > 0){
		$output .= "</a>";
	}
	$output .= "</span>";
	
	if (strlen($image) == 0){
		$output = '';
	}
	
	return $output;
}

?>