<?php
/*
Plugin Name: Like - Dislike button
Plugin URI: #
Description: ###########
Author: Prakhar
Version: 0.1
Author URI: #
 */

global $pkDislike;
// setcookie('thumbPressss', '', time() - (86400 * 30), "/");

define('PKTHUMBURL', WP_PLUGIN_URL . "/pk_like_btn");
define('PKTHUMBPATH', WP_PLUGIN_DIR . "/pk_like_btn");

function pk_array_delete($del_val, $messages) {
	if (($key = array_search($del_val, $messages)) !== false) {
		unset($messages[$key]);
	}

	return $messages;
}

function pk_return_zero($n) {
	if ($n < 0) {
		$n = 0;
	}
	return $n;
}

add_action('wp_ajax_thumbBtnAction', 'pk_thumbBtnAction_func');
add_action('wp_ajax_nopriv_thumbBtnAction', 'pk_thumbBtnAction_func');
function pk_thumbBtnAction_func() {
	// var_dump($_SERVER); die;
	extract($_POST);
	$likes = $dislike = array();
	$return = array('dislike' => array('ack' => '', 'count' => 0), 'like' => array('ack' => '', 'count' => 0));

	$likeCount = get_post_meta($postId, 'likeCount', true);
	$likeCount = $likeCount ? $likeCount : '0';
	$disLikeCount = get_post_meta($postId, 'disLikeCount', true);
	$disLikeCount = $disLikeCount ? $disLikeCount : '0';

	$thumbCookie = !empty($_COOKIE['thumbPressss']) ? $_COOKIE['thumbPressss'] : '';
	// var_dump($thumbCookie);

	if ($thumbCookie) {
		$thumbCookie = stripslashes($thumbCookie);
		$thumbCookie = unserialize($thumbCookie);
		$return['ck_b'] = $thumbCookie;

		if ($act == 'like') {
			if (in_array($postId, $thumbCookie['dislikes'])) {
				$thumbCookie['dislikes'] = pk_array_delete($postId, $thumbCookie['dislikes']);
				$disLikeCount--;
				update_post_meta($postId, 'disLikeCount', pk_return_zero($disLikeCount));
			}

			if (in_array($postId, $thumbCookie['likes'])) {
				$thumbCookie['likes'] = pk_array_delete($postId, $thumbCookie['likes']);
				$likeCount--;
				$return['like']['ack'] = 0;

			} else {
				$thumbCookie['likes'][] = $postId;
				$likeCount++;
				$return['like']['ack'] = 1;
				// echo 'dddddddddddd';
			}

			$return['like']['count'] = pk_return_zero($likeCount);
			$return['dislike']['count'] = pk_return_zero($disLikeCount);
			update_post_meta($postId, 'likeCount', pk_return_zero($likeCount));

		}

		if ($act == 'dislike') {

			if (in_array($postId, $thumbCookie['likes'])) {
				$thumbCookie['likes'] = pk_array_delete($postId, $thumbCookie['likes']);
				$likeCount--;
				update_post_meta($postId, 'likeCount', pk_return_zero($likeCount));
			}

			if (in_array($postId, $thumbCookie['dislikes'])) {
				$thumbCookie['dislikes'] = pk_array_delete($postId, $thumbCookie['dislikes']);
				$disLikeCount--;
				$return['dislike']['ack'] = 0;

			} else {
				$thumbCookie['dislikes'][] = $postId;
				$disLikeCount++;
				$return['dislike']['ack'] = 1;
				// echo 'dddddddddddd';

			}

			$return['like']['count'] = pk_return_zero($likeCount);
			$return['dislike']['count'] = pk_return_zero($disLikeCount);
			update_post_meta($postId, 'disLikeCount', pk_return_zero($disLikeCount));

		}
		// var_dump($thumbCookie);
		$return['ck_n'] = $thumbCookie;
		setcookie('thumbPressss', serialize($thumbCookie), time() + (86400 * 30), "/"); // 86400 = 1 day

	} else {

		if ($act == 'like') {
			$likes = array($postId);
			$return['like']['ack'] = 1;
			$return['like']['count'] = 1;
			$return['dislike']['ack'] = 0;
			$return['dislike']['count'] = 0;
			update_post_meta($postId, 'likeCount', 1);
			update_post_meta($postId, 'disLikeCount', 0);
		}

		if ($act == 'dislike') {
			$dislikes = array($postId);
			$return['like']['ack'] = 0;
			$return['like']['count'] = 0;
			$return['dislike']['ack'] = 1;
			$return['dislike']['count'] = 1;
			update_post_meta($postId, 'likeCount', 0);
			update_post_meta($postId, 'disLikeCount', 1);
		}

		$cookie_value = array('dislikes' => $dislikes, 'likes' => $likes);

		setcookie('thumbPressss', serialize($cookie_value), time() + 604800, "/");
	}

	echo json_encode($return);
	wp_die();
}

