jQuery(document).ready(function($){

	let post_types = {};
	
	$(".archive-container").each(function() {
		const post_type = $(this).attr("data-post-type");
		const default_post_count = parseInt($(this).attr("data-post-count"));
		let post_count = default_post_count;

		post_types[post_type] = {
			default_post_count,
			post_count
		};
	});

	function loadPostArchive(post_type) {
		const atts = Object.fromEntries([...$(".filter-value[data-post-type='" + post_type + "']")].map(filter => [$(filter).data("type"), $(filter).val()]));

		atts.count = post_types[post_type].post_count;
		atts.post_type = post_type;

		const archive_url = new URL("https://csqddev.com.au/aoh/wp-json/ajf_get/" + post_type);

		for(const property in atts) {
			archive_url.searchParams.set(property, atts[property]);
		}

		fetch(archive_url.toString())
			.then(r => r.json())
			.then(r => {
			if(r.html) {
				$(".archive-container[data-post-type='" + post_type + "']").html(r.html);
			}
		})
	}

	$(".filter-value").on("change", function() {
		const post_type = $(this).attr("data-post-type");
		post_types[post_type].post_count = post_types[post_type].default_post_count;
		loadPostArchive(post_type);
	});
	
	$(document).on("click", ".view-more-container .view-more-button", function() {
		const post_type = $(this).attr("data-post-type");		
		post_types[post_type].post_count += post_types[post_type].default_post_count;
		loadPostArchive(post_type);
	});
	
});