<?php
/**
 * The template for displaying all single posts
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/#single-post
 *
 * @package WordPress
 * @subpackage Twenty_Seventeen
 * @since 1.0
 * @version 1.0
 */

get_header(); ?>

<div class="wrap">
	<div id="primary" class="content-area">
		<main id="main" class="site-main" role="main">

			<?php
			/* Start the Loop */
			while ( have_posts() ) :
				the_post();
				get_template_part( 'template-parts/post/content', get_post_format() );

				$get_meta = $wp_geolib->geolib_get_article($post->ID);
				$title = isset($get_meta->title) ? $get_meta->title : null;
				$area_id = isset($get_meta->area_id) ? $get_meta->area_id : null;
				$level_id = isset($get_meta->level_id) ? $get_meta->level_id:null;
				$geom = isset($get_meta->geom) ? $get_meta->geom:null;
				$filepath = isset($get_meta->filepath) ? $get_meta->filepath:null;
				$published = isset($get_meta->published) ? $get_meta->published:null;

				echo '<div>表題：' . esc_html($title ) . '</div><br/>';
				echo '<div>エリア：' . esc_html($area_id) . '</div><br/>';
				echo '<div>レベル：' . esc_html($level_id) . '</div><br/>';
				echo '<div>初期表示範囲：' . esc_html($geom) . '</div><br/>';
				echo '<div>保存先：' . esc_html($filepath) . '</div><br/>';
				echo '<div>公開：' . esc_html($published) . '</div><br/>';

			endwhile; // End of the loop.
			?>

		</main><!-- #main -->
	</div><!-- #primary -->

</div><!-- .wrap -->