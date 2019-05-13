<?php
/*
Template Name: 地学ライブラリリスト
*/

get_header(); ?>
<div class="wrap">
		<h1>ライブラリリスト</h1>
<div id="primary" class="content-area">
	<main id="main" class="site-main" role="main">
		<?php
		$current_user = wp_get_current_user();
		$current_user_id = $current_user->ID;

		//pagedに値をセットするのを忘れずに！
		$the_query = new WP_Query( array(
		'paged'       => get_query_var( 'paged' ) ? intval( get_query_var( 'paged' ) ) : 1,
		'post_type'   => 'geolib',
		'posts_per_page' => 10, // 表示件数
		'orderby'     => 'ID',
		'order' => 'DESC'
		) ); ?>

    	<table cellpadding="0" cellspacing="0">
        	<thead>
            	<tr>
                	<th scope="col"  style="width:80px;"></th>
                	<th scope="col">表題</th>
                	<th scope="col">地域</th>
                	<th scope="col">レベル</th>
                	<th scope="col">更新日時</th>
                	<th scope="col">ユーザー</th>
            	</tr>
        	</thead>
          	<tbody>
			<?php
			require_once (  dirname(__FILE__) .'./../articles.php');
			if ( $the_query->have_posts() ) while ( $the_query->have_posts() ) :
				$the_query->the_post();
				$post_id = get_the_ID();
				$post_user = get_the_author();
				$article =geolib_get_article($post_id);
				$title = isset($article->title) ? $article->title : null;
				$area_id = isset($article->area_id) ? $article->area_id : null;
				$level_id = isset($article->level_id) ? $article->level_id:null;
				?>
            	<tr>
                	<td style="width:80px;">
                	<?php
                    	$post_user_id = $post->post_author;
                    	if ((current_user_can('administrator')) or ($current_user_id == $post_user_id)){
                    		edit_post_link('Edit','[',']');
                    }
                 ?>
                </td>
                <td><a href="<?=the_permalink() ?>" title="<?= the_content(); ?>"><b>【<?=the_title() ?>】</b></a></td>
                <td><?= get_the_term_list($post->ID, 'area', '', ',');  ?></td>
                <td><?= get_the_term_list($post->ID, 'level', '', ',');  ?></td>
                <td><?= the_modified_date('Y/m/d');?> <?=the_modified_date('H:i'); ?></td>
                <td><?= $post_user ?></td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
<?php
//ページネーション表示前に$GLOBALS['wp_query']->max_num_pagesに値をセット
$GLOBALS['wp_query']->max_num_pages = $the_query->max_num_pages;
the_posts_pagination();
wp_reset_postdata();
		?>
	</main><!-- .site-main -->
</div><!-- .content-area -->
</div><!-- .wrap -->
