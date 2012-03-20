function bldg_listview_pagination_hooks(){

	$(document).keyup(function(e){

		var goto = function(selector){
			var href = $(selector).attr("href");

			if (href){
				location.href = href;
			}
		};

		if (e.which == 37){
			goto("#pagination_prev_link");
		}

		if (e.which == 39){
			goto("#pagination_next_link");
		}
	});
}

function bldg_listview_mouse_events(bldgs){

	var l = bldgs.length;

	for (var i=0; i < l; i++){

		var props = bldgs[i].properties;
		var selector = "#building_" + props.id;

		$(selector).mouseover(function(){
			var el = $(this)[0];
			var bid = "#" + el.id;
			var vid = bid.replace("building_", "vector_");
			_bldg_mouseover_building(bid, vid, null);
		});

		$(selector).mouseout(function(){
			var el = $(this)[0];
			var bid = "#" + el.id;
			var vid = bid.replace("building_", "vector_");
			_bldg_mouseout_building(bid, vid);
		});
	}
}

function bldg_modestmap(parent){

	if (! parent){
		parent = 'map';
	}

	var template = 'http://woe.spum.org/t/dithering/{Z}/{X}/{Y}.png';

	var provider = new com.modestmaps.TemplatedMapProvider(template);
	provider.setZoomRange(1, 18);

	var map = new com.modestmaps.Map(parent, provider);
	return map;
}

function bldg_map_building_with_nearby(bldg, nearby){

	var locations = new Array();

	var bldg = JSON.parse(bldg);
	var geom = bldg.geometry;

	var coords = geom.coordinates[0];
	var points = coords.length;

	for (var i=0; i < points; i++){
		var c = coords[i];
		var p = new com.modestmaps.Location(c[1], c[0]);
		locations.push(p);
	}

	//

	var count_nearby = nearby.length;

	for (var i=0; i < count_nearby; i++){

		nearby[i] = JSON.parse(nearby[i]);
		geom = nearby[i].geometry;

		var type = geom.type;
		var coords = geom.coordinates[0];
		var count_coords = coords.length;

		for (var j=0; j < count_coords; j++){
			var c = coords[j];
			var p = new com.modestmaps.Location(c[1], c[0]);
			locations.push(p);
		}
	}

	var map = bldg_modestmap();
	map.setExtent(locations);

	var mrk = new com.modestmaps.Markers(map);

	var bldg_more = {
		'attrs' : {
			'fill' : '#F778A1',
			'fill-opacity' :.6,
		},
		'no_mouse_events' : 1,
	};

	_bldg_draw_buildings(mrk, nearby);
	_bldg_draw_buildings(mrk, [bldg], bldg_more);
}

function bldg_map_buildings(bldgs, centroid){

	var count_bldgs = bldgs.length;

	var locations = new Array();
	var polys = new Array();
	var points = new Array();

	for (var i=0; i < count_bldgs; i++){

		bldgs[i] = JSON.parse(bldgs[i]);
		geom = bldgs[i].geometry;

		var type = geom.type;
		var coords = geom.coordinates[0];
		var count_coords = coords.length;

		for (var j=0; j < count_coords; j++){
			var c = coords[j];
			var p = new com.modestmaps.Location(c[1], c[0]);
			locations.push(p);
		}

		if (type == 'Polygon'){
			polys.push(bldgs[i]);
		}		

		else if (type == 'Point'){
			points.push(bldgs[i]);
		}
	}

	var map = bldg_modestmap();
	map.setExtent(locations);

	var mrk = new com.modestmaps.Markers(map);

	_bldg_draw_buildings(mrk, polys);

	if (map.getZoom() < 14){
		_bldg_draw_points(mrk, points);
	}
}

function _bldg_draw_points(mrk, points, more){

	var count_points = points.length;

	var underpainting = {
		'radius' : 6,
		'attrs' : {
			'fill' : 'rgb(250, 250, 210)',
			'fill-opacity' : 0,
			'stroke-width' : 8,
			'stroke' : 'rgb(255, 255, 255)',
		}
	};

	var geoms = new Array();

	for (var i=0; i < count_points; i++){
		geoms.push(points[i].geometry);
	}

	var geometry = {
		'type' : 'GeometryCollection',
		'geometries': geoms
	};

	mrk.drawGeoJson([{
		'geometry' : geometry,
	}], underpainting);

	var painting = {
		'radius' : 6,
		'attrs' : {
			'fill' : 'rgb(250, 250, 210)',
			'fill' : '#2B547E',
			'fill-opacity' : 1,
			'stroke-width' : 3,
			'stroke' : 'rgb(0, 0, 0)',
			'stroke' : '#95B9C7',
		},
		'onload' : _bldg_onthis_dothat,
	};

	for (var i=0; i < count_points; i++){
		mrk.drawGeoJson([
			points[i]
		], painting);
	}
}

function _bldg_draw_buildings(mrk, bldgs, more){

	var count_bldgs = bldgs.length;

	var underpainting = {
		'attrs' : {
			'fill' : 'rgb(250, 250, 210)',
			'fill-opacity' : 0,
			'stroke-width' : 9,
			'stroke' : 'rgb(255, 255, 255)',
		}
	};

	var geoms = new Array();

	for (var i=0; i < count_bldgs; i++){
		geoms.push(bldgs[i].geometry);
	}

	var geometry = {
		'type' : 'GeometryCollection',
		'geometries': geoms
	};

	mrk.drawGeoJson([{
		'geometry' : geometry,
	}], underpainting);

	var attrs = {
		'fill' : '#2B547E',
		'fill-opacity' : 1,
		'stroke-width' : 4,
		'stroke' : '#95B9C7',
	};

	if ((more) && (more['attrs'])){
		attrs = array_merge(attrs, more['attrs']);
	}

	var painting = {
		'attrs' : attrs,
	};

	if ((! more) || (! more['no_mouse_events'])){
		painting['onload'] = _bldg_onthis_dothat;
	}

	for (var i=0; i < count_bldgs; i++){
		mrk.drawGeoJson([bldgs[i]], painting);
	}
}

function _bldg_onthis_dothat(el, properties){

	el.node.setAttribute("id", "vector_" + properties.id);

	el.mouseout(function(e){
		var bid = "#building_" + properties.id;
		var vid = "#vector_" + properties.id;
		_bldg_mouseout_building(bid, vid);
	});

	el.mouseover(function(e){
		var bid = "#building_" + properties.id;
		var vid = "#vector_" + properties.id;
		_bldg_mouseover_building(bid, vid, 'scroll');
	});

	el.click(function(e){
		var permalink = '/id/' + properties.id;
		location.href = permalink;
	});
}

function _bldg_mouseout_building(bid, vid){
	$(bid).css("background-image", "none");
	$(vid).css("fill", "#2B547E");
}

function _bldg_mouseover_building(bid, vid, scroll){

	$(bid).css("background-image", "url(/images/dot.gif)");

	// why doesn't this work for <circle>s
	$(vid).css("fill", "#F778A1");

	if (scroll){
		$('html, body').animate({
			scrollTop: $(bid).offset().top
		}, 500);
	}
}
