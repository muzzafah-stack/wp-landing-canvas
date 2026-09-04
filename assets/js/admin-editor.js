/**
 * WP LandingCanvas — Admin Editor & CodeMirror Controller
 *
 * @package WP_LandingCanvas
 * @version 1.1.0
 */

/* global jQuery, wp, wplcSettings, YoastSEO */
(function ($) {
	'use strict';

	$(document).ready(function () {
		var editors = {};

		/**
		 * Helper to check if Canvas mode is enabled.
		 */
		function isCanvasEnabled() {
			return $('#wplc-enable-canvas').is(':checked');
		}

		/**
		 * Helper to get current HTML content (from CodeMirror or textarea).
		 */
		function getCanvasHtmlContent() {
			if (editors.html && editors.html.codemirror) {
				return editors.html.codemirror.getValue();
			}
			var $htmlArea = $('#wplc_html_content');
			return $htmlArea.length ? $htmlArea.val() : '';
		}

		/**
		 * Debounced notifier for active SEO plugins (Rank Math, Yoast SEO, etc.).
		 */
		var seoRefreshTimer = null;
		function triggerSeoRefresh() {
			clearTimeout(seoRefreshTimer);
			seoRefreshTimer = setTimeout(function () {
				// Rank Math real-time analysis refresh
				if (typeof wp !== 'undefined' && wp.hooks && typeof wp.hooks.doAction === 'function') {
					wp.hooks.doAction('rank_math_content_changed');
					wp.hooks.doAction('rank_math_refresh_content_analysis');
				}

				// Yoast SEO real-time analysis refresh
				if (typeof YoastSEO !== 'undefined' && YoastSEO.app && typeof YoastSEO.app.pluginReloaded === 'function') {
					YoastSEO.app.pluginReloaded('wplcSeoPlugin');
				}
			}, 300);
		}

		/**
		 * Register SEO bridge hooks for Rank Math and Yoast SEO.
		 */
		function initSeoBridge() {
			// 1. Rank Math Integration
			if (typeof wp !== 'undefined' && wp.hooks && typeof wp.hooks.addFilter === 'function') {
				wp.hooks.addFilter('rank_math_content', 'wplc', function (content) {
					if (isCanvasEnabled()) {
						var canvasHtml = getCanvasHtmlContent();
						return canvasHtml !== '' ? canvasHtml : content;
					}
					return content;
				});
			}

			// 2. Yoast SEO Integration
			function registerYoastPlugin() {
				if (typeof YoastSEO !== 'undefined' && YoastSEO.app && typeof YoastSEO.app.registerPlugin === 'function') {
					try {
						YoastSEO.app.registerPlugin('wplcSeoPlugin', { status: 'ready' });
						YoastSEO.app.registerModification('content', function (content) {
							if (isCanvasEnabled()) {
								var canvasHtml = getCanvasHtmlContent();
								return canvasHtml !== '' ? canvasHtml : content;
							}
							return content;
						}, 'wplcSeoPlugin', 5);
					} catch (e) {
						// Fail gracefully if already registered
					}
				}
			}

			if (typeof YoastSEO !== 'undefined' && YoastSEO.app && YoastSEO.app.isLoaded) {
				registerYoastPlugin();
			} else {
				$(window).on('YoastSEO:ready', registerYoastPlugin);
			}
		}

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
				if (editors.html && editors.html.codemirror) {
					editors.html.codemirror.on('change', function () {
						triggerSeoRefresh();
					});
				}
			}

			// Raw textarea change fallback
			$htmlArea.on('input change', function () {
				triggerSeoRefresh();
			});

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

			// Trigger SEO recalculation on mode switch
			triggerSeoRefresh();
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

		// Initialize all editors and SEO bridge on page load
		initCodeEditors();
		initSeoBridge();
	});
})(jQuery);
