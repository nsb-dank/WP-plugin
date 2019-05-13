<link rel="stylesheet" href="<?=plugins_url() ?>/wp-geolib/css/mapStyle.css" />

<link rel="stylesheet" href="<?=plugins_url() ?>/wp-geolib/css/leaflet.css" />
<link rel="stylesheet" href="<?=plugins_url() ?>/wp-geolib/css/L.Control.HtmlLegend.css" />
<script src="<?=plugins_url() ?>/wp-geolib/js/leaflet.js"></script>
<!--
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.3.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.3.4/dist/leaflet.js"></script>
  -->
 <script src="//cdnjs.cloudflare.com/ajax/libs/chroma-js/1.3.4/chroma.min.js"></script>
 <script src="//d3js.org/d3.v4.min.js"></script>
 <script src="//npmcdn.com/geotiff@0.3.6/dist/geotiff.js"></script>

<script src="<?=plugins_url() ?>/wp-geolib/js/L.TileLayer.BetterWMS.js"></script>
<script src="<?=plugins_url() ?>/wp-geolib/js/proj4.js"></script>
<script src="<?=plugins_url() ?>/wp-geolib/js/leaflet.sld.js"></script>
<script src="<?=plugins_url() ?>/wp-geolib/js/L.Control.HtmlLegend.js"></script>
<script src="<?=plugins_url() ?>/wp-geolib/js/leaflet.rotatedMarker.js"></script>
<script src="https://ihcantabria.github.io/Leaflet.CanvasLayer.Field/dist/leaflet.canvaslayer.field.js"></script>
<?php
/**
 * The template for displaying geolib single posts
 * @package WordPress
 * @since 1.0
 * @version 1.0
 */

require_once (  dirname(__FILE__) .'/admin-geolib.php');
/* Start the Loop */
while ( have_posts() ) :
	the_post();
	$post_id = get_the_ID();
	$article =geolib_get_article($post_id);
	$title = get_the_title();

	$meta_id = $article->meta_id;
	$filepath = isset($article->filepath) ? $article->filepath:null;
	$published = isset($article->published) ? $article->published:null;

	$scenarioMaps = geolib_get_maps($meta_id,'Scenario');
	$subjectMaps = geolib_get_maps($meta_id,'Subject');
	$associatedMaps = geolib_get_maps($meta_id,'Associated');
endwhile; // End of the loop.
?>
<?php
//記事データレコードを取得
$libraryname = $title;
$abstract = get_the_content();
$dispArea[]= [
    'type' => 'Feature',
    'geometry'=>json_decode($article->geog)
];

//記事のフォルダ情報を取得
$folder = ABSPATH.'wp-content/plugins/wp-geolib/files'.$filepath;
$url = plugins_url().'/wp-geolib/files'.$filepath;
$scenarioFolder =  $folder.'/scenario/';
$subjectFolder =  $folder.'/subject/';
$associatedFolder =  $folder.'/associated/';
$scenarioUrl = $url.'/scenario/';
$subjectUrl = $url.'/subject/';
$associatedUrl =$url.'/associated/';
?>
<!-- ヘッダタイトルの表示 -->
<div id="map_title" style="height:auto;">
	<table>
		<tr>
			<td><h2>【<?= $title ?>】 </h2></td>
			<td align="right"><input value="前に戻る" onclick="history.back();" type="button"></td>
		</tr>
		<tr>
			<td colspan="2"><?= $abstract ?></td>
		</tr>
	</table>
</div>

<!-- 地図表示 -->
<div id="map_frame" style="height:80%;">
	<noscript><p>JavaScript対応ブラウザで表示してください。</p></noscript>
    <!-- 地図キャンバス表示 -->
	<div id="map_canvas" class="canvas_sideOpen">	</div>

<!-- ベース地図表示スクリプトを読み込み -->
<?php require_once(  dirname(__FILE__) .'/basemap.php'); ?>
<!-- Geolib 地図表示スクリプトを読み込み -->
<?php require_once(  dirname(__FILE__) .'/geolibmap.php'); ?>

<!-- 初期表示範囲を指定 -->
<?php
$initDisp ="<script type='text/javascript'>setDispArea(".json_encode($dispArea).")</script>";
echo   $initDisp;
?>

