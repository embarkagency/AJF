jQuery(document).ready(function($){
	class AJF_class {
		constructor() {
			this.post_types = {};
			this.event_listeners = {};

			this.on('render', ({ post_type, html }) => {
				$(".archive-container[data-post-type='" + post_type + "']").html(html);
			}, true);

			this.on('pagination', ({ post_type, pagination }) => {
				$(".pagination-container[data-post-type='" + post_type + "']").html(pagination);
			}, true);

			this.on('filter', ({ post_type, skip_load, src }) => {
				if(!skip_load && src === "bind") {
					this.load(post_type);
				}
			}, true);

			this.on('load-more', ({ post_type }) => {
				this.post_types[post_type].post_count += this.post_types[post_type].default_post_count;
				this.resetPage(post_type);
				this.load(post_type);
			}, true);

			this.init();
		}

		init() {
			const $this = this;

			$(".archive-container").each(function() {
				const post_type = $(this).attr("data-post-type");
				const default_post_count = parseInt($(this).attr("data-post-count"));
				const default_page = $(this).attr("data-page") ? parseInt($(this).attr("data-page")) : 1;

				let post_count = default_post_count;

				$this.post_types[post_type] = {
					default_post_count,
					post_count,
					page: default_page
				};
			});

			$(".filter-value").each(function() {
				$this.setFilterValue(this, false);
			});

			$(document).on("change", ".filter-value", function() {
				const data = $this.setFilterValue(this);
				const post_type = $(this).attr("data-post-type");
				data.post_type = post_type;
				data.src = "bind";
				if($(this).attr("data-skip-load")) {
					data.skip_load = true;
				} else {
					data.skip_load = false;
				}
				$this.trigger("filter", { data });
			});

			$(document).on("click", ".filter-value[data-type='clear']", function() {
				const post_type = $(this).attr("data-post-type");
				$this.clear(post_type);
			});

			$(document).on("click", ".view-more-container .view-more-button", function() {
				const post_type = $(this).attr("data-post-type");		
				$this.trigger("load-more", {
					data: {
						post_type
					}
				})
			});

			// $(document).keydown(function(e){
			// 	if(e.which == 39) { //RIGHT
			// 		$this.next();
			// 	} else if(e.which == 37) { //LEFT
			// 		$this.prev();
			// 	}
			// });

			$(document).on("click", ".pagination-grid .pagination-num", function(e) {
				e.preventDefault();
				const post_type = $(this).attr("data-post-type");
				const page_num = parseInt($(this).attr("data-page"));
				$this.trigger("page-change", { data: {
					post_type,
					page_num
				}});
				$this.setPage(page_num, post_type);
			});

			$(document).ready(function() {
				for(const post_type in $this.post_types) {
					$this.trigger("ready", {
						data: {
							post_type
						}
					});
				}
			});
		}

		setFilterValue(el, shouldReset=true) {
			const post_type = $(el).attr("data-post-type");
			this.post_types[post_type].post_count = this.post_types[post_type].default_post_count;

			if(shouldReset) {
				this.resetPage(post_type);
			}

			const type = $(el).data("input-type");
			let key = $(el).data("type");
			let value = $(el).val();

			if(type === "checkbox") {
				value = $(el).is(":checked");
			}

			const data = {
				type,
				key,
				value,
			};

			this.setValue(post_type, data);
			this.set(post_type, data.key, data.value, false, false);
			return data;
		}

		setValue(post_type, data) {
			post_type = post_type || Object.keys(this.post_types)[0];
			if(!this.post_types[post_type].values) {
				this.post_types[post_type].values = {};
			}

			this.post_types[post_type].values[data.key] = data;
		}

		getAll(post_type) {
			post_type = post_type || Object.keys(this.post_types)[0];

			const keyVals = (this.post_types[post_type].values ? Object.values(this.post_types[post_type].values) : []).map(filter => {
				return [filter.key, filter.value];
			});

			return Object.fromEntries(keyVals);
		}

		async setAll(post_type, data, shouldLoad=true) {
			if(arguments.length === 1) {
				data = post_type;
				post_type = Object.keys(this.post_types)[0];
			}
			for(const key in data) {
				this.set(post_type, key, data[key], false);
			}
			if(shouldLoad) {
				await this.load(post_type);
			}
		}

		async set(post_type, key, value, shouldLoad=true, shouldTrigger=true) {
			if(arguments.length < 3) {
				value = key;
				key = post_type;
				post_type = Object.keys(this.post_types)[0];
			} else {
				post_type = post_type || Object.keys(this.post_types)[0];
			}

			if(typeof value === "object" && !Array.isArray(value)) {
				this.setAll(post_type, value);
				return;
			}

			const filter = $(".filter-value[data-type='" + key + "'][data-post-type='" + post_type + "']");
			filter.attr("data-skip-load", "true");
			const type = filter.data("input-type");
			if(type === "checkbox") {
				filter.prop("checked", value);
				if(shouldTrigger) {
					filter.trigger("change");
				}
			} else {
				filter.val(value);
				if(shouldTrigger) {
					filter.trigger("change");
				}
			}
			filter.removeAttr("data-skip-load");

			if(shouldLoad) {
				await this.load(post_type);
			}
		}

		get(post_type, key) {
			if(arguments.length === 1) {
				key = post_type;
				post_type = Object.keys(this.post_types)[0];
			} else {
				post_type = post_type || Object.keys(this.post_types)[0];
			}

			const vals = this.getAll(post_type);
			return vals[key];
		}

		postType(post_type) {
			return this.post_types[post_type] ? true : false;
		}

		async clear(post_type, shouldLoad=true) {
			post_type = post_type || Object.keys(this.post_types)[0];

			$(".filter-value[data-post-type='" + post_type + "']").each(function() {
				$(this).attr("data-skip-load", "true");
				const type = $(this).data("input-type");
				if(type === "checkbox") {
					$(this).prop("checked", false);
				} else {
					$(this).val("");
				}

				$(this).trigger("change");
				$(this).removeAttr("data-skip-load");
			});

			this.trigger("clear", { data: { post_type } });
			if(shouldLoad) {
				await this.load(post_type);
			}
		}

		resetPage(post_type) {
			post_type = post_type || Object.keys(this.post_types)[0];
			this.post_types[post_type].page = 1;
		}

		async setPage(page=1, post_type) {
			post_type = post_type || Object.keys(this.post_types)[0];
			this.post_types[post_type].page = parseInt(page);
			await this.load(post_type);
		}
		
		async next(post_type) {
			post_type = post_type || Object.keys(this.post_types)[0];
			this.post_types[post_type].page++;
			await this.load(post_type);
		}

		async prev(post_type) {
			post_type = post_type || Object.keys(this.post_types)[0];
			this.post_types[post_type].page--;
			await this.load(post_type);
		}

		async reload(post_type) {
			post_type = post_type || Object.keys(this.post_types)[0];
			await this.load();
		}

		replaceState(post_type, archive_url) {
			const cur_url = new URL(document.location);
			archive_url = new URL(archive_url);

			if(Object.keys(this.post_types).length > 1) {
				const params = Object.fromEntries(archive_url.searchParams);
				for (const param in params) {
					const val = params[param];
					archive_url.searchParams.delete(param);
					archive_url.searchParams.set(post_type + "__" + param, val);
				}
			}

			cur_url.searchParams.forEach((value, key) => {
				if(!archive_url.searchParams.has(key)) {
					archive_url.searchParams.set(key, value);
				}
			});

			history.replaceState({}, '', archive_url.search);
		}

		async load(post_type, render=true) {
			post_type = post_type || Object.keys(this.post_types)[0];
			return new Promise((resolve, reject) => {
				const $this = this;
				const atts = $this.getAll(post_type);
	
				atts.pge = $this.post_types[post_type].page;

				if(atts["count"]) {
					atts["count"] = atts["count"] || $this.post_types[post_type].default_post_count;
					$this.post_types[post_type].post_count = atts["count"];
				}

				const archive_url = new URL(ajf_rest_url + "/" + post_type);
				for(const property in atts) {
					archive_url.searchParams.set(property, atts[property]);
				}
				this.replaceState(post_type, archive_url);
				archive_url.searchParams.set('count', $this.post_types[post_type].post_count);
	
				const params = archive_url.searchParams;

				$this.trigger("submit", {
					data: {
						post_type,
						url: archive_url.toString(),
						params,
						filters: atts
					},
				});
	
				fetch(archive_url.toString())
				.then(r => r.json())
				.then(r => {
					$this.trigger("load", {
						data: {
							post_type,
							html: r.html,
							items: r.items || [],
							total: r.total,
							error: r.error,
							response: r,
							url: archive_url.toString(),
							params,
						}
					});
					if(render) {
						if(r.html) {
							$this.trigger("render", {
								data: {
									post_type,
									html: r.html,
									items: r.items || [],
									total: r.total,
									response: r,
									url: archive_url.toString(),
									params,
								}
							});
						}
						$this.trigger("pagination", {
							data: {
								post_type,
								pagination: r.pagination || "",
								total: r.total,
								items: r.items || [],
								response: r,
								url: archive_url.toString(),
								params,
							}
						});
						if(r.error) {
							$this.trigger("error", {
								data: {
									post_type,
									error: r.error,
									response: r,
									url: archive_url.toString(),
									params,
								}
							});
						}
					}
					resolve(r);
				})
			});
		}
		
		on(type, fn, toEnd=false) {
			if(!this.event_listeners[type]) {
				this.event_listeners[type] = [];
			}

			if(toEnd) {
				this.event_listeners[type].push(fn);
			} else {
				this.event_listeners[type].unshift(fn);
			}
		}
		
		trigger(type, params={}) {
			if(this.event_listeners[type]) {	
				for(let i = 0; i < this.event_listeners[type].length; i++) {
					const event_listener = this.event_listeners[type][i];

					if(typeof event_listener === "function") {
						const shouldBreak = event_listener.bind(this)(...Object.values(params));
						if(shouldBreak) {
							break;
						}
					}
				}
			}
		}
	}
	
	window.AJF = new AJF_class();
});