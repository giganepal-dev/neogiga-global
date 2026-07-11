from uuid import UUID

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


def test_source_link_preserves_final_review_status_sql():
    adapter = NeoGigaCanonicalAdapter("postgresql://not-used", source_checksum="abc", dry_run=True)

    import inspect

    body = inspect.getsource(adapter._source_link)
    assert "WHEN catalog_product_sources.review_status IN ('approved', 'rejected')" in body


def test_create_batch_returns_string_uuid():
    class Result:
        def fetchone(self):
            return {"id": UUID("b146b6d9-3f1c-4795-a1d2-a9cbedcba081")}

    class Conn:
        def execute(self, *_args, **_kwargs):
            return Result()

    adapter = NeoGigaCanonicalAdapter("postgresql://not-used", source_checksum="abc", dry_run=True)

    assert adapter._create_batch(Conn(), 1, 1000) == "b146b6d9-3f1c-4795-a1d2-a9cbedcba081"
