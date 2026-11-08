# Synapse: a second brain with Symfony AI

A tutorial series for the Symfony AI hackathon. We build a second brain: a folder of markdown notes in the Open Knowledge Format that agents write to and read from, that other tools reach over MCP, and that can ask a second brain for help. Everything runs on Symfony AI 1.0.0-BETA1 with Cohere as the model.

The code lives in branches. Each branch builds on the previous one and holds the finished state of its step.

| Branch | What you get | Changes |
|---|---|---|
| [`01-write-agent`](../../tree/01-write-agent) | A capture agent that files raw material in the inbox and writes finished notes into the brain | - |
| [`02-read-agent`](../../tree/02-read-agent) | A read agent that answers from the brain, with RAG in the background | [`01-write-agent...02-read-agent`](../../compare/01-write-agent...02-read-agent) |
| [`03-mcp-server`](../../tree/03-mcp-server) | The brain as an MCP server, protected by an external authorization server | [`02-read-agent...03-mcp-server`](../../compare/02-read-agent...03-mcp-server) |

`git diff origin/02-read-agent origin/03-mcp-server` shows what a step changes.

## Requirements

- PHP 8.4 or newer and Composer
- A `COHERE_API_KEY` from https://dashboard.cohere.com/api-keys. A trial key is enough for this repository, because the brain is small.

Symfony AI is at `1.0.0-BETA1`. The backwards compatibility promise starts with 1.0.0, so the code may need small changes when the final release ships. The project sets `minimum-stability` to `beta` for that reason.

## Start

```bash
git checkout 01-write-agent
composer install
printf 'COHERE_API_KEY=...\n' > .env.local
php bin/console app:capture "Idea: a tutorial about MCP authorization"
```

The agent writes to `notes/`. `notes/inbox/` holds raw material, `notes/brain/` holds finished notes in OKF v0.2. The rules and the shape of each note type are in `config/okf.yaml`, and `src/Brain/OkfValidator.php` enforces the structure before anything is written to `notes/brain/`: path and folder, type, title, a one line description, one to four tags, sources, the heading and sections, and footnotes. `Brain::assertLinksExist()` checks that note links point to notes that exist. Whether a description is a good sentence is left to the model. `notes/inbox/` is raw material and is not validated. Read [the safety section of the first article](https://johanneswachter.dev/blog/synapse-capture-agent-symfony-ai/) before you give an agent write access to anything you care about, and keep `notes/` in Git so every change is a diff you can review.


## The index

From step 02 the notes are searched through an index in `var/brain.sqlite`. It is not updated when a note is written. Run `php bin/console ai:store:clear brain --force` before `php bin/console ai:store:index brain`, because indexing twice stores every passage twice. The live run of `bin/smoke` drops and rebuilds this index.

## Who may write in step 03

Step 03 checks that a token is valid, for this server and not for another one, but every valid token may use the write tools. Roles arrive in step 04.

## Check a branch

`bin/smoke` runs the checks for the current branch. Without a key it checks the wiring, with a key the agents answer for real. It puts your notes back when it ends. If a run is killed hard, `var/smoke-notes` holds the original notes, and the next run refuses to start until you have moved them back.

The code is MIT licensed, see `LICENSE`.

