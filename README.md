Synchronise two directories.
============================

`rsync` is the tool of choice for ensuring that two directories have the same content, but is only present by default on Unix systems. This repository provides simple recursive directory synchronisation in plain PHP, compatible on Linux, Mac and Windows.

***

<a href="https://circleci.com/gh/PhpGt/Sync" target="_blank">
	<img src="https://badge.status.php.gt/sync-build.svg" alt="Build status" />
</a>
<a href="https://scrutinizer-ci.com/g/PhpGt/Sync" target="_blank">
	<img src="https://badge.status.php.gt/sync-quality.svg" alt="Code quality" />
</a>
<a href="https://scrutinizer-ci.com/g/PhpGt/Sync" target="_blank">
	<img src="https://badge.status.php.gt/sync-coverage.svg" alt="Code coverage" />
</a>
<a href="https://packagist.org/packages/PhpGt/Sync" target="_blank">
	<img src="https://badge.status.php.gt/sync-version.svg" alt="Current version" />
</a>
<a href="http://www.php.gt/sync" target="_blank">
	<img src="https://badge.status.php.gt/sync-docs.svg" alt="PHP.Gt/Sync documentation" />
</a>

## Example usage

```php
$source = "/var/www/example.com";
$destination = "/var/backup/example.com";

try {
	$sync = new DirectorySync($source, $destination);
	$sync->exec(DirectorySync::MODIFIED_TIME);
}
catch(SyncException $exception) {
	fwrite(STDERR, "Error performing sync: " . $exception->getMessage());
	exit(1);
}

$stats = $sync->getStats();
echo "Sync complete!" . PHP_EOL;
echo "Changed: " . $stats->getChangedCount();
echo "Deleted: " . $stats->getDeletedCount();
echo "Untouched: " . $stats->getUntouchedCount();
```