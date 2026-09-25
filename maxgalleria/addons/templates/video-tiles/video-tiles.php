<?php
class MaxGalleriaVideoTiles {
	public $addon_key;
	public $addon_name;
	public $addon_type;
	public $addon_subtype;
	public $addon_settings;
	public $addon_image;
	public $addon_output;

	public function __construct() {
		$this->addon_key = 'video-tiles';
		$this->addon_name = esc_html__('Video Tiles', 'maxgalleria');
		$this->addon_type = 'template';
		$this->addon_subtype = 'video';
		$this->addon_settings = MAXGALLERIA_PLUGIN_DIR . '/addons/templates/video-tiles/video-tiles-settings.php';
		$this->addon_image = MAXGALLERIA_PLUGIN_URL . '/addons/templates/video-tiles/images/video-tiles.png';
		$this->addon_output = array($this, 'get_output');
		
		require_once 'video-tiles-options.php';
		
		add_action('save_post', array($this, 'save_gallery_options'), 10, 2);
		add_action('maxgalleria_template_options', array($this, 'show_template_options'));
		add_action('wp_ajax_save_video_tiles_defaults', array($this, 'save_video_tiles_defaults'));
	}
	
	public function save_gallery_options($post_id, $post = null) {
		if (!$this->can_save_gallery_options($post_id, $post)) {
			return $post_id;
		}

		$options = new MaxGalleriaVideoTilesOptions($post_id);
		$options->save_options();

		return $post_id;
	}
			
	private function can_save_gallery_options($post_id, $post = null) {
		if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
			return false;
		}

		if (wp_is_post_autosave($post_id) || wp_is_post_revision($post_id)) {
			return false;
		}

		if ($post instanceof WP_Post && $post->post_type != MAXGALLERIA_POST_TYPE) {
			return false;
		}

		if (!$post instanceof WP_Post && get_post_type($post_id) != MAXGALLERIA_POST_TYPE) {
			return false;
		}

