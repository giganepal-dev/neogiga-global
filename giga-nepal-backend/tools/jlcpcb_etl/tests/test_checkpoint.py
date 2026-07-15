from tools.jlcpcb_etl.checkpoint import CheckpointStore, ImportCheckpoint


def test_checkpoint_write_read(tmp_path):
    store = CheckpointStore(tmp_path / "checkpoint.json")
    checkpoint = ImportCheckpoint(
        source_checksum="abc",
        import_batch_id="batch-1",
        source_table="components",
        last_processed_key="C1",
        rows_read=1,
        rows_loaded=1,
        rows_skipped=0,
        source_offset=70000,
        next_offset=70001,
        target="neogiga",
        run_id="run-1",
        committed_batch_ids=["batch-1"],
        status="completed",
    )
    store.write(checkpoint)
    loaded = store.read()
    assert loaded.source_checksum == "abc"
    assert loaded.last_processed_key == "C1"
    assert loaded.source_offset == 70000
    assert loaded.next_offset == 70001
    assert loaded.target == "neogiga"
    assert loaded.run_id == "run-1"
    assert loaded.committed_batch_ids == ["batch-1"]
    assert loaded.status == "completed"


def test_checkpoint_checksum_mismatch(tmp_path):
    store = CheckpointStore(tmp_path / "checkpoint.json")
    store.write(ImportCheckpoint("abc", None, "components", "C1"))
    assert store.can_resume("abc")
    assert not store.can_resume("def")
