<?php

declare(strict_types=1);

namespace voku\stoppropaganda;

use Httpful\ClientMulti;
use Httpful\Factory;
use Httpful\Http;
use Httpful\Request;
use Httpful\Response;
use Httpful\Uri;
use voku\helper\HtmlDomParser;
use voku\helper\UTF8;
use function array_values;
use function random_int;
use function shuffle;

class StopPropaganda
{
    /**
     * @var null|string[]
     */
    private static $browserAgents;

    /**
     * @var null|string[]
     */
    private static $urlTargetsAll;

    /**
     * @var array<string, string>
     */
    private static $urlsDone = [];

    /**
     * @var string[]
     */
    private $urlTargets;

    public function __construct($urlTargets = [])
    {
        if (self::$browserAgents === null) {
            self::$browserAgents = include __DIR__ . '/data/browser_agents.php';
        }

        if (self::$urlTargetsAll === null) {
            self::$urlTargetsAll = include __DIR__ . '/data/url_targets.php';
        }

        if ($urlTargets === []) {
            $this->urlTargets = self::$urlTargetsAll;
        } else {
            $this->urlTargets = $urlTargets;
        }

        shuffle($this->urlTargets);

        // DEBUG
        var_dump($this->urlTargets);
    }

    private function getRandomBrowserAgent(): string
    {
        $randomIndex = array_rand(self::$browserAgents);

        return self::$browserAgents[$randomIndex];
    }

    /**
     * @param float|int $min
     * @param float|int $max
     *
     * @return float
     */
    private function random_float($min, $max)
    {
        return random_int($min, $max - 1) + (random_int(0, PHP_INT_MAX - 1) / PHP_INT_MAX);
    }

    /**
     * @return void
     */
    public function start()
    {
        $outerLoopUrls = [];
        $multi = new ClientMulti(
            static function (Response $response, Request $request) use (&$outerLoopUrls) {
                if ($response->getStatusCode() < 400) {
                    /** @var HtmlDomParser|null $dom */
                    $dom = $response->getRawBody();

                    if ($dom) {
                        $uri = $request->getUri();
                        assert($uri instanceof Uri);

                        $urls = [];
                        $baseTag = $dom->findOneOrFalse('base');
                        if ($baseTag) {
                            $baseUrl = $baseTag->getAttribute('href');
                        } else {
                            $baseUrl = null;
                        }
                        $links = $dom->findMulti('a');
                        foreach ($links as $link) {
                            $href = $link->getAttribute('href');

                            // skip js links
                            if (
                                strpos($href, '(') !== false
                                ||
                                strpos($href, ')') !== false
                                ||
                                strpos($href, 'javascript:') === 0
                            ) {
                                continue;
                            }

                            // skip mailto links
                            if (strpos($href, 'mailto:') === 0) {
                                continue;
                            }

                            // skip tel links
                            if (strpos($href, 'tel:') === 0) {
                                continue;
                            }

                            // fix url without scheme
                            if (
                                !UTF8::is_url($href)
                                &&
                                strpos($href, '//') === 0
                            ) {
                                $href = $uri->getScheme() . ':' . $href;
                            }

                            // fix relative url
                            if (!UTF8::is_url($href)) {
                                if ($baseUrl) {
                                    $href = rtrim($baseUrl, '/') . '/' . ltrim($href, '/');
                                } else {
                                    $href = $uri->getScheme() . '://' . $uri->getHost() . '/' . ltrim($href, '/');
                                }
                            }

                            $newUri = (new Factory())->createUri($href);
                            if (
                                !isset(self::$urlsDone[$href])
                                &&
                                UTF8::is_url($href)
                                &&
                                $newUri->__toString() !== $uri->__toString()
                                &&
                                $newUri->getHost() === $uri->getHost()
                            ) {
                                self::$urlsDone[$href] = $href;
                                $urls[$href] = $href;
                            }
                        }

                        if ($urls === []) {
                            $urlTmp = $uri->__toString();
                            $outerLoopUrls[$urlTmp] = $urlTmp;
                        } else {
                            $stopPropagandaInner = new StopPropaganda(array_values($urls));
                            $stopPropagandaInner->start();
                        }
                    }
                }
            }
        );

        foreach ($this->urlTargets as $url) {
            $request = (new Request($this->random_float(0, 100) > 50 ? Http::GET : Http::POST))
                  ->withUriFromString($url)
                  ->withUserAgent($this->getRandomBrowserAgent())
                  ->followRedirects()
                  ->withConnectionTimeoutInSeconds($this->random_float(1, 5))
                  ->withTimeout($this->random_float(1, 5))
                  ->withContentEncoding($this->random_float(0, 100) > 50 ? 'gzip' : 'deflate')
                  ->withProtocolVersion($this->random_float(0, 100) > 80 ? Http::HTTP_2_0 : Http::HTTP_1_1)
                  ->expectsHtml();

            $multi->add_request($request);
        }

        $multi->start();

        if ($outerLoopUrls !== []) {
            $stopPropagandaInner = new StopPropaganda(array_values($outerLoopUrls));
            $stopPropagandaInner->start();
        }
    }
}