<?php

class TMDB
{
    private string $apiKey;
    private string $baseUrl = 'https://api.themoviedb.org/3';
    private string $imageCdn = 'https://image.tmdb.org/t/p/w500';
    private string $backdropCdn = 'https://image.tmdb.org/t/p/original';

    public function __construct()
    {
        /*
         * IMPORTANT:
         * On Render, add:
         *
         * TMDB_API_KEY = your_tmdb_api_key
         *
         * Never put the real API key directly in this file.
         */

        $this->apiKey = trim((string) getenv('TMDB_API_KEY'));
    }

    /**
     * Get currently playing movies
     */
    public function getNowPlaying(): array
    {
        return $this->fetch('/movie/now_playing');
    }

    /**
     * Get upcoming movies
     */
    public function getUpcoming(): array
    {
        return $this->fetch('/movie/upcoming');
    }

    /**
     * Get movie details
     */
    public function getMovieDetails(int|string $id): array
    {
        $id = $this->cleanMovieId($id);

        if ($id === '') {
            return $this->emptyResponse();
        }

        return $this->fetch(
            '/movie/' . $id,
            [
                'append_to_response' => 'videos,credits'
            ]
        );
    }

    /**
     * Get movie credits
     */
    public function getMovieCredits(int|string $id): array
    {
        $id = $this->cleanMovieId($id);

        if ($id === '') {
            return $this->emptyResponse();
        }

        return $this->fetch('/movie/' . $id . '/credits');
    }

    /**
     * Get movie videos
     */
    public function getMovieVideos(int|string $id): array
    {
        $id = $this->cleanMovieId($id);

        if ($id === '') {
            return $this->emptyResponse();
        }

        return $this->fetch('/movie/' . $id . '/videos');
    }

    /**
     * Search movies
     */
    public function searchMovie(string $query): array
    {
        $query = trim($query);

        if ($query === '') {
            return $this->emptyResponse();
        }

        return $this->fetch(
            '/search/movie',
            [
                'query' => $query
            ]
        );
    }

    /**
     * Get movie genres
     */
    public function getGenres(): array
    {
        return $this->fetch('/genre/movie/list');
    }

    /**
     * Get movies by genre
     */
    public function getMoviesByGenre(int|string $genreId): array
    {
        $genreId = $this->cleanMovieId($genreId);

        if ($genreId === '') {
            return $this->emptyResponse();
        }

        return $this->fetch(
            '/discover/movie',
            [
                'with_genres' => $genreId
            ]
        );
    }

    /**
     * Convert poster path into a usable image URL.
     */
    public function getImageUrl(
        ?string $path,
        string $fallback = 'assets/img/no-poster.png'
    ): string {
        if (empty($path)) {
            return $fallback;
        }

        $path = trim($path);

        /*
         * Already a complete URL.
         */
        if (
            str_starts_with($path, 'http://') ||
            str_starts_with($path, 'https://') ||
            str_starts_with($path, 'data:image/')
        ) {
            return $path;
        }

        /*
         * TMDB image path.
         * Example:
         * /abc123.jpg
         */
        if (str_starts_with($path, '/')) {
            return $this->imageCdn . $path;
        }

        /*
         * Base64 image.
         */
        return 'data:image/jpeg;base64,' . $path;
    }

    /**
     * Convert backdrop path into a usable image URL.
     */
    public function getBackdropUrl(
        ?string $path,
        string $fallback = ''
    ): string {
        if (empty($path)) {
            return $fallback;
        }

        $path = trim($path);

        /*
         * Already a complete URL.
         */
        if (
            str_starts_with($path, 'http://') ||
            str_starts_with($path, 'https://') ||
            str_starts_with($path, 'data:image/')
        ) {
            return $path;
        }

        /*
         * TMDB backdrop path.
         */
        if (str_starts_with($path, '/')) {
            return $this->backdropCdn . $path;
        }

        /*
         * Base64 image.
         */
        return 'data:image/jpeg;base64,' . $path;
    }

    /**
     * Perform request to TMDB API.
     */
    private function fetch(
        string $endpoint,
        array $params = []
    ): array {
        /*
         * API key missing.
         */
        if ($this->apiKey === '') {
            error_log('TMDB ERROR: TMDB_API_KEY environment variable is missing.');

            return $this->emptyResponse();
        }

        /*
         * Add API key.
         */
        $params['api_key'] = $this->apiKey;

        /*
         * Build URL.
         */
        $url = $this->baseUrl
            . $endpoint
            . '?'
            . http_build_query($params);

        /*
         * Initialize cURL.
         */
        $ch = curl_init();

        if ($ch === false) {
            error_log('TMDB ERROR: Unable to initialize cURL.');

            return $this->emptyResponse();
        }

        /*
         * cURL settings.
         */
        curl_setopt_array(
            $ch,
            [
                CURLOPT_URL => $url,

                CURLOPT_RETURNTRANSFER => true,

                CURLOPT_FOLLOWLOCATION => true,

                CURLOPT_MAXREDIRS => 5,

                /*
                 * Keep SSL verification enabled on Render.
                 */
                CURLOPT_SSL_VERIFYPEER => true,

                CURLOPT_SSL_VERIFYHOST => 2,

                /*
                 * Connection timeout.
                 */
                CURLOPT_CONNECTTIMEOUT => 5,

                /*
                 * Maximum request time.
                 */
                CURLOPT_TIMEOUT => 15,

                /*
                 * Tell TMDB what we accept.
                 */
                CURLOPT_HTTPHEADER => [
                    'Accept: application/json'
                ],

                /*
                 * Use a normal User-Agent.
                 */
                CURLOPT_USERAGENT => 'BookMyTicket/1.0'
            ]
        );

        /*
         * Execute request.
         */
        $response = curl_exec($ch);

        /*
         * Check cURL error.
         */
        if ($response === false) {
            $error = curl_error($ch);

            error_log(
                'TMDB cURL ERROR: ' . $error
            );

            curl_close($ch);

            return $this->emptyResponse();
        }

        /*
         * Get HTTP status.
         */
        $httpCode = (int) curl_getinfo(
            $ch,
            CURLINFO_HTTP_CODE
        );

        /*
         * Close cURL.
         */
        curl_close($ch);

        /*
         * HTTP error.
         */
        if ($httpCode < 200 || $httpCode >= 300) {
            error_log(
                'TMDB HTTP ERROR: ' .
                $httpCode .
                ' | Endpoint: ' .
                $endpoint
            );

            /*
             * Try to return TMDB's response if it is valid JSON.
             */
            $errorData = json_decode(
                $response,
                true
            );

            if (is_array($errorData)) {
                return $errorData;
            }

            return $this->emptyResponse();
        }

        /*
         * Decode JSON.
         */
        $data = json_decode(
            $response,
            true
        );

        /*
         * JSON decoding failed.
         */
        if (!is_array($data)) {
            error_log(
                'TMDB ERROR: Invalid JSON response.'
            );

            return $this->emptyResponse();
        }

        return $data;
    }

    /**
     * Clean movie/genre ID.
     */
    private function cleanMovieId(int|string $id): string
    {
        $id = trim((string) $id);

        /*
         * TMDB IDs should be numeric.
         */
        if ($id === '' || !ctype_digit($id)) {
            return '';
        }

        return $id;
    }

    /**
     * Standard empty response.
     */
    private function emptyResponse(): array
    {
        return [
            'results' => [],
            'page' => 1,
            'total_pages' => 0,
            'total_results' => 0
        ];
    }
}
?>
