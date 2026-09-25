<?php
class MaxGalleriaNextGen {
	public $nonce_nextgen_importer = array(
		'action' => 'nextgen_importer',
		'name' => 'maxgalleria_nextgen_importer'
	);
	
	public function __construct() {
		// Ajax call for importing a NextGEN gallery
		add_action('wp_ajax_import_nextgen_gallery', array($this, 'import_nextgen_gallery'));
		
		// Ajax call for getting the import percent of a NextGEN gallery
		add_action('wp_ajax_get_nextgen_import_percent', array($this, 'get_nextgen_import_percent'));
		add_action('wp_ajax_nopriv_get_nextgen_import_percent', array($this, 'get_nextgen_import_percent'));
		
		// Ajax call for resetting the import percent and count of a NextGEN gallery
		add_action('wp_ajax_reset_nextgen_import', array($this, 'reset_nextgen_import'));
		add_action('wp_ajax_nopriv_reset_nextgen_import', array($this, 'reset_nextgen_import'));
	}

	public function import_nextgen_gallery() {
		if (!current_user_can('upload_files')) {
			wp_die(esc_html__('You do not have permission to import galleries.', 'maxgalleria'), '', array('response' => 403));
		}

		if (isset($_POST) && check_admin_referer($this->nonce_nextgen_importer['action'], $this->nonce_nextgen_importer['name'])) {
			$nextgen_import_count = 0;
			$maxgalleria_gallery = null;

			switch (isset($_POST['maxgalleria_gallery_where']) && is_string($_POST['maxgalleria_gallery_where']) ? sanitize_key($_POST['maxgalleria_gallery_where']) : '') {
				case 'existing':
					$gallery_id = isset($_POST['maxgalleria_gallery_id']) && is_scalar($_POST['maxgalleria_gallery_id']) ? absint($_POST['maxgalleria_gallery_id']) : 0;
					$maxgalleria_gallery = $gallery_id ? get_post($gallery_id) : null;
					break;
				case 'new':
					$post_type = get_post_type_object(MAXGALLERIA_POST_TYPE);
					if (!$post_type || !current_user_can($post_type->cap->create_posts)) {
						wp_die(esc_html__('You do not have permission to create galleries.', 'maxgalleria'), '', array('response' => 403));
					}
					// First create the gallery post itself
					$new_id = wp_insert_post(array('post_title' => sanitize_title($_POST['maxgalleria_gallery_title']), 'post_type' => MAXGALLERIA_POST_TYPE), true);
					if (is_wp_error($new_id) || !$new_id) {
						wp_die(esc_html__('Unable to create the destination gallery.', 'maxgalleria'), '', array('response' => 500));
					}

					// Get the new gallery for the same destination checks.
					$maxgalleria_gallery = get_post($new_id);
					break;
			}

			// A valid nonce does not authorize access to the requested destination.
			if (!$maxgalleria_gallery || $maxgalleria_gallery->post_type !== MAXGALLERIA_POST_TYPE || !current_user_can('edit_post', $maxgalleria_gallery->ID)) {
				wp_die(esc_html__('You do not have permission to import into this gallery.', 'maxgalleria'), '', array('response' => 403));
			}

			if (isset($new_id)) {
				// Save defaults only after authorizing the new gallery.
				$options = new MaxGalleryOptions($new_id);
				add_post_meta($new_id, $options->type_key, 'image', true);
				add_post_meta($new_id, $options->template_key, 'image-tiles', true);

			}

			$nextgen_gallery_id = sanitize_text_field($_POST['nextgen_gallery_id']);
			$nextgen_gallery = $this->get_nextgen_gallery($nextgen_gallery_id);
			$nextgen_gallery_pics = $this->get_nextgen_gallery_pictures($nextgen_gallery_id);
			$nextgen_gallery_pics_count = $this->get_nextgen_gallery_picture_count($nextgen_gallery_id);

			do_action(MAXGALLERIA_ACTION_BEFORE_NEXTGEN_IMPORT, $nextgen_gallery, $nextgen_gallery_pics, $nextgen_gallery_pics_count);

			if (isset($maxgalleria_gallery)) {
				// Turn number of galleries to process into chunks for progress bar
				$chunks = ceil(100 / $nextgen_gallery_pics_count) + 1;
				$chunk = 0;
				
				$count = 1;
								
				foreach ($nextgen_gallery_pics as $picture) {
					do_action(MAXGALLERIA_ACTION_BEFORE_NEXTGEN_IMPORT_PICTURE, $picture);
					
					$url = site_url() . trailingslashit($nextgen_gallery->path) . $picture->filename;
          
          // ensure that leading slashes are removed fomr the $nextgen_gallery->path
          $temp_file = get_home_path() . ltrim(trailingslashit($nextgen_gallery->path), '/') . $picture->filename;
                    
          $download_success = true;
          
          $file_array = array();
          
          preg_match('/[^\?]+\.(jpg|JPG|jpe|JPE|jpeg|JPEG|gif|GIF|png|PNG|webp|WEBP|avif|AVIF)/', $url, $matches);
          $file_array['name'] = basename($matches[0]);
          $file_array['tmp_name'] = $temp_file;

          // If we got an error or the file is not a valid image, delete the temp file
          if (is_wp_error($temp_file) || !file_is_valid_image($temp_file)) {
            //@unlink($temp_file);
            $download_success = false;
          }

          if ($download_success) {

            // Set post data; the empty post_date ensures it gets today's date
            $post_data = array(
              'post_date' => '',
              'post_parent' => $maxgalleria_gallery->ID,
              'post_title' => $picture->alttext != '' ? $picture->alttext : $picture->image_slug,
              'post_excerpt' => $picture->description,
              'post_content' => $picture->description,
              'menu_order' => $count,
              'ancestors' => array()
            );

            // Sideload the image to create its attachment to the gallery
            $attachment_id = media_handle_sideload($file_array, $maxgalleria_gallery->ID, $picture->description, $post_data);

            if (!is_wp_error($attachment_id)) {
              $result = $attachment_id;

              // the orginal file is deleted from the NG gallery during upload; this code will replace the deleted file
              $backup_file = $temp_file . '_backup';
              copy($backup_file, $temp_file);

              // no alt_text available
              //update_post_meta($attachment_id, '_wp_attachment_image_alt', $alt_text);
            }
                        
          }

					$nextgen_import_count++;
					
					// Increment chunks for progress bar
					$chunk++;
          $total_chunks = $chunk * $chunks;
          if($total_chunks > 100 && $count == 0)
            $total_chunks = 0;
					update_option('maxgalleria_nextgen_import_percent', $total_chunks);
					
					do_action(MAXGALLERIA_ACTION_AFTER_NEXTGEN_IMPORT_PICTURE, $picture);
					$count++;
				}
			}
			
			do_action(MAXGALLERIA_ACTION_AFTER_NEXTGEN_IMPORT, $nextgen_gallery, $nextgen_gallery_pics, $nextgen_gallery_pics_count);
			
			$gallery_edit_url = admin_url('post.php?post=' . $maxgalleria_gallery->ID . '&action=edit');
			$message = sprintf(esc_html__('Successfully imported %d of %d images from NextGEN into the "%s%s%s" gallery.', 'maxgalleria'), $nextgen_import_count, $nextgen_gallery_pics_count, '<a href="' . esc_url($gallery_edit_url) . '">', esc_html($maxgalleria_gallery->post_title), '</a>');
			echo wp_kses($message, array('a' => array('href' => array())));
			die();
		}
	}
  
