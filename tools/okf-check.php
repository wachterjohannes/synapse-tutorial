<?php
// Writes into a throw-away brain and checks the OKF rules. No API key needed.
require __DIR__.'/../vendor/autoload.php';

use App\Brain\Brain;
use App\Brain\OkfFormat;
use App\Brain\OkfValidator;
use App\Brain\Vault;
use Symfony\Component\Clock\Clock;

$root = sys_get_temp_dir().'/okf-check-'.bin2hex(random_bytes(4));
mkdir($root.'/brain', 0o775, true);
file_put_contents($root.'/brain/log.md', "# Update log\n\n");
$brain = new Brain(new Vault($root), new Clock(), new OkfValidator(new OkfFormat(__DIR__.'/../config/okf.yaml')));

$fails = [];
$expect = static function (string $what, bool $ok) use (&$fails): void {
    if (!$ok) {
        $fails[] = $what;
    }
};
$rejects = static function (callable $call): bool {
    try {
        $call();
    } catch (InvalidArgumentException) {
        return true;
    }

    return false;
};

$decision = "# Use hyphens\n\n## Context\n\nNames vary.\n\n## Decision\n\nHyphens.\n\n## Consequences\n\nReadable paths.\n";
$path = $brain->saveToBrain('decisions/use-hyphens.md', 'Decision', 'Use hyphens', 'File names use hyphens.', ['naming'], $decision, ['https://example.com/a']);
$file = (string) file_get_contents($root.'/'.$path);
$expect('frontmatter has type, status draft and generated', str_contains($file, "type: Decision\n") && str_contains($file, "status: draft\n") && str_contains($file, "generated:\n  by: synapse/1.0\n"));
$expect('frontmatter lists the source with an id', str_contains($file, "sources:\n  - id: s1\n"));
$expect('log.md has a dated heading with the entry', 1 === preg_match('/^# Update log\n\n## \d{4}-\d\d-\d\d\n\n\* added brain\/decisions\/use-hyphens\.md\n/', (string) file_get_contents($root.'/brain/log.md')));

$expect('a missing type is rejected', $rejects(fn () => $brain->saveToBrain('topics/a.md', '', 'A', 'a.', ['a'], "# A\n")));
$expect('a Decision without sections is rejected', $rejects(fn () => $brain->saveToBrain('decisions/b.md', 'Decision', 'B', 'b.', ['b'], "# B\n\nJust text.\n")));
$expect('a wrong folder for the type is rejected', $rejects(fn () => $brain->saveToBrain('topics/c.md', 'Decision', 'C', 'c.', ['c'], $decision)));
$expect('the reserved name index.md is rejected', $rejects(fn () => $brain->saveToBrain('topics/index.md', 'Topic', 'Index', 'i.', ['i'], "# Index\n")));
$expect('a body without the title heading is rejected', $rejects(fn () => $brain->saveToBrain('topics/d.md', 'Topic', 'D', 'd.', ['d'], "Text only.\n")));
$expect('a relative link is rejected', $rejects(fn () => $brain->saveToBrain('topics/e.md', 'Topic', 'E', 'e.', ['e'], "# E\n\nSee [x](other.md).\n")));
$expect('a footnote without a source is rejected', $rejects(fn () => $brain->saveToBrain('topics/f.md', 'Topic', 'F', 'f.', ['f'], "# F\n\nClaim.[^s1]\n")));
$expect('an existing note is not replaced', $rejects(fn () => $brain->saveToBrain('decisions/use-hyphens.md', 'Decision', 'Use hyphens', 'again.', ['naming'], $decision)));

$expect('too many tags are rejected', $rejects(fn () => $brain->saveToBrain('topics/g.md', 'Topic', 'G', 'g.', ['a', 'b', 'c', 'd', 'e'], "# G\n")));
$expect('a link to a note that does not exist is rejected', $rejects(fn () => $brain->saveToBrain('topics/h.md', 'Topic', 'H', 'h.', ['h'], "# H\n\nSee [x](/topics/nothing.md).\n")));
$expect('a tag with a trailing newline is rejected', $rejects(fn () => $brain->saveToBrain('topics/i.md', 'Topic', 'I', 'i.', ["tag\n"], "# I\n")));
$expect('a path with a trailing newline is rejected', $rejects(fn () => $brain->saveToBrain("topics/j.md\n", 'Topic', 'J', 'j.', ['j'], "# J\n")));

$vault = new Vault($root);
$expect('a vault path with a trailing newline is rejected', $rejects(fn () => $vault->read("brain/log.md\n")));
symlink('/etc/hosts', $root.'/brain/leak.md');
$expect('a symlink inside the vault is not read', $rejects(fn () => $vault->read('brain/leak.md')));
$expect('a symlink inside the vault is not listed', !\in_array('brain/leak.md', $vault->list(), true));
symlink('/etc', $root.'/inbox-link');
mkdir($root.'/inbox');
symlink('/tmp', $root.'/inbox/out');
$expect('a note cannot be created through a symlinked folder', $rejects(fn () => $vault->create('inbox/out/x.md', 'x')));
unlink($root.'/brain/leak.md');

// "php tools/okf-check.php -v" prints what the Vault says to each of these.
$show = static function (string $path, callable $call): void {
    try {
        $call();
    } catch (InvalidArgumentException $e) {
        if (\in_array('-v', $_SERVER['argv'] ?? [], true)) {
            echo $path.' => '.$e->getMessage()."\n";
        }
    }
};
foreach (['brain/../../.env', 'brain/topics/x.txt', '/etc/passwd.md', 'brain//x.md'] as $bad) {
    $expect(sprintf('"%s" is not a valid note path', $bad), $rejects(fn () => $vault->read($bad)));
    $show($bad, fn () => $vault->read($bad));
}
$show('brain/decisions/use-hyphens.md', fn () => $vault->create('brain/decisions/use-hyphens.md', 'again'));
$expect('a slug with a slash is rejected', $rejects(fn () => $brain->saveToInbox('a/b', 'x')));

// A log.md that lost its title line must not swallow the entry.
file_put_contents($root.'/brain/log.md', "no title here\n");
$brain->saveToBrain('topics/no-title.md', 'Topic', 'No title', 'The entry survives.', ['log'], "# No title\n");
$expect('a log.md without a title still gets the entry', str_contains((string) file_get_contents($root.'/brain/log.md'), 'added brain/topics/no-title.md'));

$expect('the timestamp in the frontmatter is UTC', 1 === preg_match('/at: (\S+)Z\n/', $file, $at) && abs(strtotime($at[1].' UTC') - time()) < 120);

unlink($root.'/brain/log.md');
$brain->saveToBrain('topics/after-log.md', 'Topic', 'After log', 'The log is created when it is missing.', ['log'], "# After log\n");
$expect('a missing log.md is created instead of failing', is_file($root.'/brain/topics/after-log.md') && str_contains((string) file_get_contents($root.'/brain/log.md'), 'added brain/topics/after-log.md'));

exec('rm -rf '.escapeshellarg($root));
if ($fails) {
    fwrite(\STDERR, "OKF check failed: ".implode('; ', $fails)."\n");
    exit(1);
}
