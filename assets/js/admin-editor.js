/**
 * WP LandingCanvas — Admin Editor & CodeMirror Controller
 *
 * @package WP_LandingCanvas
 * @version 1.0.0
 */

/* global jQuery, wp, wplcSettings */
(function ($) {
	'use strict';

	$(document).ready(function () {
		var editors = {};

		/**
		 * Initialize WordPress Native CodeMirror on textareas.
		 */
		function initCodeEditors() {
			if (typeof wp === 'undefined' || !wp.codeEditor) {
				return;
			}

			// HTML Body Editor
			var $htmlArea = $('#wplc_html_content');
			if ($htmlArea.length) {
				editors.html = wp.codeEditor.initialize($htmlArea, wplcSettings.cmHtml || {});
			}

			// Custom CSS Editor
			var $cssArea = $('#wplc_custom_css');
			if ($cssArea.length) {
				editors.css = wp.codeEditor.initialize($cssArea, wplcSettings.cmCss || {});
			}

			// Head Scripts Editor
			var $headArea = $('#wplc_head_scripts');
			if ($headArea.length) {
				editors.head = wp.codeEditor.initialize($headArea, wplcSettings.cmJs || {});
			}

			// Footer Scripts Editor
			var $footerArea = $('#wplc_footer_scripts');
			if ($footerArea.length) {
				editors.footer = wp.codeEditor.initialize($footerArea, wplcSettings.cmJs || {});
			}
		}

		/**
		 * Toggle Switch Handler (Enable / Disable Landing Page Mode).
		 */
		$('#wplc-enable-canvas').on('change', function () {
			var isChecked = $(this).is(':checked');
			var $panel = $('#wplc-editor-panel');
			var $badge = $('#wplc-status-badge');

			if (isChecked) {
				$panel.removeClass('wplc-hidden');
				$badge.removeClass('wplc-badge-inactive').addClass('wplc-badge-active').text(wplcSettings.i18n.active);

				// Refresh visible active editor
				setTimeout(function () {
					refreshActiveEditor();
				}, 50);
			} else {
				$panel.addClass('wplc-hidden');
				$badge.removeClass('wplc-badge-active').addClass('wplc-badge-inactive').text(wplcSettings.i18n.disabled);
			}
		});

		/**
		 * Tab Switching Handler.
		 */
		$('.wplc-tab-btn').on('click', function (e) {
			e.preventDefault();
			var targetTab = $(this).data('tab');

			// Update Tab buttons
			$('.wplc-tab-btn').removeClass('active').attr('aria-selected', 'false');
			$(this).addClass('active').attr('aria-selected', 'true');

			// Update Tab Panels
			$('.wplc-tab-content').removeClass('active');
			var $targetPanel = $('#' + targetTab);
			$targetPanel.addClass('active');

			// Refresh CodeMirror instance in current tab
			setTimeout(function () {
				refreshActiveEditor();
			}, 50);
		});

		/**
		 * Helper to refresh active CodeMirror instance.
		 */
		function refreshActiveEditor() {
			var $activeTab = $('.wplc-tab-content.active');
			if (!$activeTab.length) {
				return;
			}

			var tabId = $activeTab.attr('id');
			if (tabId === 'tab-html' && editors.html && editors.html.codemirror) {
				editors.html.codemirror.refresh();
			} else if (tabId === 'tab-css' && editors.css && editors.css.codemirror) {
				editors.css.codemirror.refresh();
			} else if (tabId === 'tab-head' && editors.head && editors.head.codemirror) {
				editors.head.codemirror.refresh();
			} else if (tabId === 'tab-footer' && editors.footer && editors.footer.codemirror) {
				editors.footer.codemirror.refresh();
			}
		}

		// Initialize all editors on page load
		initCodeEditors();
	});
})(jQuery);