	public function get_nextgen_import_percent() {
		$import_percent = get_option('maxgalleria_nextgen_import_percent');
		$percentage = ($import_percent > 100) ? 100 : $import_percent;	
		
		echo (int) $percentage;
		die();
	}

	public function reset_nextgen_import() {
		update_option('maxgalleria_nextgen_import_count', 0);
		update_option('maxgalleria_nextgen_import_percent', 0);
		
		// No need to echo anything for a return
		die();
	}

	public function get_nextgen_gallery($id) {
		global $wpdb;
		return $wpdb->get_row($wpdb->prepare("SELECT * FROM " . $this->get_nextgen_gallery_table() . " WHERE gid = %d", $id));
	}

	public function get_nextgen_galleries() {
		global $wpdb;
		return $wpdb->get_results("SELECT * FROM " . $this->get_nextgen_gallery_table());
	}

	public function get_nextgen_gallery_picture_count($id) {
		global $wpdb;
		return $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM " . $this->get_nextgen_pictures_table() . " WHERE galleryid = %d", $id));
	}

	public function get_nextgen_gallery_pictures($id) {
		global $wpdb;
		return $wpdb->get_results($wpdb->prepare("SELECT * FROM " . $this->get_nextgen_pictures_table() . " WHERE galleryid = %d", $id));
	}

	public function get_nextgen_gallery_table() {
		global $wpdb;
		return $wpdb->prefix . 'ngg_gallery';
	}

	public function get_nextgen_pictures_table() {
		global $wpdb;
		return $wpdb->prefix . 'ngg_pictures';
	}

	public function is_nextgen_installed() {
		$plugins = get_plugins();

		foreach ($plugins as $plugin_path => $plugin) {
			if ($plugin['Name'] == 'NextGEN Gallery' || $plugin['Name'] == 'NextGEN Gallery by Photocrati') {
				return true;
			}
		}
		
		return false;
	}
}
?>