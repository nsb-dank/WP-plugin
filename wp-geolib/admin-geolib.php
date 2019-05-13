<?PHP
/*
 * geolib管理画面ファンクション
 */

/* ファイルアップロードに必要 */
function geolib_metabox_edit_form_tag() {
	echo ' enctype="multipart/form-data"';
}
add_action('post_edit_form_tag', 'geolib_metabox_edit_form_tag');

/*****************************************
 * カスタム投稿の編集画面メインカラムにボックスを追加
 *****************************************/
function geolib_add_article_box() {
	add_meta_box(
		'geolib_articlesection', 'ライブラリ詳細',  'geolib_box_callback', 'geolib'
			);
	add_meta_box(
		'geolib_foldersection','ライブラリフォルダ','geolib_folder_box_callback',	'geolib','side'
	);
}

/*****************************************
 * articleボックスのコンテンツを表示
 *      * @param WP_Post $post The object for the current post.
 *****************************************/
function geolib_box_callback( $post ) {
	wp_nonce_field(  'geolib_save_box_data', 'geolib_nonce' );
	// -------------------------------------------------
	//geolibデータ
	// -------------------------------------------------
	$post_id =$post->ID;
	$user_id = $post->post_author;
	// -------------------------------------------------
	// articleデータ
	// -------------------------------------------------
	$get_article = geolib_get_article($post->ID);

	$get_article = isset($get_article) ? $get_article : null;
	$meta_id = isset($get_article->meta_id) ? $get_article->meta_id : null;;
	$filepath = "/".$user_id."/".$meta_id;
	$post_id = isset($get_article->post_id) ? $get_article->post_id : null;
	$geog = isset($get_article->geog) ? $get_article->geog : null;
	$xmin =138;
	$xmax =140;
	$ymin =35;
	$ymax =37;
	if ($geog !=null){
		$array = json_decode($geog,true);
		//$array  = json_decode($obj['geog'],true);
		foreach ($array['coordinates'] as $arr)
		{
			$xmin = $arr[0][0];
			$xmax = $arr[2][0];
			$ymin=$arr[0][1];
			$ymax = $arr[2][1];
		}
	}
	$published = isset($get_article->published) ? $get_article->published:null;
	?>
	<!--  表示  -->

	<input type ="hidden" name ="meta_id" value="<?= $meta_id ?>"/>
	<table>
	<tr>
	<td rowspan="2">初期表示範囲</td>
	<td rowspan="2"><input type ="text"  name="xmin" value="<?= esc_attr($xmin) ?>" size = "10" /></td>
	<td><input type ="text"  name="ymax" value="<?= esc_attr($ymax) ?>" size = "10" /></td>
	<td rowspan="2"><input type ="text"  name="xmax" value="<?= esc_attr($xmax) ?>" size = "10" /></td>
	</tr>
	<tr>
		<td><input type ="text"  name="ymin" value="<?= esc_attr($ymin)  ?>" size = "10" /></td>
	</tr>
	</table>
	<input type ="hidden"  name="filepath" value="<?= esc_attr($filepath) ?>" size = "50" /><br/>
	<!--
	 <?php
	 if($published == "1"){
	 ?>
 		<input name="published" id="published"  type="checkbox" checked="checked" value="1" />
	 <?php
	 }else{
	 ?>
 		<input name="published" id="published"  type="checkbox" value="0" />
	 <?php
	 }
	 ?>
 -->
	<h4 style="width:100%; background-color: gold;">シナリオマップ（scenarioフォルダ）</h4>
	<?php
	// -------------------------------------------------
	 // シナリオマップデータ
	// -------------------------------------------------
	 if($get_article){
	 	$scenariomap_path = dirname(__FILE__) . '/files'.$filepath."/scenario/";
	 	$get_scenariomaps = geolib_get_maps($meta_id, 'Scenario');
	 	$get_scenariomaps = isset($get_scenariomaps) ? $get_scenariomaps : null;
	 	?>
    	<table style="width:100%;">
    		<tr>
    			<th><font color="maroon">削除</font></th>
    			<th>フォルダ名</th>
    			<th>名称</th>
    			<th>種類</th>
    			<th>ファイル</th>
    		</tr>
    	<?php
    	if ($get_scenariomaps){
    		$i=0;
    		foreach ($get_scenariomaps as $get_scenariomap){
    			$scenario_id = $get_scenariomap->id;
    			$scenario_foldername = $get_scenariomap->foldername;
    			$scenario_title = $get_scenariomap->title;
    			$scenario_sourcetype = $get_scenariomap->sourcetype;
    			$scenario_files = fileCount($scenariomap_path.$scenario_foldername.'/','*');
    			?>
    			<input type ="hidden" name ="scenario_id[]" value="<?= $scenario_id ?>"/>
    			<tr>
    				<td align="center">
    					<select  name="scenario_delflag[]" >
    						<option value="0"></option>
    						<option value="1">削除</option>
    					</select>
    				</td>
    				<td>
    					<input type ="hidden" name ="scenario_foldername[]" value="<?= $scenario_foldername ?>"/>
    					<?= $scenario_foldername ?></td>
    				<td><input type ="text" name="scenario_title[]" size = "25" value="<?= $scenario_title ?>"/></td>
    				<td><select name="scenario_sourcetype[]" value="<?= $scenario_sourcetype ?>">
    						<option value="GeoJSON"<?= $scenario_sourcetype  == 'GeoJSON' ? ' selected="selected"' : '';?>>GeoJSON</option>
    						<option value="geoTIFF"<?= $scenario_sourcetype  == 'geoTIFF' ? ' selected="selected"' : '';?>>geoTIFF</option>
    					</select>
    				</td>
    				<td><?= $scenario_files ?>ファイル<input name="scenario_map_<?=$i ?>[]" type="file"  multiple/></td>
    			</tr>
    			<?php
    			$i=$i+1;
    		}
    	}
    	?>
    		<tr>
    			<td align="center"><font color="navy">追加</font></td>
    			<td>
    			<input type ="text" name="new_scenario_foldername" size = "10" />
    			</td>
    			<td><input type ="text" name="new_scenario_title" size = "25" /></td>
    			<td><select name="new_scenario_sourcetype" >
    					<option value=""></option>
    					<option value="GeoJSON">GeoJSON</option>
    					<option value="geoTIFF">geoTIFF</option>
    					</select>
    			</td>
    			<td><!-- <input name="new_scenario_map[]" type="file"  multiple/> --></td>
    		</tr>
    	</table>
    	<?php
    }else{
    	?>
    	<div>シナリオマップを登録するには、一度 [公開]ボタンをクリックして保存 して下さい。</div>
    	<?php
    }
	?>

    <h4 style="width:100%; background-color: palegreen;">主題図（subjectフォルダ）</h4>
    <?php
    // -------------------------------------------------
    // 主題図データ
    // -------------------------------------------------
    if($get_article){
    	$subjectmap_path = dirname(__FILE__) . '/files'.$filepath."/subject/";
    	$get_subjectmaps = geolib_get_maps($meta_id, 'Subject');
    	$get_subjectmaps = isset($get_subjectmaps) ? $get_subjectmaps : null;
    	?>
    	<table style="width:100%;">
    		<tr>
    			<th><font color="maroon">削除</font></th>
    			<th>フォルダ名</th>
    			<th>名称</th>
    			<th>種類</th>
    			<th>ファイル</th>
    		</tr>
    		<?php
    	if ($get_subjectmaps){
    		$i=0;
    		foreach ($get_subjectmaps as $get_subjectmap){
    			$subject_id = $get_subjectmap->id;
    			$subject_foldername = $get_subjectmap->foldername;
    			$subject_title = $get_subjectmap->title;
    			$subject_sourcetype = $get_subjectmap->sourcetype;
    			$subject_files = fileCount($subjectmap_path.$subject_foldername.'/','*');
    			?>
    			<input type ="hidden" name ="subject_id[]" value="<?= $subject_id ?>"/>
    			<tr>
    				<td align="center">
    					<select  name="subject_delflag[]" >
    						<option value="0"></option>
    						<option value="1">削除</option>
    					</select>
    				</td>
    				<td>
    					<input type ="hidden" name ="subject_foldername[]" value="<?= $subject_foldername ?>"/>
    					<?= $subject_foldername ?></td>
    				<td><input type ="text" name="subject_title[]" size = "25" value="<?= $subject_title ?>"/></td>
    				<td><select name="subject_sourcetype[]" value="<?= $subject_sourcetype ?>">
    						<option value="GeoJSON"<?= $subject_sourcetype  == 'GeoJSON' ? ' selected="selected"' : '';?>>GeoJSON</option>
    						<option value="geoTIFF"<?= $subject_sourcetype  == 'geoTIFF' ? ' selected="selected"' : '';?>>geoTIFF</option>
    					</select>
    				</td>
    				<td><?= $subject_files ?>ファイル<input name="subject_map_<?= $i ?>[]" type="file"  multiple/></td>
    			</tr>
    		<?php
    		$i=$i+1;
    		}
    	}
    	?>
    		<tr>
    			<td align="center"><font color="navy">追加</font></td>
    			<td>
    			<input type ="text" name="new_subject_foldername" size = "10" />
    			</td>
    			<td><input type ="text" name="new_subject_title" size = "25" /></td>
    			<td><select name="new_subject_sourcetype" >
    					<option value=""></option>
    					<option value="GeoJSON">GeoJSON</option>
    					<option value="geoTIFF">geoTIFF</option>
    					</select>
    			</td>
    			<td><!-- <input name="new_subject_map[]" type="file"  multiple/> --></td>
    		</tr>
    	</table>
    	<?php
    }else{
    	?>
    	<div>主題図を登録するには、一度 [公開] ボタンをクリックして保存して下さい。</div>
    	<?php
    }
	?>

    <h4 style="width:100%; background-color: lightblue;">関連図（associatedフォルダ）</h4>
    <?php
    // -------------------------------------------------
    // 関連図データ
    // -------------------------------------------------
    if($get_article){
    	$associatedmap_path = dirname(__FILE__) . '/files'.$filepath."/associated/";
    	$get_associatedmaps = geolib_get_maps($meta_id, 'Associated');
    	$get_associatedmaps = isset($get_associatedmaps) ? $get_associatedmaps : null;
    	?>
    	<table style="width:100%;">
    		<tr>
    			<th><font color="maroon">削除</font></th>
    			<th>フォルダ名</th>
    			<th>名称</th>
    			<th>スクリプト</th>
    			<th>著者</th>
    		</tr>
    		<?php
    	if ($get_associatedmaps){
    		foreach ($get_associatedmaps as $get_associatedmap){
    			$associated_id = $get_associatedmap->id;
    			$associated_foldername = $get_associatedmap->foldername;
    			$associated_title = $get_associatedmap->title;
    			$associated_script = $get_associatedmap->script;
    			$associated_attribution = $get_associatedmap->attribution;
    			?>
    			<input type ="hidden" name ="associated_id[]" value="<?= $associated_id ?>"/>
    			<tr>
    				<td align="center">
    					<select  name="associated_delflag[]" >
    						<option value="0"></option>
    						<option value="1">削除</option>
    					</select>
    				</td>
    				<td>
    					<input type ="hidden" name ="associated_foldername[]" value="<?= $associated_foldername ?>"/>
    					<?= $associated_foldername ?></td>
    				<td><input type ="text" name="associated_title[]" size = "25" value="<?= $associated_title ?>"/></td>
    				<td><input type ="text" name="associated_script[]" size = "30" value="<?= esc_html($associated_script) ?>"/></td>
    				<td><textarea name="associated_attribution[]" size = "20" ><?= esc_html($associated_attribution) ?></textarea></td>
    			</tr>
    		<?php
    		}
    	}
    	?>
    		<tr>
    			<td align="center"><font color="navy">追加</font></td>
    			<td>
    			<input type ="text" name="new_associated_foldername" size = "10" />
    			</td>
    			<td><input type ="text" name="new_associated_title" size = "25" /></td>
    			<td><input type ="text" name="new_associated_script" size = "30" value=""/></td>
    			<td><textarea name="new_associated_attribution" size = "20" ></textarea></td>
    		</tr>
    	</table>
    	<?php
    }else{
    	?>
    	<div>関連図を登録するには、一度 [公開] ボタンをクリックして保存して下さい。</div>
    	<?php
    }
    ?>

    <h4 style="width:100%; background-color: pink;">アイコンファイル（iconフォルダ）</h4>
    <?php
    // -------------------------------------------------
    // シンボルファイル
    // -------------------------------------------------
    if($get_article){
    	$path = dirname(__FILE__) . '/files'.$filepath."/icon/";
    	?>
    	<div><?= fileCount($path, "*.svg"); ?>個のSVGファイルが登録されています。</div>
    	<div>シンボルファイル（SVG)を指定してください:
    		<input type="file" name="symbolfile[]"  id="symbolfile" onChange="printsymbolfile()"  multiple/>
    	</div>
    	    <div id="symbol_result"></div>
        <script>
            function printsymbolfile(){
                var fileList = document.getElementById("symbolfile").files;
                var list = "";
                for(var i=0; i<fileList.length; i++){
                list += fileList[i].name + "<br>";
                }
                document.getElementById("symbol_result").innerHTML = list;
            }
        </script>
    	<?php
    }else{
    	?>
    	<div>アイコンファイルを登録するには、一度 [公開] ボタンをクリックして保存して下さい。</div>
    	<?php
    }
    	?>

    <h4 style="width:100%; background-color: pink;">シナリオファイル（scenario/htmlフォルダ）</h4>
    <?php
    // -------------------------------------------------
    // シナリオファイル
    // -------------------------------------------------
    if($get_article){
    	$path = dirname(__FILE__) . '/files'.$filepath."/scenario/html/";
    	?>
    	<div><?=fileCount($path,'*') ?>'個のファイルまたはフォルダが登録されています。</div>
    	<div>シナリオ一式を格納したZipファイルを指定してください。:
    		<input type="file" name="scenariofile" id="scenariofile" onChange="printscenariofile()"  />
    	</div>
    	        <div id="scenario_result"></div>
        <script>
            function printscenariofile(){
                var fileList = document.getElementById("scenariofile").files;
                var list = "";
                for(var i=0; i<fileList.length; i++){
                list += fileList[i].name + "<br>";
                }
                document.getElementById("scenario_result").innerHTML = list;
            }
        </script>
<?php
    }else{
    	?>
    	<div>シナリオファイルを登録するには、一度 [公開] ボタンをクリックして保存して下さい。</div>
    	<?php
    }
}

    /*****************************************
     * folderボックスのコンテンツを表示
     *      * @param WP_Post $post The object for the current post.
     *****************************************/
    function geolib_folder_box_callback( $post ) {
    	$user_id = $post->post_author;
       	$get_article = geolib_get_article($post->ID);
    	$get_article = isset($get_article) ? $get_article : null;
    	if($get_article){
    		$meta_id = $get_article->meta_id;
    		$filepath = "/".$user_id."/".$meta_id;
    		$path = dirname(__FILE__) . '/files'.$filepath."/";
    		dispTree($path,$filepath);
    	}else{
    		echo '<div>データフォルダは作成されていません</div>';
    	}
   }

    /*****************************************
     * articlesテーブルデータの取得
     *****************************************/
    function geolib_get_article($post_id) {
        if (!is_numeric($post_id)) return;
        global $wpdb;
        $articles_table = $wpdb->prefix . 'articles';
        $get_article = $wpdb->get_results(
            $wpdb->prepare( "SELECT
					meta_id,
					post_id,
					user_id,
					ST_AsGeoJson(geom) as geog,
					filepath,
					published
				FROM  $articles_table  WHERE  post_id = %d", $post_id
            )
        );
        return isset($get_article[0]) ? $get_article[0] : null;
    }

    /*****************************************
     * mapsテーブルデータの取得
     *****************************************/
    function geolib_get_maps($meta_id,$map_type) {
    	if (!is_numeric($meta_id)) return;
    	global $wpdb;
    	$maps_table = $wpdb->prefix . 'maps';
    	$get_maps = $wpdb->get_results(
    			$wpdb->prepare( "SELECT * FROM  $maps_table
    					WHERE  meta_id = %d and maptype = %s
						ORDER By title", $meta_id,$map_type
    					)
    			);
    	return isset($get_maps) ? $get_maps : null;
    }


