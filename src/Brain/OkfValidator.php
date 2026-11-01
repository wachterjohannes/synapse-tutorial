<?php

namespace App\Brain;

/**
 * Checks a note before it is written. OKF itself only asks for a type, everything else here is
 * a house rule of this brain, described in config/okf.yaml: a fixed set of types, one folder per type, a body with a known shape.
 * The messages are written for the agent, which reads them and tries again.
 */
final class OkfValidator
{
    /** OKF reserves these two names in every folder */
    private const RESERVED = ['index.md', 'log.md'];

    public function __construct(private readonly OkfFormat $format)
    {
    }

    /**
     * @param list<string> $tags
     * @param list<string> $sources
     */
    public function validate(string $path, string $type, string $title, string $description, array $tags, string $body, array $sources): void
    {
        $errors = [];

        if ('' === trim($type)) {
            $errors[] = 'The note needs a type.';
        } elseif (!$this->format->has($type)) {
            $errors[] = sprintf('The type "%s" is unknown. Use one of: %s.', $type, implode(', ', $this->format->types()));
        }

        if (!preg_match('#^([a-z]+)/([a-z0-9]+(?:-[a-z0-9]+)*)\.md\z#', $path, $m)) {
            $errors[] = sprintf('The path "%s" must look like topics/mcp-auth.md: a folder, then lowercase words joined by hyphens, then .md.', $path);
        } else {
            if (\in_array(basename($path), self::RESERVED, true)) {
                $errors[] = sprintf('%s is reserved in OKF. Pick another file name.', basename($path));
            }
            if ($this->format->has($type) && $this->format->folder($type) !== $m[1]) {
                $errors[] = sprintf('A %s belongs into the folder %s/, not %s/.', $type, $this->format->folder($type), $m[1]);
            }
        }

        if ('' === trim($title)) {
            $errors[] = 'The note needs a title.';
        }
        if ('' === trim($description) || str_contains(trim($description), "\n")) {
            $errors[] = 'The description must be one sentence on one line.';
        }
        if ([] === $tags || \count($tags) > 4) {
            $errors[] = 'Give the note one to four tags.';
        }
        if (mb_strlen(trim($description)) > 200) {
            $errors[] = 'The description must be one short sentence, at most 200 characters.';
        }
        foreach ($tags as $tag) {
            if (!preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*\z/', $tag)) {
                $errors[] = sprintf('The tag "%s" must be lowercase, with hyphens between words.', $tag);
            }
        }
        foreach ($sources as $source) {
            if (!preg_match('#^https?://\S+\z#', $source)) {
                $errors[] = sprintf('The source "%s" must be a full http(s) URL.', $source);
            }
        }

        $body = ltrim($body);
        if ('' !== trim($title) && !str_starts_with($body, '# '.trim($title)."\n") && trim($body) !== '# '.trim($title)) {
            $errors[] = sprintf('The body must start with the heading "# %s".', trim($title));
        }
        foreach ($this->format->has($type) ? $this->format->sections($type) : [] as $section) {
            if (!preg_match('/^## '.preg_quote($section, '/').'\s*$/m', $body)) {
                $errors[] = sprintf('A %s needs a section "## %s".', $type, $section);
            }
        }
        if (preg_match('#\]\((?!https?://|mailto:|\#|/)[^)\s]*\.md#', $body)) {
            $errors[] = 'Link to other notes with a path from the brain root, for example [MCP](/topics/mcp.md).';
        }
        if (preg_match_all('/\[\^([A-Za-z0-9_-]+)\]/', $body, $labels)) {
            foreach (array_unique($labels[1]) as $label) {
                if (!preg_match('/^s([1-9]\d*)\z/', $label, $n) || (int) $n[1] > \count($sources)) {
                    $errors[] = sprintf('The footnote [^%s] has no source. Sources are numbered s1, s2 in the order you pass them.', $label);
                }
            }
        }

        if ([] !== $errors) {
            throw new \InvalidArgumentException(implode(' ', $errors));
        }
    }
}
