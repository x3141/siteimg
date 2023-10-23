<?php

namespace App\Parser;

use App\Exception\ParserException;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 *
 */
class Parser
{
    /**
     * @var
     */
    private $url;
    /**
     * @var string
     */
    private $basePath = '';

    /**
     * @param HttpClientInterface $httpClient
     */
    public function __construct(public HttpClientInterface $httpClient)
    {
    }

    /**
     * @return array
     */
    public function parseSite(): array
    {
        try {
            $imageLinks = array_unique($this->getSiteImageLinks($this->url));
        } catch (ClientExceptionInterface | TransportExceptionInterface | ServerExceptionInterface | RedirectionExceptionInterface
        | \Exception $e) {
            throw new ParserException($e->getMessage());
        }

        return $this->makeImageInfoArray($imageLinks);
    }

    /**
     * @param array $links
     * @return array
     */
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

    /**
     * @param $url
     * @return int
     */
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

    /**
     * @param string $url
     * @return array
     * @throws \Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     */
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

    /**
     * @param $src
     * @return mixed|string
     */
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
