$(function() {
    $.fn.checkBoxTree = function(options) {
        return this.each(function() {
            var tree = new CheckBoxTree(this, options);
            $(this).data('tree', tree);
        });
    };

    function CheckBoxTree(container, options) {
        var $tree = $(container);
        options.selectable = false;
        var disabled = (options.disabled)? ' disabled="disabled"':'';
        options.onCreateLi = function(node, $li) {
            var $title = $li.find('.jqtree-title');
            $title.html('<label class="form-check-label"><input class="form-check-input" type="checkbox"'+disabled+'>&nbsp;'+node.name+'</label>');

            if (node.leaf) {
                var html = [];
                html.push('<div class="jqtree-leaf">');
                $.each(node.leaf, function(i, item) {
                    var checked = (item.checked)? 'checked=" checked"':'';
                    html.push('<label>');
                    html.push('<input class="form-check-input" type="checkbox" name="'+options.checkboxName+'" value="'+item.id+'"'+checked+disabled+'>&nbsp;');
                    html.push(item.name);
                    html.push('</label>');
                });
                html.push('</div>');
                $title.after(html.join(''));
            }
        };

        $tree.tree(options);
        $tree.on('click', '.jqtree-title > label > input', function(e) {
            var $this = $(e.target);
            var $li = $this.closest('li');
            $li.find('input').prop('checked', $this.is(':checked'));
        });

        $tree.on('click', 'input', function(e) {
             if (options.onClick) {
                options.onClick(e);
            }
        });
    }


    $.fn.editTree = function(options) {
        return this.each(function() {
            var tree = new EditTree(this, options);
            $(this).data('tree', tree);
        });
    };

    function EditTree(container, options) {
        var $tree = $(container);
        options.onCreateLi = function(node, $li) {
        	$li.attr('id', node.id);
        	if (options.onClickTitle) {
        		$li.addClass('clickable');
        	}

            var html = [];
            html.push('<span>'+node.name+'</span>');
            
        	html.push('<span class="jqtree-title-edit">');
        	if (node.newBtn == undefined || node.newBtn == true) {
        		html.push('<a class="new"><i class="fa fa-plus"></i></a>');
        	}
        	if (node.editBtn == undefined || node.editBtn == true) {
        		html.push('<a class="edit"><i class="fa fa-pencil-square-o "></i></a>');
        	}
        	if (node.removeBtn == undefined || node.removeBtn == true) {
        		html.push('<a class="remove"><i class="fa fa-remove"></i></a>');
        	}
        	html.push('</span>');

            var $title = $li.find('.jqtree-title');
            $title.html(html.join(''));
        };

        $tree.tree(options);

        // 绑定单击事件
        $tree.on('click', '.jqtree-element', function(e) {
            var $this = $(e.target);
            var $li = $this.closest('li');
            var id = $li.attr('id');
            if (options.onClickTitle) {
            	options.onClickTitle(id);
            }
        });

        // 绑定新增按钮单击事件
        $tree.on('click', '.jqtree-title-edit > .new', function(e) {
            var $this = $(e.target);
            var $a = $this.closest('a');
            var $li = $a.parent().parent().parent().parent();
            var id = $li.attr('id');
            if (options.onClickNew) {
            	options.onClickNew(id);
            }
            e.stopPropagation();
        });

        // 绑定编辑按钮单击事件
        $tree.on('click', '.jqtree-title-edit > .edit', function(e) {
            var $this = $(e.target);
            var $a = $this.closest('a');
            var $li = $a.parent().parent().parent().parent();
            var id = $li.attr('id');
            if (options.onClickEdit) {
            	options.onClickEdit(id);
            }
            e.stopPropagation();
        });

        // 绑定删除按钮单击事件
        $tree.on('click', '.jqtree-title-edit > .remove', function(e) {
            var $this = $(e.target);
            var $a = $this.closest('a');
            var $li = $a.parent().parent().parent().parent();
            var id = $li.attr('id');
            if (options.onClickRemove) {
            	options.onClickRemove(id);
            }
            e.stopPropagation();
        });

        $tree.on('mouseover', 'li', function(e) {
            var $this = $(e.target);
            var $li = $this.closest('li');
            $li.addClass('jqtree-over');
            $li.children('.jqtree-element').find('.jqtree-title-edit').show();
        });
        $tree.on('mouseout', 'li', function(e) {
            var $this = $(e.target);
            var $li = $this.closest('li');
            $li.removeClass('jqtree-over');
            $li.children('.jqtree-element').find('.jqtree-title-edit').hide();
        });
    }
});
