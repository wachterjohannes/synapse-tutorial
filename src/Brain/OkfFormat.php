<?php

namespace App\Brain;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Yaml\Yaml;

/**
 * The shape of a note, read from config/okf.yaml. The validator checks against it
 * and the describe_format tool prints it, so the agent and the code never disagree.
 */
final class OkfFormat
{
    /** @var array{rules: list<string>, types: array<string, array{folder: string, sections: list<string>, example: string}>} */
    private array $format;

    public function __construct(#[Autowire('%kernel.project_dir%/config/okf.yaml')] string $file)
    {
        $this->format = Yaml::parseFile($file);
    }

    /**
     * @return list<string>
     */
    public function types(): array
    {
        return array_keys($this->format['types']);
    }

    public function has(string $type): bool
    {
        return isset($this->format['types'][$type]);
    }

    public function folder(string $type): string
    {
        return $this->format['types'][$type]['folder'];
    }

    /**
     * @return list<string>
     */
    public function sections(string $type): array
    {
        return $this->format['types'][$type]['sections'];
    }

    /**
     * The rules, plus the folder, sections and an example for one type, or a short list of all types.
     */
    public function describe(string $type = ''): string
    {
        $text = "Rules:\n* ".implode("\n* ", $this->format['rules'])."\n";
        if ('' === $type) {
            $lines = [];
            foreach ($this->types() as $name) {
                $lines[] = sprintf('%s (folder %s/)', $name, $this->folder($name));
            }

            return $text."\nTypes: ".implode(', ', $lines).".\nCall describe_format with a type to see its sections and an example.";
        }
        if (!$this->has($type)) {
            return sprintf('Error: The type "%s" is unknown. Use one of: %s.', $type, implode(', ', $this->types()));
        }

        $sections = $this->sections($type);

        return $text.sprintf(
            "\n%s: folder %s/, %s.\n\nExample body:\n\n%s",
            $type,
            $this->folder($type),
            [] === $sections ? 'no fixed sections' : 'sections '.implode(', ', $sections),
            $this->format['types'][$type]['example'],
        );
    }
}
