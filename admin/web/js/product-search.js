/**
 * 产品查询页
 * Created by chenzhf on 2016/1/23.
 */

define(function (require, exports) {
    function setCheckboxActive($this) {
        var $checkbox = $this.parents('.form-check');
        if ($this.is(':checked')) {
            $checkbox.addClass('text-orange');
        }
        else {
            $checkbox.removeClass('text-orange');
        }
    }

    function CatalogView(container, url, onclick) {
        var $container = $(container);
        $container.find('input').on('click', function() {
            setCheckboxActive($(this));

            var catalogIds = $container.find('input:checked').serialize();

            var ajax = require('admin.ajax');
            ajax.post(url, catalogIds, function (data) {
                if (data.success()) {
                    data = data.data();
                    onclick(data);
                }
            });
        });
    }

    function QueryNav(container, brandUrl, onclick, postFormName) {
        var $container = $(container);
        var init = function() {
            var $group = $('.group-brand');
            // 品牌选项绑定事件
            $group.find('input').on('click', function() {
                setCheckboxActive($(this));
                onclick();
            });

            $container.find('.group').each(function() {
                setMoreBtn($(this));

                var $items = $(this).find('.items');
                var $more = $(this).find('.more');
                var $less = $(this).find('.less');
                $more.on('click', function() {
                    $items.css('height', 'auto');
                    $items.css('overflow', 'visible');
                    $(this).hide();
                    $less.show();
                });
                $less.on('click', function() {
                    $items.css('height', '36px');
                    $items.css('overflow', 'hidden');
                    $(this).hide();
                    $more.show();
                });
            });
        };

        var setMoreBtn = function($group) {
            var $items = $group.find('.items');
            var $inner = $group.find('.items-inner');
            var $more = $group.find('.more');
            var $less = $group.find('.less');

            $items.css('height', '36px');
            $items.css('overflow', 'hidden');
            $less.hide();

            var itemsHeight = $items.height();
            var innerHeight = $inner.height();
            if (innerHeight > itemsHeight) {
                $more.show();
            }
            else {
                $more.hide();
            }
        };

        var renderItems = function($group, items, inputName, checked, emptyText) {
            var $items = $group.find('.items');
            var $inner = $items.find('.items-inner');
            var checkedHtml = checked? 'checked="true"' : '';
            var active = checked? 'text-orange' : '';
            $inner.html('');
            var html = [];
            $.each(items, function(i, item) {
                html.push('<div class="form-check '+active+'">');
				html.push('<label class="form-check-label">');
				html.push('<input class="form-check-input" type="checkbox" value="'+item.id+'" '+checkedHtml+'name="'+inputName+'">');
                html.push(item.name);
                html.push('</label>');
                html.push('</div>');
            });
            if (html.length == 0) {
                $inner.html('<span class="text">'+emptyText+'</span>');
            }
            else {
                $inner.html(html.join("\n"));
            }

            setMoreBtn($group);
        };

        var setCategories = function(categories) {
            // 渲染品类选项
            var $group = $('.group-category');
            renderItems($group, categories, postFormName + '[categoryId][]', true, '所有品类');

            // 品类选项绑定事件
            $group.find('input').on('click', function() {
                setCheckboxActive($(this));
                resetBrands();
                onclick();
            });

            // 获取品类对应的品牌，并设置品牌选项
            resetBrands();
            // 刷新列表
            onclick();
        };

        var resetBrands = function() {
            var $items = $('.group-category').find('.items');
            var categoryIds = $items.find('input:checked').serialize();
            getBrands(brandUrl, categoryIds, function(data) {
                setBrands(data);
            });
        };

        var setBrands = function(brands) {
            // 渲染品牌选项
            var $group = $('.group-brand');
            renderItems($group, brands, postFormName + '[brandId][]', false, '所有品牌');

            // 品牌选项绑定事件
            $group.find('input').on('click', function() {
                setCheckboxActive($(this));
                onclick();
            });
        };

        var getBrands = function(url, categoryIds, callback) {
            var ajax = require('admin.ajax');
            ajax.post(url, categoryIds, function (data) {
                if (data.success()) {
                    data = data.data();
                    callback(data);
                }
            });
        };

        init();
        return {
            setCategories : setCategories
        };
    }

    function categoryNavInit(options) {
        var defaults = {
            queryNavContainer : '.qry-nav',
            catalogContainer : '#catalog-view',
            postFormName : 'ProductSearchForm'
        };
        var params = $.extend({}, defaults, options || {});

        var queryNav = new QueryNav(params.queryNavContainer, params.brandUrl, params.clickReload, params.postFormName);
        var catalogView = new CatalogView(params.catalogContainer, params.categoriesUrl, function(data) {
            queryNav.setCategories(data);
        });
    }

    var carStyleQuery = null;

    var carStyleQueryInit = function (onclick, seriesData, searchUrl) {
        var component = {};

        component.carBrand = new CarBrand(component, onclick);
        component.carStyle = new CarStyle(component, onclick, seriesData, searchUrl);
        component.carYear  = new CarYear(onclick);
        component.carDisplacement = new CarDisplacement(onclick);

        component.reset = function() {
            component.carBrand.reset();
            component.carStyle.disable();
            component.carYear.disable();
            component.carDisplacement.disable();
        };

        component.reset();

        carStyleQuery = component;
    };

    var CarBrand = function (component, onclick) {
        var options = {
            label : '全部品牌'
        };
        var pop = new Popup('#btn-q-car-brand', options);

        var init = function () {
            // 车品牌首字母按钮
            pop.$pop.find('.spell').on('click', function () {
                display($(this).attr('value'));
            });

            //默认显示A字母品牌
            display('A');

            // 点击车品牌按钮
            var $a = pop.$pop.find('a');
            $a.on('click', function () {
                component.reset();

                pop.setVal($(this));
                pop.close();

                if (component.carStyle != null) {
                    component.carStyle.refactor($(this).attr('value'));
                }
            })
        };

        var display = function (spell) {
            if (typeof spell == 'string') {
                //字母A-Z
                var letter = spell.substr(-1, 1);

                $('.spell').removeClass('cur');
                $('li[value="spell_'+ letter +'"]').addClass('cur');

                $('.li_spell').hide();
                $('.li_spell_' + letter).show();
            } else {
                //全部
                var $li = $('.spell_letter_all');
                $('.spell').removeClass('cur');
                $li.addClass('cur');

                $('.li_spell').hide();

                pop.setVal($li);
                component.reset();

                onclick();
            }
        };

        init();

        return {
            reset : pop.resetBtn,
            disable : pop.disableBtn
        }
    };

    var CarStyle = function (component, onclick, seriesData, searchUrl) {
        var options = {
            label : '全部车型',
            disableAlert : '请先选择品牌'
        };
        var pop = new Popup('#btn-q-car-series', options);

        var carYear = null;
        var carDisplacement = null;

        var refactor = function (brandId) {
            pop.resetBtn();
            pop.enableBtn();

            // 创建子选项
            buildItem(brandId);

            // 点击子选项
            var $a = pop.$pop.find('a');
            $a.on('click', function () {
                pop.setVal($(this));
                pop.close();

                var self = $(this);

                var ajax = require('admin.ajax');
                ajax.post(searchUrl, {seriesId: self.attr('value')}, function (data) {
                    if (data.success()) {
                        data = data.data();

                        if (component.carYear != null) {
                            component.carYear.refactor(data);
                        }

                        if (component.carDisplacement != null) {
                            component.carDisplacement.refactor(data);
                        }
                        onclick();
                    }
                });
            });
        };

        var buildItem = function (brandId) {
            var series = seriesData;

            var div = [];
            if (series.hasOwnProperty(brandId)) {
                var makers = series[brandId]['makers'];
                for (var i = 0; i < makers.length; i++) {
                    div.push('<div class="sub-car-maker">'
                        + '<div class="maker-title">'
                        + makers[i]['name'] + '</div><div class="clearfix"></div>');
                    var maker = makers[i];
                    for (var j = 0; j < maker['series'].length; j++) {
                        div.push('<a class="sub-car-series" value="' + maker['series'][j]['id'] + '">' + maker['series'][j]['name']);
                        div.push('</a>');
                    }
                    div.push('</div>');
                }
            }
            var html = div.join('');

            pop.$pop.children().remove();
            pop.$pop.append(html);
        };

        return {
            reset : pop.resetBtn,
            disable : pop.disableBtn,
            refactor : refactor
        }
    };

    var CarYear = function (onclick) {
        var options = {
            label : '全部年份',
            disableAlert : '请先选择品牌和车型'
        };
        var pop = new Popup('#btn-q-car-years', options);

        var refactor = function (data) {
            pop.resetBtn();
            pop.enableBtn();

            // 创建子选项
            buildItem(data);

            // 点击子选项
            var $a = pop.$pop.find('a');
            $a.on('click', function () {
                pop.setVal($(this));
                onclick();
            });

        };

        var buildItem = function(data) {
            var div = [];
            div.push('<a class="sub-car-years" value="">全部年份</a>');

            if(data.hasOwnProperty('years')) {
                for (var year in data.years) {
                    div.push('<a class="sub-car-years" value="'+ year +'">'+ year + '</a>');
                }
            }
            var html = div.join('');
            pop.$pop.children().remove();
            pop.$pop.append(html);
        };

        return {
            reset : pop.resetBtn,
            disable : pop.disableBtn,
            refactor : refactor
        }
    };

    var CarDisplacement = function (onclick) {
        var options = {
            label : '全部排量',
            disableAlert : '请先选择品牌和车型'
        };
        var pop = new Popup('#btn-q-car-displacements', options);

        var refactor = function (data) {
            pop.resetBtn();
            pop.enableBtn();

            // 创建子选项
            buildItem(data);

            // 点击子选项
            var $a = pop.$pop.find('a');
            $a.on('click', function () {
                pop.setVal($(this));
                onclick();
            });
        };

        var buildItem = function(data) {
            var div = [];
            div.push('<a class="sub-car-displacements" value="">全部排量</a>');

            if(data.hasOwnProperty('displacements')) {
                for (var displacement in data.displacements) {
                    div.push('<a class="sub-car-displacements" value="'+ displacement +'">'+ displacement + '</a>');
                }
            }
            var html = div.join('');
            pop.$pop.children().remove();
            pop.$pop.append(html);
        };

        return {
            reset : pop.resetBtn,
            disable : pop.disableBtn,
            refactor : refactor
        }
    };

    var Popup = function(btn, options) {
        var $btn = $(btn);

        var $li = $btn.parent();
        var $pop = $li.find('.popup');
        var $input = $li.find('input');
        var $btnLabel = $btn.find('span');
        var $preItem = null;

        var defaults = {
            label : '全部',
            disableAlert : ''
        };
        var params = $.extend({}, defaults, options || {});

        var showDisableAlert = function () {
            var ui = seajs.require('admin.ui');
            ui.errorBox(params.disableAlert);
        };

        var init = function () {
            enableBtn();

            $pop.on('click', function(e){
                e.stopPropagation();
            });

            $(document).on('click', function(e){
                if($(e.target).parent().is($btn)) {
                    if (!isBtnEnable()) {
                        showDisableAlert();
                        return;
                    }
                    show();
                } else {
                    close();
                }
            });
        };

        var show = function () {
            $pop.show();
        };

        var close = function () {
            $pop.hide();
        };

        var enableBtn = function () {
            $btn.removeClass('disabled');
        };

        var disableBtn = function () {
            $btn.addClass('disabled');
            $input.attr('value', '');
            $btnLabel.html(params.label);
            $btnLabel.attr('title', params.label);
            clearPreItem();
        };

        var isBtnEnable = function () {
            return !$btn.hasClass('disabled');
        };

        var resetBtn = function () {
            $input.attr('value', '');
            $btnLabel.html(params.label);
            $btnLabel.attr('title', params.label);
            clearPreItem();
        };

        var setVal = function ($tag) {
            clearPreItem();

            var val = $tag.attr('value');
            $input.attr('value', (typeof val == 'string') ? val : '');
            $btnLabel.html($tag.text());
            $btnLabel.attr('title', $tag.text());

            //置关键字搜索框为空
            $('#productsearchform-keyword').val('');

            $tag.addClass('cur');
            $preItem = $tag;

            close();
        };

        var clearPreItem = function () {
            if ($preItem != null) {
                $preItem.removeClass('cur');
            }
        };

        init();

        return {
            $pop : $pop,
            show : show,
            close : close,
            setVal : setVal,
            resetBtn : resetBtn,
            disableBtn : disableBtn,
            enableBtn : enableBtn
        }
    };

    var carStyleQueryReset = function () {
        if (carStyleQuery != null) {
            carStyleQuery.reset();
        }
    };

    function sortInit (onclick, asc, desc) {
        var resort = function ($btn) {

            var $inputs = $('.sort-bar').find('input'),
                $sortInput = $inputs.eq(0),
                $orderInput = $inputs.eq(1);

            var ascIcon  = '<i class="fa fa-sort-amount-asc"></i>',
                descIcon = '<i class="fa fa-sort-amount-desc"></i>';

            $btn.parent().find('i').remove();
            $btn.parent().find('.sort').removeClass('active');

            var thisVal = $btn.attr('value');

            if ($sortInput.attr('value') == thisVal) {
                if ($orderInput.val() == asc) {
                    $orderInput.val(desc);
                    $btn.append(descIcon);
                }
                else {
                    $orderInput.val(asc);
                    $btn.append(ascIcon);
                }
            }
            else {
                $orderInput.val(desc);
                $btn.append(descIcon);
            }

            $sortInput.attr('value', thisVal);

            $btn.addClass('active');
            onclick();
        };

        //注册点击事件
        $('.sort').on('click', function () {
            resort($(this));
        });
    }

    exports.sortInit = sortInit;

    exports.carStyleQueryInit = carStyleQueryInit;
    exports.carStyleQueryReset = carStyleQueryReset;

    exports.categoryNavInit = categoryNavInit
});
