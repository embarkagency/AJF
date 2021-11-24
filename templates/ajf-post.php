<?php

register_grid_template("ajf-post", [
    "data" => "post",
    "pagination" => true,
    "has_nav" => false,
    "include_items" => true,
    "count" => 10,
    "render" => function ($details) {
        return $details["post_title"] . "<br />";
    },
]);

register_filters_template("ajf-post", [
    "query" => [
        "name" => "Search",
        "type" => "text",
        "matches" => function ($atts, $details) {
            return wp_ajf_contains($atts["query"], $details["post_title"]);
        },
    ],
]);
