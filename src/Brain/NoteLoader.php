<?php

namespace App\Brain;

use Symfony\AI\Store\Document\Loader\DirectoryLoader;
use Symfony\AI\Store\Document\Loader\MarkdownLoader;
use Symfony\AI\Store\Document\LoaderInterface;
use Symfony\AI\Store\Document\Metadata;
use Symfony\Component\Finder\Finder;

/**
 * Loads every markdown note below a folder, one document per file.
 * The source of each document becomes the path relative to the notes folder, because that is how the tools name a note.
 * Symlinks are not followed, like in the Vault: the index only holds real files that lie inside the folder.
 * index.md and log.md are reserved OKF files. They list notes instead of holding knowledge, so they stay out of the index.
 */
final class NoteLoader implements LoaderInterface
{
    private const RESERVED = ['index.md', 'log.md'];

    public function load(?string $source = null, array $options = []): iterable
    {
        $real = realpath((string) $source);
        if (false === $real) {
            return;
        }
        // The source is the folder with the finished notes, so the vault root is one level up.
        $root = \dirname($real).'/';

        // The directory loader hands out resolved paths, so a symlink can no longer be told apart there. Ask the finder first.
        $files = [];
        foreach ((new Finder())->files()->in($real)->name('*.md') as $file) {
            if (!$file->isLink()) {
                $files[$file->getRealPath()] = true;
            }
        }

        foreach ((new DirectoryLoader(['md' => new MarkdownLoader()]))->load($real, $options) as $document) {
            $path = $document->getMetadata()[Metadata::KEY_SOURCE] ?? '';
            if (\in_array(basename($path), self::RESERVED, true) || !isset($files[$path])) {
                continue;
            }
            // Each real file once, even when a link points at it. Store the path the tools use ("brain/topics/rag-basics.md").
            unset($files[$path]);
            $document->getMetadata()[Metadata::KEY_SOURCE] = substr($path, \strlen($root));

            yield $document;
        }
    }
}
