"""Reject generated/dependency files in a Composer release archive."""
from pathlib import Path
import sys
import zipfile

with zipfile.ZipFile(sys.argv[1]) as archive:
    names = archive.namelist()
    forbidden = ("vendor/", ".git/", ".cache/", ".phpunit.cache/", ".phpstan.cache/", "site/", "tests/", "examples/symfony/var/")
    unwanted = [name for name in names if name.startswith(forbidden) or name in {"composer.lock", "examples/symfony/config/reference.php"}]
    if unwanted:
        raise SystemExit(f"Archive contains {len(unwanted)} unwanted paths: {unwanted[:5]}")
    for required in ("LICENSE", "composer.json", "src/Game/GameRules.php", "src/Import/DrawImporter.php", "src/Import/Source/CsvDrawSource.php", "src/Bridge/Symfony/LotteryToolkitBundle.php", "README.md", "docs/api-reference.md"):
        if required not in names:
            raise SystemExit(f"Archive is missing {required}")
    print(f"Archive verified: {len(names)} files, no dependencies or generated artifacts.")
