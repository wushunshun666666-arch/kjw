$.fn.viewMap = function(options) {
	return this.each(function() {
		var map = new ViewMap(this, options);
		$(this).data('map', map);
	});
};

function ViewMap(container, options) {
	$container = $(container);
	var id = $container.attr('id');
	var markers = options.markers;
	var center = options.center;	

	//创建Map实例
	var map = new BMap.Map(id);

	var points = [];
	for(var i = 0; i < markers.length;i++){
		var point = new BMap.Point(markers[i].lng,markers[i].lat);
		 point['label'] = markers[i].label;
		points.push(point);
	}

	var mapZoom = 16;
	if(center == null){
		var view = map.getViewport(eval(points));
		mapZoom = view.zoom; 

		var centerPoint = view.center; 
	}else{
		points.push({
			"lng": center.lng,
			"lat": center.lat,
			"label": center.label,
		})

		var centerPoint = new BMap.Point(center.lng, center.lat);
	}

	map.centerAndZoom(centerPoint,mapZoom);
	addMarker(points);

	//在轨迹点上创建图标，并添加点击事件
	function addMarker(points){ 
		// 创建标注对象并添加到地图   
		for(var i = 0;i <points.length;i++){
			var	point = new BMap.Point(points[i].lng,points[i].lat);
			var	marker = new BMap.Marker(point);

			//如果有设置label的话就显示
			if(points[i].label){
					var  Label = new BMap.Label(points[i].label,{offset:new BMap.Size(1,-1),position:point});
					Label.setStyle({
							fontSize: "16px", //字号
							border: "0", //边
							height: "46px", //高度
							width: "136px", //宽
							textAlign: "center", //文字水平居中显示
							padding: "5px 0 0 0", //行高，文字垂直居中显示
							color: "#dd4b39",
					});
					marker.setLabel(Label);
			}

			map.addOverlay(marker);
			marker.setAnimation(BMAP_ANIMATION_BOUNCE);
		}
	}

	// 添加地图类型控件
	map.addControl(new BMap.MapTypeControl());
	// 开启鼠标滚轮缩放
	map.enableScrollWheelZoom(true);
}