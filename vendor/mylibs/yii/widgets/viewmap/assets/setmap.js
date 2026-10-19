$.fn.setMap = function(options) {
	return this.each(function() {
		var map = new SetMap(this, options);
		$(this).data('map', map);
	});
};

function SetMap(container, options) {
	var $container = $(container);
	var id = $container.attr('id');
	var mapId = id+'_map';
	var searchInputId = id+'_suggestld';
	
	//创建Map实例
	var map = new BMap.Map(mapId);
	// 初始化地图，设置中心点坐标和地图级别
	var center = options.value.split(",");
	if(Math.abs(center[0]) > 0 && Math.abs(center[1]) > 0){
		var point = new BMap.Point(center[1],center[0]);
		// 创建标注并添加到地图中
		var marker = new BMap.Marker(point);
		map.addOverlay(marker);

		init(center[1],center[0]);
	}else{
		if(options.region){
			//如果之前的位置没有经纬度的话，就用加盟商的所属区域进行搜索
			searchMap(options.region);
		}else{
			//如没有区域，就用IP进行定位
			var myCity = new BMap.LocalCity();
			myCity.get(function(result){
				map.setCenter(result.name);
				init(result.center.lng,result.center.lat);
			});
		}
	}

	//建立一个自动完成的对象
	var auto = new BMap.Autocomplete(
		{
			"input":searchInputId,
			"location":map
		});

	//鼠标放在下拉列表上的事件
	auto.addEventListener("onhighlight",function(e){
		if(e.fromitem.index > -1){
			var value = e.fromitem.value;	
		}
		if(e.toitem.index > -1){
			var value = e.toitem.value;
		}

		var	region = value.province +  value.city +  value.district +  value.street +  value.business;
		searchMap(region);
	})

	//鼠标点击下拉列表后的事件
	auto.addEventListener("onconfirm",function(e){
		var  value = e.item.value;
		var region = value.province +  value.city +  value.district +  value.street +  value.business;
		searchMap(region);
	});

	//地图更改缩放级别结束时触发触发此事件
	map.addEventListener("zoomend", function(){
		var point = map.getCenter();
		init(point.lng,point.lat);
	});

	//停止拖拽地图时触发
	map.addEventListener('dragend', function(e){
		var point = map.getCenter();
		init(point.lng,point.lat);
	});

	//地图搜索
	function searchMap(region = ''){
		var ls = new BMap.LocalSearch(map);

		ls.setSearchCompleteCallback(function(res){
			if(ls.getStatus() == BMAP_STATUS_SUCCESS){
				var poi = res.getPoi(0);
				if(poi){
					init(poi.point.lng, poi.point.lat);
				}
			}
		});

		if(!region){
			var region = $('#suggestld').val();
		}

		ls.search(region);
	}

	//点击enter键。可以查询地图，但禁止enter键做其他事情，比如提交表单
	$(document).on('keydown', function(event){
		if(event.keyCode == 13){
			searchMap();
			return false;
		}
	});

	//如果init中有其他异步方法 isInit放到回调中处理
	function init(lng,lat){
		var point = new BMap.Point(lng, lat);
		map.centerAndZoom(point, 18);

		//给页面隐藏域表单赋值
		var input=document.getElementsByName(options.name)[0];
		input.value = lat+','+lng;

		// 添加地图类型控件
		map.addControl(new BMap.MapTypeControl());
		// 开启鼠标滚轮缩放
		map.enableScrollWheelZoom(true);
	}

}
