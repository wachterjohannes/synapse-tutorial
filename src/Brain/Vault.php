<?php

namespace App\Brain;

/**
 * The only place that touches the file system. Every path is relative to one root,
 * notes are created once and never replaced, and nothing outside the root can be reached, not even through a symlink.
 */
final class Vault
{
    public function __construct(private readonly string $root)
    {
    }

    /**
     * @return list<string> relative paths of all notes
     */
    public function list(): array
    {
        $notes = [];
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($this->root, \FilesystemIterator::SKIP_DOTS)) as $file) {
            if ('md' === $file->getExtension() && !$file->isLink()) {
                $notes[] = substr($file->getPathname(), \strlen($this->root) + 1);
            }
        }
        sort($notes);

        return $notes;
    }

    public function read(string $path): string
    {
        $file = $this->resolve($path);
        if (!is_file($file)) {
            throw new \InvalidArgumentException(sprintf('There is no note "%s".', $path));
        }

        return (string) file_get_contents($file);
    }

    public function create(string $path, string $content): void
    {
        $file = $this->resolve($path);
        if (!is_dir(\dirname($file)) && !@mkdir(\dirname($file), 0o775, true) && !is_dir(\dirname($file))) {
            throw new \RuntimeException(sprintf('Could not create the folder for "%s".', $path));
        }

        // Mode "x" fails when the file exists, so there is no gap between the check and the write.
        $handle = @fopen($file, 'x');
        if (false === $handle) {
            if (file_exists($file)) {
                throw new \InvalidArgumentException(sprintf('The note "%s" already exists. Pick another name.', $path));
            }

            throw new \RuntimeException(sprintf('Could not write "%s".', $path));
        }
        fwrite($handle, $content);
        fclose($handle);
    }

    /**
     * The one exception to "never replace": log.md has to be rewritten, because its newest day goes on top.
     * The file is locked from reading to writing, so two writers cannot lose each other's change.
     *
     * @param callable(string): string $change gets the current content and returns the new one
     * @param string|null              $initial content to start with when the note does not exist yet
     */
    public function update(string $path, callable $change, ?string $initial = null): void
    {
        $file = $this->resolve($path);
        if (!is_file($file)) {
            if (null === $initial) {
                throw new \InvalidArgumentException(sprintf('There is no note "%s".', $path));
            }
            try {
                $this->create($path, $initial);
            } catch (\InvalidArgumentException) {
                // somebody else created it first, which is fine
            }
        }

        $handle = fopen($file, 'r+');
        if (false === $handle || !flock($handle, \LOCK_EX)) {
            throw new \RuntimeException(sprintf('Could not lock "%s".', $path));
        }
        $content = $change((string) stream_get_contents($handle));
        ftruncate($handle, 0);
        rewind($handle);
        fwrite($handle, $content);
        fflush($handle);
        flock($handle, \LOCK_UN);
        fclose($handle);
    }

    private function resolve(string $path): string
    {
        if (!preg_match('#^(brain|inbox)/[a-z0-9][a-z0-9/_.-]*\.md\z#', $path) || str_contains($path, '..') || str_contains($path, '//')) {
            throw new \InvalidArgumentException(sprintf('"%s" is not a valid note path. Notes live in brain/ or inbox/ and end with .md, for example brain/topics/mcp-auth.md.', $path));
        }

        $file = $this->root.'/'.$path;
        $this->assertInsideRoot($file, $path);

        return $file;
    }

    /**
     * The string check above cannot see symlinks. Resolve the closest existing part of the path
     * and make sure it still sits below the root, and never follow a symlink as the note itself.
     */
    private function assertInsideRoot(string $file, string $path): void
    {
        $existing = $file;
        while (!file_exists($existing) && !is_link($existing)) {
            $existing = \dirname($existing);
        }
        $root = realpath($this->root);
        $real = realpath($existing);

        if (false === $root || false === $real || ($real !== $root && !str_starts_with($real, $root.'/')) || is_link($file)) {
            throw new \InvalidArgumentException(sprintf('"%s" leads outside the vault.', $path));
        }
    }
}
