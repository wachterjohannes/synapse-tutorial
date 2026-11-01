<?php

namespace App\Ai;

use App\Brain\Brain;
use Symfony\AI\Agent\Toolbox\Attribute\AsTool;

#[AsTool('save_to_inbox', 'Saves raw, unsorted material as a new note in the inbox. Use this when you are unsure where the material belongs.')]
final class SaveToInbox
{
    public function __construct(private readonly Brain $brain)
    {
    }

    /**
     * @param string $slug short lowercase name with dashes, for example "mcp-auth-idea"
     * @param string $text the text of the note
     */
    public function __invoke(string $slug, string $text): string
    {
        try {
            return 'Saved '.$this->brain->saveToInbox($slug, $text);
        } catch (\InvalidArgumentException $e) {
            return 'Error: '.$e->getMessage();
        }
    }
}
