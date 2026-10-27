<?php
// Lab 4 - WITH Decorator (GOOD starter, live API, has TODOs)

interface HttpClient
{
    public function get(string $url): string;
}

class BaseHttpClient implements HttpClient
{
    public function get(string $url): string
    {
        // Live to real endpoint
        return file_get_contents($url);
    }
}

abstract class HttpDecorator implements HttpClient
{
    public function __construct(protected HttpClient $wrapped) {}
}

class LoggingDecorator extends HttpDecorator
{
    public function get(string $url): string
    {
        echo "[Log] GET $url\n";
        $r = $this->wrapped->get($url);
        echo "[Log] Got " . strlen($r) . " bytes\n";
        return $r;
    }
}

class CachingDecorator extends HttpDecorator
{
    private array $cache = [];

    public function get(string $url): string
    {
        if (isset($this->cache[$url])) {
            echo "[Cache] Hit $url\n";
            return $this->cache[$url];
        }
        $r = $this->wrapped->get($url);
        $this->cache[$url] = $r;
        echo "[Cache] Stored $url\n";
        return $r;
    }
}

// Missing 1: RetryDecorator::get() is // TODO
// try the wrapped client, catch Exception, retry up to 3 times, rethrow on the last.
class RetryDecorator extends HttpDecorator
{
    public function get(string $url): string
    {
        // TODO: for ($i=0; $i<3; $i++) try { return $this->wrapped->get($url); } catch (Exception $e) { if ($i==2) throw $e; }
        for ($i = 0; $i < 3; $i++) {
            try {
                return $this->wrapped->get($url);
            } catch (Exception $e) {
                if ($i === 2) {
                    throw $e;
                }
                echo "[Retry] Attempt " . ($i + 1) . " failed, retrying...\n";
            }
        }
        return ""; // unreachable
    }
}

// Missing 2: TokenCounterDecorator::get() is // TODO
// call the wrapped client then echo word count (e.g., str_word_count($r)).
class TokenCounterDecorator extends HttpDecorator
{
    public function get(string $url): string
    {
        // TODO: $r = $this->wrapped->get($url); echo "[Tokens] " . str_word_count($r) . "\n"; return $r;
        $r = $this->wrapped->get($url);
        echo "[Tokens] " . str_word_count($r) . " words\n";
        return $r;
    }
}

if (basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'])) {
    echo "WITH Decorator (GOOD - complete TODOs):\n";

    $url = "https://jsonplaceholder.typicode.com/posts/1";

    // Stack: Logging(Caching(Base))
    $client = new BaseHttpClient();
    $client = new CachingDecorator($client);
    $client = new LoggingDecorator($client);

    echo "\n--- Stack: Logging(Caching(Base)) ---\n";
    echo substr($client->get($url), 0, 60) . "...\n"; // live
    echo substr($client->get($url), 0, 60) . "...\n"; // cached

    // Missing 3: Reorder decorators: new Logging(new Caching(...)) vs new Caching(new Logging(...))
    // document where the log line appears (order matters).
    echo "\n--- Stack: Caching(Logging(Base)) — log appears on every miss ---\n";
    $client2 = new BaseHttpClient();
    $client2 = new LoggingDecorator($client2);
    $client2 = new CachingDecorator($client2);
    echo substr($client2->get($url), 0, 60) . "...\n"; // logs, then caches
    echo substr($client2->get($url), 0, 60) . "...\n"; // cache hit, no log

    // Full 4-decorator stack demo
    echo "\n--- Full stack: TokenCounter(Retry(Logging(Caching(Base)))) ---\n";
    $full = new BaseHttpClient();
    $full = new CachingDecorator($full);
    $full = new LoggingDecorator($full);
    $full = new RetryDecorator($full);
    $full = new TokenCounterDecorator($full);
    echo substr($full->get($url), 0, 60) . "...\n";

    echo "\n  TODO: Implement Retry + TokenCounter, test with \$url\n";
    echo "  4 decorators = 16 combos with 5 classes (BaseHttpClient + 4 decorators).\n";
}
