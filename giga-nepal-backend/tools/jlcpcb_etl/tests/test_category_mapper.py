from pathlib import Path

from tools.jlcpcb_etl.category_mapper import CategoryMapper, UNKNOWN_PATH


def test_known_category_maps_to_hierarchy():
    mapper = CategoryMapper()
    result = mapper.map_category("Ceramic Capacitors", "123")
    assert result.path == "Passive Components/Capacitors/Ceramic Capacitors"
    assert result.source_category_id == "123"
    assert not result.is_unknown


def test_unknown_category_goes_to_review():
    mapper = CategoryMapper()
    result = mapper.map_category("Unusual Quantum Widget", "x")
    assert result.path == UNKNOWN_PATH
    assert result.is_unknown


def test_custom_mapping_file(tmp_path: Path):
    mapping_file = tmp_path / "categories.yaml"
    mapping_file.write_text("mappings:\n  custom category: Test/Custom\n", encoding="utf-8")
    mapper = CategoryMapper(mapping_file)
    assert mapper.map_category("Custom Category").path == "Test/Custom"


def test_ancestors_are_stable():
    assert CategoryMapper.ancestors("A/B/C") == ["A", "A/B", "A/B/C"]
