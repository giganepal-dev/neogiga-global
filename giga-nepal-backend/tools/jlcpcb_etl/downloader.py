"""Download discovery and streaming for the open CDFER SQLite database."""

from __future__ import annotations

import hashlib
import os
import re
import time
from dataclasses import dataclass
from pathlib import Path
from urllib.parse import urljoin, urlparse

import requests


REPO_URL = "https://github.com/CDFER/jlcpcb-parts-database"
README_URL = "https://raw.githubusercontent.com/CDFER/jlcpcb-parts-database/main/README.md"
GITHUB_PAGES_BASE = "https://cdfer.github.io/jlcpcb-parts-database/"
APPROVED_HOSTS = {
    "github.com",
    "raw.githubusercontent.com",
    "objects.githubusercontent.com",
    "github-releases.githubusercontent.com",
    "api.github.com",
    "cdfer.github.io",
}


@dataclass(frozen=True)
class DownloadResult:
    path: Path
    source_url: str
    checksum: str
    size_bytes: int
    reused: bool = False


def is_approved_url(url: str) -> bool:
    parsed = urlparse(url)
    if parsed.scheme != "https":
        return False
    host = parsed.netloc.lower()
    return host in APPROVED_HOSTS or host.endswith(".github.io")


def fetch_readme(session: requests.Session | None = None) -> str:
    client = session or requests.Session()
    response = client.get(README_URL, timeout=30, headers={"User-Agent": "NeoGiga-JLCPCB-ETL"})
    response.raise_for_status()
    return response.text


def discover_sqlite_url(readme_text: str, override_url: str | None = None) -> str:
    if override_url:
        if not is_approved_url(override_url):
            raise ValueError(f"JLCPCB_SQLITE_URL host is not approved: {override_url}")
        return override_url

    candidates: list[str] = []
    for match in re.finditer(r"\[[^\]]+\]\(([^)]+)\)", readme_text):
        candidates.append(match.group(1).strip())
    candidates.extend(re.findall(r"https://[^\s)]+", readme_text))

    scored: list[tuple[int, str]] = []
    for candidate in candidates:
        url = urljoin(GITHUB_PAGES_BASE, candidate.strip())
        if not is_approved_url(url):
            continue
        lowered = url.lower()
        score = 0
        if "sqlite" in lowered:
            score += 10
        if lowered.endswith((".sqlite", ".sqlite3", ".db")):
            score += 10
        if "components" in lowered:
            score += 3
        if "basic" in lowered or "preferred" in lowered or lowered.endswith(".csv"):
            score -= 20
        if score > 0:
            scored.append((score, url))
    if not scored:
        raise RuntimeError("Could not discover official in-stock SQLite URL from README")
    scored.sort(reverse=True)
    return scored[0][1]


def sha256_file(path: Path) -> str:
    digest = hashlib.sha256()
    with path.open("rb") as handle:
        for chunk in iter(lambda: handle.read(1024 * 1024), b""):
            digest.update(chunk)
    return digest.hexdigest()


def download_sqlite(
    output_dir: Path,
    sqlite_file: Path | None = None,
    override_url: str | None = None,
    retries: int = 3,
) -> DownloadResult:
    output_dir.mkdir(parents=True, exist_ok=True)
    if sqlite_file:
        checksum = sha256_file(sqlite_file)
        return DownloadResult(sqlite_file, f"file://{sqlite_file}", checksum, sqlite_file.stat().st_size, True)

    readme = fetch_readme()
    source_url = discover_sqlite_url(readme, override_url)
    filename = Path(urlparse(source_url).path).name or "jlcpcb-components.sqlite3"
    target = output_dir / filename
    checksum_file = target.with_suffix(target.suffix + ".sha256")

    for attempt in range(1, retries + 1):
        try:
            with requests.get(source_url, stream=True, timeout=60, headers={"User-Agent": "NeoGiga-JLCPCB-ETL"}) as response:
                response.raise_for_status()
                tmp = target.with_suffix(target.suffix + ".part")
                digest = hashlib.sha256()
                size = 0
                with tmp.open("wb") as handle:
                    for chunk in response.iter_content(chunk_size=1024 * 1024):
                        if not chunk:
                            continue
                        handle.write(chunk)
                        digest.update(chunk)
                        size += len(chunk)
                checksum = digest.hexdigest()
                if target.exists() and checksum_file.exists() and checksum_file.read_text().strip() == checksum:
                    tmp.unlink(missing_ok=True)
                    return DownloadResult(target, source_url, checksum, target.stat().st_size, True)
                os.replace(tmp, target)
                checksum_file.write_text(checksum + "\n", encoding="utf-8")
                return DownloadResult(target, source_url, checksum, size, False)
        except Exception:
            if attempt == retries:
                raise
            time.sleep(2 ** attempt)
    raise RuntimeError("unreachable download retry state")
