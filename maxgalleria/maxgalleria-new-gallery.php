<?php
class MaxGalleriaNewGallery {
	public function __construct() {
		add_action('wp_ajax_save_new_gallery_type', array($this, 'save_new_gallery_type'));
	}

	public function save_new_gallery_type() {	
		check_ajax_referer('save_new_gallery_type', 'maxgalleria_save_new_gallery_type');

		$post_id = isset($_POST['post_ID']) ? absint($_POST['post_ID']) : 0;
		if (!$post_id || get_post_type($post_id) !== MAXGALLERIA_POST_TYPE || !current_user_can('edit_post', $post_id)) {
			wp_die(esc_html__('You do not have permission to edit this gallery.', 'maxgalleria'), '', array('response' => 403));
		}

		$gallery_type = isset($_POST['maxgallery_type']) && !is_array($_POST['maxgallery_type']) ? sanitize_key($_POST['maxgallery_type']) : '';
		if ($gallery_type && !in_array($gallery_type, array('image', 'video'), true)) {
			wp_die(esc_html__('Invalid gallery type.', 'maxgalleria'), '', array('response' => 400));
		}

		$options = new MaxGalleryOptions($post_id);
		$options->save_post_meta($options->type_key);
		$options->save_post_meta($options->template_key);

    echo esc_url_raw(admin_url() . "post.php?post=" . $post_id . "&action=edit");
		die();
	}
	
	public function show_meta_box_new($post) {
		require_once 'meta/meta-new.php';
	}
}
?>
