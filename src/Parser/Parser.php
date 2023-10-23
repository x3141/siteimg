<?php

namespace App\Parser;

use Symfony\Component\DomCrawler\Crawler;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class Parser
{
    private $url;
    private $basePath = '';

    public function __construct(public HttpClientInterface $httpClient)
    {
    }

    public function parseSite(): array
    {
        $imageLinks = array_unique($this->getSiteImageLinks($this->url));

        return $this->makeImageInfoArray($imageLinks);
    }

    private function makeImageInfoArray(array $links)
    {
        $result = [];
        $totalSize = 0;

        foreach ($links as $link) {
            $currentSize = $this->remoteImageSize($link);
            $result['image'][] = ['size' => $currentSize, 'url' => $link];
            $totalSize = $totalSize + $currentSize;
        }

        $result['total']['size']['byte'] = $totalSize;
        $result['total']['size']['Mb'] = round($totalSize / 1048576, 2);

        return $result;
    }

    private function remoteImageSize($url)
    {
        $headers = get_headers($url);
        $size = 0;

        array_map(
            function ($n) use (&$size) {
                if (str_starts_with($n, 'Content-Length: ')) {
                    $size = (int)str_replace('Content-Length: ', '', $n);
                }
            }, $headers);

        return $size;
    }

    private function getSiteImageLinks(string $url): array
    {
        $response = $this->httpClient->request('GET', $url);
        $statusCode = $response->getStatusCode();
        $linkArray = [];

        if ($statusCode) {
            $content = $response->getContent();
            $crawler = new Crawler($content);
            $crawler->filter('img')->each(
                function ($node) use (&$linkArray, $url) {
                    $linkArray[] = $this->makeFullPath($node->attr('src'));
                }
            );
        }

        return $linkArray;
    }

    function makeFullPath($src)
    {
        if (str_starts_with($src, 'http')) {
            return $src;
        } else {
            return $this->basePath . $src;
        }
    }

    /**
     * @return mixed
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * @param mixed $url
     */
    public function setUrl($url): void
    {
        $re = '/https?:\/\/[\w\.-]*/m';
        preg_match_all($re, $url, $matches, PREG_SET_ORDER, 0);

        if (isset($matches[0][0])) {
            $this->basePath = $matches[0][0];
        }

        $this->url = $url;
    }
}
