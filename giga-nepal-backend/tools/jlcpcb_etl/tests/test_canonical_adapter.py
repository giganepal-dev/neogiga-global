from uuid import UUID

import pytest

from tools.jlcpcb_etl.canonical_adapter import NeoGigaCanonicalAdapter, payload_hash, slugify, stable_sku
from tools.jlcpcb_etl.tests.test_transformer import MAPPING, sample_record
from tools.jlcpcb_etl.transformer import transform_record


def test_slugify_and_stable_sku_are_deterministic():
    assert slugify("Texas Instruments Inc.") == "texas-instruments-inc"
    assert stable_sku("C123") == "NG-C123"


def test_payload_hash_is_stable_for_same_part():
    part = transform_record(sample_record(), MAPPING)

    assert payload_hash(part) == payload_hash(part)


def test_canonical_adapter_dry_run_does_not_connect():
    part = transform_record(sample_record(), MAPPING)
    adapter = NeoGigaCanonicalAdapter("postgresql://not-used", source_checksum="abc", dry_run=True)

    result = adapter.publish([part])

    assert result.rows_read == 1
    assert result.products_inserted == 0
    assert result.import_batch_id is None


def test_quality_score_penalizes_missing_datasheet():
    part = transform_record({**sample_record(), "datasheet": ""}, MAPPING)
    adapter = NeoGigaCanonicalAdapter("postgresql://not-used", source_checksum="abc", dry_run=True)

    assert adapter._quality_score(part) < 1


def test_product_seo_meta_is_localized_and_noindex():
    part = transform_record(sample_record(), MAPPING)
    adapter = NeoGigaCanonicalAdapter("postgresql://not-used", source_checksum="abc", dry_run=True)

    meta = adapter._product_seo_meta(part, "Texas Instruments ABC-1")

    assert meta["robots"] == "noindex,nofollow"
    assert "global" in meta["localized"]
    assert "india" in meta["localized"]
    assert "nepal" in meta["localized"]
    assert "ABC-1" in meta["keywords"]
    assert "Buy ABC-1 Online in India" in meta["localized"]["india"]["title"]
    assert "Technical Data & RFQ" in meta["localized"]["india"]["title"]
    assert meta["localized"]["india"]["domain"] == "in.neogiga.com"
    assert "Buy ABC-1 in Nepal" in meta["localized"]["nepal"]["title"]
    assert meta["localized"]["nepal"]["domain"] == "np.neogiga.com"
    assert "local stock" not in str(meta).lower()
    assert "fast dispatch" not in str(meta).lower()


def test_category_seo_meta_uses_professional_country_templates():
    adapter = NeoGigaCanonicalAdapter("postgresql://not-used", source_checksum="abc", dry_run=True)

    meta = adapter._category_seo_meta("Resistor", "Electronic Components/Resistor")

    assert meta["localized"]["india"]["title"] == "Buy Resistors Online in India | Technical Data & RFQ | NeoGiga India"
    assert meta["localized"]["nepal"]["title"] == "Buy Resistors in Nepal | Technical Data & RFQ | NeoGiga Nepal"
    assert "buy online" in meta["keywords"]


def test_source_link_preserves_final_review_status_sql():
    adapter = NeoGigaCanonicalAdapter("postgresql://not-used", source_checksum="abc", dry_run=True)

    import inspect

    body = inspect.getsource(adapter._source_link)
    assert "WHEN catalog_product_sources.review_status IN ('approved', 'rejected')" in body
    assert "product_id = EXCLUDED.product_id" not in body
    assert "import_batch_id = EXCLUDED.import_batch_id" not in body


def test_canonical_aliases_are_persisted_separately_from_import_errors():
    adapter = NeoGigaCanonicalAdapter("postgresql://not-used", source_checksum="abc", dry_run=True)

    import inspect

    body = inspect.getsource(adapter._source_alias)
    assert "catalog_product_source_aliases" in body
    assert "import_batch_id =" not in body
    assert "original_raw_value" in body
    assert "normalized_value" in body


def test_existing_catalog_values_are_preserved_by_upgrade_only_import():
    adapter = NeoGigaCanonicalAdapter("postgresql://not-used", source_checksum="abc", dry_run=True)

    import inspect

    assert "UPDATE product_specs" not in inspect.getsource(adapter._specs)
    assert "UPDATE product_documents" not in inspect.getsource(adapter._datasheet)
    assert "ON CONFLICT (distributor, sku) DO NOTHING" in inspect.getsource(adapter._offer)


def test_new_product_placeholder_is_local_and_additive_only():
    adapter = NeoGigaCanonicalAdapter("postgresql://not-used", source_checksum="abc", dry_run=True)

    import inspect

    body = inspect.getsource(adapter._placeholder_image)
    assert "/images/products/neogiga-component-placeholder.svg" in body
    assert "WHERE NOT EXISTS" in body
    assert "UPDATE product_images" not in body


def test_writable_publish_requires_complete_provenance_before_connecting():
    part = transform_record(sample_record(), MAPPING)
    adapter = NeoGigaCanonicalAdapter("postgresql://not-used", source_checksum="abc", dry_run=False)

    with pytest.raises(RuntimeError, match="batch provenance fields"):
        adapter.publish([part], last_source_id=part.source_part_id, source_rows_read=1)


def test_adapter_rejects_unbounded_write_chunks_before_connecting():
    part = transform_record(sample_record(), MAPPING)
    adapter = NeoGigaCanonicalAdapter("postgresql://not-used", source_checksum="abc", dry_run=False)

    with pytest.raises(RuntimeError, match="cannot exceed 1000"):
        adapter.publish([part] * 1001, last_source_id=part.source_part_id, source_rows_read=1001)


def test_batch_completion_merges_instead_of_overwriting_provenance_metadata():
    adapter = NeoGigaCanonicalAdapter("postgresql://not-used", source_checksum="abc", dry_run=True)

    import inspect

    body = inspect.getsource(adapter._complete_batch)
    assert "COALESCE(metadata, '{}'::jsonb) ||" in body


def test_create_batch_returns_string_uuid():
    class Result:
        def fetchone(self):
            return {"id": UUID("b146b6d9-3f1c-4795-a1d2-a9cbedcba081")}

    class Conn:
        def execute(self, *_args, **_kwargs):
            return Result()

    adapter = NeoGigaCanonicalAdapter("postgresql://not-used", source_checksum="abc", dry_run=True)

    assert adapter._create_batch(Conn(), 1, 1000) == "b146b6d9-3f1c-4795-a1d2-a9cbedcba081"
