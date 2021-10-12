jQuery(document).ready(function($){
	
	class AJF_class {
		constructor() {
			const $this = this;
			this.post_types = {};
			this.event_listeners = {};

			$(".archive-container").each(function() {
				const post_type = $(this).attr("data-post-type");
				const default_post_count = parseInt($(this).attr("data-post-count"));
				let post_count = default_post_count;

				$this.post_types[post_type] = {
					default_post_count,
					post_count
				};
			});

			function loadPostArchive(post_type) {
				const atts = Object.fromEntries([...$(".filter-value[data-post-type='" + post_type + "']")].map(filter => [$(filter).data("type"), $(filter).val()]));

				const archive_url = new URL(ajf_rest_url + "/" + post_type);

				for(const property in atts) {
					archive_url.searchParams.set(property, atts[property]);
				}

				history.replaceState({}, '', archive_url.search);

				archive_url.searchParams.set('count', $this.post_types[post_type].post_count);
				archive_url.searchParams.set('post_type', post_type);

				fetch(archive_url.toString())
					.then(r => r.json())
					.then(r => {
					if(r.html) {
						$(".archive-container[data-post-type='" + post_type + "']").html(r.html)
					}
				})
			}

			$(".filter-value").on("change", function() {
				const post_type = $(this).attr("data-post-type");
				$this.post_types[post_type].post_count = $this.post_types[post_type].default_post_count;
				loadPostArchive(post_type);
				$this.trigger("filter", {
					type: $(this).data("type")
				});
			});

			$(document).on("click", ".view-more-container .view-more-button", function() {
				const post_type = $(this).attr("data-post-type");		
				$this.post_types[post_type].post_count += $this.post_types[post_type].default_post_count;
				loadPostArchive(post_type);
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