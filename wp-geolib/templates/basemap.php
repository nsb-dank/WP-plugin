<!-- ベース地図表示スクリプト -->
<script type="text/javascript">
// Leaflet用地図表示スクリプト
//背景地図タイルレイヤの設定
var map = null;
var baseLayerIdsArray = []; // Leaflet専用
var currentBaseLayer = null; // Leaflet専用
var initialBaseLayer = null; // Leaflet専用
var baseLayer = []; // Leaflet専用
var baseLayerName = []; // Leaflet専用
var controlLayers = null; // Leaflet専用
var baseLayersArray = []; // Leaflet専用
var overlayLayersArray = []; // Leaflet専用

// 地理院タイル 標準地図
baseLayerIdsArray.push('GSIstd');
baseLayerName['GSIstd'] = "地理院地図";
baseLayer['GSIstd'] = L.tileLayer('//cyberjapandata.gsi.go.jp/xyz/std/{z}/{x}/{y}.png', {
	attribution: "<a href='http://maps.gsi.go.jp/development/ichiran.html' target='_blank'>地理院タイル</a>",
	errorTileUrl: "tile_nodata.gif"
});
//地理院タイル 淡色地図
baseLayerIdsArray.push('GSIpale');
baseLayerName['GSIpale'] = "地理院淡色地図";
baseLayer['GSIpale'] = L.tileLayer('//cyberjapandata.gsi.go.jp/xyz/pale/{z}/{x}/{y}.png', {
	attribution: "<a href='http://maps.gsi.go.jp/development/ichiran.html' target='_blank'>地理院タイル</a>",
	minNativeZoom: 2,
	errorTileUrl: "tile_nodata.gif"
});
// 地理院タイル 色別標高図
baseLayerIdsArray.push('GSIrelief');
baseLayerName['GSIrelief'] = "段彩陰影";
baseLayer['GSIrelief'] = L.layerGroup([
	L.tileLayer('//cyberjapandata.gsi.go.jp/xyz/std/{z}/{x}/{y}.png', {
		attribution: "<a href='http://maps.gsi.go.jp/development/ichiran.html' target='_blank'>地理院タイル</a>",
		maxZoom: 4,
		errorTileUrl: "tile_nodata.gif"
	}),
	L.tileLayer('//cyberjapandata.gsi.go.jp/xyz/relief/{z}/{x}/{y}.png', {
		attribution: "<a href='http://maps.gsi.go.jp/development/ichiran.html' target='_blank'>地理院タイル</a>",
		minZoom: 5,
		maxNativeZoom: 15,
		errorTileUrl: "tile_nodata.gif"
	})
]);
// 地理院タイル 航空写真
baseLayerIdsArray.push('GSIort');
baseLayerName['GSIort'] = "航空写真";
baseLayer['GSIort']= L.tileLayer('https://cyberjapandata.gsi.go.jp/xyz/seamlessphoto/{z}/{x}/{y}.jpg', {
    attribution: "&copy; <a href='http://maps.gsi.go.jp/development/ichiran.html' target='_blank'>国土地理院</a>",
    zIndex: 200
});
// Open Street Map
baseLayerIdsArray.push('OSM');
baseLayerName['OSM'] = "Open Street Map";
baseLayer['OSM'] = L.tileLayer('http://{s}.tile.osm.org/{z}/{x}/{y}.png', {
    attribution : "&copy; <a href='http://osm.org/copyright' target='_blank'>OpenStreetMap</a>",
    zIndex: 300
});

//シームレス地質図
baseLayerIdsArray.push('GSJ');
baseLayerName['GSJ'] = "シームレス地質図";
baseLayer['GSJ'] = L.tileLayer('https://gbank.gsj.jp/seamless/v2/api/1.2/tiles/{z}/{y}/{x}.png', {
    attribution : "&copy; <a href='https://gbank.gsj.jp/seamless/' target='_blank'>GSJ, AIST</a>"
});

//ベースマップ
baseLayersArray ={
	'地理院地図' : baseLayer['GSIstd'],
	'地理院淡色地図' : baseLayer['GSIpale'],
	'段彩陰影' : baseLayer['GSIrelief'],
	'航空写真' : baseLayer['GSIort'],
	'Open Street Map' : baseLayer['OSM']
};
initialBaseLayer = baseLayer['GSIstd']; //初期表示するベースマップ

overlayLayersArray ={
		'シームレス地質図' : baseLayer['GSJ'],
};


//地図表示の設定
var initcx = 139;
var initcy = 36;
var initZoomlv = 5;

var map_options = {
		center: [initcy, initcx],
		zoom: initZoomlv,
		keyboard: true,
		boxZoom: true,
		minZoom: 1,
		maxZoom: 18,
		doubleClickZoom: true,
		scrollWheelZoom: true,
		touchZoom: true,
		layers: [initialBaseLayer]
	};

map = L.map('map_canvas', map_options);

//表示中心位置とズームレベル
map.setView([initcy, initcx], initZoomlv);

