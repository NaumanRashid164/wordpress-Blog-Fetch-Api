<?php

class Blogs
{
    public static $url = ""; // Insert WordPress site URL here
    public static $per_page = 10; // Default items per page for pagination
    private $curl;

    public function __construct()
    {
        // Initialize cURL session
        $this->curl = curl_init();
    }

    public function __destruct()
    {
        // Close cURL session
        curl_close($this->curl);
    }

    /**
     * Filters the response data according to specified requirements.
     * 
     * @param array|null $response Raw response data from API
     * @return array Filtered response data
     */
    public function filter_response(array $response = null): array
    {
        $filtered = [];
        foreach ($response as $data) {
            if (isset($data['id'])) {
                // Format the post data
                $date = date("d M Y h:i A", strtotime($data["date"]));
                $img = $data['_embedded']["wp:featuredmedia"][0]['source_url'] ?? '';

                $filtered[] = [
                    "id" => $data["id"],
                    "json_link" => self::$url . "/wp-json/wp/v2/posts/" . $data["id"],
                    "img" => $img,
                    "date" => $date,
                    "slug" => $data["slug"],
                    "type" => $data["type"],
                    "link" => $data["link"],
                    "categories" => implode(",", $data["categories"]),
                    "tags" => implode(",", $data["tags"]),
                    "title" => $data["title"]["rendered"],
                    "content" => $data["content"]["rendered"],
                    "excerpt" => $data["excerpt"]["rendered"],
                ];
            }
        }
        return $filtered;
    }

    /**
     * Configures query parameters for API requests.
     * 
     * @param array $params Reference to parameters array, modifies page and per_page values.
     */
    private function paramsHandle(array &$params): void
    {
        // Set default pagination values
        $params["page"] = $params["page"] ?? 1;
        $params["per_page"] = $params["per_page"] ?? self::$per_page;
        $params = http_build_query($params);
    }

    /**
     * Fetches posts from the WordPress API based on provided parameters.
     * 
     * @param array|null $params Query parameters (page, per_page, orderby, etc.)
     * @return array Filtered response with headers
     * @throws Exception If cURL encounters an error
     */
    public function posts(array $params = null): array
    {
        $this->paramsHandle($params); // Prepare parameters for query
        $headers = [];

        // Set cURL options for fetching posts
        $postUrl = self::$url . "/wp-json/wp/v2/posts?_embed&$params";
        curl_setopt_array($this->curl, [
            CURLOPT_URL => $postUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HEADERFUNCTION => function ($curl, $header) use (&$headers) {
                $len = strlen($header);
                $header = explode(':', $header, 2);
                if (count($header) < 2) return $len; // Skip invalid headers
                $headers[strtolower(trim($header[0]))][] = trim($header[1]);
                return $len;
            }
        ]);

        $response = curl_exec($this->curl);

        // Handle cURL errors
        if (curl_errno($this->curl)) {
            throw new Exception(curl_error($this->curl));
        }

        // Decode and filter the response data
        $response = json_decode($response, true);
        $response = $this->filter_response($response);
        $response["header"] = $headers; // Attach headers to the response

        return $response;
    }

    /**
     * Fetches a single post by ID or by slug.
     * 
     * @param int|string $filter Post ID or slug
     * @return array Filtered post data
     * @throws Exception If cURL encounters an error
     */
    public function post($filter): array
    {
        $query = is_numeric($filter) ? "/$filter?" : "?slug=$filter&";
        $postUrl = self::$url . "/wp-json/wp/v2/posts" . $query . "_embed";

        // Set cURL options for single post retrieval
        curl_setopt_array($this->curl, [
            CURLOPT_URL => $postUrl,
            CURLOPT_RETURNTRANSFER => true,
        ]);

        $response = curl_exec($this->curl);

        // Handle cURL errors
        if (curl_errno($this->curl)) {
            throw new Exception(curl_error($this->curl));
        }

        // Decode and filter the response data
        $posts = json_decode($response, true);
        $posts = isset($posts[0]) ? $posts : [$posts];
        return $this->filter_response($posts);
    }
}

// Instantiate Blogs class
$blogs = new Blogs();

// Fetch single post by slug
$path = pathinfo($_SERVER["REDIRECT_URL"]);
$slug = $path["filename"];
$post = $blogs->post($slug)[0] ?? [];

// Fetch latest posts with parameters
$params = [
    'orderby' => 'date',
    'order' => 'desc',
];
$topPosts = $blogs->posts($params) ?? [];

// Extract total pages and total posts count from headers
$totalPages = $topPosts["header"]["x-wp-totalpages"][0] ?? 0;
$totalPosts = $topPosts["header"]["x-wp-total"][0] ?? 0;
