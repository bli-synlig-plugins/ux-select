(function () {
	"use strict";

	function inBuilder() {
		return !!document.querySelector("[data-uxs-builder]");
	}

	function panels(setId) {
		return document.querySelectorAll('[data-uxs-panel][data-uxs-set="' + setId + '"]');
	}

	function selects(setId) {
		return document.querySelectorAll('[data-uxs-select][data-uxs-set="' + setId + '"]');
	}

	function apply(setId, value) {
		selects(setId).forEach(function (select) {
			if (select.value !== value) {
				select.value = value;
			}
		});

		if (inBuilder()) {
			return;
		}

		panels(setId).forEach(function (panel) {
			var raw = panel.getAttribute("data-uxs-values") || panel.getAttribute("data-uxs-value") || "";
			var values = raw.split(/[\s,]+/).filter(Boolean);
			var on = values.indexOf(value) !== -1;
			panel.hidden = !on;
			panel.setAttribute("aria-hidden", on ? "false" : "true");
		});
	}

	function init() {
		var seen = {};
		document.querySelectorAll("[data-uxs-select]").forEach(function (select) {
			var setId = select.getAttribute("data-uxs-set");
			if (!setId || seen[setId]) {
				return;
			}
			seen[setId] = true;
			var initial = select.getAttribute("data-uxs-default");
			if (initial == null) {
				initial = select.value;
			}
			apply(setId, initial);
		});
	}

	document.addEventListener("change", function (event) {
		var select = event.target.closest("[data-uxs-select]");
		if (!select) {
			return;
		}
		var setId = select.getAttribute("data-uxs-set");
		if (setId) {
			apply(setId, select.value);
		}
	});

	if (document.readyState === "loading") {
		document.addEventListener("DOMContentLoaded", init);
	} else {
		init();
	}
})();
