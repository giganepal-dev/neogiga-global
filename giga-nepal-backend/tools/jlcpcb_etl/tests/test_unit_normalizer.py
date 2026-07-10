from decimal import Decimal

from tools.jlcpcb_etl.unit_normalizer import normalize_unit_value


def test_capacitance_nf_to_pf():
    result = normalize_unit_value("100nF")
    assert result.ok
    assert result.normalized_value == Decimal("100000")
    assert result.normalized_unit == "pF"


def test_capacitance_uf_to_pf():
    result = normalize_unit_value("0.1uF")
    assert result.ok
    assert result.normalized_value == Decimal("100000")
    assert result.normalized_unit == "pF"


def test_unicode_microfarad():
    result = normalize_unit_value("0.1µF")
    assert result.ok
    assert result.normalized_value == Decimal("100000")


def test_resistance_kohm_unicode():
    result = normalize_unit_value("4.7kΩ")
    assert result.ok
    assert result.normalized_value == Decimal("4700")
    assert result.normalized_unit == "Ω"


def test_resistance_megaohm():
    result = normalize_unit_value("1MΩ")
    assert result.ok
    assert result.normalized_value == Decimal("1000000")


def test_voltage_mv_to_v():
    result = normalize_unit_value("250mV")
    assert result.ok
    assert result.normalized_value == Decimal("0.250")
    assert result.normalized_unit == "V"


def test_invalid_ambiguous_value():
    result = normalize_unit_value("1/2W")
    assert not result.ok
    assert "ambiguous" in result.error
