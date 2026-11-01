<?php

namespace App\Brain;

use Symfony\Component\Clock\ClockInterface;

/**
 * The brain is a folder of markdown notes. Raw material lands in inbox/, finished notes
 * in brain/ as Open Knowledge Format concept documents: YAML frontmatter with a type, then markdown.
 */
final class Brain
{
    /** OKF names the producer of generated content as name/version */
    private const PRODUCER = 'synapse/1.0';

    public function __construct(
        private readonly Vault $vault,
        private readonly ClockInterface $clock,
        private readonly OkfValidator $validator,
    ) {
    }

    /**
     * @return list<string>
     */
    public function listAll(): array
    {
        return $this->vault->list();
    }

    public function read(string $path): string
    {
        return $this->vault->read($path);
    }

    public function saveToInbox(string $slug, string $text): string
    {
        if (!preg_match('/^[a-z0-9][a-z0-9-]*\z/', $slug)) {
            throw new \InvalidArgumentException(sprintf('"%s" is not a valid slug. Use lowercase letters, digits and dashes, for example "mcp-auth-idea".', $slug));
        }
        $path = sprintf('inbox/%s-%s.md', $this->now()->format('Y-m-d'), $slug);
        $this->vault->create($path, rtrim($text)."\n");

        return $path;
    }

    /**
     * @param list<string> $tags
     * @param list<string> $sources
     */
    public function saveToBrain(string $path, string $type, string $title, string $description, array $tags, string $body, array $sources = []): string
    {
        $this->validator->validate($path, $type, $title, $description, $tags, $body, $sources);
        $this->assertLinksExist($body);

        $path = 'brain/'.$path;
        $now = $this->now();
        $frontmatter = sprintf(
            "type: %s\ntitle: %s\ndescription: %s\ntags: [%s]\nstatus: draft\ngenerated:\n  by: %s\n  at: %s\n",
            $type,
            $this->scalar($title),
            $this->scalar($description),
            implode(', ', $tags),
            self::PRODUCER,
            $now->format('Y-m-d\TH:i:s\Z'),
        );
        if ([] !== $sources) {
            $frontmatter .= "sources:\n";
            foreach (array_values($sources) as $i => $source) {
                $frontmatter .= sprintf("  - id: s%d\n    resource: %s\n", $i + 1, $this->scalar($source));
            }
        }

        $this->vault->create($path, sprintf("---\n%s---\n\n%s\n", $frontmatter, rtrim($body)));
        // The note exists now. A failing log must not turn into an error, because the agent would retry under another name.
        try {
            $this->log($now->format('Y-m-d'), sprintf('added %s', $path));
        } catch (\Throwable $e) {
            error_log(sprintf('synapse: could not write log.md for %s: %s', $path, $e->getMessage()));
        }

        return $path;
    }

    /**
     * log.md is grouped by day with the newest day first, as OKF asks for.
     */
    private function log(string $day, string $entry): void
    {
        $heading = "## $day\n\n";
        $this->vault->update('brain/log.md', static function (string $log) use ($heading, $entry): string {
            if (str_contains($log, $heading)) {
                return str_replace($heading, $heading."* $entry\n", $log);
            }

            $updated = preg_replace('/^(# [^\n]*\n\n)/', "\$1{$heading}* $entry\n\n", $log, 1, $count);

            // A log.md without its title line would swallow the entry, so it gets a title instead.
            return 1 === $count ? $updated : "# Update log\n\n{$heading}* $entry\n\n".ltrim($log);
        }, "# Update log\n\n");
    }

    /**
     * The prompt says "link only to notes that list_notes showed you". A model can ignore that, so the code checks it.
     */
    private function assertLinksExist(string $body): void
    {
        if (!preg_match_all('#\]\((/[^)\s]+\.md)\)#', $body, $links)) {
            return;
        }
        $existing = $this->vault->list();
        $missing = array_filter(array_unique($links[1]), static fn (string $link): bool => !\in_array('brain'.$link, $existing, true));
        if ([] !== $missing) {
            throw new \InvalidArgumentException(sprintf('These links point to notes that do not exist: %s. Link only to notes that list_notes showed you.', implode(', ', $missing)));
        }
    }

    /**
     * Dates in the notes are UTC, because the frontmatter says so with a trailing Z.
     */
    private function now(): \DateTimeImmutable
    {
        return $this->clock->now()->setTimezone(new \DateTimeZone('UTC'));
    }

    private function scalar(string $value): string
    {
        return json_encode(str_replace("\n", ' ', $value), \JSON_UNESCAPED_UNICODE | \JSON_UNESCAPED_SLASHES);
    }
}
