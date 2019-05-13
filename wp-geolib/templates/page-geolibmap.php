<!--
<link rel="stylesheet" href="https://geolib.nsb-dank.co.jp/wp-content/plugins/wp-geolib/css/leaflet.css" />
<script src="https://geolib.nsb-dank.co.jp/wp-content/plugins/wp-geolib/js/leaflet.js"></script>
-->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.3.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.3.4/dist/leaflet.js"></script>
<script src="https://geolib.nsb-dank.co.jp/wp-content/plugins/wp-geolib/js/L.TileLayer.BetterWMS.js"></script>
<script src="https://geolib.nsb-dank.co.jp/wp-content/plugins/wp-geolib/js/proj4.js"></script>
<?php
/*
Template Name: 地学ライブラリマップ
*/
get_header(); ?>
<div class="wrap">
	<h1>ライブラリマップ</h1>
	<div id="main"  style="float : left;width:27%;">
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
                	<th scope="col">表題</th>
            	</tr>
        	</thead>
          	<tbody>
			<?php
			require_once (  dirname(__FILE__) .'./../articles.php');
			if ( $the_query->have_posts() ) while ( $the_query->have_posts() ) :
				$the_query->the_post();
				$post_id = get_the_ID();
				$post_user = get_the_author();
				$title = get_the_title();
				$content = get_the_content();
				$article =geolib_get_article($post_id);

				$area_id = isset($article->area_id) ? $article->area_id : null;
				$level_id = isset($article->level_id) ? $article->level_id:null;
				$libraries[] = [
						'type' => 'Feature',
						'properties' => [
								'name' => $title,
								'popupContent' => '<a href='.get_the_permalink() .'><b>'.$title.'</b></a><br/>'.$content
						],
						'geometry'=>json_decode($article->geog)
				];
				?>
            	<tr>
                	<td><a href="<?=the_permalink() ?>" title="<?= the_content(); ?>"><b>【<?=the_title() ?>】</b></a></td>
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
	</div><!-- .content-area -->

	<!-- 地図の表示 -->
	<div id="map" style="float:right;width: 70%; height: 60%;">
	 	地図表示
	</div>
</div><!-- .wrap -->

<script type="text/javascript">

var lat = 139;
var lng = 36;

//背景地図タイルレイヤの設定
var t_std = new L.tileLayer('https://cyberjapandata.gsi.go.jp/xyz/std/{z}/{x}/{y}.png', {
    attribution: "&copy; <a href='http://maps.gsi.go.jp/development/ichiran.html#std' target='_blank'>国土地理院</a>",
    zIndex: 100
});
var t_ort = new L.tileLayer('https://cyberjapandata.gsi.go.jp/xyz/seamlessphoto/{z}/{x}/{y}.jpg', {
    attribution: "&copy; <a href='http://maps.gsi.go.jp/development/ichiran.html' target='_blank'>国土地理院</a>",
    zIndex: 200
});
var t_osm = new L.tileLayer('http://{s}.tile.osm.org/{z}/{x}/{y}.png', {
    attribution : "&copy; <a href='http://osm.org/copyright' target='_blank'>OpenStreetMap</a>",
    zIndex: 300
});

//地図表示の設定
var initcx = lat;
var initcy = lng;
var initZoomlv = 5;

var map = L.map('map', {
    center: [initcy, initcx],
    zoom: initZoomlv,
    keyboard: true,
    boxZoom: true,
    minZoom: 1,
    maxZoom: 18,
    doubleClickZoom: true,
    scrollWheelZoom: true,
    touchZoom: true,
    layers: [t_std]
});
//表示中心位置とズームレベル
map.setView([initcy, initcx], initZoomlv);

//ラベルの表示
var labelLayer = L.tileLayer.betterWms('https://gbank.gsj.jp/ows/seamlessgeology200k_b' ,{
   layers: 'label',
   format: 'image/png',
   transparent : true,
   opacity: 1.0,
   zIndex: 1000,
   attribution: '<a href="https://www.gsj.jp/license/index.html" target="_blank">GSJ, AIST</a>'
});

//地質図の表示
var detailLayer = L.tileLayer.betterWms('https://gbank.gsj.jp/ows/seamlessgeology200k_b' ,{
	layers: 'area',
	format: 'image/png',
	transparent : true,
	opacity: 0.7,
	zIndex: 2000,
	attribution: '<a href="https://www.gsj.jp/license/index.html" target="_blank">GSJ, AIST</a>'
})

//ラインの表示
var lineLayer = L.tileLayer.betterWms('https://gbank.gsj.jp/ows/seamlessgeology200k_b' ,{
	layers: 'line',
	format: 'image/png',
	transparent : true,
	opacity: 0.8,
	zIndex: 3000,
	attribution: '<a href="https://www.gsj.jp/license/index.html" target="_blank">GSJ, AIST</a>'
});

//ベースマップ
var Map_BaseLayer = {
    "地理院地図（標準地図）": t_std,
    "航空写真": t_ort,
    "OpenStreetmap": t_osm
};

// オーバーレイレイヤ
var Overlays = {
	'地質図 (基本版)': detailLayer,
	'地質図ライン': lineLayer,
	'地質図ラベル': labelLayer,
};
//レイヤコントロールを表示
L.control.layers(
	Map_BaseLayer ,Overlays,
	{ collapsed: true,
		position: 'topleft'  }
	).addTo(map);

//スケールコントロールを表示（オプションはフィート単位を非表示）
L.control.scale({ maxWidth: 250, imperial: false }).addTo(map);

//ライブラリのポリゴンを表示
var libraries = <?php echo json_encode($libraries); ?>;

L.geoJSON(libraries, {
        style:  { color: "#ff0000"}	,
    	onEachFeature: onEachFeature
	    }).addTo(map);

function onEachFeature(feature, layer) {
// does this feature have a name property?
	if (feature.properties) {
		layer.bindPopup(feature.properties.popupContent);
	}
}
</script>

