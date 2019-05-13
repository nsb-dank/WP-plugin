<!-- GeoJSON取得・表示 -->
<script type="text/javascript">//=======================================================
//スタイル付きGeoJSONのスタイルプロパティを設定
var mapLayers = new L.LayerGroup();
var scenarioLayers = new L.LayerGroup();
var subjectmap = [];
var scenariomap =[];
var associatedmap = [];
var mapLayer=[];

function geojson_style(prop, articleUrl) {
	var s = {};
	for(name in prop) {
		if(name.match(/^_/) && !name.match(/_markerType/)){
			if(prop[name] != null){
				if( prop['_markerType']=='Circle' && name =='_radius'){
					continue;
				}
				if(name == '_iconUrl'){
					prop[name] = articleUrl +'/icon/'+ prop[name]
					prop['_iconSize'] = [20,20]
					prop['_iconAnchor'] = [0,0]
				}
				s[name.substr(1)]=prop[name];
			}
		}
	}
	return s;
}

//スタイル付きGeoJSONのポップアッププロパティを設定
function popup_scenarioproperties(prop) {
	var s = ''
	for(name in prop) {
		if(!name.match(/^_/)){
			if(prop[name] != null){
				//s += name + "：" + prop[name] + "<br>";
				if(name == 'remarks'){s += prop[name] + "<br>"};
				if(name == 'filename'){s += "説明ファイル：" + prop[name] + "<br>"};
			}
		}
	}
	return s;
}

//スタイル付きGeoJSONのポップアッププロパティを設定
function popup_geoproperties(prop) {
	var s = ''
	for(name in prop) {
		if(!name.match(/^_/)){
			if(prop[name] != null){
				//s += name + "：" + prop[name] + "<br>";
				if(name == 'remarks'){s += prop[name] + "<br>"};
				if(name == 'filename'){s += "説明ファイル：" + prop[name] + "<br>"};
				if(name == 'strike_value'){s += "走向：" + prop[name] + "<br>"};
				if(name == 'dip_value'){s += "傾斜：" + prop[name] + "<br>"};
				if(name == 'legend01'){s += prop[name] + "<br>"};
				if(name == 'legend02'){s += prop[name] + "<br>"};
				if(name == 'legend03'){s += prop[name] + "<br>"};
				if(name == 'legend04'){s += prop[name] + "<br>"};
				if(name == 'legend05'){s += prop[name] + "<br>"};
				if(name == 'legend06'){s += prop[name] + "<br>"};
				if(name == 'legend07'){s += prop[name] + "<br>"};
			}
		}
	}
	return s;
}

//====================================
//地質図のスタイル付きGeoJSONを取得して表示
//====================================
function setSubjectMapLayer(fileUrl,articleUrl) {
	// fileUrl:スタイル付きGeoJSONファイルURL
	//console.log(fileUrl);
	// fileUrlファイル読み込み
	var xhr = new XMLHttpRequest();
	xhr.open('GET', fileUrl, false);
	xhr.send(null);
	var geodata = JSON.parse(xhr.responseText);
	//console.log(geodata);
	var geoLayer = L.geoJson(geodata, {
		pointToLayer: function (feature, latlng) {
			var s = geojson_style(feature.properties, articleUrl);
			if(feature.properties['_markerType']=='Icon'){
				var myIcon = L.icon(s);
				return L.marker(latlng, {icon: myIcon,rotationAngle:feature.properties['strike_value']});
			}
			if(feature.properties['_markerType']=='DivIcon'){
				var myIcon = L.divIcon(s);
				return L.marker(latlng, {icon: myIcon});
			}
			if(feature.properties['_markerType']=='Circle'){
				return L.circle(latlng,feature.properties['_radius'],s);
			}
			if(feature.properties['_markerType']=='CircleMarker'){
				return L.circleMarker(latlng,s);
			}
		},
		style: function (feature) {
			if(!feature.properties['_markerType']){
				var s = geojson_style(feature.properties, articleUrl);
				return s;
			}
		},
		onEachFeature: function (feature, layer) {
			layer.bindPopup(popup_geoproperties(feature.properties));
			//layer.bindTooltip(popup_properties(feature.properties));
		}
	});
	mapLayers.addLayer(geoLayer);
}