//レイヤコントロールを表示
L.control.layers(
		baseLayersArray,overlayLayersArray,
		{ collapsed: true,
			position: 'topright'  }
	).addTo(map);

//スケールコントロールを表示（オプションはフィート単位を非表示）
L.control.scale({ maxWidth: 250, imperial: false }).addTo(map);


var sideBarVisibility = true;
var iconSideBar_right_close = "./wp-content/plugins/wp-geolib/css/images/arrow_left.png";
var iconSideBar_right_open = "./wp-content/plugins/wp-geolib/css/images/arrow_right.png";
var iconSideBar_bottom_close = "./wp-content/plugins/wp-geolib/css/images/arrow_bottom.png";
var iconSideBar_bottom_open = "./wp-content/plugins/wp-geolib/css/images/arrow_top.png";
setSideBarControl();
if (sideBarVisibility == true) {
	showSideBar(1);
} else {
	showSideBar(0);
}


//地図キャンバスの表示範囲をライブラリレイヤにする。
function setDispArea(polygon){
	var fl = L.geoJSON(polygon);
	map.fitBounds(fl.getBounds());
}

//パネル表示切替
function setSideBarControl() {
	var controlSideBar = L.control();
	controlSideBar.onAdd = function(map) {
		var customControlDiv_SideBar = document.createElement('DIV');
		customControlDiv_SideBar.style.padding = '2px';
		var customControlBox_SideBar = document.createElement('DIV');
		var sideBarControlHtml = "";
		sideBarControlHtml += '<a href=\"#\" onclick=\"showSideBar(); return false;\">';
		sideBarControlHtml += '<div style=\"background-color:#ffffff; padding:7px; border-radius:4px; box-shadow:0px 0px 2px 2px rgba(0,0,0,0.22);\">';
		if (sideBarVisibility == true) {
			sideBarControlHtml += '<img id=\"sidebar_control1\" class=\"iconSideBarRight\" src=\"' + iconSideBar_right_close + '\" title=\"凡例パネルを閉じる\" style=\"vertical-align:middle;\">';
			sideBarControlHtml += '<img id=\"sidebar_control2\" class=\"iconSideBarBottom\" src=\"' + iconSideBar_bottom_close + '\" title=\"凡例パネルを閉じる\" style=\"vertical-align:middle;\">';
		} else {
			sideBarControlHtml += '<img id=\"sidebar_control1\" class=\"iconSideBarRight\" src=\"' + iconSideBar_right_open + '\" title=\"凡例パネルを開く\" style=\"vertical-align:middle;\">';
			sideBarControlHtml += '<img id=\"sidebar_control2\" class=\"iconSideBarTop\" src=\"' + iconSideBar_bottom_open + '\" title=\"凡例パネルを開く\" style=\"vertical-align:middle;\">';
		}
		sideBarControlHtml += '</div>';
		sideBarControlHtml += '</a>';
		customControlBox_SideBar.innerHTML = sideBarControlHtml;
		customControlDiv_SideBar.appendChild(customControlBox_SideBar);
		this._div = customControlDiv_SideBar;
		return this._div;
	};
	controlSideBar.setPosition('topleft');
	controlSideBar.addTo(map);
}

function showSideBar(argVisibility) {
	var orderToShow = true;
	if (sideBarVisibility == true) {
		orderToShow = false;
	} else {
		orderToShow = true;
	}
	if (argVisibility == 0) {
		orderToShow = false;
	} else if (argVisibility == 1) {
		orderToShow = true;
	}
	var currentClassName = document.getElementById("map_canvas").className;
	if (orderToShow == true) {
		if ( document.getElementById("map_panel") ) {
			document.getElementById("map_canvas").className = currentClassName.replace('canvas_sideClosed','canvas_sideOpen');
			document.getElementById("map_panel").className = 'panel_sideOpen';
		}
		if ( document.getElementById("sidebar_control1") ) {
			document.getElementById("sidebar_control1").src = iconSideBar_right_close;
			document.getElementById("sidebar_control1").title = '凡例パネルを閉じる';
		}
		if ( document.getElementById("sidebar_control2") ) {
			document.getElementById("sidebar_control2").src = iconSideBar_bottom_close;
			document.getElementById("sidebar_control2").title = '凡例パネルを閉じる';
		}
		sideBarVisibility = true;
	} else {
		if ( document.getElementById("map_panel") ) {
			document.getElementById("map_canvas").className = currentClassName.replace('canvas_sideOpen','canvas_sideClosed');
			document.getElementById("map_panel").className = 'panel_sideClosed';
		}
		if ( document.getElementById("sidebar_control1") ) {
			document.getElementById("sidebar_control1").src = iconSideBar_right_open;
			document.getElementById("sidebar_control1").title = '凡例パネルを開く';
		}
		if ( document.getElementById("sidebar_control2") ) {
			document.getElementById("sidebar_control2").src = iconSideBar_bottom_open;
			document.getElementById("sidebar_control2").title = '凡例パネルを開く';
		}
		sideBarVisibility = false;
	}
	map.invalidateSize();
}
</script>