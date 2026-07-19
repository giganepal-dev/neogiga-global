from tools.jlcpcb_etl.manufacturer_normalizer import normalize_manufacturer_name, normalize_mpn


def test_texas_instruments_suffix_is_removed():
    result = normalize_manufacturer_name("Texas Instruments Incorporated")
    assert result.display_name == "Texas Instruments Incorporated"
    assert result.normalized_name == "texas instruments"


def test_case_insensitive_match():
    result = normalize_manufacturer_name("TEXAS INSTRUMENTS")
    assert result.normalized_name == "texas instruments"


def test_analog_devices_suffix_is_removed():
    result = normalize_manufacturer_name("Analog Devices, Inc.")
    assert result.normalized_name == "analog devices"


def test_aliases_are_configurable():
    result = normalize_manufacturer_name("NXP USA", aliases={"nxp usa": "nxp"})
    assert result.normalized_name == "nxp"


def test_known_texas_instruments_variants_share_the_canonical_identity():
    for value in ("Texas", "Texas I", "Texas instrument", "Texas Instruements"):
        assert normalize_manufacturer_name(value).normalized_name == "texas instruments"


def test_known_analog_devices_maxim_variant_shares_the_canonical_identity():
    result = normalize_manufacturer_name("Analog Devices Inc /Maxim Integrated")
    assert result.normalized_name == "analog devices"


def test_quotes_and_pipe_are_removed_from_source_display_name():
    result = normalize_manufacturer_name(' "LITTELFUSE INC " | ')
    assert result.display_name == "LITTELFUSE INC"


def test_normalize_mpn_removes_spaces_and_uppercases():
    assert normalize_mpn(" esp 32-wroom ") == "ESP32-WROOM"
