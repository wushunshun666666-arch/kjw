/**
 * 销售统计表格
 */
define(function(require, exports, module) {
	function SalesChart(canvas, data, label) {
		var panel = $(canvas).get(0);
		var $data = data;

		var createChart = function(config) {
			var current = Chart.getChart(panel);
			if (current) {
				current.destroy();
			}
			return new Chart(panel, config);
		};
		
		var getColor = function(i) {
			var colors = [
				{"color": "#F7464A", "highlight": "#FF5A5E", "alias": "red"},
				{"color": "#FDB45C", "highlight": "#FFC870", "alias": "yellow"},
				{"color": "#46BFBD", "highlight": "#5AD3D1", "alias": "green"},
				{"color": "#f56954", "highlight": "#f56954", "alias": "yellow"},
				{"color": "#00a65a", "highlight": "#00a65a", "alias": "green"},
				{"color": "#f39c12", "highlight": "#f39c12", "alias": "yellow"},
				{"color": "#00c0ef", "highlight": "#00c0ef", "alias": "yellow"},
				{"color": "#3c8dbc", "highlight": "#3c8dbc", "alias": "yellow"},
				{"color": "#d2d6de", "highlight": "#d2d6de", "alias": "yellow"}
			];
			
			i = i%colors.length;
			return colors[i];
		};
		
		var pieData = function() {
			var labels = [];
			var values = [];
			var colors = [];
			var highlights = [];
			var i = 0;
			$data.forEach(function($item) {
				var color = getColor(i++);
				labels.push($item.label);
				values.push($item.value);
				colors.push(color.color);
				highlights.push(color.highlight);
			});
			return {
				labels: labels,
				datasets: [{
					data: values,
					backgroundColor: colors,
					hoverBackgroundColor: highlights
				}]
			};
		};

		var lineData = function() {
			return {
				labels: $data.labels,
				datasets: [
					{
						label: label || '',
						borderColor: "rgba(0, 153, 255, 0.8)",
						backgroundColor: "rgba(0, 153, 255, 0.15)",
						pointBackgroundColor: "rgba(0, 153, 255, 1)",
						pointBorderColor: "#FFFFFF",
						pointHoverBackgroundColor: "#FFFFFF",
						pointHoverBorderColor: "rgba(0, 153, 255, 1)",
						borderWidth: 3,
						pointRadius: 4,
						fill: false,
						data: $data.items
					}
				]
			};
		};

		var barData = function() {
			return {
				labels: $data.labels,
				datasets: [
					{
						label: label || '',
						backgroundColor: "rgba(0, 153, 255, 0.8)",
						borderColor: "rgba(0, 153, 255, 1)",
						borderWidth: 1,
						data: $data.items
					}
				]
			};
		};

		var cartesianOptions = function(unit) {
			return {
				responsive: true,
				maintainAspectRatio: false,
				plugins: {
					legend: {display: Boolean(label)}
				},
				scales: {
					y: {
						grid: {display: false},
						ticks: {
							callback: function(value) {
								return value + (unit || '');
							}
						}
					}
				}
			};
		};

		var Line = function(unit) {
			return createChart({
				type: 'line',
				data: lineData(),
				options: cartesianOptions(unit)
			});
		};
		
		var Bar = function(unit) {
			return createChart({
				type: 'bar',
				data: barData(),
				options: cartesianOptions(unit)
			});
		};
		
		var Pie = function() {
			return createChart({
				type: 'pie',
				data: pieData(),
				options: {
					responsive: true,
					maintainAspectRatio: false
				}
			});
		};

		return {
			Line:Line,
			Bar:Bar,
			Pie:Pie
		};
	}
	
	exports.line = function(canvas, data, unit) {
		var chart = new SalesChart(canvas, data, unit);
		chart.Line(unit);
	};
	
	exports.bar = function(canvas, data, unit) {
		var chart = new SalesChart(canvas, data, unit);
		chart.Bar(unit);
	};
	
	exports.pie = function(canvas, data) {
		var chart = new SalesChart(canvas, data);
		chart.Pie();
	};
});
