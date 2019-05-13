<?PHP
/*
Plugin Name: WP Web地学ライブラリ
Description: WordPressにWeb地学ライブラリサイト機能を追加するプラグインです。
Version: 0.1.0
Author: Dank
Author URI: https://www.nsb-dank.co.jp
*/
require_once dirname(__FILE__) . '/admin-geolib.php';
require_once dirname(__FILE__) . '/save-geolib.php';
require_once dirname(__FILE__) . '/map_util.php';
/*****************************************
 * カスタム投稿タイプ「地学ライブラリ」を追加する
 *****************************************/
function geolib_post_type_create() {
	$Supports = [
			'title',
			'editor',
			'thumbnail',
			'author',
			'page-attributes'
	];
	register_post_type( 'geolib',
			array(
					'label' => '地学ライブラリ',
					'labels' => array(
							'name' => '地学ライブラリ',
							'all_items'      => '地学ライブラリ一覧',
							'singular_name' => '地学ライブラリ',
							'add_new' => '新規追加',
							'add_new_item' =>'地学ライブラリを作成する',
							'edit_item' => '地学ライブラリを編集する',
							'new_item' => '新しい地学ライブラリ',
							'view_item' => '地学ライブラリを表示する',
							'search_items' => '地学ライブラリを検索',
							'not_found' =>  '地学ライブラリはありません',
							'not_found_in_trash' => 'ゴミ箱に地学ライブラリはありません',
							'parent_item_colon' => ''
					),
					'exclude_from_search' => true, //検索対象に含めるか
					'show_ui' => true, //管理画面に表示するか
					'show_in_menu' => true, //管理画面のメニューに表示するか
					'public' => true,
					'has_archive' => true,
					'menu_position' => 3,
					'hierarchical' => false,
					'supports' => $Supports
			)
			);
	/* カスタムタクソノミー エリア */
	register_taxonomy(
			'area',  /* タクソノミーのslug */
			'geolib',           /* 属する投稿タイプ */
			array(
					'hierarchical' => true,
					'update_count_callback' => '_update_post_term_count',
					'label' => '地域',
					'singular_label' => '地域',
					'public' => true,
					'show_ui' => true
			)
			);

	/* カスタムタクソノミー レベル */
	register_taxonomy(
			'level',  /* タクソノミーのslug */
			'geolib',        /* 属する投稿タイプ */
			array(
					'hierarchical' => true,
					'update_count_callback' => '_update_post_term_count',
					'label' => 'レベル',
					'singular_label' => 'レベル',
					'public' => true,
					'show_ui' => true
			)
			);
}
// カスタム投稿タイプを作成
add_action( 'init', 'geolib_post_type_create');
// カスタム投稿の編集画面メインカラムにボックスを追加
add_action ('add_meta_boxes', 'geolib_add_article_box');

/*****************************************
* geolib投稿タイプのテンプレートァイルを指定する
*****************************************/
    function geolib_template_loader( $template ) {
        // テンプレートファイルの場所
    	$template_dir = dirname(__FILE__) . '/';
    	if ( is_singular( 'geolib' ) ) {
            $file_name  = 'single-geolib.php';
        }elseif ( is_page( 'geoliblist' ) ) {
        	$file_name  = 'page-geoliblist.php';
        }elseif ( is_page( 'geolibmap' ) ) {
        	$file_name  = 'page-geolibmap.php';
        }
         if ( isset( $file_name ) ) {
            // テーマ（子 → 親）のファイルを先に探す
            $theme_file = locate_template( $file_name );
        }
        if ( isset( $theme_file ) && $theme_file ) {
            $template = $theme_file;
        } elseif ( isset( $file_name ) && $file_name ) {
            $template = $template_dir . $file_name;
        }
        return $template;
    }
    //geolib投稿タイプのテンプレートにアクセスできるように設定を追加
   add_filter( 'template_include', 'geolib_template_loader');

   /*****************************************
    * 固定ページ「地学ライブラリリスト」と「地学ライブラリマップ」を追加する
    *****************************************/
   function geolib_add_page(){
   	$geoliblist =  array(
   			'post_name' => 'geoliblist' ,
   			'post_title' => '地学ライブラリリスト',
   			'post_content' => '現在登録されているライブラリの一覧です。',
   			'post_status' => 'publish',
   			'post_type' => 'page',
   			'page_template' => 'page-geoliblist.php'
   		);
   		wp_insert_post($geoliblist);
   		$geolibmap =  array(
   				'post_name' => 'geolibmap' ,
   				'post_title' => '地学ライブラリマップ',
   				'post_content' => '現在登録されているライブラリのマップ表示です。',
   				'post_status' => 'publish',
   				'post_type' => 'page',
   				'page_template' => 'page-geolibmap.php'
   		);
   		wp_insert_post($geolibmap);
   }
   //固定ページを作成
   register_activation_hook(__FILE__,  'geolib_add_page');

    /*****************************************
     * articlesテーブルの追加
     *****************************************/
    //プラグインを有効化した場合にテーブルを作成する
    register_activation_hook(__FILE__, 'geolib_table_create');

/*****************************************
     * articlesテーブルデータの作成・更新
     *  @param int $post_id The ID of the post being saved.
     *****************************************/
      // articleテーブルデータのCRUD
    add_action ('save_post_geolib',  'geolib_save_box_data');

        /*****************************************
     * articlesテーブルデータの削除
     *****************************************/
     add_action ('delete_post',  'geolib_delete_article');

     /*****************************************
      * SVGアップロード可にする
      *****************************************/
     function custom_mime_types( $mimes ) {
     	$mimes['svg'] = 'image/svg+xml';
     	return $mimes;
     }
     add_filter( 'upload_mimes', 'custom_mime_types' );

     /*****************************************
     *     プラグイン停止時の処理
         *****************************************/
     /* 固定ページを削除 */
     function geolib_delete_page(){
     	$geoliblist = get_posts(array(
     		'post_type'   => 'page',
     		'post_status' => 'publish',
     		'post_name'   => 'geoliblist') );
     	if($geoliblist)
     		wp_delete_post($geoliblist->ID,true);

     	$geolibmap = get_posts(array(
     		'post_type'   => 'page',
     		'post_status' => 'publish',
     		'post_name'   => 'geolibmap') );
     	if($geolibmap)
     			wp_delete_post($geolibmap->ID,true);
     }
     register_deactivation_hook(__FILE__, 'geolib_delete_page');

