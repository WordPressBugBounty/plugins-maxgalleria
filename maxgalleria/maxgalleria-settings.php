<?php
class MaxGalleriaSettings {
  
	public $nonce_save_general_settings = array(
		'action' => 'save_general_settings',
		'name' => 'maxgalleria_save_general_settings'
	);
	
	public function __construct() {
		// Ajax call for saving general settings
		add_action('wp_ajax_save_general_settings', array($this, 'save_general_settings'));
	}

	public function get_rewrite_slug() {
		// Get the rewrite slug, with 'gallery' as the default
		return get_option(MAXGALLERIA_SETTING_REWRITE_SLUG, 'gallery');
	}
	
	public function get_exclude_galleries_from_search() {
		return get_option(MAXGALLERIA_SETTING_EXCLUDE_GALLERIES_FROM_SEARCH, '');
	}
	
	public function get_show_addon_page() {
		return get_option(MAXGALLERIA_SETTING_SHOW_ADDON_PAGE, '');
	}
		
	public function get_default_image_gallery_template() {
		return get_option(MAXGALLERIA_SETTING_DEFAULT_IMAGE_GALLERY_TEMPLATE, 'image-tiles');
	}
	
	public function get_default_video_gallery_template() {
		return get_option(MAXGALLERIA_SETTING_DEFAULT_VIDEO_GALLERY_TEMPLATE, 'video-tiles');
	}
	
	public static function authorize_settings_request($nonce) {
		if (!current_user_can('manage_options')) {
			wp_die(esc_html__('You do not have permission to change these settings.', 'maxgalleria'), '', array('response' => 403));
		}
		check_ajax_referer($nonce['action'], $nonce['name']);
	}

	public static function save_allowed_settings($schema, $values) {
		$validated = array();
		foreach ($schema as $key => $rule) {
			if (!array_key_exists($key, $values)) {
				continue;
			}
			if (!is_string($values[$key])) {
				self::invalid_setting();
			}
			$value = wp_unslash($values[$key]);
			if (is_array($rule)) {
				if (!in_array($value, array_map('strval', $rule), true)) {
					self::invalid_setting();
				}
			} else {
				switch ($rule) {
					case 'integer':
						// Empty retains the existing default/unlimited setting.
						if ($value !== '' && filter_var($value, FILTER_VALIDATE_INT, array('options' => array('min_range' => 0))) === false) {
							self::invalid_setting();
						}
						break;
					case 'color':
						$color = sanitize_hex_color($value);
						if ($value !== '' && !$color) {
							self::invalid_setting();
						}
						$value = $color;
						break;
					case 'html':
						$value = wp_kses_post($value);
						break;
					case 'class':
						$value = sanitize_html_class($value);
						break;
					case 'slug':
						$value = sanitize_title($value);
						$value = $value === '' ? 'gallery' : $value;
						break;
					default:
						$value = sanitize_text_field($value);
				}
			}
			$validated[$key] = $value;
		}
		foreach ($validated as $key => $value) {
			update_option($key, $value);
		}
	}

	private static function invalid_setting() {
		wp_die(esc_html__('Invalid settings value. No settings were saved.', 'maxgalleria'), '', array('response' => 400));
	}

	public function save_general_settings() {
		self::authorize_settings_request($this->nonce_save_general_settings);
		global $maxgalleria;
		$image_templates = array('image-tiles');
		$video_templates = array('video-tiles');
		foreach ($maxgalleria->get_template_addons() as $template) {
			if (in_array($template['subtype'], array('image', 'both'), true)) {
				$image_templates[] = $template['key'];
			}
			if (in_array($template['subtype'], array('video', 'both'), true)) {
				$video_templates[] = $template['key'];
			}
		}
		$schema = array(
			MAXGALLERIA_SETTING_REWRITE_SLUG => 'slug',
			MAXGALLERIA_SETTING_EXCLUDE_GALLERIES_FROM_SEARCH => array('', 'on', 'off'),
			MAXGALLERIA_SETTING_DEFAULT_IMAGE_GALLERY_TEMPLATE => $image_templates,
			MAXGALLERIA_SETTING_DEFAULT_VIDEO_GALLERY_TEMPLATE => $video_templates,
			MAXGALLERIA_SETTING_SHOW_ADDON_PAGE => array('', 'on', 'off'),
		);
		$values = $_POST + array(
			MAXGALLERIA_SETTING_REWRITE_SLUG => 'gallery',
			MAXGALLERIA_SETTING_EXCLUDE_GALLERIES_FROM_SEARCH => '',
			MAXGALLERIA_SETTING_DEFAULT_IMAGE_GALLERY_TEMPLATE => 'image-tiles',
			MAXGALLERIA_SETTING_DEFAULT_VIDEO_GALLERY_TEMPLATE => 'video-tiles',
			MAXGALLERIA_SETTING_SHOW_ADDON_PAGE => 'off',
		);
		self::save_allowed_settings($schema, $values);
		wp_die('success');
	}
}
?>