

# register_grid
The register_grid function is used to specify a type of grid that displays.
Use like ```register_grid($post_type, $config)``` where $post_type is any post type that is registered with the Wordpress system, including custom post types.

<br/>

Calling this function will register a shortcode which will be $post_type appended with -grid. Put this anywhere on your page to display this grid. For instance
```post``` will register the shortcode ```post-grid```.

<br/>

### Register Grid from Post Type Example
You can register any post type that is in the system.
```php
register_grid("post", [
    "pagination" => true,
    "get_details" => function($id) {
        return [
            "title" => get_the_title($id)
        ];
    },
    "render" => function($details) {
        return $details["title"] . "<br />";
    }

]);

//Use the shortcode [post-grid]
```
<br/><br/>

Also pass it a config option which will be used to specify what details(information) is needed, it will only pull the information that is specified in this function. It will pass an ID to the ```get_details``` property which should then return an array of properties which will then be passed to the ```render``` function. The render item is how each item in the grid will render, all items are looped over and called with this function.

<br/>

### Register Grid from API Example
You can pull data dynamically from an API or custom data source by providing a function as the 'data' property.
```php
register_grid("futurama", [
    "data" => function() {
        $apiUrl = 'http://futuramaapi.herokuapp.com/api/v2/characters';
        $response = wp_remote_get($apiUrl);
        $responseBody = wp_remote_retrieve_body( $response );
        $result = json_decode( $responseBody );
        if ( is_array( $result ) && ! is_wp_error( $result ) ) {
            return $result;
        } else {
            return [];
        }
    },
    "render" => function($details) {
        return $details["Name"] . "<br />";
    }
]);

//Use the shortcode [futurama-grid]
```


<br />


| Option | Type | Description |
| --- | --- | --- |
| `data` | `string`<br />`function()` | Specify source of data, can be the slug of a post type or a function that returns an array of items |
| `render*` | `function($details)` | The function to be looped over when rendering items. Must return HTML |
| `get_details` | `function($id)` | Specify properties/details for individual items, can only be used if data is not specified and data is being pulled from post type.  |
| `count` | `integer` | Default number of items to be shown, will be overidden by shortcode attribute and filtering |
| `class` | `string` | Class to use as the grid wrapper |
| `cache` | `boolean`<br />`integer` | True or false will be on or off, integer will be on and cache timeout in seconds. 3600 by default |
| `pagination` | `boolean` | Whether or not to display pagination |
| `view_more` | `string` | If specified will display a "View More" button with the specific text. Will show more items incrementally i.e lazy loading items. |
| `no_results` | `string` | Text/HTML to display when no results are available |
| `include_items` | `boolean` | If this is set to true, array of items will accessible with javascript |




# register_filters
The register filters method is used for specifying different parameters that can be used for filtering an existing grid.
It takes two parameters, the first must be the same slug specified in the correlating register_grid function. Filters will only work if a grid already exists. The second is an array of different filters to be used with this grid.
When registering filters you need to specify a ```type``` and a ```match``` function at the very least.

Shortcodes will be generated for all the filters as well as each individual filter if you wish to split them up onto different parts of the page.

### Register Search from Post type Example
This example should be used with the first register_grid example, and it is a simple query search matching the post title.
```php
register_filters("post", [
	"query" => [
        "name" => "Search",
        "type" => "text",
        "matches" => function ($atts, $details) {
            return wp_ajf_contains($atts["query"], $details["title"]);
        },
    ]
]);

//Use the shortcode [post-filters] or [post-filters-query]
```

<br />

### Register multiple filters for Post type example
This example includes the checkbox filter for anything title with under 10 characters.
```php
register_filters("post", [
	"under_10" => [
		"name" => "Titles under 10",
		"type" => "checkbox",
		"matches" => function($atts, $details) {
			return $atts["under_10"] ? (strlen($details["title"]) < 10) : true;
		}
	],
]);

//Use the shortcode [post-filters], [post-filters-under-10]
```

<br />


| Option | Type | Description |
| --- | --- | --- |
| `name` | `string` | This will be used as the label, exclude to use the filter key |
| `type` | `string` | Can be checkbox, text, select |
| `matches` | `function($atts, $details)` | Used for matching against this filter, must return true or false |
| `icon` | `string` | An icon to be placed next to the filter element |
| `placeholder` | `string` | Only for text input type, placeholder for the input |
| `options` | `array` | Only for select input type, this is all the options. Can be automatically populated with unique options from details array if slug matches |