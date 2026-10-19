$.fn.multiSelect2Side = function(options) {
    return this.each(function() {
        var multiSelect2Side = new MultiSelect2Side(this, options);
        $(this).data('multiSelect2Side', multiSelect2Side);
    });
};

function MultiSelect2Side(container, options) {
	$container = $(container);
	$select = $container.find('select');
	$selectable = $container.find('.ms-selectable > ul');
	$selection = $container.find('.ms-selection > ul');

	$selectable.find('li').on('click', function() {
		var $this = $(this);
		var value = $this.attr('value');
		$this.hide();
		$selection.find('li[value='+value+']').show();
		$select.children('option[value='+value+']').prop('selected', true);
	});
	$selection.find('li').on('click', function() {
		var $this = $(this);
		var value = $this.attr('value');
		$this.hide();
		$selectable.find('li[value='+value+']').show();
		$select.children('option[value='+value+']').prop('selected', false);
	});
}
