<?php
class TMDB {
    private string $apiKey = '12711b61d7c6fd67d82d3624be601459';
    private string $baseUrl = 'https://api.themoviedb.org/3';
    private string $imageCdn = 'https://image.tmdb.org/t/p/w500';
    private string $backdropCdn = 'https://image.tmdb.org/t/p/original';

    public function getNowPlaying(): array {
        return $this->fetch('/movie/now_playing');
    }

    public function getUpcoming(): array {
        return $this->fetch('/movie/upcoming');
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

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            curl_close($ch);
            return ['results' => []];
        }

        curl_close($ch);
        $data = json_decode($response, true);
        return is_array($data) ? $data : ['results' => []];
    }
}
?>
