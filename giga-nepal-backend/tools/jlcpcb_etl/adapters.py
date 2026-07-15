"""Target adapter interfaces for JLCPCB ETL writes."""

from __future__ import annotations

from typing import Any, Iterable, Protocol

from .canonical_adapter import AdapterResult, NeoGigaCanonicalAdapter
from .transformer import TransformedPart


class TargetCatalogAdapter(Protocol):
    def publish(self, parts: Iterable[TransformedPart]) -> AdapterResult:
        ...


class StandaloneTestAdapter:
    """No-op adapter used for dry-run validation and local tests."""

    def publish(self, parts: Iterable[TransformedPart]) -> AdapterResult:
        items = list(parts)
        return AdapterResult(rows_read=len(items))


class TargetAdapterRegistry:
    @staticmethod
    def create_neogiga(
        dsn: str,
        *,
        source_checksum: str | None,
        dry_run: bool,
        no_search_index: bool = False,
        no_seo: bool = False,
        source_provenance: dict[str, Any] | None = None,
        import_mode: str = "pilot",
    ) -> NeoGigaCanonicalAdapter:
        return NeoGigaCanonicalAdapter(
            dsn,
            source_checksum=source_checksum,
            dry_run=dry_run,
            no_search_index=no_search_index,
            no_seo=no_seo,
            source_provenance=source_provenance,
            import_mode=import_mode,
        )
