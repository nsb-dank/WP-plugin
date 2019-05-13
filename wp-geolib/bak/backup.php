<?PHP
/*
Plugin Name: WP Web地学ライブラリ(BAK)
Description: WordPressにWeb地学ライブラリサイト機能を追加するプラグインです。
Version: 1.0.0
Author: Dank
Author URI: https://www.nsb-dank.co.jp
*/

class WpGeolib {
    //プラグインのテーブル名
    var $table_name;
    public function __construct()
    {
        global $wpdb;
        defined( 'ABSPATH' ) or die( 'No script kiddies please!' );
        load_plugin_textdomain('wp-geolib', false,  dirname( plugin_basename( __FILE__ ) ) . '/languages');
        // 接頭辞（wp_）を付けてテーブル名を設定
        $this->table_name = $wpdb->prefix . 'articles';
        // プラグイン有効化したときに実行
        register_activation_hook (__FILE__, array($this, 'geolib_activate'));
        //カスタム投稿タイプの作成
        add_action( 'init', array($this,'my_custom_post_geolib' ));
        // カスタムフィールドの作成
        add_action( 'add_meta_boxes', array($this, 'ex_metabox'));
        add_action ('save_post_geolib', array($this, 'save_meta'));
        add_action ('delete_post', array($this, 'dalete_meta'));
        add_filter( 'template_include', array($this, 'template_loader' ) );
    }

