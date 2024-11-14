# Blogs Class for Fetching WordPress Posts

This PHP class, `Blogs`, is designed to interact with a WordPress REST API to fetch and filter blog posts and metadata, including images, categories, and tags. It includes methods to retrieve multiple posts with pagination and a single post by ID or slug, with optimized cURL handling.

## Features

- **Fetch multiple posts** with pagination and sorting options.
- **Retrieve individual posts** by ID or slug.
- **Filter and format** post data, including featured images, title, date, content, and metadata.
- **Header handling** to capture pagination details such as total posts and pages.

## Requirements

- PHP 7.0 or higher
- cURL extension enabled in PHP

## Setup

1. **Clone this repository** or copy the `Blogs` class file into your project.
2. **Update `self::$url`** in the class with your WordPress site URL.
3. Optional: Set `self::$per_page` to your preferred default posts-per-page count.
