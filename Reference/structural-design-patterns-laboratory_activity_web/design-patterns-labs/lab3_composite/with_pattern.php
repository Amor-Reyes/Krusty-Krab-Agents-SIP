<?php
// Lab 3 - WITH Composite (GOOD starter, live API, has TODOs)

// ===== FORUM VARIANT: ForumComponent =====

interface ForumComponent
{
    public function display(int $depth = 0): void;
}

class Post implements ForumComponent
{
    public function __construct(
        private string $author,
        private string $message
    ) {}

    public function display(int $depth = 0): void
    {
        $indent = str_repeat("  ", $depth);
        echo $indent . "- Post by {$this->author}: {$this->message}\n";
    }
}

class Thread implements ForumComponent
{
    /** @var ForumComponent[] */
    private array $children = [];

    public function __construct(private string $title) {}

    public function add(ForumComponent $c): void
    {
        $this->children[] = $c;
    }

    public function display(int $depth = 0): void
    {
        // Missing 1: loop over $this->children and call $child->display($depth + 1)
        // foreach ($this->children as $child) { $child->display($depth + 1); }
        $indent = str_repeat("  ", $depth);
        echo $indent . "+ Thread: {$this->title}\n";
        foreach ($this->children as $child) {
            $child->display($depth + 1);
        }
    }

    public static function fromApi(int $postId): self
    {
        $postJson    = file_get_contents("https://jsonplaceholder.typicode.com/posts/{$postId}");
        $post        = json_decode($postJson, true);
        $thread      = new self($post['title']);
        $thread->add(new Post("Author {$post['userId']}", substr($post["body"], 0, 40) . "..."));
        $commentsJson = file_get_contents("https://jsonplaceholder.typicode.com/posts/{$postId}/comments");
        $comments    = json_decode($commentsJson, true);
        $replies     = new Thread("Replies");
        foreach (array_slice($comments, 0, 2) as $c) {
            $replies->add(new Post($c["email"], substr($c["body"], 0, 30) . "..."));
        }
        $thread->add($replies);
        return $thread;
    }
}

// Missing 3 (extensibility): Bundle - a pre-set combo ForumComponent as a new leaf (1 class, no client changes)
class Bundle implements ForumComponent
{
    private array $items;

    public function __construct(string ...$labels)
    {
        $this->items = $labels;
    }

    public function display(int $depth = 0): void
    {
        $indent = str_repeat("  ", $depth);
        echo $indent . "[Bundle] " . implode(', ', $this->items) . "\n";
    }
}

// ===== RAG VARIANT: TextComponent (Missing 2) =====

// Missing 2: Create the RAG variant: Component TextComponent with getText() + embed();
// Leaf Chunk; Composites Section and Document
// (children; getText() recursively concatenates, embed() chunks to embeddings).

interface TextComponent
{
    public function getText(): string;
    public function embed(): array;
}

class Chunk implements TextComponent
{
    public function __construct(private string $content) {}

    public function getText(): string
    {
        return $this->content;
    }

    public function embed(): array
    {
        // In a real system this would call an embeddings API.
        return [crc32($this->content)];
    }
}

class Section implements TextComponent
{
    /** @var TextComponent[] */
    private array $children = [];

    public function __construct(private string $title) {}

    public function add(TextComponent $c): void
    {
        $this->children[] = $c;
    }

    public function getText(): string
    {
        $parts = ["== {$this->title} =="];
        foreach ($this->children as $child) {
            $parts[] = $child->getText();
        }
        return implode("\n", $parts);
    }

    public function embed(): array
    {
        $embeddings = [];
        foreach ($this->children as $child) {
            $embeddings = array_merge($embeddings, $child->embed());
        }
        return $embeddings;
    }
}

class Document implements TextComponent
{
    /** @var TextComponent[] */
    private array $children = [];

    public function __construct(private string $name) {}

    public function add(TextComponent $c): void
    {
        $this->children[] = $c;
    }

    public function getText(): string
    {
        $parts = ["=== Document: {$this->name} ==="];
        foreach ($this->children as $child) {
            $parts[] = $child->getText();
        }
        return implode("\n", $parts);
    }

    public function embed(): array
    {
        $embeddings = [];
        foreach ($this->children as $child) {
            $embeddings = array_merge($embeddings, $child->embed());
        }
        return $embeddings;
    }
}

if (basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'])) {
    echo "WITH Composite (GOOD - complete TODOs):\n";

    // Forum thread demo (live API)
    $thread = Thread::fromApi(1);
    $thread->display();

    // Add a Bundle leaf (extensibility demo, no client changes)
    $bundle = new Bundle("Pinned", "Announcement", "FAQ");
    $bundle->display(1);

    echo "\n--- RAG Document/Section/Chunk ---\n";

    // RAG document tree demo
    $doc = new Document("Design Patterns Guide");
    $sec1 = new Section("Adapter");
    $sec1->add(new Chunk("Adapter wraps incompatible interfaces."));
    $sec1->add(new Chunk("Use when you cannot change legacy code."));
    $sec2 = new Section("Bridge");
    $sec2->add(new Chunk("Bridge decouples abstraction from implementation."));
    $doc->add($sec1);
    $doc->add($sec2);

    echo $doc->getText() . "\n";
    echo "Embeddings: " . implode(', ', $doc->embed()) . "\n";

    echo "\n  TODO: Fix Thread::display loop, create Document/Section/Chunk RAG.\n";
}
