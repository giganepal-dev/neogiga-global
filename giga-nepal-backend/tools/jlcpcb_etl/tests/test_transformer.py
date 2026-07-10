import pytest

from tools.jlcpcb_etl.schema_inspector import SourceMapping
from tools.jlcpcb_etl.transformer import parse_price_breaks, transform_record


MAPPING = SourceMapping(
    parts_table="components",
    source_id="id",
    mpn="mpn",
    manufacturer="manufacturer",
    category="category",
    description="description",
    package="package",
    datasheet_url="datasheet",
    sku="lcsc",
    stock="stock",
    price_breaks="prices",
)


def sample_record():
    return {
        "id": "C123",
        "mpn": "ABC-1",
        "manufacturer": "Texas Instruments Incorporated",
        "category": "Ceramic Capacitors",
        "description": "Test part",
        "package": "0603",
        "datasheet": "https://example.com/data.pdf",
        "lcsc": "C123",
        "stock": "42",
        "prices": '{"1": "0.10", "10": "0.08"}',
        "capacitance": "100nF",
        "unmapped_note": "AEC-Q200",
    }


def test_transform_preserves_identity_and_raw_attributes():
    transformed = transform_record(sample_record(), MAPPING)
    assert transformed.source_part_id == "C123"
    assert transformed.normalized_mpn == "ABC-1"
    assert transformed.manufacturer.normalized_name == "texas instruments"
    assert transformed.category.path == "Passive Components/Capacitors/Ceramic Capacitors"
    assert transformed.attributes["capacitance"]["normalized_value"] == "100000"
    assert transformed.attributes["raw"]["unmapped_note"] == "AEC-Q200"
    assert transformed.offer["stock"] == 42
    assert transformed.offer["price_breaks"] == [{"quantity": "1", "price": "0.10"}, {"quantity": "10", "price": "0.08"}]


def test_transform_is_idempotent_for_same_input():
    first = transform_record(sample_record(), MAPPING)
    second = transform_record(sample_record(), MAPPING)
    assert first.normalized_mpn == second.normalized_mpn
    assert first.manufacturer.normalized_name == second.manufacturer.normalized_name
    assert first.source_part_id == second.source_part_id


def test_malformed_price_breaks_warns_and_keeps_record():
    record = sample_record()
    record["prices"] = "not-a-price"
    transformed = transform_record(record, MAPPING)
    assert transformed.offer["price_breaks"] == []
    assert any("malformed price break" in warning for warning in transformed.warnings)


def test_parse_price_breaks_rejects_malformed_text():
    with pytest.raises(ValueError):
        parse_price_breaks("bad")


def test_missing_manufacturer_is_skipped():
    record = sample_record()
    record["manufacturer"] = ""
    with pytest.raises(ValueError, match="missing manufacturer"):
        transform_record(record, MAPPING)


def test_missing_mpn_uses_stable_source_id_fallback():
    record = sample_record()
    record["mpn"] = ""
    transformed = transform_record(record, MAPPING)
    assert transformed.mpn == "LCSC-C123"
    assert any("missing MPN" in warning for warning in transformed.warnings)


def test_missing_mpn_column_uses_stable_source_id_fallback():
    mapping = SourceMapping(
        parts_table="components",
        source_id="lcsc",
        mpn=None,
        manufacturer="manufacturer",
        category="category",
        description="description",
        package="package",
        datasheet_url="datasheet",
        sku="lcsc",
        stock="stock",
        price_breaks="prices",
    )
    transformed = transform_record(sample_record(), mapping)
    assert transformed.mpn == "LCSC-C123"


def test_lookup_enriched_real_schema_values_are_used():
    mapping = SourceMapping(
        parts_table="components",
        source_id="lcsc",
        mpn="mfr",
        manufacturer="manufacturer_id",
        category="category_id",
        description="description",
        package="package",
        datasheet_url="datasheet",
        sku="lcsc",
        stock="stock",
        price_breaks="price",
    )
    record = {
        "lcsc": 1002,
        "mfr": "GZ1608D601TF",
        "manufacturer_id": 10,
        "__manufacturer_name": "Sunlord",
        "category_id": 1,
        "__category_parent": "Filters/EMI Optimization",
        "__category_name": "Ferrite Beads",
        "description": "Ferrite Bead",
        "package": "0603",
        "datasheet": "",
        "stock": 100,
        "price": '[{"qFrom": 20, "price": 0.01}]',
    }
    transformed = transform_record(record, mapping)
    assert transformed.mpn == "GZ1608D601TF"
    assert transformed.manufacturer.display_name == "Sunlord"
    assert transformed.category.path == "Filters & EMI/Ferrite Beads"
