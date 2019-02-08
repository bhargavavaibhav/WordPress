<?php
/*
Plugin Name: Woocommerce variations image gallery
Description: A WooCommerce plugin to create image gallery for perticular product's variation.
Author: Prakhar Kant Tripathi
Version: 0.0.1
*/


if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}


add_action('add_meta_boxes', 'pk_add_vari_images', 10, 1); 

function pk_add_vari_images($post) {
	add_meta_box(
		'pk_add_vari', 
		__('Add images per Variations'),
		'pk_add_vari_cb',
		'product',
		'normal',
		'default'
	); 
} 


function pk_add_vari_cb() {
	?>
	<style type="text/css">
		#pk_add_vari{opacity: 0;} 
		.adds_gallery_lists li {
			display: inline-block; 
			margin-right: 5px;
			margin-bottom: 5px;			
			position: relative; 
		} 
		.adds_gallery_lists li a {
			display: block; 
			position: relative; 
		} 
		.adds_gallery_lists .overlay { 
			background: #000;
		    border: 1px solid #ffffff;
		    border-radius: 50%;
		    color: #ffffff;
			cursor: pointer; 
			display: none; 
			line-height: 17px;
			padding: 2px 5px;
			position: absolute;
			top: -10px;
		    right: -10px;
		}
		.adds_gallery_lists li:hover .overlay { display: block; z-index: 2;	} 
	</style>
	<script type="text/javascript">
		var $ = jQuery; 
		$(document).ready(function(){ 
			var thumbs_id = []; 
			
			$(document).on('click', '.woocommerce_variation > h3', function(){ 
				var th = $(this); 
				if($(this).hasClass('bind')) return ;

				$(this).addClass('bind'); 

				th.parent().find('.options').before(
					'<div class="form-row adds_gallery"><a href="" class="button button-primary pk_gallery">Add additional gallery</a><ul class="adds_gallery_lists"></ul><a href="" class="pk_gallery_save button button-primary">Save Gallery</a></div>'
					); 

				id 		= th.parent().find('.upload_image a.upload_image_button').prop('rel'), 

				$.ajax({
					url: '<?php echo admin_url("admin-ajax.php"); ?>',
					type: 'post',
					data: {
						action 	: 'pk_gallery_load',
						id 		: id
					}, 
					success: function(res) {
						th.parent().find('.adds_gallery_lists').append(res);
					}, 
					error: function(res) { 
						console.log(res);
						alert('Here found some type of error!');
					}
				}); 
			}); 

			$(document).on('click', '.woocommerce_variation .pk_gallery', function(e){ 
				e.preventDefault(); 
				var th 		= $(this), 
					id 		= $(this).parent().find( '.upload_image a.upload_image_button' ).prop('rel'), 
					thumbs 	= th.next(),
					mediaFrame;

				pk_media_gallery(id, thumbs, true); 			
			}); 
			
			$(document).on('click', '.woocommerce_variation .pk_avi_thumb', function(e){ 
				e.preventDefault(); 
				var th 		= $(this), 
					id 		= $(this).parents('.woocommerce_variation').find('.upload_image a.upload_image_button').prop('rel'), 
					thumbs 	= th.next(),
					mediaFrame;

				pk_media_gallery(id, th, false); 		
			}); 

			$(document).on('click', '.woocommerce_variation .overlay', function(e){ 
				e.preventDefault(); 
				var th 		= $(this); 
					
				th.parents('li').remove(); 
			}); 

			$(document).on('click', '.woocommerce_variation .pk_gallery_save', function(e){
				e.preventDefault(); 
				var th 		= $(this),
					id 		= $(this).parents('.woocommerce_variation').find('.upload_image a.upload_image_button').prop('rel'), 
					ids 	= '';

				th.prev().find('.pk_avi_thumb').each(function(){
					ids += $(this).data('id')+','; 
				});

				console.log(id +'     '+ ids); 

				$.ajax({
					url: '<?php echo admin_url("admin-ajax.php"); ?>',
					type: 'post',
					data: {
						action 	: 'pk_gallery_save',
						ids 	: ids,
						id 		: id
					}, 
					success: function(res) {
						alert(res);
					}, 
					error: function(res) { 
						console.log(res);
						alert('Here found some type of error!');
					}
				}); 

			}); 
		}); 

		function pk_media_gallery(var_id, thumbs, multiple) {
			// create the media frame
			mediaFrame = wp.media.frames.mediaFrame = wp.media({ 
				title: 'Variations Images',
				button: {
					text: 'add'
				},

				// only images
				library: {
					type: 'image'
				},

				multiple: multiple
			});

			// after a file has been selected
			mediaFrame.on( 'select', function() {
				var selection = mediaFrame.state().get( 'selection' );

				selection.map( function( attachment ) {

					attachment = attachment.toJSON();

					if ( attachment.id ) {
						var url = attachment.sizes.thumbnail ? attachment.sizes.thumbnail.url : attachment.url; 
						
						if(multiple) {
							thumbs.append( '<li><a href="#" class="pk_avi_thumb" data-id="' + attachment.id + '"><img src="' + url + '" width="40" height="40" /></a><span class="overlay">x</span></li>' );
						} else {
							thumbs.replaceWith( '<a href="#" class="pk_avi_thumb" data-id="' + attachment.id + '"><img src="' + url + '" width="40" height="40" /></a>' );
						}
						
					}
				});

				// make sure attachments are link to the variation post id instead of parent post id
				wp.media.model.settings.post.id = var_id;
			}); 

			// open the modal frame
			mediaFrame.open(); 
		}	
	</script>
	<?php 
} 





