<?php

register_grid_template("ajf-team", [
    "count" => -1,
    "class" => "team-grid",
    // "include_items" => true,
    "get_details" => function ($id) {
        $details = [
            "id" => $id,
            "photo" => get_field("photo", $id),
            "name" => get_the_title($id),
            "position" => get_field("position", $id),
            "bio" => apply_filters('the_content', get_the_content(null, false, $id)),
        ];

        return $details;
    },
    "render" => function ($details) {
        return '
			<a class="archive-item team" data-fancybox data-src="#team-bio-' . $details["id"] . '" data-touch="false" data-auto-focus="false" href="javascript:;">
				<div class="photo-container">
					<div class="photo" style="background-image: url(' . $details["photo"] . ')"></div>
				</div>
				<div class="details">
					<div class="name">' . $details["name"] . '</div>
					<div class="position">' . $details["position"] . '</div>
				</div>

				<div class="bio-popup texture" id="team-bio-' . $details["id"] . '" style="display: none;">
					<div class="bio-popup-inner">
						<div class="left-column">
							<div class="photo-container">
								<div class="photo" style="background-image: url(' . $details["photo"] . ')"></div>
							</div>
						</div>
						<div class="right-column">
							<button class="close-button" onClick="jQuery.fancybox.close();">Close</button>

							<div class="details">
								<h3 class="name">' . $details["name"] . '</h3>
								<div class="position">' . $details["position"] . '</div>
								<br />
							</div>
							<div class="bio">' . $details["bio"] . '</div>
						</div>
					</div>
				</div>
			</a>
		';
    },
]);

register_filters_template("ajf-team", [
    "query" => [
        "name" => "Search",
        "type" => "text",
        "matches" => function ($atts, $details) {
            return wp_ajf_contains($atts["query"], $details["name"]);
        },
    ],
]);
