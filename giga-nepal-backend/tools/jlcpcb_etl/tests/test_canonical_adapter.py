from tools.jlcpcb_etl.canonical_adapter import NeoGigaCanonicalAdapter, payload_hash, slugify, stable_sku
from tools.jlcpcb_etl.tests.test_transformer import MAPPING, sample_record
from tools.jlcpcb_etl.transformer import transform_record


def test_slugify_and_stable_sku_are_deterministic():
    assert slugify("Texas Instruments Inc.") == "texas-instruments-inc"
    assert stable_sku("C123") == "JLCPCB-C123"


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
