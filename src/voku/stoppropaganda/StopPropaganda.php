<?php

declare(strict_types=1);

namespace voku\stoppropaganda;

use Httpful\ClientMulti;
use Httpful\Factory;
use Httpful\Http;
use Httpful\Request;
use Httpful\Response;
use Throwable;
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
                        $urls = [];
                        $links = $dom->findMulti('a');
                        foreach ($links as $link) {
                            $href = $link->getAttribute('href');
                            $newUri = (new Factory())->createUri($href);
                            if ($uri && UTF8::is_url($href) && $newUri->getHost() === $uri->getHost()) {
                                $urls[$href] = $href;
                            }
                        }
                        if ($urls === [] && $uri) {
                            $urlTmp = $uri->__toString();
                            $outerLoopUrls[$urlTmp] = $urlTmp;
                        }

                        try {
                            $stopPropagandaInner = new StopPropaganda(array_values($urls));
                            $stopPropagandaInner->start();
                        } catch (Throwable $e) {
                            // DEBUG
                            var_dump($e->__toString());
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
            try {
                $stopPropagandaInner = new StopPropaganda(array_values($outerLoopUrls));
                $stopPropagandaInner->start();
            } catch (Throwable $e) {
                // DEBUG
                var_dump($e->__toString());
            }
        }
    }
}