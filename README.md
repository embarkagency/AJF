

# register_grid
The register_grid function is used to specify a type of grid that displays. Use like ```register_grid($post_type, $config)``` where $post_type is any post type that is registered with the Wordpress system, including custom post types.

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
```
Will generate the shortcode ```[post-grid]```
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
```
Will generate the shortcode ```[futurama-grid]```
<br/>

# register_filters