		return current_user_can('edit_post', $post_id);
	}
	
	public function show_template_options() {
		global $post;
		$options = new MaxGalleriaVideoTilesOptions($post->ID);
		
		if ($options->get_template() == 'video-tiles') {
			require_once 'video-tiles-meta.php';
		}
	}
	
	public function save_video_tiles_defaults() {
		MaxGalleriaSettings::authorize_settings_request(array(
			'action' => 'save_video_tiles_defaults',
			'name' => 'maxgalleria_save_video_tiles_defaults',
		));
		$options = new MaxGalleriaVideoTilesOptions();
		// Only this template's defaults, including supported legacy fields, may be saved.
		$schema = array(
			$options->videos_per_page_default_key => 'integer',
			$options->skin_default_key => array_keys(array_merge($options->new_skins, $options->skins)),
			$options->thumb_caption_enabled_default_key => array('', 'on', 'off'),
			$options->thumb_caption_position_default_key => array_keys($options->caption_positions),
			$options->thumb_click_default_key => array_keys($options->thumb_clicks),
			$options->thumb_click_new_window_default_key => array('', 'on', 'off'),
			$options->thumb_columns_default_key => array_keys($options->thumb_columns),
			$options->thumb_image_class_default_key => 'class',
			$options->thumb_image_container_class_default_key => 'class',
			$options->thumb_image_rel_attribute_default_key => 'text',
			$options->thumb_shape_default_key => array_keys($options->thumb_shapes),
			$options->vertical_fit_enabled_default_key => array('', 'on', 'off'),
			$options->escape_key_enabled_default_key => array('', 'on', 'off'),
			$options->align_top_enabled_default_key => array('', 'on', 'off'),
			$options->hide_close_btn_enabled_default_key => array('', 'on', 'off'),
			$options->bg_click_close_enabled_default_key => array('', 'on', 'off'),
			$options->fixed_content_position_default_key => array_keys($options->content_positions),
			$options->overflow_y_default_key => array_keys($options->overflow_y_settings),
			$options->removal_delay_default_key => 'integer',
			$options->main_class_default_key => 'class',
			$options->gallery_enabled_default_key => array('', 'on', 'off'),
			$options->arrow_markup_default_key => 'html',
			$options->prev_button_title_default_key => 'html',
			$options->next_button_title_default_key => 'html',
			$options->counter_markup_default_key => 'html',
			$options->sort_order_default_key => array_keys($options->sort_orders),
			$options->dfactory_lightbox_default_key => array('', 'on', 'off'),
			$options->ns_show_border_default_key => array('', 'on', 'off'),
			$options->ns_border_thickness_default_key => array('', '1', '3', '5', '7', '9', '10', '15'),
			$options->ns_border_color_default_key => 'color',
			$options->ns_border_radius_default_key => array('0', '10', '20', '30', '40', '50', '60', '70', '80', '90'),
			$options->ns_shadow_default_key => array('none', 'inside', 'behind', 'color'),
			$options->ns_shadow_blur_default_key => array('5', '10', '15', '20'),
			$options->ns_shadow_color_default_key => 'color',
			$options->ns_shadow_spread_default_key => array('0', '1', '2', '3'),
			$options->ns_lightbox_close_default_key => array('', '0', '1', '2', '3', '4'),
			$options->ns_lightbox_arrow_default_key => array('0', '1', '2', '3', '4', '5', '6', '7'),
			$options->sort_type_default_key => array_keys($options->sort_by),
			$options->lightbox_skin_default_key => array_keys($options->lightbox_skins),
			$options->lightbox_effect_default_key => array_keys($options->lightbox_effects),
			$options->lightbox_kb_nav_default_key => array('', 'on', 'off'),
			$options->lightbox_img_click_close_default_key => array('', 'on', 'off'),
			$options->lightbox_overlay_click_close_default_key => array('', 'on', 'off'),
			$options->lightbox_close_text_default_key => 'text',
			$options->lightbox_next_text_default_key => 'text',
			$options->lightbox_prev_text_default_key => 'text',
			$options->lightbox_caption_enabled_default_key => array('', 'on', 'off'),
			$options->lightbox_error_text_default_key => 'text',
		);
		MaxGalleriaSettings::save_allowed_settings($schema, $_POST);
		wp_die('success');
	}

	public function enqueue_styles($options) {
		// Check to add lightbox styles
		if ($options->get_thumb_click() == 'lightbox') {
      
			$lightbox_stylesheet = apply_filters(MAXGALLERIA_FILTER_IMAGE_TILES_LIGHTBOX_STYLESHEET, MAXGALLERIA_PLUGIN_URL . '/libs/topbox/css/topbox.css');
			wp_enqueue_style('maxgalleria-topbox', $lightbox_stylesheet);      
          
		}
		
		// The main styles for this template
		$main_stylesheet = apply_filters(MAXGALLERIA_FILTER_VIDEO_TILES_MAIN_STYLESHEET, MAXGALLERIA_PLUGIN_URL . '/addons/templates/video-tiles/video-tiles.css');
		wp_enqueue_style('maxgalleria-video-tiles', $main_stylesheet);
		
		// Load skin style
		$skin = $options->get_skin();
		$skin_stylesheet = apply_filters(MAXGALLERIA_FILTER_VIDEO_TILES_SKIN_STYLESHEET, MAXGALLERIA_PLUGIN_URL . '/addons/templates/video-tiles/skins/' . $skin . '.css', $skin);
		wp_enqueue_style('maxgalleria-video-tiles-skin-' . $skin, $skin_stylesheet);
		
		$mg_style_file = get_post_meta($options->gallery_id, "mg-css-file", true );
		if($mg_style_file !== '') {
		  wp_enqueue_style('maxgalleria-video-tiles-style-' . $options->gallery_id, $mg_style_file);			
		}
    
		$lightbox_skin = get_post_meta($options->gallery_id, $options->lightbox_skin_key, true );
    if($lightbox_skin != '' && $lightbox_skin != 'none') {
			wp_enqueue_style('maxgalleria-topbox-style-' . $options->gallery_id, esc_url(MAXGALLERIA_PLUGIN_URL . '/libs/topbox/skins/' . $lightbox_skin  . '/styles.css'));      
    }
    		
		// Check to load custom styles
		if ($options->get_custom_styles_enabled() == 'on' && $options->get_custom_styles_url() != '') {
			wp_enqueue_style('maxgalleria-video-tiles-custom', $options->get_custom_styles_url());
		}
	}

	public function enqueue_scripts($options) {
		wp_enqueue_script('jquery');
    		
		if ($options->get_thumb_click() == 'lightbox') {
      
      $lightbox_script = apply_filters(MAXGALLERIA_FILTER_IMAGE_TILES_LIGHTBOX_SCRIPT, MAXGALLERIA_PLUGIN_URL . '/libs/topbox/js/topbox.js');
      wp_enqueue_script('maxgalleria-topbox', $lightbox_script, array('jquery'));
      			
			$main_script = apply_filters(MAXGALLERIA_FILTER_VIDEO_TILES_MAIN_SCRIPT, MAXGALLERIA_PLUGIN_URL . '/addons/templates/video-tiles/video-tiles.js');
			wp_enqueue_script('maxgalleria-video-tiles', $main_script, array('jquery'));
		}
		
		// Check to load custom scripts
		if ($options->get_custom_scripts_enabled() == 'on' && $options->get_custom_scripts_url() != '') {
			wp_enqueue_script('maxgalleria-video-tiles-custom', $options->get_custom_scripts_url(), array('jquery'));
		}
	}
	
	public function get_thumb_image($options, $attachment) {
		global $maxgalleria;

		$thumb_shape = $options->get_thumb_shape();
		$thumb_columns = $options->get_thumb_columns();
		
		return $maxgalleria->image_gallery->get_thumb_image($attachment, $thumb_shape, $thumb_columns);
	}
	
	public function get_output($gallery, $attachments) {
    
		$options = new MaxGalleriaVideoTilesOptions($gallery->ID);
    
    $gallery_attribute = '';    
    
		do_action(MAXGALLERIA_ACTION_VIDEO_TILES_BEFORE_ENQUEUE_STYLES, $options);
		$this->enqueue_styles($options);
		do_action(MAXGALLERIA_ACTION_VIDEO_TILES_AFTER_ENQUEUE_STYLES, $options);
		
		do_action(MAXGALLERIA_ACTION_VIDEO_TILES_BEFORE_ENQUEUE_SCRIPTS, $options);
		$this->enqueue_scripts($options);
		do_action(MAXGALLERIA_ACTION_VIDEO_TILES_AFTER_ENQUEUE_SCRIPTS, $options);
		
		$output = wp_kses_post(apply_filters(MAXGALLERIA_FILTER_VIDEO_TILES_BEFORE_GALLERY_OUTPUT, '', $gallery, $attachments, $options));
		$output .= '<div id="maxgallery-' . esc_attr($gallery->ID) . '" class="mg-video-tiles ' . esc_attr($options->get_skin()) . '">';
		
		if ($options->get_description_enabled() == 'on' && $options->get_description_position() == 'above') {
			if ($options->get_description_text() != '') {
				$output .= '<p class="mg-description">' . wp_kses_post($options->get_description_text()) . '</p>';
			}
		}
    
    // check for dfactory selector
    if($options->get_dfactory_lightbox() == 'on') {
      $dfactory_settings = get_option('responsive_lightbox_settings');
      if(!empty($dfactory_settings)) 
        $reponsive_lb_settings = $dfactory_settings['selector'];        
      else
        $reponsive_lb_settings = "";
    }
    		
		$output .= '	<div class="mg-thumbs ' . esc_attr($options->get_thumb_columns_class()) . '">';
		$output .= '		<ul>';
    
    if($options->get_gallery_enabled() == 'on') {
      // either gallery1 or don't include the attribute
      $gallery_attribute = "data-lightbox-gallery='gallery-" . esc_attr($gallery->ID) . "'";
    }
    
		foreach ($attachments as $attachment) {
			$excluded = get_post_meta($attachment->ID, 'maxgallery_attachment_video_exclude', true);
			if (!$excluded) {
				$title = $attachment->post_title;
				$caption = $attachment->post_excerpt; // Used for the thumb caption, if enabled
				$alt = get_post_meta($attachment->ID, '_wp_attachment_image_alt', true);
				$href = get_post_meta($attachment->ID, 'maxgallery_attachment_video_url', true);
				$image_class = $options->get_thumb_image_class();
				$image_container_class = $options->get_thumb_image_container_class();
				$image_rel = $options->get_thumb_image_rel_attribute();
				$target = '';
								
				if ($options->get_thumb_click_new_window() == 'on') {
          // either _blank or empty
					$target = '_blank';
				}
        
        if($options->get_lightbox_caption_enabled() == 'on')
          $image_caption = $caption;
        else
          $image_caption = "";        
				
				$thumb_image = $this->get_thumb_image($options, $attachment);
				$thumb_image_element = '<img class="' . esc_attr($image_class) . '" src="' . esc_url($thumb_image['url']) . '" width="' . esc_attr($thumb_image['width']) . '" height="' . esc_attr($thumb_image['height']) . '" alt="' . esc_attr($alt) . '" title="' . esc_attr($title) . '" />';
				        
				$output .= '<li>';
				
				if ($options->get_thumb_caption_enabled() == 'on' && $options->get_thumb_caption_position() == 'above') {
					$output .= '	<p class="caption above">' . wp_kses_post($caption) . '</p>';
				}
        				        
        if($options->get_dfactory_lightbox() == 'on') 
				  $output .= '	<a data-rel="' . esc_attr($reponsive_lb_settings) . '-0" class="video" data-video-thumb-id="' . esc_attr($attachment->ID) . '" href="' . esc_url($href) . '" target="' . $target . '" rel="' . esc_attr($image_rel) . '">';
        else {
          // reformat youtube urls
          $id_pos = strpos($href, '?v=');
          if($id_pos !== false) {
            $href = "https://youtu.be/" . substr($href, $id_pos + 3);
          }
          // the lightbox does not support HTML taga
          $output .= '	<a class="video-lightbox-'. esc_attr($gallery->ID)  .'" href="' . esc_url($href) . '" title="'. esc_attr(strip_tags($image_caption)) .'" aria-haspopup="dialog" ' . $gallery_attribute . ' >';
        }  
          
				$output .= '		<div class="' . esc_attr($image_container_class) . '">';
				$output .= 				wp_kses_post(apply_filters(MAXGALLERIA_FILTER_VIDEO_TILES_BEFORE_THUMB, '', $options));
				$output .=				wp_kses_post(apply_filters(MAXGALLERIA_FILTER_VIDEO_TILES_THUMB, $thumb_image_element, $thumb_image, $image_class, $alt, $title));
				$output .= 				wp_kses_post(apply_filters(MAXGALLERIA_FILTER_VIDEO_TILES_AFTER_THUMB, '', $options));
				
				if ($options->get_thumb_caption_enabled() == 'on' && $options->get_thumb_caption_position() == 'bottom') {
					$output .= '		<div class="caption-bottom-container">';
					$output .= '			<p class="caption bottom">' . wp_kses_post($caption) . '</p>';
					$output .= '		</div>';
				}
				
				$output .= '		</div>';
				
				if ($options->get_thumb_caption_enabled() == 'on' && $options->get_thumb_caption_position() == 'below') {
					$output .= '<p class="caption below">' . wp_kses_post($caption) . '</p>';
				}
				
				$output .= '	</a>';
				$output .= '</li>';
			}
		}

		$output .= '		</ul>';
		$output .= '		<div class="clear"></div>';
		$output .= '	</div>';
		
		if ($options->get_description_enabled() == 'on' && $options->get_description_position() == 'below') {
			if ($options->get_description_text() != '') {
				$output .= '<p class="mg-description">' . wp_kses_post($options->get_description_text()) . '</p>';
			}
		}
        
		// Hidden elements used by video-tiles.js
		$output .= '	<span style="display: none;" class="hidden-video-tiles-gallery-id">' . esc_attr($gallery->ID) . '</span>';
		$output .= '	<span style="display: none;" class="hidden-video-tiles-thumb-click">' . esc_attr($options->get_thumb_click()) . '</span>';
		$output .= '	<span style="display: none;" class="hidden-video-tiles-lightbox-skin">' . esc_html($options->get_lightbox_skin()) . '</span>';
		$output .= '	<span style="display: none;" class="hidden-video-tiles-lightbox-effect">' . esc_html($options->get_lightbox_effect()) . '</span>';    
		$output .= '	<span style="display: none;" class="hidden-video-tiles-lightbox-kb-nav">' . esc_html(($options->get_lightbox_kb_nav() == 'on') ? 'true' : 'false' ) . '</span>';    
		$output .= '	<span style="display: none;" class="hidden-video-tiles-lightbox-img-click-close">' . esc_html(($options->get_lightbox_img_click_close() == 'on') ? 'true' : 'false' ) . '</span>';    
		$output .= '	<span style="display: none;" class="hidden-video-tiles-lightbox-ol-click-close">' . esc_html(($options->get_lightbox_overlay_click_close() == 'on') ? 'true' : 'false' ) . '</span>';    
		$output .= '	<span style="display: none;" class="hidden-video-tiles-lightbox-close-tip-text">' . esc_html($options->get_lightbox_close_text()) . '</span>';    
		$output .= '	<span style="display: none;" class="hidden-video-tiles-lightbox-next-tip-text">' . esc_html($options->get_lightbox_next_text()) . '</span>';    
		$output .= '	<span style="display: none;" class="hidden-video-tiles-lightbox-prev-tip-text">' . esc_html($options->get_lightbox_prev_text()) . '</span>';    
		$output .= '	<span style="display: none;" class="hidden-video-tiles-lightbox-error-tip-text">' . esc_html($options->get_lightbox_error_text()) . '</span>';    

		$output .= '</div>';
		$output .= wp_kses_post(apply_filters(MAXGALLERIA_FILTER_VIDEO_TILES_AFTER_GALLERY_OUTPUT, '', $gallery, $attachments, $options));
		
		return wp_kses_post(apply_filters(MAXGALLERIA_FILTER_VIDEO_TILES_GALLERY_OUTPUT, $output, $gallery, $attachments, $options));
	}
}
?>