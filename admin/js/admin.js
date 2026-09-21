/* CyberneticsNexa Chatbot – admin screen behaviour */
jQuery(function ($) {
	'use strict';

	/* ---------------------------------------------------------- Tabs */
	var $tabs   = $('.cnx-tabs .nav-tab');
	var $panels = $('.cnx-panel');
	var TAB_KEY = 'cnx_active_tab';

	function showTab(hash) {
		var $target = $panels.filter(hash);
		if (!$target.length) { hash = '#cnx-general'; $target = $panels.filter(hash); }
		$panels.hide();
		$target.show();
		$tabs.removeClass('nav-tab-active').filter('[href="' + hash + '"]').addClass('nav-tab-active');
		try { window.sessionStorage.setItem(TAB_KEY, hash); } catch (e) { /* ignore */ }
	}

	$tabs.on('click', function (e) {
		e.preventDefault();
		showTab($(this).attr('href'));
	});

	var startTab = window.location.hash;
	if (!startTab) {
		try { startTab = window.sessionStorage.getItem(TAB_KEY); } catch (e) { startTab = ''; }
	}
	showTab(startTab || '#cnx-general');

	/* ---------------------------------------------------------- Colour pickers */
	$('.cnx-color').wpColorPicker();

	/* ---------------------------------------------------------- Media picker */
	function setPreview($box, url) {
		var $prev = $box.find('.cnx-media-preview').empty();
		if (url) { $prev.append($('<img>').attr('src', url)); }
		$box.find('.cnx-media-remove').toggle(!!url);
	}

	function showError($box, msg) {
		$box.find('.cnx-media-error').text(msg || '').toggle(!!msg);
	}

	$(document).on('click', '.cnx-media-select', function (e) {
		e.preventDefault();
		var $box = $(this).closest('.cnx-media');
		showError($box, '');

		if (typeof wp === 'undefined' || !wp.media) {
			showError($box, mediaMissingMsg());
			return;
		}

		// One frame per picker, re-used on the next click.
		var frame = $box.data('cnxFrame');
		if (!frame) {
			frame = wp.media({
				title: CNX_ADMIN.chooseImage,
				button: { text: CNX_ADMIN.useImage },
				library: { type: 'image' },
				multiple: false
			});

			frame.on('select', function () {
				var att = frame.state().get('selection').first().toJSON();
				var url = (att.sizes && att.sizes.thumbnail) ? att.sizes.thumbnail.url : att.url;
				$box.find('.cnx-media-id').val(att.id);
				$box.find('.cnx-media-url').val('');   // library image wins over a pasted URL
				setPreview($box, url);
			});

			$box.data('cnxFrame', frame);
		}
		frame.open();
	});

	function mediaMissingMsg() { return CNX_ADMIN.mediaMissing; }

	// Pasting a URL replaces any library image.
	$(document).on('input change', '.cnx-media-url', function () {
		var $box = $(this).closest('.cnx-media');
		var url  = $.trim($(this).val());
		if (url) { $box.find('.cnx-media-id').val('0'); }
		setPreview($box, url);
	});

	$(document).on('click', '.cnx-media-remove', function (e) {
		e.preventDefault();
		var $box = $(this).closest('.cnx-media');
		$box.find('.cnx-media-id').val('0');
		$box.find('.cnx-media-url').val('');
		setPreview($box, '');
		showError($box, '');
	});

	/* ---------------------------------------------------------- Custom icon field visibility */
	function toggleCustomIcon() {
		$('.cnx-custom-icon').toggle($('#cnx-launcher_icon').val() === 'custom');
	}
	$('#cnx-launcher_icon').on('change', toggleCustomIcon);
	toggleCustomIcon();

	/* ---------------------------------------------------------- Quick replies repeater */
	var $rows   = $('#cnx-rows');
	var tpl     = $('#cnx-row-tpl').html();
	var counter = $rows.children('.cnx-row').length + 1000; // never collides with existing indexes

	$rows.sortable({
		handle: '.cnx-handle',
		items: '> .cnx-row',
		axis: 'y',
		placeholder: 'cnx-row-placeholder',
		forcePlaceholderSize: true
	});

	$('#cnx-add-row').on('click', function () {
		if ($rows.children('.cnx-row').length >= CNX_ADMIN.max) {
			window.alert(CNX_ADMIN.maxMsg);
			return;
		}
		var $row = $($.trim(tpl.replace(/__INDEX__/g, counter++)));
		$rows.append($row);
		$row.find('.cnx-title-input').trigger('focus');
	});

	$rows.on('click', '.cnx-remove', function (e) {
		e.preventDefault();
		$(this).closest('.cnx-row').slideUp(150, function () { $(this).remove(); });
	});

	$rows.on('change', '.cnx-type', function () {
		$(this).closest('.cnx-row').attr('data-type', $(this).val());
	});

	$rows.on('input', '.cnx-title-input', function () {
		var v = $.trim($(this).val());
		$(this).closest('.cnx-row').find('.cnx-row-title').text(v || CNX_ADMIN.newReply);
	});
});
