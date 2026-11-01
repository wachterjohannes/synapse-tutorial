<?php

namespace App\Ai;

use App\Brain\Brain;
use Symfony\AI\Agent\Toolbox\Attribute\AsTool;

#[AsTool('save_to_brain', 'Writes a finished concept document into the brain (OKF format). Checks the format and returns an error that says what to fix. Creates a new note and never replaces an existing one.')]
final class SaveToBrain
{
    public function __construct(private readonly Brain $brain)
    {
    }

    /**
     * @param string       $path        folder and file name, for example "topics/mcp-auth.md". Folder by type: Topic topics, Decision decisions, Person people, Project projects
     * @param string       $type        Topic, Decision, Person or Project
     * @param string       $title       a short title
     * @param string       $description one sentence that says what the note is about
     * @param list<string> $tags        a few lowercase tags
     * @param string       $body        the markdown body, starting with "# " and the title, in the shape of the type
     * @param list<string> $sources     full URLs the material came from, cited in the body as [^s1], [^s2]
     */
    public function __invoke(string $path, string $type, string $title, string $description, array $tags, string $body, array $sources = []): string
    {
        try {
            return 'Saved '.$this->brain->saveToBrain($path, $type, $title, $description, $tags, $body, $sources);
        } catch (\InvalidArgumentException $e) {
            return 'Error: '.$e->getMessage();
        }
    }
}
