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
				let post_count = default_post_count;

				const page = 1;

				$this.post_types[post_type] = {
					default_post_count,
					post_count,
					page
				};
			});

			$(".filter-value").on("change", function() {
				const post_type = $(this).attr("data-post-type");
				$this.post_types[post_type].post_count = $this.post_types[post_type].default_post_count;
				$this.resetPage(post_type);
				$this.load(post_type);
				$this.trigger("filter", {
					type: $(this).data("type")
				});
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
				
				$this.post_types[post_type].page = page_num;
				$this.load(post_type);
			})

			$(document).ready(function() {
				$this.trigger("ready", {
					data: {}
				});
			});
		}

		resetPage(post_type) {
			this.post_types[post_type].page= 1;
		}

		load(post_type) {
			const $this = this;
			const atts = Object.fromEntries([...$(".filter-value[data-post-type='" + post_type + "']")].map(filter => [$(filter).data("type"), $(filter).val()]));

			atts.pge = $this.post_types[post_type].page;

			const archive_url = new URL(ajf_rest_url + "/" + post_type);

			for(const property in atts) {
				archive_url.searchParams.set(property, atts[property]);
			}

			history.replaceState({}, '', archive_url.search);

			archive_url.searchParams.set('count', $this.post_types[post_type].post_count);
			archive_url.searchParams.set('post_type', post_type);

			$this.trigger("submit", {
				data: {
					url: archive_url.toString(),
					params: archive_url.searchParams
				},
			});

			fetch(archive_url.toString())
				.then(r => r.json())
				.then(r => {
				$this.trigger("load", {
					data: {
						post_type,
						html: r.html,
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
							response: r,
							url: archive_url.toString(),
							params: archive_url.searchParams,
						}
					});
				}
				if(r.pagination) {
					$this.trigger("pagination", {
						data: {
							post_type,
							pagination: r.pagination,
							response: r,
							url: archive_url.toString(),
							params: archive_url.searchParams,	
						}
					})
				}
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
			})
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