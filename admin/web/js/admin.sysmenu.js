/**
 * UI
 */
define(function(require, exports, module) {
	/**
	 * 系统菜单类
	 */
	function SysMenu(sidebar, threeLevelMenu, items, options) {
		var $sidebar = $(sidebar);
		var $threeLevelMenu = $(threeLevelMenu);
		var current = null;

		var init = function() {
			render();
			initEvents();
		};

		var render = function() {
			var html = [];
			renderItems(html, items);

			$sidebar.html(html.join(''));
		};

		var activateLink = function($link) {
			$sidebar.find('.nav-link.active').removeClass('active');
			$link.addClass('active');

			var $parentItem = $link.closest('.nav-treeview').closest('.nav-item');
			if ($parentItem.length) {
				$parentItem.addClass('menu-open').children('.nav-link').addClass('active');
			}
			current = $link;
		};

		var renderItems = function(html, items) {
			html.push('<ul class="nav sidebar-menu flex-column" data-lte-toggle="treeview" role="navigation">');
			$.each(items, function(id, item) {
				item.i = item.i || 'fa-circle-o';

				html.push('<li class="nav-item">');

				if (item.url) {
					html.push('<a class="nav-link" url="'+item.url+'" href="javascript:void(0);">');
				}
				else {
					html.push('<a class="nav-link" href="javascript:void(0);">');
				}
				html.push('<i class="nav-icon fa '+item.i+'"></i>');
				html.push('<p>'+item.label);
				if (item.items) {
					html.push('<i class="nav-arrow fa fa-angle-right"></i>');
				}
				html.push('</p>');
				html.push('</a>');

				if (item.items) {
					renderTwoLevelItems(html, item.items);
				}

				html.push('</li>');
			});

			html.push('</ul>');
		};

		var renderTwoLevelItems = function(html, items) {
			html.push('<ul class="nav nav-treeview">');
			$.each(items, function(id, item) {
				item.i = item.i || 'fa-circle-o';
				html.push('<li class="nav-item">');

				if (item.url) {
					html.push('<a class="nav-link" url="'+item.url+'" href="javascript:void(0);">');
				}
				else {
					html.push('<a class="nav-link" href="javascript:void(0);">');
				}
				html.push('<i class="nav-icon fa '+item.i+'"></i>');
				html.push('<p>'+item.label+'</p>');
				html.push('</a>');

				if (item.items) {
					renderThreeLevelItems(html, item.items);
				}

				html.push('</li>');
			});

			html.push('</ul>');
		};

		var renderThreeLevelItems = function(html, items) {
			html.push('<ul class="three-level-menu clearfix">');
			$.each(items, function(id, item) {
				html.push('<li>');

				if (item.url) {
					html.push('<span url="'+item.url+'">');
				}
				else {
					html.push('<span>');
				}
				html.push('<span>'+item.label+'</span>');
				html.push('</span>');

				html.push('</li>');
			});

			html.push('</ul>');
		};

		var initEvents = function() {
			$('li > a', $sidebar).on('click', function(e) {
				var $this = $(this);
				var checkElement = $this.next();

				// AdminLTE 4 owns opening and closing .nav-treeview through
				// data-lte-toggle="treeview". Business code only handles navigation.
				if (checkElement.is('.nav-treeview')) {
					e.preventDefault();
				}
				else {
					// 显示三级菜单
					if (checkElement.is('.three-level-menu')) {
						$threeLevelMenu.html(checkElement.clone());
						$threeLevelMenu.find('li span').on('click', function() {
							var $this = $(this);
							var url = $this.attr('url');
							if (url) {
								options.navigate(url);
							}
							$threeLevelMenu.find('li span.active').removeClass('active');
							$this.addClass('active');
						});
						$threeLevelMenu.find('li span').first().trigger('click');
						$threeLevelMenu.find('.three-level-menu').show();
					}
					else {
						var url = $this.attr('url');
						if (url) {
							options.navigate(url);
						}
						$threeLevelMenu.html('');
					}

					activateLink($this);
				}
			});
		};

		var locate = function(route) {
			var routePattern = new RegExp(route);
			var $currentMenu = null;
			$('ul.three-level-menu li span').each(function() {
				var url = $(this).attr('url');
				if (url && routePattern.test(url)) {
					$currentMenu = $(this);
					return false;
				}
			});
			if ($currentMenu) {
				var subMenu = $currentMenu.parents('ul').first();
				$currentMenu.addClass('active');
				var menu = subMenu.parents('ul').first();
				if (menu.is('.nav-treeview')) {
					var parent_li = menu.parents('li').first();
					parent_li.addClass('menu-open');
					parent_li.children('.nav-link').addClass('active');
					subMenu.closest('.nav-item').children('.nav-link').addClass('active');
					if (subMenu.is('.three-level-menu')) {
						$threeLevelMenu.html(subMenu.clone());
						$threeLevelMenu.find('li span').on('click', function() {
							var $this = $(this);
							var url = $this.attr('url');
							if (url) {
								options.navigate(url);
							}
							$threeLevelMenu.find('li span.active').removeClass('active');
							$this.addClass('active');
						});
						$threeLevelMenu.find('.three-level-menu').show();
					};
				}
			}
		};
		
		var collapse = function() {
			$('body').removeClass('sidebar-expanded-on-hover').addClass('sidebar-collapse');
		};

		init();

		return {
			locate: locate,
			collapse: collapse
		};
	}

	/**
	 * 导出接口
	 */
	exports.SysMenu = SysMenu;
});
