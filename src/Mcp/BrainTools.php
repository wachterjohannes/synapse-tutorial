<?php

namespace App\Mcp;

use App\Ai\DescribeFormat;
use App\Ai\ListNotes;
use App\Ai\ReadNote;
use App\Ai\SaveToBrain;
use App\Ai\SaveToInbox;
use App\Ai\SearchNotes;
use Mcp\Capability\Attribute\McpTool;

/**
 * The brain as an MCP server. The tools are the same objects the agents use,
 * so a file name means the same thing for the agent and for a remote client.
 */
final class BrainTools
{
    public function __construct(
        private readonly SearchNotes $searchNotes,
        private readonly ListNotes $listNotes,
        private readonly ReadNote $readNote,
        private readonly SaveToInbox $saveToInbox,
        private readonly SaveToBrain $saveToBrain,
        private readonly DescribeFormat $describeFormat,
    ) {
    }

    /**
     * Searches the brain and returns the best matching passages with the file they come from.
     *
     * @param string $query what to look for, as a question or a few keywords
     */
    #[McpTool(name: 'search_notes')]
    public function searchNotes(string $query): string
    {
        return ($this->searchNotes)($query);
    }

    /**
     * Lists the paths of all notes in the brain and in the inbox.
     */
    #[McpTool(name: 'list_notes')]
    public function listNotes(): string
    {
        return ($this->listNotes)();
    }

    /**
     * Reads one note by the path list_notes or search_notes shows.
     *
     * @param string $path for example brain/topics/rag-basics.md
     */
    #[McpTool(name: 'read_note')]
    public function readNote(string $path): string
    {
        return ($this->readNote)($path);
    }

    /**
     * Saves raw material as a new note in the inbox.
     *
     * @param string $slug short lowercase name with dashes
     * @param string $text the text of the note
     */
    #[McpTool(name: 'save_to_inbox')]
    public function saveToInbox(string $slug, string $text): string
    {
        return ($this->saveToInbox)($slug, $text);
    }

    /**
     * Shows the rules for a note and, for a given type, its folder, sections and an example. Call it before save_to_brain.
     *
     * @param string $type Topic, Decision, Person or Project. Leave empty to list the types.
     */
    #[McpTool(name: 'describe_format')]
    public function describeFormat(string $type = ''): string
    {
        return ($this->describeFormat)($type);
    }

    /**
     * Writes a finished concept document into the brain. Creates a new note, never replaces one.
     *
     * @param string       $path        path below the brain, for example topics/mcp-auth.md
     * @param string       $type        the kind of concept, for example Topic or Decision
     * @param string       $title       a short title
     * @param string       $description one sentence about the note
     * @param list<string> $tags        a few lowercase tags
     * @param string       $body        the markdown body, starting with "# " and the title
     * @param list<string> $sources     full URLs the material came from, cited in the body as [^s1], [^s2]
     */
    #[McpTool(name: 'save_to_brain')]
    public function saveToBrain(string $path, string $type, string $title, string $description, array $tags, string $body, array $sources = []): string
    {
        return ($this->saveToBrain)($path, $type, $title, $description, $tags, $body, $sources);
    }
}