<!-- 凡例パネル表示 -->
	<div id="map_panel" class="panel_sideOpen">
    <div class="panel_title scenario">シナリオ</div>
    <div class="panel">
    <?php
    foreach ($scenarioMaps as $map){
        if($map->maptype=='Scenario'){
            $mapFolder = $scenarioFolder.$map->foldername.'/*';
            $maptitle = $map->title;
	        $mapId = $map->foldername;
	        $scenarioMapInit = "<script type ='text/javascript'>
                   var mapLayers = new L.LayerGroup();
                </script>";
	        echo $scenarioMapInit;

	        foreach((array)glob($mapFolder,GLOB_ERR) as $files){
            	$scenarioMapUrl = $scenarioUrl.$mapId."/";
                $fileName = basename($files);
	            $fileUrl = $scenarioMapUrl.$fileName;
	            $geocode = '<script type="text/javascript">setScenarioMapLayer("'.$fileUrl.'","'.$url.'")</script>';
	            echo   $geocode;
            }

            $scenarioMapDisp = "<script type ='text/javascript'>
                   mapLayer['".$mapId."'] = mapLayers;
                </script>";
            echo $scenarioMapDisp;
            ?>
	        <label><input type="checkbox" name="<?=$mapId?>" onclick='setLayerVisible(this.checked,"<?=$mapId?>","lg");'><span><?=$maptitle?></span></label>
	  		<div style="width:100%;"><input type="range"  name="<?=$mapId?>" 	oninput='changeLayerGroupOpacity(this.value,"<?=$mapId?>");'  min="0" max="100" value="50"></div>
	  	<?php
        }
    }?>
    </div>

	<div class="panel_title overlay">主題図</div>
	<div class="panel">
    <?php
    foreach ($subjectMaps as $map){
        if($map->maptype=='Subject'){
            $mapFolder = $subjectFolder.$map->foldername.'/';
            $maptitle = $map->title;
            $mapId = $map->foldername;
            // GeoJSON
            if ($map->sourcetype == 'GeoJSON'){
                $subjectMapInit = "<script type ='text/javascript'>
                        var mapLayers = new L.LayerGroup();
                        </script>";
                echo $subjectMapInit;
                foreach(glob($mapFolder.'{*.geojson}',GLOB_BRACE) as $subfolder){
                	$subjectMapUrl = $subjectUrl.$mapId.'/';
                    $file = basename($subfolder);
	               $fileUrl = $subjectMapUrl.$file;
	               $geocode = '<script type="text/javascript">setSubjectMapLayer("'.$fileUrl.'","'.$url.'")</script>';
	               echo   $geocode;
                }

                $subjectMapDisp = "<script type ='text/javascript'>;
                        mapLayer['".$mapId."'] = mapLayers;
                        </script>";
                echo $subjectMapDisp;
                ?>
	        	<label><input type="checkbox"  name="<?=$mapId?>" onclick='setLayerVisible(this.checked,"<?=$mapId?>","lg");'><span><?=$maptitle?></span></label>
	  			<div style="width:100%;"><input type="range"  name="<?=$mapId?>" 	oninput='changeLayerGroupOpacity(this.value,"<?=$mapId?>");'  min="0" max="100" value="50"></div>
	  			<?php

            // geoTiff
            }else if($map->sourcetype == 'geoTIFF'){
                foreach(glob($mapFolder.'{*.tif,*.tiff}',GLOB_BRACE) as $file){
                    //tiffファイルの情報を取得
                    $subjectMapUrl = $subjectUrl.$mapId."/";
                    $tifFile = basename($file);
                    $tifPath =$mapFolder.$tifFile;
                    $tifUrl = $subjectMapUrl.$tifFile;
                    //pngファイルの情報をセット
                    $reg="/(.*)(?:\.([^.]+$))/";
                    preg_match($reg,$tifFile,$retArr);
                    $pngFile = $retArr[1].".png";
                    $pngPath = $mapFolder.$pngFile;
                    $pngUrl = $subjectMapUrl.$pngFile;
                    // pngファイルがない場合は、ImageMagickで作成する。
                    if (!file_exists($pngPath)){
                        exec('C:\xampp\ImageMagick\bin\magick '.$tifPath.' '.$pngPath);
                    }
                    $geocode = "<script type='text/javascript'>
                        d3.request('".$tifUrl."').responseType('arraybuffer').get(
                            function (error, tiffData) {
                                var geo = L.ScalarField.fromGeoTIFF(tiffData.response);
                                var layer = L.canvasLayer.scalarField(geo,{
                                    color: chroma.scale('Spectral').domain(geo.range)
                                 });
                                var bounds = layer.getBounds();
                                   console.log(bounds);
                            mapLayer['".$mapId."'] = L.imageOverlay('".$pngUrl."', bounds, {attribution:''});
                        });
                  		</script>";
                    echo   $geocode;
                }

                ?>
	        	<label><input type="checkbox"  name="<?=$mapId?>" onclick='setLayerVisible(this.checked,"<?=$mapId?>","l");'><span><?=$maptitle?></span></label>
	  			<div style="width:100%;"><input type="range"  name="<?=$mapId?>" 	oninput='changeLayerOpacity(this.value,"<?=$mapId?>");'  min="0" max="100" value="50"></div>
	  			<?php
            }
        }
    }?>
	</div>

	<div class="panel_title tile">関連図</div>
	<div class="panel">
    <?php
    foreach ($associatedMaps as $map){
        if($map->maptype=='Associated'){
            $maptitle = $map->title;
            $mapId = $map->foldername;
            $script = $map->script;
            $attribution = $map->attribution;

            $associatedMapDisp = "<script type ='text/javascript'>
                   mapLayer['".$mapId."'] = L.tileLayer('".$script."',
                     {attribution : '".$attribution."'});
                </script>";
            echo $associatedMapDisp;
            ?>
	        <label><input type="checkbox" name="<?=$mapId?>" onclick='setLayerVisible(this.checked,"<?=$mapId?>","l");'><span><?=$maptitle?></span></label>
	  		<div style="width:100%;">　- <input type="range"  name="<?=$mapId?>" 	oninput='changeLayerOpacity(this.value,"<?=$mapId?>");' min="0" max="100" value="50"> +</div>
	  	<?php
        }
    }?>
	</div>

	</div>
	<div style="clear:both;"><hr style="display:none;" /></div>
</div>
<!-- フッタ表示 -->
<div  align="center">Web地学ライブラリ</div>