//====================================
//シナリオマップのスタイル付きGeoJSONを取得して表示
//====================================
function setScenarioMapLayer(fileUrl,articleUrl) {
	// fileUrl:スタイル付きGeoJSONファイルURL
	//console.log(fileUrl);
	// fileUrlファイル読み込み
	var xhr = new XMLHttpRequest();
	xhr.open('GET', fileUrl, false);
	xhr.send(null);
	var geodata = JSON.parse(xhr.responseText);
	//console.log(geodata);
	var sceLayer = L.geoJson(geodata, {
		pointToLayer: function (feature, latlng) {
			var s = geojson_style(feature.properties, articleUrl);
			if(feature.properties['_markerType']=='Icon'){
				var myIcon = L.icon(s);
				return L.marker(latlng, {icon: myIcon});
			}
			if(feature.properties['_markerType']=='DivIcon'){
				var myIcon = L.divIcon(s);
				return L.marker(latlng, {icon: myIcon});
			}
			if(feature.properties['_markerType']=='Circle'){
				return L.circle(latlng,feature.properties['_radius'],s);
			}
			if(feature.properties['_markerType']=='CircleMarker'){
				return L.circleMarker(latlng,s);
			}
		},
		style: function (feature) {
			if(!feature.properties['_markerType']){
				var s = geojson_style(feature.properties, articleUrl);
				return s;
			}
		},
		onEachFeature: function (feature, layer) {
			//layer.bindPopup(popup_properties(feature.properties));
			layer.bindTooltip(popup_scenarioproperties(feature.properties));
			layer.on('click', function (e) {window.open(articleUrl+"/scenario/html/"+feature.properties.filename,"window1","width=900,height=800,scrollbars=1");});
		}
	});
	mapLayers.addLayer(sceLayer);
}

//====================================
// チェックボックス制御
//====================================
/*
	function setScenarioMapVisible(value,mapId){
	if (value) {
		map.addLayer(scenariomap[''+mapId+'']);
	} else {
		map.removeLayer(scenariomap[''+mapId+'']);
	}
}
	function setSubjectMapVisible(value,mapId){
	if (value) {
		map.addLayer(subjectmap[''+mapId+'']);
	} else {
		map.removeLayer(subjectmap[''+mapId+'']);
	}
}
function setAssociatedMapVisible(value,mapId){
	if (value) {
		map.addLayer(associatedmap[''+mapId+''] );
	} else {
		map.removeLayer(associatedmap[''+mapId+'']);
	}
}
*/
function setLayerVisible(value,mapId,group){
	if (value) {
		map.addLayer(mapLayer[''+mapId+''] );
		if (gloup = 'lg'){
			changeLayerGroupOpacity('50',mapId)
		}else{
			changeLayerOpacity('50',mapId)
		}
	} else {
		map.removeLayer(mapLayer[''+mapId+'']);
	}
}

//====================================
// 透過度制御
//====================================
/*
 function changeScenarioMapOpacity(value,mapId){
	//scenariomap[''+mapId+''].setStyle({opacity: value / 100.0 });
    var layersInGroup = scenariomap[''+mapId+''].getLayers();
    layersInGroup.forEach(function( layer )  {
		//console.log(layer._leaflet_id);
        layer.setStyle({opacity: value / 100.0 , fillOpacity: value / 100.0});
 	});
}
function changeSubjectMapOpacity(value,mapId){
	//subjectmap[''+mapId+''].setOpacity(value / 100.0);
    var layersInGroup = subjectmap[''+mapId+''].getLayers();
    layersInGroup.forEach(function( layer )  {
		//console.log(layer._leaflet_id);
        layer.setStyle({opacity: value / 100.0 , fillOpacity: value / 100.0});
 	});
}
function changeAssociatedMapOpacity(value,mapId){
	associatedmap[''+mapId+''].setOpacity(value / 100.0);
}
*/

function changeLayerGroupOpacity(value,mapId){
	//subjectmap[''+mapId+''].setOpacity(value / 100.0);
    var layersInGroup = mapLayer[''+mapId+''].getLayers();
    layersInGroup.forEach(function( layer )  {
		//console.log(layer._leaflet_id);
        layer.setStyle({opacity: value / 100.0 , fillOpacity: value / 100.0});
 	});
}
function changeLayerOpacity(value,mapId){
	mapLayer[''+mapId+''].setOpacity(value / 100.0);
}

</script>