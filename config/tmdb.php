<?php
class TMDB {
    private string $apiKey = '12711b61d7c6fd67d82d3624be601459';
private string $baseUrl = 'https://api.themoviedb.org/3';
    private string $imageCdn = 'https://image.tmdb.org/t/p/w500';
    private string $backdropCdn = 'https://image.tmdb.org/t/p/original';
    public function getNowPlaying(): array {
        return $this->getNowStreaming();
    }

    public function getNowStreaming(): array {
        $today = date('Y-m-d');
        $windowStart = date('Y-m-d', strtotime('-180 days'));

        $results = $this->fetch('/discover/movie', [
            'sort_by' => 'primary_release_date.desc',
            'language' => 'en-US',
            'region' => 'IN',
            'include_adult' => 'false',
            'include_video' => 'false',
            'with_release_type' => '2|3',
            'primary_release_date.gte' => $windowStart,
            'primary_release_date.lte' => $today,
            'vote_count.gte' => 20,
            'page' => 1,
        ]);

        if (!empty($results['results'])) {
            return $results;
        }

        return $this->fetch('/movie/now_playing', ['language' => 'en-US', 'region' => 'IN']);
    }

    public function getUpcoming(): array {
        $today = date('Y-m-d');

        $results = $this->fetch('/discover/movie', [
            'sort_by' => 'primary_release_date.asc',
            'language' => 'en-US',
            'region' => 'IN',
            'include_adult' => 'false',
            'include_video' => 'false',
            'with_release_type' => '2|3',
            'primary_release_date.gte' => $today,
            'vote_count.gte' => 10,
            'page' => 1,
        ]);

        if (!empty($results['results'])) {
            return $results;
        }

        return $this->fetch('/movie/upcoming', ['language' => 'en-US', 'region' => 'IN']);
    }

    public function getMovieDetails(int|string $id): array {
        return $this->fetch('/movie/' . $id, ['append_to_response' => 'videos,credits']);
    }

    public function getMovieCredits(int|string $id): array {
        return $this->fetch('/movie/' . $id . '/credits');
    }

    public function getMovieVideos(int|string $id): array {
        return $this->fetch('/movie/' . $id . '/videos');
    }

    public function searchMovie(string $query): array {
        return $this->fetch('/search/movie', ['query' => $query]);
    }

    public function getGenres(): array {
        return $this->fetch('/genre/movie/list');
    }

    public function getMoviesByGenre(int|string $genreId): array {
        return $this->fetch('/discover/movie', ['with_genres' => $genreId]);
    }

    public function getImageUrl(?string $path, string $fallback = 'assets/img/no-poster.png'): string {
        if (empty($path)) return $fallback;
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://') || str_starts_with($path, 'data:image/')) return $path;
        if (str_starts_with($path, '/')) return $this->imageCdn . $path;
        return 'data:image/jpeg;base64,' . $path;
    }

    public function getBackdropUrl(?string $path, string $fallback = ''): string {
        if (empty($path)) return $fallback;
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://') || str_starts_with($path, 'data:image/')) return $path;
        if (str_starts_with($path, '/')) return $this->backdropCdn . $path;
        return 'data:image/jpeg;base64,' . $path;
    }

    private function fetch(string $endpoint, array $params = []): array {
        $params['api_key'] = $this->apiKey;
        $url = $this->baseUrl . $endpoint . '?' . http_build_query($params);

        $lastError = null;
        for ($attempt = 1; $attempt <= 3; $attempt++) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 15);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 8);
            curl_setopt($ch, CURLOPT_USERAGENT, 'BookMyTicket/1.0');
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

            if ($response === false) {
                $lastError = curl_error($ch);
                curl_close($ch);
                if ($attempt < 3) {
                    usleep(500000);
                    continue;
                }
                return ['results' => []];
            }

            curl_close($ch);

            $data = json_decode($response, true);
            if (is_array($data)) {
                if (($data['success'] ?? true) === true) {
                    return $data;
                }
                if ($attempt < 3) {
                    usleep(500000);
                    continue;
                }
            }

            if (is_array($data)) {
                return $data;
            }

            if ($attempt < 3) {
                usleep(500000);
                continue;
            }

            return ['results' => []];
        }

        return ['results' => []];
}
}
?>