    function geolib_activate() {
        global $wpdb;
        $geolib_db_version = '1.0';
        $installed_ver = get_option( 'geolib_meta_version' );
        // テーブルのバージョンが違ったら作成
        if( $installed_ver != $geolib_db_version ) {
            $sql = "CREATE TABLE " . $this->table_name . " (
          meta_id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
          post_id bigint(20) UNSIGNED DEFAULT '0' NOT NULL,
          title varchar(255),
          area_id int(11) DEFAULT NULL,
          level_id int(11) DEFAULT NULL,
          geom geometry DEFAULT NULL,
          filepath varchar(255) DEFAULT NULL,
          published tinyint(1) NOT NULL DEFAULT '0',
          UNIQUE KEY meta_id (meta_id)
        )
        CHARACTER SET 'utf8';";
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);
            update_option('geolib_meta_version', $geolib_db_version);
        }
    }

    function my_custom_post_geolib() {
        $labels = array(
            'name'               => __( 'Geo Libraries', 'post type general name', 'geolib' ),
            'singular_name'      => __( 'Geo Library', 'post type singular name', 'geolib' ),
            'add_new'            => __( 'Add New', 'post type singular name', 'geolib' ),
            'add_new_item'       => __( 'Add New Geo Library', 'geolib' ),
            'edit_item'          => __( 'Edit Geo Library', 'geolib' ),
            'new_item'           => __( 'New Geo Library', 'geolib' ),
            'all_items'          => __( 'All Geo Libraries', 'geolib' ),
            'view_item'          => __( 'View Geo Library', 'geolib' ),
            'search_items'       => __( 'Search Geo Libraries', 'geolib' ),
            'not_found'          => __( 'No geo libraries found', 'geolib' ),
            'not_found_in_trash' => __( 'No geo libraries found in the Trash', 'geolib' ),
            'parent_item_colon'  => '',
            'menu_name'          => __('Geo Libraries', 'geolib'),
        );
        $args = array(
            'labels'                => $labels,
            'description'           => 'Holds our libraries and ilbrary specific data',
            'public'                => true,
            'menu_position'         => 5,
            'supports'              => array( 'title', 'editor', 'thumbnail', 'excerpt', 'comments' ),
            'has_archive'           => true,
            'hierarchical'          => false,
            'show_ui'               => true,
            'show_in_menu'          => true,
            'menu_position'         => 5,
            'show_in_admin_bar'     => true,
            'show_in_nav_menus'     => true,
            'can_export'            => true,
            'has_archive'           => true,
            'exclude_from_search'   => false,
            'publicly_queryable'    => true,
            'capability_type'       => 'page',
        );
        register_post_type( 'geolib', $args );
    }

    function ex_metabox( $geolib ) {
        add_meta_box(
            'exmeta_sectionid',
            __('Detail'),
            array($this, 'ex_meta_html'),
            'geolib'
            );
    }
    function ex_meta_html () {
        wp_nonce_field( plugin_basename( __FILE__ ), $this->table_name );
        global $geolib;
        global $wpdb;
        $get_meta = $wpdb->get_results(
            $wpdb->prepare( "SELECT * FROM
        ".$this->table_name. " WHERE
        post_id = %d", $geolib->ID
                )
            );
        $get_meta = isset($get_meta[0]) ? $get_meta[0] : null;
        $title = isset($get_meta->title) ? $get_meta->title:null;
        $area_id = isset($get_meta->area_id) ? $get_meta->area_id:null;
        $level_id = isset($get_meta->level_id) ? $get_meta->level_id:null;
        $geom = isset($get_meta->geom) ? $get_meta->geom:null;
        $filepath = isset($get_meta->filepath) ? $get_meta->filepath:null;
        $published = isset($get_meta->published) ? $get_meta->published:null;
        ?>
    <div>
    <table>
      <tr>
        <th><?= __('Title') ?></th>
        <td><input name="title" value="<?php echo $title ?>" /></td>
      </tr>
      <tr>
        <th><?= __('Area') ?></th>
        <td><input name="area_id" value="<?php echo $area_id ?>" /></td>
      </tr>
      <tr>
        <th><?= __('Level') ?></th>
        <td><input name="level_id" value="<?php echo $level_id ?>" /></td>
      </tr>
      <tr>
        <th><?= __('Geometry') ?></th>
        <td><input name="geom" value="<?php echo $geom ?>" /></td>
      </tr>
      <tr>
        <th><?= __('File Path') ?></th>
        <td><input name="filepath" value="<?php echo $filepath ?>" /></td>
      </tr>
      <tr>
        <th><?= __('Published') ?></th>
        <td><input name="published" value="<?php echo $published ?>" /></td>
      </tr>
    </table>
    </div>
    <?php
  }
  function save_meta($post_id) {
    if (!isset($_POST[$this->table_name])) return;
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )  return;
      if ( !wp_verify_nonce( $_POST[$this->table_name], plugin_basename( __FILE__ ) ) )  return;
    global $wpdb;
    global $geolib;
    //リビジョンを残さない
    if ($geolib->ID != $post_id) return;

    $temp_title = isset($_POST['title']) ? $_POST['title'] : null;
    $temp_area_id = isset($_POST['area_id']) ? $_POST['area_id'] : null;
    $temp_level_id = isset($_POST['level_id']) ? $_POST['level_id'] : null;
    $temp_geom = isset($_POST['geom']) ? $_POST['geom'] : null;
    $temp_filepath = isset($_POST['filepath']) ? $_POST['filepath'] : null;
    $temp_published = isset($_POST['published']) ? $_POST['published'] : null;
    //保存するために配列にする
    $set_arr = array(
    'title' => $temp_title,
    'area_id' => $temp_area_id,
    'level_id' => $temp_level_id,
    'geom' => $temp_geom,
    'filepath' => $temp_filepath,
    'published' => $temp_published
      );
    $get_id = $wpdb->get_var(
                $wpdb->prepare( "SELECT post_id FROM
                  ". $this->table_name ." WHERE
                  post_id = %d", $post_id)
    );
    //レコードがなかったら新規追加あったら更新
    if ($get_id) {
      $wpdb->update( $this->table_name, $set_arr, array('post_id' => $post_id));
    } else {
      $set_arr['post_id'] = $post_id;
      $wpdb->insert( $this->table_name, $set_arr);
    }
    $wpdb->show_errors();
  }
  function dalete_meta($post_id) {
    global $wpdb;
    $wpdb->query( $wpdb->prepare( "DELETE FROM $this->table_name WHERE post_id = %d", $post_id) );
  }
  function get_meta($post_id) {
    if (!is_numeric($post_id)) return;
    global $wpdb;
    $get_meta = $wpdb->get_results(
      $wpdb->prepare( "SELECT * FROM
        ".$this->table_name. " WHERE
        post_id = %d", $post_id
      )
    );
    return isset($get_meta[0]) ? $get_meta[0] : null;
  }

  function template_loader( $template ) {
      // テンプレートファイルの場所
      $template_dir = plugin_dir_path( __DIR__ ) . 'wp-geolib/templates/';
      if ( is_search() && 'geolib' == $_GET['s'] ) {
          // 探すべきファイル名
          $file_name  = 'search-geolib.php';
      } elseif ( is_singular( 'geolib' ) ) {
          $file_name  = 'single-geolib.php';
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

}
$exmeta = new WpGeolib;