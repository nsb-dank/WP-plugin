<?PHP
/*
 * geolibテーブル作成・データ更新ファンクション
 */
/******************************************
  * articlesおよびmapsテーブルの追加
  *****************************************/
function geolib_table_create() {
	global $wpdb;
	// 接頭辞（wp_）を付けてテーブル名を設定
	$articles_table = $wpdb->prefix . 'articles';
	$maps_table = $wpdb->prefix . 'maps';
	$geolib_db_version = '0.2';

	//現在のDBバージョン取得
	$installed_ver = get_option( 'geolib_meta_version' );

	// テーブルのバージョンが違ったら作成
	if( $installed_ver != $geolib_db_version ) {
		// articlesテーブルの作成
            $sql_articles = "CREATE TABLE " . $article_table . " (
                meta_id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                post_id bigint(20) UNSIGNED DEFAULT '0' NOT NULL,
                user_id bigint(20) UNSIGNED DEFAULT '0' NOT NULL,
                geom geometry DEFAULT NULL,
                filepath varchar(255) DEFAULT NULL,
                published tinyint(1) NOT NULL DEFAULT '0',
                UNIQUE KEY meta_id (meta_id)
            )
            CHARACTER SET 'utf8';";
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql_articles);

            // mapsテーブルの作成
            $sql_maps = "CREATE TABLE " . $maps_table . " (
                id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                meta_id bigint(20) UNSIGNED DEFAULT '0' NOT NULL,
                maptype varchar(30) NOT NULL,
                title varchar(255) NOT NULL,
                sourcetype varchar(30) DEFAULT NULL,
                foldername varchar(255) DEFAULT NULL,
                script text,
                attribution varchar(255) DEFAULT NULL,
                created timestamp NOT NULL ,
                modified timestamp NULL,
                UNIQUE KEY id (id)
            )
            CHARACTER SET 'utf8';";
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql_maps);

            update_option('geolib_meta_version', $geolib_db_version);
        }
    }

/*****************************************
 * データの作成・更新
 *  @param int $post_id The ID of the post being saved.
 *****************************************/