function voteme_getvotelink($post_ID = false) {
	global $pkDislike;
	$pkDislike = true;

	$like = $dislike = "";

	if (!$post_ID) {
		$post_ID = get_the_ID();
	}

	// update_post_meta($post_ID, 'likeCount', 0);
	// update_post_meta($post_ID, 'disLikeCount', 0);
	// setcookie('thumbLikes', '', time() - (86400 * 30), "/");

	$likeCount = get_post_meta($post_ID, 'likeCount', true);
	$likeCount = $likeCount ? $likeCount : '0';
	$disLikeCount = get_post_meta($post_ID, 'disLikeCount', true);
	$disLikeCount = $disLikeCount ? $disLikeCount : '0';

	$thumbLikes = $_COOKIE['thumbPressss'];
	// var_dump($thumbLikes);

	if ($thumbLikes) {
		$thumbLikes = stripslashes($thumbLikes);
		$thumbLikes = unserialize($thumbLikes);
		if (in_array($post_ID, $thumbLikes['likes'])) {
			$like = true;
		}

		if (in_array($post_ID, $thumbLikes['dislikes'])) {
			$dislike = true;
		}

	}

	ob_start();?>
	<div class="thumbBtn">
	  <div class="likeThumb">
	    <a href="javascript:void(0)" data-id="<?=$post_ID?>" data-act="like" data-likes="<?php echo $likeCount ?>">
	    	<sub data-id="<?=$post_ID?>" data-act="like"><?php echo $likeCount; ?></sub>
			<img class="blkImg <?php echo $like == true ? '' : 'active'; ?>" src="<?php echo PKTHUMBURL ?>/imgs/like.png" data-id="<?=$post_ID?>" data-act="like" />
			<img class="redImg <?php echo $like == true ? 'active' : ''; ?>" src="<?php echo PKTHUMBURL ?>/imgs/like_a.png" data-id="<?=$post_ID?>" data-act="like" />

	    </a>
	  </div>

	  <div class="unlikeThumb">
	    <a href="javascript:void(0)" data-id="<?=$post_ID?>" data-act="dislike" data-dislikes="<?php echo $dislikeCount ?>">
	    	<sub data-id="<?=$post_ID?>" data-act="dislike"><?php echo $disLikeCount; ?></sub>
			<img class="blkImg <?php echo $dislike == true ? '' : 'active'; ?>" data-id="<?=$post_ID?>" data-act="dislike" src="<?php echo PKTHUMBURL ?>/imgs/dislike.png" />
			<img class="redImg <?php echo $dislike == true ? 'active' : ''; ?>" data-id="<?=$post_ID?>" data-act="dislike" src="<?php echo PKTHUMBURL ?>/imgs/dislike_a.png" />
	    </a>
	  </div>

	</div>
	<style type="text/css">
		.thumbBtn > div {display: inline-block; }
		.thumbBtn sub {color: red; }
		.thumbBtn img {display: none;}
		.thumbBtn img.active {display: inline-block;}
		.show_loader {position:relative; pointer-events: none; }
		.show_loader:after {content: " "; display: block; position: absolute; width: 110%; height: 140%; top: -20%; left: -5%; background-image:url(<?php echo PKTHUMBURL; ?>/imgs/loading.gif); background-repeat: no-repeat; background-color: rgba(0,0,0,0.5); background-position: 50% 50%; }
	</style>
	<?php
return ob_get_clean();

}

function voteme_printvotelink($content) {
	$c = voteme_getvotelink();
	return $c . $content;
}
// add_filter('the_content', 'voteme_printvotelink');

add_action('wp_footer', 'pk_thumb_ajax_func');
function pk_thumb_ajax_func() {

	?>
	<script type="text/javascript">
		if(jQuery('.thumbBtn').length > 0) {
			jQuery('.thumbBtn a').click(function(e) {
				var th = jQuery(this);
				var pr = th.parents('.thumbBtn');
				postId = th.data('id');
				act = th.data('act');
				e.preventDefault();

				pr.addClass('show_loader');

				jQuery.ajax({
					type: 'POST',
					url: '<?php echo admin_url("admin-ajax.php"); ?>',
					data: {
						action: 'thumbBtnAction',
						postId: postId,
						act: act
					},
					dataType: 'json',
					async: false,
					// cache: false,
					success: function(data, textStatus, XMLHttpRequest) {
						pr.removeClass('show_loader');
						// window.location.reload();
						if(data.like.ack==1) {
							pr.find('.blkImg[data-act="like"][data-id="'+postId+'"]').hide();
							pr.find('.redImg[data-act="like"][data-id="'+postId+'"]').show();
						} else {
							pr.find('.blkImg[data-act="like"][data-id="'+postId+'"]').show();
							pr.find('.redImg[data-act="like"][data-id="'+postId+'"]').hide();
						}

						if(data.dislike.ack==1) {
							pr.find('.blkImg[data-act="dislike"][data-id="'+postId+'"]').hide();
							pr.find('.redImg[data-act="dislike"][data-id="'+postId+'"]').show();
						} else {
							pr.find('.blkImg[data-act="dislike"][data-id="'+postId+'"]').show();
							pr.find('.redImg[data-act="dislike"][data-id="'+postId+'"]').hide();
						}

						if( sub = pr.find('sub[data-act="like"][data-id="'+postId+'"]') ) {
							sub.html(data.like.count);
						}
						if(sub = pr.find('sub[data-act="dislike"][data-id="'+postId+'"]') ) {
							sub.html(data.dislike.count);
						}
					},

					error: function(res, textStatus, errorThrown) {
						pr.removeClass('show_loader');
						// console.log(res);
						// window.location.reload();
					}
				});

			});

		}

	</script>
	<?php

}
