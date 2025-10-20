# AJAX Filterables (AJF) - Comprehensive Plugin Documentation

**Version**: 5.10.2
**Author**: Daniel Garden
**Plugin Type**: WordPress AJAX Filtering System

---

## Table of Contents

1. [Overview](#overview)
2. [Core Functionality](#core-functionality)
3. [Architecture](#architecture)
4. [Use Cases](#use-cases)
5. [Configuration Options](#configuration-options)
6. [API Reference](#api-reference)
7. [Elementor Integration](#elementor-integration)
8. [Advanced Features](#advanced-features)
9. [Potential Upgrades](#potential-upgrades)

---

## Overview

AJAX Filterables (AJF) is a powerful WordPress plugin that enables developers and site administrators to create dynamic, AJAX-powered filterable grids for any post type without page reloads. The plugin provides a flexible framework for displaying and filtering content with real-time updates, pagination, and extensive customization options.

### Key Features

- **AJAX-Powered Filtering**: Real-time content filtering without page reloads
- **Universal Post Type Support**: Works with built-in and custom post types
- **External Data Sources**: Pull data from APIs or custom data sources
- **Multiple Filter Types**: Text search, dropdowns, checkboxes, and custom filters
- **Template System**: Reusable grid and filter configurations
- **Elementor Integration**: Drag-and-drop widgets for visual page builders
- **JavaScript API**: Extensive event system and programmatic control
- **REST API**: Backend API for AJAX requests with caching
- **Mustache Templating**: Flexible HTML rendering with Mustache syntax
- **Shortcode Support**: Easy integration via WordPress shortcodes
- **Pagination & Lazy Loading**: Built-in pagination and "view more" functionality
- **URL State Management**: Filter states persist in URL parameters
- **Caching System**: Configurable response caching for performance

---

## Core Functionality

### 1. Grid Registration System

The plugin centers around registering "grids" that display collections of data.

#### Basic Grid Registration

```php
register_grid("post", [
    "pagination" => true,
    "count" => 10,
    "render" => function($details) {
        return '<div class="item">' . $details["post_title"] . '</div>';
    }
]);
```

**Output**: Creates shortcode `[post-grid]`

#### Grid with Custom Data Source (API)

```php
register_grid("futurama", [
    "data" => function() {
        $response = wp_remote_get('http://futuramaapi.herokuapp.com/api/v2/characters');
        $result = json_decode(wp_remote_retrieve_body($response));
        return is_array($result) ? $result : [];
    },
    "render" => function($details) {
        return '<div>' . $details["Name"] . '</div>';
    }
]);
```

**Output**: Creates shortcode `[futurama-grid]`

### 2. Filter Registration System

Filters allow users to refine grid content dynamically.

#### Text Search Filter

```php
register_filters("post", [
    "query" => [
        "name" => "Search",
        "type" => "text",
        "placeholder" => "Enter search term...",
        "matches" => function($atts, $details) {
            return match_contains($atts["query"], $details["post_title"]);
        }
    ]
]);
```

**Output**: Creates shortcodes `[post-filters]` and `[post-filters-query]`

#### Select Dropdown Filter

```php
register_filters("post", [
    "category" => [
        "name" => "Category",
        "type" => "select",
        "options" => ["News", "Blog", "Tutorial"],
        "matches" => function($atts, $details) {
            return match_exact($atts["category"], $details["category"]);
        }
    ]
]);
```

#### Checkbox Filter

```php
register_filters("post", [
    "featured" => [
        "name" => "Show Featured Only",
        "type" => "checkbox",
        "matches" => function($atts, $details) {
            return $atts["featured"] ? $details["is_featured"] : true;
        }
    ]
]);
```

### 3. Template System

Templates enable code reuse across multiple grids.

#### Creating a Template

```php
register_grid_template("ajf-post", [
    "data" => "post",
    "pagination" => true,
    "count" => 10,
    "render" => function($details) {
        return $details["post_title"] . "<br />";
    }
]);

register_filters_template("ajf-post", [
    "query" => [
        "name" => "Search",
        "type" => "text",
        "matches" => function($atts, $details) {
            return match_contains($atts["query"], $details["post_title"]);
        }
    ]
]);
```

#### Using a Template

```php
// Use template as-is
register_grid("posts", "ajf-post");

// Override template properties
register_grid("posts", "ajf-post", [
    "pagination" => false,
    "count" => 20
]);
```

### 4. Mustache Templating

Grids can use Mustache templates instead of PHP render functions.

```php
register_grid("post", [
    "data" => "post",
    "render" => '<div class="post">
        <h3>{{post_title}}</h3>
        <img src="{{thumbnail}}" />
        <p>{{post_excerpt}}</p>
        <a href="{{permalink}}">Read More</a>
    </div>'
]);
```

**Available Variables** (for WordPress posts):
- `{{post_title}}`, `{{post_content}}`, `{{post_excerpt}}`
- `{{thumbnail}}` - Featured image URL
- `{{permalink}}` - Post URL
- `{{ID}}` - Post ID
- All custom fields (ACF fields included automatically)
- Taxonomy terms as `{{taxonomy_name_list}}`

---

## Architecture

### File Structure

```
wp-ajax-filtering/
├── wp_ajax_filtering.php       # Main plugin file (AJF_Instance class)
├── js/
│   ├── ajf-main.js            # Frontend JavaScript API
│   ├── ajf-elementor.js       # Elementor integration
│   └── datepicker.min.js      # Date picker library
├── css/
│   ├── ajf-widget-grid.css    # Grid widget styles
│   ├── ajf-widget-filters.css # Filter widget styles
│   └── datepicker.min.css     # Date picker styles
├── widgets/
│   ├── elementor-grid.php     # Elementor grid widget
│   ├── elementor-filter.php   # Base filter widget
│   ├── elementor-filter-text.php
│   ├── elementor-filter-select.php
│   └── elementor-filter-clear.php
├── Mustache/                   # Mustache templating library
└── README.md                   # Basic documentation
```

### PHP Class Architecture

**Main Class**: `AJF_Instance`
- Manages grid and filter registrations
- Handles shortcode generation
- Provides REST API endpoints
- Integrates with Elementor
- Manages caching and data retrieval

**Global Functions** (convenience wrappers):
- `register_grid()`, `register_grid_template()`
- `register_filters()`, `register_filters_template()`
- `get_grid_data()` - Retrieve grid data programmatically
- `match_exact()`, `match_contains()`, `match_like()` - Filter helpers

### JavaScript Architecture

**Class**: `AJF_class` (available as `window.AJF`)

**Event System**:
- `submit`, `load`, `render`, `pagination`, `filter`, `clear`, `page-change`, `ready`, `fetch`, `error`, `post-render`, `load-more`

**Methods**:
- `get()`, `set()`, `getAll()`, `setAll()` - State management
- `load()`, `reload()` - Data fetching
- `next()`, `prev()`, `setPage()` - Pagination
- `clear()` - Reset filters
- `on()`, `trigger()` - Event handling

---

## Use Cases

### 1. Blog Archive with Search and Category Filtering

**Scenario**: Display blog posts with text search and category dropdown.

```php
register_grid("post", [
    "data" => "post",
    "count" => 9,
    "pagination" => true,
    "render" => function($details) {
        return '
            <article class="blog-post">
                <img src="' . $details["thumbnail"] . '" />
                <h2>' . $details["post_title"] . '</h2>
                <p>' . $details["post_excerpt"] . '</p>
                <a href="' . $details["permalink"] . '">Read More</a>
            </article>
        ';
    }
]);

register_filters("post", [
    "query" => [
        "name" => "Search Posts",
        "type" => "text",
        "placeholder" => "Search...",
        "matches" => function($atts, $details) {
            return match_contains($atts["query"], $details["post_title"] . " " . $details["post_content"]);
        }
    ],
    "category" => [
        "name" => "Category",
        "type" => "select",
        "matches" => function($atts, $details) {
            return match_exact($atts["category"], $details["category"]);
        }
    ]
]);
```

**Usage**:
```
[post-filters]
[post-grid]
```

### 2. Team Directory with Position Filtering

**Scenario**: Display team members with filtering by department.

```php
register_grid("team_member", [
    "count" => -1, // Show all
    "get_details" => function($id) {
        return [
            "name" => get_the_title($id),
            "position" => get_field("position", $id),
            "department" => get_field("department", $id),
            "photo" => get_field("photo", $id),
            "bio" => get_field("bio", $id)
        ];
    },
    "render" => function($details) {
        return '
            <div class="team-card">
                <img src="' . $details["photo"] . '" />
                <h3>' . $details["name"] . '</h3>
                <p>' . $details["position"] . '</p>
            </div>
        ';
    }
]);

register_filters("team_member", [
    "department" => [
        "name" => "Department",
        "type" => "select",
        // Options auto-populated from 'department' field
        "matches" => function($atts, $details) {
            return match_exact($atts["department"], $details["department"]);
        }
    ]
]);
```

### 3. Product Catalog with Multiple Filters

**Scenario**: E-commerce product display with price range, category, and availability.

```php
register_grid("product", [
    "count" => 12,
    "pagination" => true,
    "order" => function($a, $b) {
        return $a["price"] - $b["price"];
    },
    "get_details" => function($id) {
        return [
            "title" => get_the_title($id),
            "price" => get_field("price", $id),
            "category" => get_field("category", $id),
            "in_stock" => get_field("in_stock", $id),
            "image" => get_the_post_thumbnail_url($id)
        ];
    },
    "render" => function($details) {
        return '
            <div class="product-card">
                <img src="' . $details["image"] . '" />
                <h3>' . $details["title"] . '</h3>
                <p class="price">$' . $details["price"] . '</p>
                <span>' . ($details["in_stock"] ? "In Stock" : "Out of Stock") . '</span>
            </div>
        ';
    }
]);

register_filters("product", [
    "category" => [
        "name" => "Category",
        "type" => "select",
        "matches" => function($atts, $details) {
            return match_exact($atts["category"], $details["category"]);
        }
    ],
    "in_stock" => [
        "name" => "In Stock Only",
        "type" => "checkbox",
        "matches" => function($atts, $details) {
            return $atts["in_stock"] ? $details["in_stock"] : true;
        }
    ],
    "count" => [
        "type" => "select",
        "options" => [12, 24, 48]
    ],
    "clear" => [
        "type" => "clear",
        "name" => "Clear All Filters"
    ]
]);
```

### 4. External API Integration

**Scenario**: Display data from external API with custom filtering.

```php
register_grid("github-repos", [
    "data" => function($atts) {
        $username = "octocat";
        $response = wp_remote_get("https://api.github.com/users/{$username}/repos");
        $repos = json_decode(wp_remote_retrieve_body($response));
        return is_array($repos) ? $repos : [];
    },
    "count" => 10,
    "pagination" => true,
    "render" => function($details) {
        return '
            <div class="repo">
                <h3>' . $details->name . '</h3>
                <p>' . $details->description . '</p>
                <span>⭐ ' . $details->stargazers_count . '</span>
            </div>
        ';
    }
]);

register_filters("github-repos", [
    "query" => [
        "name" => "Search Repos",
        "type" => "text",
        "matches" => function($atts, $details) {
            return match_contains($atts["query"], $details->name);
        }
    ]
]);
```

### 5. Multi-Site News Aggregator

**Scenario**: Aggregate posts from multiple sources with unified filtering.

```php
register_grid("news-feed", [
    "data" => function($atts) {
        $items = [];

        // Local posts
        $posts = get_posts(['numberposts' => 50]);
        foreach($posts as $post) {
            $items[] = [
                "title" => $post->post_title,
                "date" => $post->post_date,
                "source" => "Internal",
                "url" => get_permalink($post->ID)
            ];
        }

        // External RSS feed
        $rss = fetch_feed('https://example.com/feed');
        if (!is_wp_error($rss)) {
            foreach ($rss->get_items(0, 50) as $item) {
                $items[] = [
                    "title" => $item->get_title(),
                    "date" => $item->get_date('Y-m-d H:i:s'),
                    "source" => "External",
                    "url" => $item->get_permalink()
                ];
            }
        }

        return $items;
    },
    "count" => 20,
    "pagination" => true,
    "cache" => 1800, // 30 minutes
    "order" => function($a, $b) {
        return strtotime($b["date"]) - strtotime($a["date"]);
    },
    "render" => function($details) {
        return '
            <div class="news-item">
                <span class="source">' . $details["source"] . '</span>
                <h3><a href="' . $details["url"] . '">' . $details["title"] . '</a></h3>
                <time>' . $details["date"] . '</time>
            </div>
        ';
    }
]);
```

---

## Configuration Options

### Grid Configuration

| Option | Type | Description | Default |
|--------|------|-------------|---------|
| `data` | `string\|function` | Post type slug or function returning array of items | Post type from grid name |
| `data_params` | `array` | Additional WP_Query parameters (when using post type) | `[]` |
| `render` | `function\|string` | Render function or Mustache template | Required |
| `get_details` | `function($id)` | Extract data from post (post types only) | Auto (all fields + ACF) |
| `count` | `integer` | Items per page (0 = unlimited) | `0` |
| `pagination` | `boolean` | Enable pagination | `false` |
| `has_nav` | `boolean` | Show prev/next buttons in pagination | `true` |
| `view_more` | `string` | Enable lazy loading with button text | `null` |
| `order` | `string\|function` | "random" or custom sort function | `null` |
| `class` | `string` | CSS class for grid wrapper | `"archive-grid"` |
| `as` | `string` | HTML element for grid wrapper | `"div"` |
| `wrapper` | `string` | HTML element for container | `"div"` |
| `cache` | `boolean\|integer` | Enable caching (true = 3600s, int = custom seconds) | `3600` |
| `include_items` | `boolean` | Make items array accessible via JavaScript | `false` |
| `prepend` | `string\|function` | HTML before grid | `""` |
| `append` | `string\|function` | HTML after grid | `""` |
| `header` | `string\|function` | HTML inside grid before items | `""` |
| `footer` | `string\|function` | HTML inside grid after items | `""` |
| `no_results` | `string` | Message when no items found | `"No results found"` |

### Filter Configuration

| Option | Type | Description | Required |
|--------|------|-------------|----------|
| `type` | `string` | "text", "select", "checkbox", "hidden", "clear" | Yes |
| `matches` | `function($atts, $details)` | Filter matching function | Yes (except "clear") |
| `name` | `string` | Display label | No (uses filter key) |
| `placeholder` | `string` | Input placeholder (text only) | No |
| `options` | `array` | Dropdown options (select only) | Auto-populated if field exists |
| `icon` | `string` | Icon URL | No |
| `has_any` | `boolean` | Include "Any" option (select only) | `true` |
| `multi` | `boolean` | Multi-select (select only) | `false` |
| `style` | `string` | "select" or "buttons" (select only) | `"select"` |
| `default` | `mixed` | Default value | No |
| `autocomplete` | `boolean` | Enable browser autocomplete | `true` |
| `sort` | `boolean` | Sort select options alphabetically | `true` |
| `calc` | `function($atts, $details)` | Transform filter value before matching | No |
| `before_match` | `function($atts, $details)` | Modify details before matching | No |

### Shortcode Attributes

All shortcodes support these attributes:

```
[post-grid count="6" pagination="false" order="random"]
```

- `count` - Override item count
- `pagination` - true/false/"only" (only shows pagination separately)
- `order` - "random" to randomize results
- Any registered filter name as attribute

---

## API Reference

### PHP Functions

#### `register_grid($post_type, $config, $template_override)`

Register a new grid.

**Parameters**:
- `$post_type` (string) - Unique identifier for the grid
- `$config` (array|string) - Configuration array or template name
- `$template_override` (array) - Override template properties

**Returns**: void

**Example**:
```php
register_grid("post", [
    "count" => 10,
    "render" => function($details) {
        return $details["post_title"];
    }
]);
```

#### `register_filters($post_type, $filters, $template_override)`

Register filters for a grid.

**Parameters**:
- `$post_type` (string) - Grid identifier (must match grid registration)
- `$filters` (array|string) - Filter configuration array or template name
- `$template_override` (array) - Override template properties

**Returns**: void

#### `get_grid_data($post_type, $atts)`

Retrieve grid data programmatically.

**Parameters**:
- `$post_type` (string) - Grid identifier
- `$atts` (array) - Filter attributes

**Returns**: array - Array of filtered items

**Example**:
```php
$featured_posts = get_grid_data("post", [
    "featured" => true,
    "count" => 5
]);
```

#### Helper Functions

```php
// Exact match
match_exact($needle, $haystack)

// Case-insensitive contains
match_contains($needle, $haystack)

// Case-insensitive exact match
match_like($needle, $haystack)
```

### JavaScript API

#### Properties

```javascript
AJF.post_types // Object containing all registered grids
```

#### Methods

**State Management**:

```javascript
// Get single filter value
AJF.get("query")
AJF.get("post", "query") // For specific post type

// Get all filter values
AJF.getAll()
AJF.getAll("post")

// Set single filter value
AJF.set("query", "search term") // Auto-loads
AJF.set("post", "query", "search term") // Specific post type

// Set multiple filter values
AJF.set({
    "query": "search",
    "category": "News"
})
AJF.setAll("post", { ... }) // Specific post type

// Set without auto-loading
await AJF.set("query", "search", false)
```

**Pagination**:

```javascript
// Go to specific page
await AJF.setPage(2)
await AJF.setPage(2, "post")

// Next/Previous
await AJF.next()
await AJF.prev()

// Reset to page 1
AJF.resetPage()
```

**Data Loading**:

```javascript
// Reload current filters
await AJF.load()
await AJF.load("post")
await AJF.reload() // Alias

// Clear all filters
await AJF.clear()
await AJF.clear("post")
```

**Event Listeners**:

```javascript
// Available events:
// ready, submit, fetch, load, render, post-render,
// pagination, filter, clear, page-change, error, load-more

AJF.on("submit", ({ post_type, params, filters }) => {
    console.log("Submitting filters:", filters);
});

AJF.on("load", ({ post_type, html, items, total }) => {
    console.log("Loaded", total, "items");
});

AJF.on("render", ({ post_type, html }) => {
    // Custom rendering
    $(".custom-container").html(html);
    return true; // Prevent default rendering
});

AJF.on("filter", ({ key, value }) => {
    console.log("Filter changed:", key, "=", value);
});

AJF.on("clear", ({ post_type }) => {
    console.log("Filters cleared");
});

AJF.on("page-change", ({ post_type, page_num }) => {
    console.log("Page changed to:", page_num);
});

AJF.on("error", ({ post_type, error }) => {
    console.error("Grid error:", error);
});
```

**Event Priority**:
```javascript
// Add to beginning (higher priority)
AJF.on("load", callback, false)

// Add to end (lower priority)
AJF.on("load", callback, true)

// Override all listeners
AJF.on("load", callback, "override")
```

### REST API

**Endpoint**: `GET /wp-json/ajf/v1`

**Parameters**:
- `post_type` (required) - Grid identifier
- `count` - Items per page
- `pge` - Page number
- All registered filter names

**Response**:
```json
{
    "html": "<div>...</div>",
    "pagination": "<div class='pagination-grid'>...</div>",
    "items": [...], // If include_items enabled
    "total": 100,
    "filters": {...} // Filter metadata
}
```

**Caching**: Responses cached based on grid configuration (`cache` option).

---

## Elementor Integration

### Widgets Provided

1. **AJF Grid Widget** - Display filterable grid
2. **AJF Text Filter Widget** - Text search filter
3. **AJF Select Filter Widget** - Dropdown filter

### Grid Widget Options

| Setting | Description |
|---------|-------------|
| **Source** | Post type to display |
| **Unique ID** | Identifier for multi-grid pages |
| **Count** | Items per page (0 = all) |
| **Pagination** | Enable/disable pagination |
| **Prev/Next** | Show previous/next buttons |
| **Order By** | Default, Random, ID, Title, Date |
| **Render Template** | Mustache template for items |
| **Debug Mode** | Show all available variables |

### Filter Widget Options

| Setting | Description |
|---------|-------------|
| **Source** | Post type (must match grid) |
| **Unique ID** | Must match grid's unique ID |
| **Filter Type** | Auto-set (text/select) |
| **Label** | Display label |
| **Slug** | URL parameter name |
| **Placeholder** | (Text only) Input placeholder |

### Elementor Usage

1. Add **AJF Grid** widget to page
2. Configure data source and template
3. Add **AJF Filter** widgets
4. Ensure filters have same "Unique ID" as grid
5. Filters and grid auto-connect via matching IDs

### Debug Mode

Enable "Debug Mode" in grid widget to display all available variables:

```
{{ID}}
{{post_title}}
{{post_content}}
{{thumbnail}}
{{permalink}}
{{custom_field_name}}
...
```

---

## Advanced Features

### 1. Custom Sorting

```php
// Random order
"order" => "random"

// Custom sort function
"order" => function($a, $b) {
    return $a["priority"] - $b["priority"];
}
```

### 2. Multi-Select Filters

```php
register_filters("post", [
    "tags" => [
        "name" => "Tags",
        "type" => "select",
        "multi" => true,
        "matches" => function($atts, $details) {
            if (!isset($atts["tags"]) || empty($atts["tags"])) return true;

            $selected_tags = is_array($atts["tags"]) ? $atts["tags"] : [$atts["tags"]];
            $post_tags = $details["tags"]; // Array of tags

            return count(array_intersect($selected_tags, $post_tags)) > 0;
        }
    ]
]);
```

### 3. Button-Style Filters

```php
register_filters("product", [
    "size" => [
        "name" => "Size",
        "type" => "select",
        "style" => "buttons", // Render as buttons instead of dropdown
        "options" => ["Small", "Medium", "Large", "XL"],
        "matches" => function($atts, $details) {
            return match_exact($atts["size"], $details["size"]);
        }
    ]
]);
```

### 4. Hidden Filters

Set default filters not visible to users:

```php
register_filters("post", [
    "status" => [
        "type" => "hidden",
        "default" => "published",
        "matches" => function($atts, $details) {
            return $details["status"] === $atts["status"];
        }
    ]
]);
```

### 5. Calculated Filter Values

```php
register_filters("product", [
    "price_range" => [
        "name" => "Max Price",
        "type" => "select",
        "options" => [50, 100, 200, 500],
        "calc" => function($atts, $details) {
            // Transform selected value before matching
            return floatval($atts["price_range"]);
        },
        "matches" => function($atts, $details) {
            return $details["price"] <= $atts["price_range"];
        }
    ]
]);
```

### 6. Pre-Match Data Transformation

```php
register_filters("post", [
    "query" => [
        "name" => "Search",
        "type" => "text",
        "before_match" => function($atts, $details) {
            // Add computed search field
            $details["searchable"] = $details["post_title"] . " " .
                                     $details["post_content"] . " " .
                                     $details["author_name"];
            return $details;
        },
        "matches" => function($atts, $details) {
            return match_contains($atts["query"], $details["searchable"]);
        }
    ]
]);
```

### 7. Dynamic Headers/Footers

```php
register_grid("product", [
    "header" => function($items) {
        return '<div class="results-count">Found ' . count($items) . ' products</div>';
    },
    "footer" => function($items) {
        $total_value = array_sum(array_column($items, 'price'));
        return '<div class="total-value">Total Value: $' . $total_value . '</div>';
    }
]);
```

### 8. Custom AJAX Handling

```javascript
AJF.on("render", ({ post_type, html }) => {
    // Custom fade-in animation
    $(".archive-container").fadeOut(200, function() {
        $(this).html(html).fadeIn(200);
    });
    return true; // Prevent default
});
```

### 9. URL State Management

Filters automatically update URL parameters:

```
?query=search&category=News&pge=2
```

Multiple grids on one page use prefixed parameters:

```
?post__query=blog&product__category=Electronics
```

### 10. Custom Data Params

Pass additional WP_Query arguments:

```php
register_grid("post", [
    "data" => "post",
    "data_params" => [
        "post_status" => "publish",
        "author" => 5,
        "meta_query" => [
            [
                "key" => "featured",
                "value" => "1"
            ]
        ]
    ]
]);
```

### 11. Separate Pagination

Display pagination separately from grid:

```
[post-grid pagination="false"]

<div class="content-area">
    Some other content...
</div>

[post-grid pagination="only"]
```

### 12. ACF Integration

All ACF fields automatically available:

```php
"render" => function($details) {
    return '
        <div>
            ' . $details["custom_acf_field"] . '
            ' . $details["repeater_field"] . '
        </div>
    ';
}
```

Or with Mustache:
```
{{custom_acf_field}}
{{repeater_field}}
```

### 13. Multisite Support

Works seamlessly on WordPress Multisite installations. Each site maintains separate grid registrations.

---

## Potential Upgrades

Based on the current architecture and common use cases, here are recommended enhancements:

### High Priority Upgrades

#### 1. **Advanced Filter Types**

**Description**: Add more sophisticated filter types.

**Suggested Additions**:
- **Range Slider**: For price, date, or numeric ranges
- **Date Picker**: With date range selection
- **Radio Buttons**: Alternative to select dropdowns
- **Tag/Multi-Select**: Pill-style tag selection
- **Autocomplete**: Ajax-powered search suggestions
- **Toggle Switch**: Modern alternative to checkboxes
- **Color Picker**: For color-based filtering

**Implementation Example**:
```php
register_filters("product", [
    "price" => [
        "type" => "range",
        "min" => 0,
        "max" => 1000,
        "step" => 10,
        "matches" => function($atts, $details) {
            return $details["price"] >= $atts["price"]["min"] &&
                   $details["price"] <= $atts["price"]["max"];
        }
    ]
]);
```

**Benefits**:
- Better UX for numeric/date filtering
- More intuitive for users
- Reduces need for custom implementations

---

#### 2. **Filter Presets/Saved Searches**

**Description**: Allow users to save and load filter combinations.

**Features**:
- Save current filter state with custom name
- Quick-load preset filters
- Share preset URLs
- Admin-defined default presets

**Implementation Example**:
```javascript
// Save preset
AJF.savePreset("Featured Products", {
    "category": "Electronics",
    "in_stock": true,
    "price_max": 500
});

// Load preset
AJF.loadPreset("Featured Products");

// List all presets
const presets = AJF.getPresets();
```

**Benefits**:
- Improved user workflow
- Quick access to common searches
- Better for power users

---

#### 3. **Improved Caching System**

**Description**: More granular cache control and invalidation.

**Features**:
- Post-type based cache invalidation (auto-clear on post update)
- Cache warming (pre-generate common filter combinations)
- Conditional caching (cache only expensive queries)
- Cache statistics dashboard
- Redis/Memcached support

**Implementation Example**:
```php
register_grid("product", [
    "cache" => [
        "enabled" => true,
        "ttl" => 3600,
        "invalidate_on" => ["save_post_product", "delete_post_product"],
        "warm" => [
            ["category" => "Electronics"],
            ["in_stock" => true]
        ]
    ]
]);
```

**Benefits**:
- Better performance
- Reduced server load
- More control over cache behavior

---

#### 4. **Export Functionality**

**Description**: Allow users to export filtered results.

**Features**:
- CSV export
- PDF export
- Excel export
- JSON export
- Print-friendly view
- Email results
- Customizable export fields

**Implementation Example**:
```php
register_filters("product", [
    "export" => [
        "type" => "export",
        "name" => "Export Results",
        "formats" => ["csv", "pdf", "excel"],
        "fields" => ["post_title", "price", "category"]
    ]
]);
```

**Benefits**:
- Users can save/share results
- Better for data analysis
- Improves plugin value

---

#### 5. **Filter Analytics Dashboard**

**Description**: Track which filters are most used.

**Features**:
- Track filter usage frequency
- Monitor search terms
- Identify "no results" searches
- Filter performance metrics
- User behavior insights
- Admin dashboard widget

**Implementation**:
- Log filter submissions to database
- Aggregate statistics
- Display in WordPress admin
- Export analytics data

**Benefits**:
- Understand user behavior
- Optimize filter configuration
- Improve content strategy

---

#### 6. **Faceted Search with Result Counts**

**Description**: Show result counts next to each filter option.

**Example**:
```
Category:
[ ] Electronics (45)
[ ] Clothing (23)
[ ] Books (12)
```

**Implementation**:
```php
register_filters("product", [
    "category" => [
        "type" => "select",
        "show_counts" => true, // NEW
        "matches" => function($atts, $details) {
            return match_exact($atts["category"], $details["category"]);
        }
    ]
]);
```

**Benefits**:
- Better user experience
- Prevents "0 results" dead ends
- Industry standard feature

---

#### 7. **Load More / Infinite Scroll**

**Description**: Enhanced lazy loading capabilities.

**Current**: Basic "View More" button
**Enhanced**:
- True infinite scroll
- Configurable scroll threshold
- Loading animations
- Scroll-to-top button
- Memory management (unload old items)

**Implementation Example**:
```php
register_grid("post", [
    "infinite_scroll" => [
        "enabled" => true,
        "threshold" => 200, // pixels from bottom
        "max_pages" => 10,
        "unload_after" => 3 // pages
    ]
]);
```

**Benefits**:
- Modern UX pattern
- Better mobile experience
- Keeps users engaged

---

#### 8. **Filter Dependencies/Conditional Logic**

**Description**: Filters that appear/change based on other filter values.

**Example**:
```php
register_filters("vehicle", [
    "make" => [
        "type" => "select",
        "options" => ["Toyota", "Honda", "Ford"]
    ],
    "model" => [
        "type" => "select",
        "depends_on" => "make", // NEW
        "options" => function($atts) { // Dynamic options
            if ($atts["make"] === "Toyota") {
                return ["Camry", "Corolla", "RAV4"];
            } else if ($atts["make"] === "Honda") {
                return ["Civic", "Accord", "CR-V"];
            }
            // ...
        }
    ]
]);
```

**Benefits**:
- Cleaner interface
- Context-aware filtering
- Better for hierarchical data

---

#### 9. **Mobile-First Filter UI**

**Description**: Optimize filter UX for mobile devices.

**Features**:
- Off-canvas filter drawer
- Sticky "Filter" button
- Touch-optimized controls
- Mobile-specific layouts
- Filter result preview before applying
- Quick clear button

**Implementation**:
```php
register_filters("product", [
    "mobile_ui" => [
        "type" => "drawer", // drawer, modal, inline
        "sticky_button" => true,
        "preview_results" => true
    ]
]);
```

**Benefits**:
- Better mobile experience
- Standard UX pattern
- Increased conversions

---

#### 10. **Search Suggestions/Autocomplete**

**Description**: Provide real-time search suggestions as user types.

**Features**:
- Ajax-powered suggestions
- Highlight matching text
- Recent searches
- Popular searches
- Fuzzy matching
- Keyboard navigation

**Implementation Example**:
```php
register_filters("post", [
    "query" => [
        "type" => "text",
        "autocomplete" => [
            "enabled" => true,
            "min_chars" => 2,
            "max_results" => 10,
            "source" => "post_title", // Field to search
            "highlight" => true
        ]
    ]
]);
```

**Benefits**:
- Faster search
- Better discoverability
- Reduced typos

---

### Medium Priority Upgrades

#### 11. **Multi-Grid Synchronization**

Synchronize filters across multiple grids on the same page.

```javascript
AJF.sync(["products", "related-products"], ["category", "price_range"]);
```

---

#### 12. **Filter Validation**

Validate filter inputs before submission.

```php
"validation" => [
    "required" => true,
    "min_length" => 3,
    "pattern" => "/^[a-zA-Z0-9]+$/"
]
```

---

#### 13. **Custom Loading States**

More control over loading animations and states.

```php
"loading" => [
    "spinner" => "url/to/spinner.gif",
    "overlay" => true,
    "message" => "Loading products..."
]
```

---

#### 14. **Elementor Dynamic Tags**

Support for Elementor dynamic tags in templates.

```
{{elementor:post:title}}
{{elementor:post:featured_image}}
```

---

#### 15. **REST API Authentication**

Protect API endpoints with authentication.

```php
"api_auth" => [
    "enabled" => true,
    "method" => "jwt", // jwt, basic, api_key
]
```

---

#### 16. **Grid Templates Library**

Pre-built templates for common use cases.

- Blog grid
- Product grid
- Portfolio grid
- Team directory
- Event calendar
- Job listings

---

#### 17. **A/B Testing Integration**

Built-in A/B testing for filter layouts.

```php
"ab_test" => [
    "enabled" => true,
    "variants" => ["default", "variant_a"],
    "metric" => "clicks"
]
```

---

#### 18. **GraphQL Support**

Alternative to REST API using GraphQL.

```javascript
const { items } = await AJF.graphql(`
    query {
        postGrid(filters: { category: "News" }) {
            items { title, permalink }
            total
        }
    }
`);
```

---

#### 19. **SSR/Static Generation Support**

Generate static HTML for better performance and SEO.

```php
"static_generation" => [
    "enabled" => true,
    "paths" => ["/?category=news", "/?featured=true"]
]
```

---

#### 20. **Improved Debug Tools**

Enhanced debugging and development tools.

- Visual query builder
- Performance profiler
- Filter simulation
- Error logging
- SQL query viewer

---

### Low Priority / Nice-to-Have

21. **Multi-Language Support (i18n)** - WPML/Polylang integration
22. **Visual Grid Builder** - Drag-and-drop grid configuration in admin
23. **Filter History** - Browser back/forward support
4. **Custom URL Structures** - SEO-friendly URLs (`/category/news/` instead of `?category=news`)
25. **Email Alerts** - Notify users when new items match their filters
26. **Comparison Tool** - Side-by-side item comparison
27. **Favorites/Wishlist** - Save items for later
28. **Social Sharing** - Share specific filter combinations
29. **Voice Search** - Voice input for text filters
30. **AI-Powered Search** - Natural language search ("Show me cheap red dresses")

---

## Performance Considerations

### Current Performance Features

1. **Response Caching** - 3600s default, configurable per grid
2. **Lazy Loading** - View more button for incremental loading
3. **Selective Data Loading** - Only fetch required fields via `get_details`
4. **Client-Side Filtering** - Some filters can work client-side
5. **Efficient Querying** - Optimized WP_Query usage

### Performance Best Practices

1. **Use `count` wisely** - Don't load more than needed
2. **Enable caching** - Especially for expensive queries
3. **Limit `get_details`** - Only fetch fields you'll use
4. **Use `data_params`** - Optimize WP_Query with meta_query, tax_query
5. **Pagination over lazy load** - For SEO and performance
6. **External data caching** - Cache API responses longer
7. **Optimize render functions** - Avoid heavy processing in render

### Scaling Recommendations

- **Database indexing** on commonly filtered meta fields
- **CDN** for static assets
- **Object caching** (Redis/Memcached) for production
- **Limit results** for large datasets (>1000 items)
- **Consider Elasticsearch** for complex search requirements

---

## Security Considerations

### Current Security Features

1. **Nonce verification** - AJAX requests validated
2. **Capability checks** - Admin functions protected
3. **SQL injection prevention** - Uses WP_Query/prepared statements
4. **XSS prevention** - Output escaped in templates
5. **ABSPATH check** - Prevents direct file access

### Security Best Practices

1. **Sanitize user input** in custom filter functions
2. **Escape output** in render functions
3. **Validate data sources** when using external APIs
4. **Rate limit** API endpoints for public sites
5. **Use capabilities** for admin-only grids
6. **Review custom code** in templates for vulnerabilities

---

## Browser Compatibility

**Supported**:
- Chrome 90+
- Firefox 88+
- Safari 14+
- Edge 90+

**Dependencies**:
- jQuery (included with WordPress)
- Mustache.js (bundled)

**Polyfills** may be needed for:
- `fetch()` in IE11 (if supporting legacy browsers)
- `Promise` in older browsers

---

## Troubleshooting

### Common Issues

**Issue**: Filters not working
**Solution**: Ensure filter `post_type` matches grid `post_type` exactly

**Issue**: No results displaying
**Solution**: Check `matches` function returns `true` for expected items

**Issue**: Pagination showing wrong page
**Solution**: Clear browser cache, check `count` setting

**Issue**: Elementor widgets not appearing
**Solution**: Ensure Elementor is active, regenerate Elementor cache

**Issue**: Slow performance
**Solution**: Enable caching, reduce `count`, optimize `get_details`

**Issue**: JavaScript errors
**Solution**: Check for jQuery conflicts, ensure `ajf_rest_url` is defined

**Issue**: Filters not persisting in URL
**Solution**: Check for multiple grids with same post_type, use unique IDs

---

## Changelog

### Version 5.10.2 (Current)
- Fixed multisite bug
- Added data params support
- Fetch override improvements

### Previous Versions
- Template system
- Elementor integration
- Mustache templating
- JavaScript API
- Caching system

---

## Support & Resources

**Documentation**: `README.md` (basic usage guide)
**GitHub**: (Repository URL if available)
**Support**: Contact plugin author for assistance

---

## Conclusion

AJAX Filterables (AJF) is a robust and flexible WordPress plugin that transforms how content is displayed and filtered on WordPress sites. With support for any post type, external data sources, extensive customization options, and modern AJAX functionality, it provides a solid foundation for creating dynamic, user-friendly content archives.

The plugin's architecture—combining PHP-based registration, REST API endpoints, JavaScript API, and Elementor integration—makes it suitable for both developers and non-technical users.

The suggested upgrades focus on enhancing user experience, adding modern UI patterns, improving performance, and providing better analytics—all while maintaining backward compatibility with existing implementations.

**Best suited for**:
- Blog and news sites with filtered archives
- E-commerce product catalogs
- Directory sites (teams, locations, businesses)
- Portfolio and gallery sites
- Event listings and calendars
- Job boards and classifieds
- Multi-source content aggregation

**Not recommended for**:
- Simple, static lists (overkill)
- Extremely large datasets without proper indexing (>10,000 items)
- Real-time data that changes rapidly (without proper cache invalidation)
- Sites with poor hosting (requires decent server resources for AJAX)

---

**Document Version**: 1.0
**Last Updated**: 2025-10-20
**Plugin Version Documented**: 5.10.2
