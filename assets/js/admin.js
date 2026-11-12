(function () {
	"use strict";

	var boot = window.uxsAdmin;
	if (!boot || !boot.settings) {
		return;
	}

	var root = document.querySelector(".uxs-admin");
	if (!root) {
		return;
	}

	var i18n = boot.i18n || {};

	function clone(value) {
		return JSON.parse(JSON.stringify(value));
	}

	function $(id) {
		return document.getElementById(id);
	}

	function escapeHtml(value) {
		return String(value)
			.replace(/&/g, "&amp;")
			.replace(/</g, "&lt;")
			.replace(/>/g, "&gt;")
			.replace(/"/g, "&quot;");
	}

	var saved = clone(boot.settings);
	if (!Array.isArray(saved.sets)) {
		saved.sets = [];
	}
	var current = clone(saved);
	var rendering = false;

	function isDirty() {
		return JSON.stringify(current) !== JSON.stringify(saved);
	}

	function syncBar() {
		var bar = $("uxs-save");
		if (bar) {
			bar.classList.toggle("is-show", isDirty());
		}
	}

	function showToast(message) {
		var toast = $("uxs-toast");
		if (!toast) {
			return;
		}
		if (message) {
			toast.textContent = message;
		}
		toast.classList.add("is-show");
		window.setTimeout(function () {
			toast.classList.remove("is-show");
		}, 2200);
	}

	function setAt(index) {
		return current.sets[index];
	}

	function render() {
		var host = $("uxs-sets");
		if (!host) {
			return;
		}

		rendering = true;
		if (!current.sets.length) {
			host.innerHTML = '<div class="uxs-empty">' + escapeHtml(i18n.empty || "") + "</div>";
		} else {
			host.innerHTML = current.sets.map(renderSet).join("");
		}
		rendering = false;
		syncBar();
	}

	function renderSet(set, index) {
		var title = set.label || i18n.unnamed || "";
		var slug = set.id
			? "<code>" + escapeHtml(set.id) + "</code>"
			: '<span class="uxs-h">' + escapeHtml(i18n.slugPending || "") + "</span>";
		var options = Array.isArray(set.options) ? set.options : [];

		var defaultOpts = '<option value="">' + escapeHtml(i18n.firstOption || "") + "</option>";
		options.forEach(function (option) {
			if (!option.id) {
				return;
			}
			defaultOpts +=
				'<option value="' +
				escapeHtml(option.id) +
				'"' +
				(set.default === option.id ? " selected" : "") +
				">" +
				escapeHtml(option.label || i18n.optionLabel || "") +
				"</option>";
		});

		var rows = options
			.map(function (option, optionIndex) {
				var slugCell = option.id
					? "<code>" + escapeHtml(option.id) + "</code>"
					: '<span class="uxs-h">' + escapeHtml(i18n.slugPending || "") + "</span>";
				return (
					"<tr data-option-index=\"" +
					optionIndex +
					'">' +
					'<td><input type="text" class="uxs-input" data-k="option-label" value="' +
					escapeHtml(option.label || "") +
					'" placeholder="' +
					escapeHtml(i18n.optionLabel || "") +
					'"></td>' +
					"<td>" +
					slugCell +
					"</td>" +
					'<td><button type="button" class="uxs-remove" data-act="remove-option">' +
					escapeHtml(i18n.removeOption || "") +
					"</button></td>" +
					"</tr>"
				);
			})
			.join("");

		return (
			'<div class="uxs-card" data-set-index="' +
			index +
			'">' +
			'<div class="uxs-set-head"><h2>' +
			escapeHtml(title) +
			'</h2><button type="button" class="uxs-remove" data-act="remove-set">' +
			escapeHtml(i18n.removeSet || "") +
			"</button></div>" +
			'<div class="uxs-row"><div><label class="uxs-t">' +
			escapeHtml(i18n.setName || "") +
			'</label></div><input type="text" class="uxs-input" data-k="label" value="' +
			escapeHtml(set.label || "") +
			'"></div>' +
			'<div class="uxs-row"><div><span class="uxs-t">' +
			escapeHtml(i18n.setSlug || "") +
			"</span></div>" +
			slug +
			"</div>" +
			'<div class="uxs-row"><div><label class="uxs-t">' +
			escapeHtml(i18n.placeholder || "") +
			'</label><span class="uxs-h">' +
			escapeHtml(i18n.placeholderH || "") +
			'</span></div><input type="text" class="uxs-input" data-k="placeholder" value="' +
			escapeHtml(set.placeholder || "") +
			'"></div>' +
			'<div class="uxs-row"><div><label class="uxs-t">' +
			escapeHtml(i18n.defaultOpt || "") +
			'</label></div><select class="uxs-input" data-k="default">' +
			defaultOpts +
			"</select></div>" +
			'<div class="uxs-row"><div><span class="uxs-t">' +
			escapeHtml(i18n.options || "") +
			"</span></div><div>" +
			'<table class="uxs-option-table"><thead><tr><th>' +
			escapeHtml(i18n.optionLabel || "") +
			"</th><th>" +
			escapeHtml(i18n.setSlug || "") +
			"</th><th></th></tr></thead><tbody>" +
			rows +
			"</tbody></table>" +
			'<p><button type="button" class="uxs-btn" data-act="add-option">' +
			escapeHtml(i18n.addOption || "") +
			"</button></p></div></div></div>"
		);
	}

	function cardIndex(el) {
		var card = el.closest("[data-set-index]");
		return card ? parseInt(card.getAttribute("data-set-index"), 10) : -1;
	}

	root.querySelectorAll(".uxs-nav button").forEach(function (button) {
		button.addEventListener("click", function () {
			var target = button.getAttribute("data-tab");
			root.querySelectorAll(".uxs-nav button").forEach(function (item) {
				item.setAttribute("aria-selected", item === button ? "true" : "false");
			});
			root.querySelectorAll(".uxs-tab").forEach(function (tab) {
				var on = tab.id === "uxs-tab-" + target;
				tab.classList.toggle("is-on", on);
				tab.hidden = !on;
			});
		});
	});

	var addSet = $("uxs-add-set");
	if (addSet) {
		addSet.addEventListener("click", function () {
			current.sets.push({
				id: "",
				label: "",
				placeholder: "",
				default: "",
				options: [],
			});
			render();
		});
	}

	root.addEventListener("input", function (event) {
		if (rendering) {
			return;
		}
		var el = event.target;
		var key = el.getAttribute("data-k");
		if (!key) {
			return;
		}
		var index = cardIndex(el);
		var set = setAt(index);
		if (!set) {
			return;
		}
		if (key === "option-label") {
			var row = el.closest("[data-option-index]");
			var optionIndex = row ? parseInt(row.getAttribute("data-option-index"), 10) : -1;
			if (set.options[optionIndex]) {
				set.options[optionIndex].label = el.value;
			}
			syncBar();
			return;
		}
		set[key] = el.value;
		if (key === "label") {
			var heading = el.closest(".uxs-card").querySelector(".uxs-set-head h2");
			if (heading) {
				heading.textContent = el.value || i18n.unnamed || "";
			}
		}
		syncBar();
	});

	root.addEventListener("change", function (event) {
		if (rendering) {
			return;
		}
		var el = event.target;
		if (el.getAttribute("data-k") === "default") {
			var set = setAt(cardIndex(el));
			if (set) {
				set.default = el.value;
				syncBar();
			}
		}
	});

	root.addEventListener("click", function (event) {
		var button = event.target.closest("[data-act]");
		if (!button) {
			return;
		}
		var act = button.getAttribute("data-act");
		var index = cardIndex(button);
		var set = setAt(index);
		if (act === "add-option") {
			if (!set) {
				return;
			}
			set.options.push({ id: "", label: "" });
			render();
			return;
		}
		if (act === "remove-option") {
			if (!set) {
				return;
			}
			var row = button.closest("[data-option-index]");
			var optionIndex = row ? parseInt(row.getAttribute("data-option-index"), 10) : -1;
			if (optionIndex >= 0) {
				set.options.splice(optionIndex, 1);
				render();
			}
			return;
		}
		if (act === "remove-set") {
			if (!window.confirm(i18n.removeConfirm || "")) {
				return;
			}
			current.sets.splice(index, 1);
			render();
		}
	});

	var reset = $("uxs-rst");
	if (reset) {
		reset.addEventListener("click", function () {
			current = clone(saved);
			render();
			current = clone(saved);
			syncBar();
		});
	}

	var save = $("uxs-sv");
	if (save) {
		save.addEventListener("click", function () {
			save.disabled = true;
			var body = new window.FormData();
			body.append("action", "uxs_save_settings");
			body.append("nonce", boot.nonce);
			body.append("settings", JSON.stringify(current));

			window
				.fetch(boot.ajaxUrl, {
					method: "POST",
					credentials: "same-origin",
					body: body,
				})
				.then(function (response) {
					return response.json();
				})
				.then(function (payload) {
					if (!payload || !payload.success) {
						throw new Error("save failed");
					}
					saved = clone(payload.data.settings || current);
					if (!Array.isArray(saved.sets)) {
						saved.sets = [];
					}
					current = clone(saved);
					render();
					showToast((payload.data && payload.data.message) || i18n.saved);
				})
				.catch(function () {
					window.alert(i18n.error);
				})
				.then(function () {
					save.disabled = false;
				});
		});
	}

	render();
})();