add_action('wp_ajax_pk_gallery_save', 'func_pk_gallery_save');
function func_pk_gallery_save() {
	extract($_POST);
	$ids = rtrim($ids, ','); 

	update_post_meta($id, 'pk_vari_gallery', $ids); 

	// get_post_meta($id, 'pk_vari_gallery', true); 
	echo 'success'; 
	wp_die(); 
} 


add_action('wp_ajax_pk_gallery_load', 'func_pk_gallery_load');
function func_pk_gallery_load() {
	extract($_POST);
	$html = ''; 	

	$ids = get_post_meta($id, 'pk_vari_gallery', true); 
	
	foreach( explode( ',', $ids ) as $attach_id ) {
		$attachment = wp_get_attachment_image_src( $attach_id, array( 40, 40 ) );

		if ( $attachment ) {		
			$html .= '<li><a href="#" class="pk_avi_thumb" data-id="' . esc_attr( $attach_id ) . '"><img src="' . esc_attr( $attachment[0] ) . '" width="40" height="40" /></a><span class="overlay">x</span></li>';
		}
	} 

	echo $html; 
	wp_die(); 
} 


// add_action('woocommerce_before_single_product_summary', 'pk_func_load_js');
function pk_func_load_js() {
	global $wpdb, $post, $woocommerce, $product; 

	// if($product->id!=695) return; 

	// echo '<pre style="display:none;">';print_r($jckWooThumbs); echo '</pre>'; 

	?>
	<link rel="stylesheet" type="text/css" href="<?php echo plugin_dir_url( __FILE__ );?>flexslider.css">
	<script type="text/javascript" src="<?php echo plugin_dir_url( __FILE__ );?>jquery.flexslider.js"></script>
	<script type="text/javascript">
		(jQuery)(function($) {
			$(document).ready(function(){ 
				$(document).on('change', 'input[name="variation_id"]', function(e){ 
					e.preventDefault(); 

					var th = $(this),
						var_id = th.val();

					$('.images').css({'opacity':'0.5', 'pointer-events':'none'}); 
					$.ajax({
						url: '<?php echo admin_url("admin-ajax.php"); ?>', 
						type: 'post',
						data: {
							action: 'pk_gallery_slider_load',
							id: var_id,
							pro_id: $('input[name="product_id"]').val()
						}, 
						success: function(res){
							console.log(res); 
							if(res == 'fail' ) {

							} else { 								
								
		    					$('.images').html(res);
		    					$('.flexslider').flexslider({
								    animation: "slide",
								    controlNav: "thumbnails"
								});
		    					$('.images').css({'opacity':'1', 'pointer-events':'initial'}); 
		    					
							}
						},
						error: function(res) {
							console.log(res); 
							alert('some error occured!'); 
						}
					}); 
				}); 
			}); 

		}); 
	</script>
	<?php 
} 


add_action('wp_ajax_pk_gallery_slider_load', 'pk_gallery_slider_load');
add_action('wp_ajax_nopriv_pk_gallery_slider_load', 'pk_gallery_slider_load');
function pk_gallery_slider_load() { 
	global $post, $woocommerce, $product; 
	
	extract($_POST); 

	$return = '';

	if($id) { 
		$ids = get_post_meta($id, 'pk_vari_gallery', true); 

		if(!$ids) { echo 'fail'; wp_die(); } 
		
		$post_id = $pro_id; 
		$product = wc_get_product( $post_id );
		$return = ''; 

		$vari_thumb_id = get_post_meta($id, '_thumbnail_id', true); 
		$img_thumb = wp_get_attachment_image_src($vari_thumb_id, 'thumbnail'); 
		$img_large = wp_get_attachment_image_src($vari_thumb_id, 'large'); 	

		// $return .= "<a href=\"{$img_large[0]}\" itemprop=\"image\" class=\"woocommerce-main-image zoom\" title=\"\" data-rel=\"prettyPhoto[product-gallery]\" rel=\"prettyPhoto[product-gallery]\">";

		// $return .= "<img width=\"{$img_large[1]}\" height=\"{$img_large[2]}\" src=\"{$img_large[0]}\" class=\"attachment-shop_single wp-post-image\" alt=\"\" title=\"\" sizes=\"\"></a>";

		if($ids) { 
			$return .= '<div class="flexslider"><ul class="slides">'; 
			//$return .= "<a href=\"{$img_large[0]}\" itemprop=\"image\" class=\"zoom first\" title=\"\" data-rel=\"prettyPhoto[product-gallery]\" rel=\"prettyPhoto[product-gallery]\">";

			$return .= "<li data-thumb=\"{$img_thumb[0]}\"><img width=\"180\" height=\"180\" src=\"{$img_large[0]}\" class=\"attachment-shop_thumbnail\" alt=\"\" title=\"\"></li>";
			// $return .= "</a>"; 
			foreach (explode(',', $ids) as $img_id) { 
				$img_thumb = wp_get_attachment_image_src($img_id, 'thumbnail'); 
				$img_large = wp_get_attachment_image_src($img_id, 'large'); 
				//$return .= "<a href=\"{$img_large[0]}\" class=\"zoom\" title=\"\" data-rel=\"prettyPhoto[product-gallery]\" rel=\"prettyPhoto[product-gallery]\">";
				$return .= "<li data-thumb=\"{$img_thumb[0]}\"><img width=\"180\" height=\"180\" src=\"{$img_large[0]}\" class=\"attachment-shop_thumbnail\" alt=\"\" title=\"\"></li>";
				// $return .= "</a>"; 
			} 
			$return .= '</ul></div>'; 
		} 
		echo $return; 
	} else { 
		echo 'fail'; 
	}

	wp_die(); 
}