"""Build a reviewable static artifact without publishing it."""
from pathlib import Path
import shutil
import subprocess

root = Path(__file__).resolve().parent.parent
stage = root / ".cache" / "docs-source"
if stage.exists():
    shutil.rmtree(stage)
stage.mkdir(parents=True)
for path in root.glob("*.md"):
    if path.name != "README.md":
        shutil.copy2(path, stage / path.name)
shutil.copy2(root / "README.md", stage / "index.md")
shutil.copy2(root / "LICENSE", stage / "LICENSE")
shutil.copytree(root / "docs", stage / "docs")
shutil.copytree(root / "examples", stage / "examples", ignore=shutil.ignore_patterns("var", "reference.php"))
for page in stage.rglob("*.md"):
    content = page.read_text()
    content = content.replace("(README.md", "(index.md").replace("(../README.md", "(../index.md")
    page.write_text(content)
subprocess.run(["mkdocs", "build", "--strict", "--config-file", str(root / "mkdocs.yml")], check=True, cwd=root)
