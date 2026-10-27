<?php
// Lab 2 - WITH Bridge (GOOD starter, has gaps to complete)
// Times formatter (abstraction) x DataSource + Compressor (implementors) - compression Bridge.
// Swap the source or the compressor freely without touching the report logic.

// ---- Implementor A: where records come from ----
interface DataSource
{
    public function fetch(): array;
}

class ApiDataSource implements DataSource
{
    public function fetch(): array
    {
        $json = @file_get_contents("https://jsonplaceholder.typicode.com/posts?_limit=4");
        if ($json === false || trim($json) === "") {
            throw new RuntimeException("Live API unreachable.");
        }
        return json_decode($json, true);
    }
}

class FileDataSource implements DataSource
{
    public function fetch(): array
    {
        $json = @file_get_contents(__DIR__ . "/data.json");
        if ($json === false) {
            throw new RuntimeException("data.json not found.");
        }
        return json_decode($json, true);
    }
}

// ---- Implementor B: how output is compressed ----
interface Compressor
{
    public function compress(string $content): string;
    public function isCompressed(): bool;
}

class GzipCompressor implements Compressor
{
    public function compress(string $content): string
    {
        return gzencode($content, 6);
    }

    public function isCompressed(): bool
    {
        return true;
    }
}

class NoneCompressor implements Compressor
{
    public function compress(string $content): string
    {
        return $content;
    }

    public function isCompressed(): bool
    {
        return false;
    }
}

// ---- Abstraction: the report, delegates both varying dimensions ----
abstract class TimesFormatter
{
    public function __construct(
        protected DataSource $source,
        protected Compressor $compressor
    ) {}

    // Missing 1: implement setCompressor($compressor) so a demo can swap compression at runtime.
    public function setCompressor(Compressor $compressor): void
    {
        $this->compressor = $compressor;
    }

    abstract protected function toText(array $records): string;

    public function generate(string $title): string
    {
        $records = $this->source->fetch();
        $body    = $this->toText($records);
        return $this->compressor->compress($title . "\n" . $body);
    }
}

class AttendanceTimesFormatter extends TimesFormatter
{
    protected function toText(array $records): string
    {
        $lines = [];
        foreach ($records as $r) {
            $lines[] = "{$r['userId']}  {$r['id']}  " . substr($r['title'] ?? '', 0, 18);
        }
        return implode("\n", $lines);
    }
}

// Missing 2: TimesheetJsonFormatter extends TimesFormatter using json_encode($records, JSON_PRETTY_PRINT).
class TimesheetJsonFormatter extends TimesFormatter
{
    protected function toText(array $records): string
    {
        return json_encode($records, JSON_PRETTY_PRINT);
    }
}

if (basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'])) {
    echo "WITH Bridge (GOOD - complete TODOs):\n";

    // Missing 3: Demo 2 formats x 2 sources x 2 compressors = 8 combos with 6 classes;
    // show gzip output is smaller than plain.
    $source = new ApiDataSource();

    $plain = new AttendanceTimesFormatter($source, new NoneCompressor());
    $plainOutput = $plain->generate("TIMES");
    echo "  [Attendance/Plain] " . strlen($plainOutput) . " bytes\n";

    $gzip = new AttendanceTimesFormatter($source, new GzipCompressor());
    $gzipOutput = $gzip->generate("TIMES");
    echo "  [Attendance/Gzip]  " . strlen($gzipOutput) . " bytes (gzip smaller: "
        . (strlen($gzipOutput) < strlen($plainOutput) ? 'yes' : 'no') . ")\n";

    $jsonPlain = new TimesheetJsonFormatter($source, new NoneCompressor());
    $jsonPlainOutput = $jsonPlain->generate("TIMES");
    echo "  [Json/Plain]       " . strlen($jsonPlainOutput) . " bytes\n";

    $jsonGzip = new TimesheetJsonFormatter($source, new GzipCompressor());
    $jsonGzipOutput = $jsonGzip->generate("TIMES");
    echo "  [Json/Gzip]        " . strlen($jsonGzipOutput) . " bytes\n";

    // Demonstrate setCompressor() runtime swap
    $plain->setCompressor(new GzipCompressor());
    $swapped = $plain->generate("TIMES-SWAPPED");
    echo "  [Attendance swapped to Gzip at runtime] " . strlen($swapped) . " bytes\n";

    echo "  2 formats x 2 sources x 2 compressors = 8 combos with 6 classes. Bridge wins!\n";
}
