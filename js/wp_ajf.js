jQuery(document).ready(function($){
	class AJF_class {
		constructor() {
			this.post_types = {};
			this.event_listeners = {};

			this.on('render', function({ post_type, html }) {
				$(".archive-container[data-post-type='" + post_type + "']").html(html);
			});

			this.on('pagination', function({ post_type, pagination }) {
				$(".pagination-container[data-post-type='" + post_type + "']").html(pagination);
			});

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
				$this.setFilterValue(this);
			});

			$(document).on("change", ".filter-value", function() {
				const data = $this.setFilterValue(this);
				const post_type = $(this).attr("data-post-type");
				data.post_type = post_type;
				$this.trigger("filter", { data });
				$this.load(post_type);
			});

			$(document).on("click", ".view-more-container .view-more-button", function() {
				const post_type = $(this).attr("data-post-type");		
				$this.post_types[post_type].post_count += $this.post_types[post_type].default_post_count;
				$this.resetPage(post_type);
				$this.load(post_type);
			});

			$(document).on("click", ".pagination-grid .pagination-num", function(e) {
				e.preventDefault();
				const post_type = $(this).attr("data-post-type");
				const page_num = parseInt($(this).attr("data-page"));

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

		setFilterValue(el) {
			const post_type = $(el).attr("data-post-type");
			this.post_types[post_type].post_count = this.post_types[post_type].default_post_count;
			this.resetPage(post_type);

			const type = $(el).data("input-type");
			const key = $(el).data("type");
			const value = $(el).val();

			const data = {
				type,
				key,
				value,
			};

			if(data.type === "checkbox") {
				data.value = $(el).is(":checked");
			}

			this.setValue(post_type, data);
			return data;
		}

		setValue(post_type, data) {
			post_type = post_type || Object.keys(this.post_types)[0];
			if(!this.post_types[post_type].values) {
				this.post_types[post_type].values = {};
			}

			this.post_types[post_type].values[data.key] = data;
		}

		getValues(post_type) {
			post_type = post_type || Object.keys(this.post_types)[0];

			const keyVals = (this.post_types[post_type].values ? Object.values(this.post_types[post_type].values) : []).map(filter => {
				return [filter.key, filter.value];
			});

			return Object.fromEntries(keyVals);
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

		async load(post_type) {
			post_type = post_type || Object.keys(this.post_types)[0];
			return new Promise((resolve, reject) => {
				const $this = this;
				const atts = $this.getValues(post_type);
	
				atts.pge = $this.post_types[post_type].page;

				if(atts["count"]) {
					atts["count"] = atts["count"] || $this.post_types[post_type].default_post_count;
					$this.post_types[post_type].post_count = atts["count"];
				}

				const archive_url = new URL(ajf_rest_url + "/" + post_type);
	
				for(const property in atts) {
					archive_url.searchParams.set(property, atts[property]);
				}
	
				history.replaceState({}, '', archive_url.search);
	
				archive_url.searchParams.set('count', $this.post_types[post_type].post_count);
	
				$this.trigger("submit", {
					data: {
						post_type,
						url: archive_url.toString(),
						params: archive_url.searchParams,
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
							params: archive_url.searchParams,
						}
					});
					if(r.html) {
						$this.trigger("render", {
							data: {
								post_type,
								html: r.html,
								items: r.items || [],
								total: r.total,
								response: r,
								url: archive_url.toString(),
								params: archive_url.searchParams,
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
							params: archive_url.searchParams,	
						}
					});
					if(r.error) {
						$this.trigger("error", {
							data: {
								post_type,
								error: r.error,
								response: r,
								url: archive_url.toString(),
								params: archive_url.searchParams,
							}
						});
					}
					resolve(r);
				})
			});
		}
		
		on(type, fn) {
			if(!this.event_listeners[type]) {
				this.event_listeners[type] = [];
			}	
			this.event_listeners[type].push(fn);
		}
		
		trigger(type, params={}) {
			if(this.event_listeners[type]) {
				this.event_listeners[type].forEach(event_listener => {
					if(typeof event_listener === "function") {
						event_listener.bind(this)(...Object.values(params));
					}
				})
			}
		}
	}
	
	window.AJF = new AJF_class();
});