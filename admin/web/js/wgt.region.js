/**
 * 区域选择器
 */
define(function(require, exports, module) {
	$.fn.region = function(options) {
		return this.each(function() {
			var region = new Region(this, options);
			$(this).data('region', region);
		});
	};

	$.fn.region.defaults = {
		'regions':[],
		'value':null,
		'selectLevel':'any',
		'emptyValue':'',
		'emptyName':null,
	};

	function Region(container, options) {
		options = $.extend({}, $.fn.region.defaults, options || {});
		var $container = $(container);
		var selects = $container.find('select');
		var $province = $(selects[0]);
		var $city = $(selects[1]);
		var $district = $(selects[2]);
		var $input = $container.find('input');

		var regions = options.regions;


		var init = function() {
			render();
			initEvent();
		};

		var render = function() {
			var province = null;
			var city = null;
			var district = null;
			var value = options.value == null ? '' : String(options.value);
			var path = value.split('/');

			if (path.length > 1) {
				province = path[0] || null;
				city = path[1] || null;
				district = path[2] || null;
			}
			else if (value && regions[value]) {
				province = value;
			}
			else if (value) {
				for (var provinceId in regions) {
					var cities = regions[provinceId].childs;
					if (cities[value]) {
						province = provinceId;
						city = value;
						break;
					}
					for (var cityId in cities) {
						var districts = cities[cityId].childs;
						if (districts[value]) {
							province = provinceId;
							city = cityId;
							district = value;
							break;
						}
					}
					if (province) {
						break;
					}
				}
			}

			setProvinces(province);
			setCities(city);
			setDistricts(district);
		};

		var setProvinces = function(select) {
			$province.html('');
			if (options.emptyName != null) {
				$province[0].add(new Option(options.emptyName, options.emptyValue), null);
			}
			$.each(regions, function(id, region) {
				$province[0].add(new Option(region.name, id), null);
			});

			if (select) {
				$province.val(select);
			}
		};

		var setCities = function(select) {
			$city.html('');
			if (options.emptyName != null) {
				$city[0].add(new Option(options.emptyName, options.emptyValue), null);
			}

			var provinceId = $province.val();
			if (provinceId != options.emptyValue && regions[provinceId]) {
				var cities = regions[provinceId].childs;
				$.each(cities, function (id, city) {
					$city[0].add(new Option(city.name, id), null);
				});
			}

			if (select) {
				$city.val(select);
			}
		};

		var setDistricts = function(select) {
			$district.html('');
			if (options.emptyName != null) {
				$district[0].add(new Option(options.emptyName, options.emptyValue), null);
			}

			var provinceId = $province.val();
			if (provinceId != options.emptyValue && regions[provinceId]) {
				var cities = regions[provinceId].childs;

				var cityId = $city.val();
				if (cityId != options.emptyValue && cities[cityId]) {
					var districts = cities[cityId].childs;
					$.each(districts, function (id, district) {
						$district[0].add(new Option(district.name, id), null);
					});
				}
			}

			if (select) {
				$district.val(select);
			}
		};

		var initEvent = function() {
			$province.on('change', function(){
				setCities();
				setDistricts();
				if (options.selectLevel == 'any') {
					$input.val($province.val());
				}
				else {
					$input.val('');
				}
				$input.trigger('blur');
			});

			$city.on('change', function() {
				setDistricts();
				if (options.selectLevel == 'any') {
					$input.val($province.val()+'/'+$city.val());
				}
				else {
					$input.val('');
				}
				$input.trigger('blur');
			});

			$district.on('change', function () {
				if (options.selectLevel == 'any') {
					$input.val($province.val()+'/'+$city.val()+'/'+$district.val());
				}
				else {
					$input.val($district.val());
				}
				$input.trigger('blur');
			});
		};

		var obj = {
		};

		init();

		return obj;
	};
});