function geolib_save_box_data($post_id) {
	/*
	 * save_postアクションは他の時にも起動する場合があるので、
	 * 先ほど作った編集フォームのから適切な認証とともに送られてきたデータかどうかを検証する必要がある。
	 */
	// nonceがセットされているかどうか確認
	if ( ! isset( $_POST['geolib_nonce'] ) ) {
		return;
	}
	// nonceが正しいかどうか検証
	if ( ! wp_verify_nonce( $_POST['geolib_nonce'], 'geolib_save_box_data' ) ) {
		return;
	}

	// 自動保存の場合はなにもしない
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	// ユーザー権限の確認
	if ( isset( $_POST['post_type'] ) && 'page' == $_POST['post_type'] ) {
		if ( ! current_user_can( 'edit_page', $post_id ) ) {
			return;
		}
	} else {
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
	}

	global $wpdb;
	global $post;

	/**** articleデータの追加・更新 ****/
	$articles_table = $wpdb->prefix . 'articles';
	//リビジョンを残さない
	if ($post->ID != $post_id) return;
	$user_id = $_POST['post_author'];
	$meta_id = isset($_POST['meta_id']) ? $_POST['meta_id'] : 0;
	$geom = "POLYGON(("
		.$_POST['xmin']." ".$_POST['ymin'].","
		.$_POST['xmin']." ".$_POST['ymax'].","
		.$_POST['xmax']." ".$_POST['ymax'].","
		.$_POST['xmax']." ".$_POST['ymin'].","
		.$_POST['xmin']." ".$_POST['ymin']."))";
	$filepath = isset($_POST['filepath']) ?  sanitize_text_field( $_POST['filepath']) : null;
	$published = isset($_POST['published']) ?  1 : 0;

	//articleレコードを取得
	$get_id = $wpdb->get_var(
		$wpdb->prepare( "SELECT post_id FROM $articles_table WHERE  post_id = %d", $post_id)
		);
		//レコードがあったら更新なかったら新規追加
	if ($get_id) {
		$wpdb->show_errors();
		$wpdb->query(
			$wpdb->prepare(
				"UPDATE $articles_table
				 SET
				user_id =$user_id,
				geom=ST_PolygonFromText('$geom',4326),
				filepath ='$filepath',
				published =$published
				 WHERE post_id =%d",$post_id
			)
		);
		// フォルダ作成
		$path =  dirname(__FILE__) . '/files/'.$user_id.'/'.$meta_id;
		mkdir($path,0777,TRUE);
		chmod($path, 0777);
	} else {
		$set_arr['post_id'] = $post_id;
		$wpdb->query(
			$wpdb->prepare(
				"INSERT INTO $articles_table
				(	post_id,
						user_id,
						geom,
						filepath,
						published	)
					VALUES
				(	$post_id,
						$user_id,
						ST_PolygonFromText('$geom',4326),
						'$filepath',
						$published)"
			)
		);
	}
	$wpdb->print_error();

	/**** mapsデータの作成・更新 *****/
	$maps_table = $wpdb->prefix . 'maps';

	// -------------------------------------------------
	// scenarioデータ
	// -------------------------------------------------
	$i=0;
	if (isset( $_POST['scenario_id'])) {
		foreach($_POST['scenario_id'] as $scenario_id){
			//mapsレコードを取得
			$get_id = $wpdb->get_var(
					$wpdb->prepare( "SELECT id FROM $maps_table WHERE  id = %d", $scenario_id)
			);
			//レコードがあったら更新または削除
			if ($get_id) {
				$scenario_delete =$_POST['scenario_delflag'][$i];
				$scenario_foldername=$_POST['scenario_foldername'][$i];
				$scenario_title = $_POST['scenario_title'][$i];
				$scenario_sourcetype = $_POST['scenario_sourcetype'][$i];
				$wpdb->show_errors();
				if(!$scenario_delete == "1"){
					// 削除フラグがチェックされていなかったら更新
					$wpdb->query(
					$wpdb->prepare(
					"UPDATE $maps_table
					SET
					 title =%s,
					 sourcetype=%s
            		  WHERE id = %d",$scenario_title,$scenario_sourcetype,$get_id
            		  )
					);
					//ファイルアップロード
					$scenario_path =  dirname(__FILE__) . '/files/'.$user_id.'/'.$meta_id.'/scenario/'.$scenario_foldername;
					$scenario_map_name = 'scenario_map_'.$i;
					fileUpload($scenario_map_name,$scenario_path);
				}else{
				// 削除フラグがチェックされていたら削除
				$wpdb->query(
				$wpdb->prepare("DELETE FROM  $maps_table  WHERE id = %d", $get_id)
				);
				}
			}
			$i = $i +1;
		}
	}
	// 追加
	$new_scenario_foldername=isset($_POST['new_scenario_foldername']) ? $_POST['new_scenario_foldername'] : null;
	if(!empty($new_scenario_foldername)){
		$new_scenario_title = isset($_POST['new_scenario_title']) ? $_POST['new_scenario_title'] : null;
		$new_scenario_sourcetype = isset($_POST['new_scenario_sourcetype']) ? $_POST['new_scenario_sourcetype'] : null;
		$get_id = $wpdb->get_var(
			$wpdb->prepare( "SELECT id FROM $maps_table WHERE  meta_id = %d AND foldername = %s"
					,$meta_id, $new_scenario_foldername )
			);
		if (!$get_id){
			$wpdb->query(
				$wpdb->prepare(
						"INSERT INTO $maps_table
            		(	meta_id,
						maptype,
            			title,
            			sourcetype,
            			foldername	)
            		VALUES
            		(	$meta_id,
						'Scenario',
            			'$new_scenario_title',
            			'$new_scenario_sourcetype',
            			'$new_scenario_foldername')"
				)
			);
		}
		// フォルダ作成
		$path =  dirname(__FILE__) . '/files/'.$user_id.'/'.$meta_id.'/scenario/'.$new_scenario_foldername;
		mkdir($path,0777,TRUE);
		chmod($path, 0777);
	}

	// -------------------------------------------------
	//  subjectデータ
	// -------------------------------------------------
	$i=0;
	if (isset( $_POST['subject_id'])) {
		foreach($_POST['subject_id'] as $subject_id){
			//mapsレコードを取得
			$get_id = $wpdb->get_var(
					$wpdb->prepare( "SELECT id FROM $maps_table WHERE  id = %d", $subject_id)
					);
			//レコードがあったら更新または削除
			if ($get_id) {
				$subject_delete =$_POST['subject_delflag'][$i];
				$subject_foldername=$_POST['subject_foldername'][$i];
				$subject_title = $_POST['subject_title'][$i];
				$subject_sourcetype = $_POST['subject_sourcetype'][$i];
				$wpdb->show_errors();
				if(!$subject_delete == "1"){
					// 削除フラグがチェックされていなかったら更新
					$wpdb->query(
							$wpdb->prepare(
									"UPDATE $maps_table
					SET
					 title =%s,
					 sourcetype=%s
            		  WHERE id = %d",$subject_title,$subject_sourcetype,$get_id
									)
							);
					//ファイルアップロード
					$subject_path =  dirname(__FILE__) . '/files/'.$user_id.'/'.$meta_id.'/subject/'.$subject_foldername;
					$subject_map_name = 'subject_map_'.$i;
					fileUpload($subject_map_name,$subject_path);
				}else{
					// 削除フラグがチェックされていたら削除
					$wpdb->query(
							$wpdb->prepare("DELETE FROM  $maps_table  WHERE id = %d", $get_id)
							);
				}
			}
			$i = $i +1;
		}
	}
	// 追加
	$new_subject_foldername=isset($_POST['new_subject_foldername']) ? $_POST['new_subject_foldername'] : null;
	if(!empty($new_subject_foldername)){
		$new_subject_title = isset($_POST['new_subject_title']) ? $_POST['new_subject_title'] : null;
		$new_subject_sourcetype = isset($_POST['new_subject_sourcetype']) ? $_POST['new_subject_sourcetype'] : null;
		$get_id = $wpdb->get_var(
				$wpdb->prepare( "SELECT id FROM $maps_table WHERE  meta_id = %d AND foldername = %s"
						,$meta_id, $new_subject_foldername )
				);
		if (!$get_id){
			$wpdb->query(
					$wpdb->prepare(
							"INSERT INTO $maps_table
            		(	meta_id,
						maptype,
            			title,
            			sourcetype,
            			foldername	)
            		VALUES
            		(	$meta_id,
						'Subject',
            			'$new_subject_title',
            			'$new_subject_sourcetype',
            			'$new_subject_foldername')"
							)
					);
		}
		// フォルダ作成
		$path =  dirname(__FILE__) . '/files/'.$user_id.'/'.$meta_id.'/subject/'.$new_subject_foldername;
		mkdir($path,0777,TRUE);
		chmod($path, 0777);
	}

	// -------------------------------------------------
	// associatedデータ
	// -------------------------------------------------
	$i=0;
	if (isset( $_POST['associated_id'])) {
		foreach($_POST['associated_id'] as $associated_id){
			//mapsレコードを取得
			$get_id = $wpdb->get_var(
					$wpdb->prepare( "SELECT id FROM $maps_table WHERE  id = %d", $associated_id)
					);
			//レコードがあったら更新または削除
			if ($get_id) {
				$associated_delete =$_POST['associated_delflag'][$i];
				$associated_foldername=$_POST['associated_foldername'][$i];
				$associated_title = $_POST['associated_title'][$i];
				$associated_script = $_POST['associated_script'][$i];
				$associated_attribution = $_POST['associated_attribution'][$i];
				$wpdb->show_errors();
				if(!$associated_delete == "1"){
					// 削除フラグがチェックされていなかったら更新
					$wpdb->query(
							$wpdb->prepare(
									"UPDATE $maps_table
					SET
					 title =%s,
					 script=%s,
					 attribution=%s
            		  WHERE id = %d",$associated_title,$associated_script,$associated_attribution,$get_id
									)
							);
				}else{
					// 削除フラグがチェックされていたら削除
					$wpdb->query(
							$wpdb->prepare("DELETE FROM  $maps_table  WHERE id = %d", $get_id)
							);
				}
			}
			$i = $i +1;
		}
	}
	// 追加
	$new_associated_foldername=isset($_POST['new_associated_foldername']) ? $_POST['new_associated_foldername'] : null;
	if(!empty($new_associated_foldername)){
		$new_associated_title = isset($_POST['new_associated_title']) ? $_POST['new_associated_title'] : null;
		$new_associated_script = isset($_POST['new_associated_script']) ? $_POST['new_associated_script'] : null;
		$new_associated_attribution = isset($_POST['new_associated_attribution']) ? esc_html($_POST['new_associated_attribution']) : null;
		$get_id = $wpdb->get_var(
				$wpdb->prepare( "SELECT id FROM $maps_table WHERE  meta_id = %d AND foldername = %s"
						,$meta_id, $new_associated_foldername )
				);
		if (!$get_id){
			$wpdb->query(
					$wpdb->prepare(
							"INSERT INTO $maps_table
            		(	meta_id,
						maptype,
            			title,
            			foldername,
            			script,
            			attribution
					)
            		VALUES
            		(	$meta_id,
						'Associated',
            			'$new_associated_title',
            			'$new_associated_foldername',
            			'$new_associated_script',
            			'$new_associated_attribution')"
							)
					);
		}
		// フォルダ作成
		$path =  dirname(__FILE__) . '/files/'.$user_id.'/'.$meta_id.'/associated/'.$new_associated_foldername;
		mkdir($path,0777,TRUE);
		chmod($path, 0777);
	}
	$wpdb->print_error();

	//symbolファイルアップロード
	//if (isset( $_POST['symbolfile'])) {
		$symbol_path =  dirname(__FILE__) . '/files/'.$user_id.'/'.$meta_id.'/icon';
		mkdir($symbol_path,0777,TRUE);
		chmod($symbol_path, 0777);
		fileUpload("symbolfile",$symbol_path);
	//}

	//scenarioファイルアップロード
	//if (isset( $_POST['scenariofile'])) {
		$scenario_path =  dirname(__FILE__) . '/files/'.$user_id.'/'.$meta_id.'/scenario/html';
		zipFileUpload("scenariofile",$scenario_path);
	//}
}

     /*****************************************
     * articlesテーブルデータの削除
     *****************************************/
    function geolib_delete_article($post_id) {
        global $wpdb;
        $articles_table = $wpdb->prefix . 'articles';
        $wpdb->query( $wpdb->prepare( "DELETE FROM  $articles_table  WHERE post_id = %d", $post_id) );
    }
    /*****************************************
     * mapsテーブルデータの削除
     *****************************************/
    function geolib_dalete_map($meta_id) {
    	global $wpdb;
    	$maps_table = $wpdb->prefix . 'maps';
    	$wpdb->query( $wpdb->prepare( "DELETE FROM  $maps_table  WHERE meta_id = %d", $meta_id) );
    }

    /*****************************************
     * ファイルアップロード
     *****************************************/

