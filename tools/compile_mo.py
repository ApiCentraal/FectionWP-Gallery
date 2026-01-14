#!/usr/bin/env python3
"""Compile a .po file to a GNU gettext .mo file.

This is a tiny, dependency-free compiler intended for this plugin repo.
It supports msgid/msgstr, multi-line strings, and plural forms.

Usage:
  python3 tools/compile_mo.py languages/fectionwp-gallery-nl_NL.po languages/fectionwp-gallery-nl_NL.mo
"""

from __future__ import annotations

import ast
import struct
import sys
from dataclasses import dataclass
from pathlib import Path


@dataclass
class PoEntry:
    msgid: str = ""
    msgid_plural: str | None = None
    msgstr: dict[int, str] | None = None


def _unquote_po_string(token: str) -> str:
    """Convert a PO string token like "foo\n" into a Python string."""
    token = token.strip()
    if not token:
        return ""
    if not (token.startswith('"') and token.endswith('"')):
        return ""
    # ast.literal_eval handles escapes (\n, \", etc.) safely.
    return ast.literal_eval(token)


def parse_po(po_text: str) -> dict[str, str]:
    entries: dict[str, str] = {}

    current = PoEntry(msgstr={})
    state: tuple[str, int | None] | None = None  # (field, index)

    def flush() -> None:
        nonlocal current
        if current.msgid == "" and not current.msgstr:
            current = PoEntry(msgstr={})
            return
        if current.msgstr is None:
            current = PoEntry(msgstr={})
            return

        if current.msgid_plural is None:
            translated = current.msgstr.get(0, "")
            entries[current.msgid] = translated
        else:
            key = current.msgid + "\x00" + (current.msgid_plural or "")
            max_index = max(current.msgstr.keys(), default=-1)
            parts = [current.msgstr.get(i, "") for i in range(max_index + 1)]
            entries[key] = "\x00".join(parts)

        current = PoEntry(msgstr={})

    for raw in po_text.splitlines():
        line = raw.strip()
        if not line or line.startswith('#'):
            if not line:
                flush()
            continue

        if line.startswith('msgid_plural'):
            current.msgid_plural = _unquote_po_string(line.split(' ', 1)[1])
            state = ('msgid_plural', None)
            continue

        if line.startswith('msgid'):
            # starting a new entry
            flush()
            current.msgid = _unquote_po_string(line.split(' ', 1)[1])
            state = ('msgid', None)
            continue

        if line.startswith('msgstr['):
            left, rest = line.split(']', 1)
            idx = int(left[len('msgstr['):])
            value = _unquote_po_string(rest.strip().lstrip())
            current.msgstr = current.msgstr or {}
            current.msgstr[idx] = value
            state = ('msgstr', idx)
            continue

        if line.startswith('msgstr'):
            value = _unquote_po_string(line.split(' ', 1)[1])
            current.msgstr = current.msgstr or {}
            current.msgstr[0] = value
            state = ('msgstr', 0)
            continue

        if line.startswith('"') and state is not None:
            # continued multi-line string
            add = _unquote_po_string(line)
            field, idx = state
            if field == 'msgid':
                current.msgid += add
            elif field == 'msgid_plural':
                current.msgid_plural = (current.msgid_plural or "") + add
            elif field == 'msgstr':
                current.msgstr = current.msgstr or {}
                current.msgstr[idx or 0] = current.msgstr.get(idx or 0, "") + add
            continue

        # Unknown line type; ignore.

    flush()
    return entries


def build_mo(entries: dict[str, str]) -> bytes:
    # Sort by msgid for deterministic output.
    items = sorted(((k.encode('utf-8'), v.encode('utf-8')) for k, v in entries.items()), key=lambda kv: kv[0])

    n = len(items)
    # MO header is 7 uint32 values.
    header_size = 7 * 4
    orig_table_offset = header_size
    trans_table_offset = orig_table_offset + n * 8

    # String data starts after both tables.
    string_data_offset = trans_table_offset + n * 8

    orig_table = []
    trans_table = []
    string_data = bytearray()

    def add_string(s: bytes) -> tuple[int, int]:
        off = string_data_offset + len(string_data)
        string_data.extend(s)
        string_data.append(0)
        return (len(s), off)

    # First, add originals and translations, recording lengths/offsets.
    for msgid_b, msgstr_b in items:
        orig_table.append(add_string(msgid_b))
        trans_table.append(add_string(msgstr_b))

    # Build binary header.
    # magic, revision, nstrings, orig_tab_off, trans_tab_off, hash_size, hash_off
    header = struct.pack(
        '<7I',
        0x950412DE,
        0,
        n,
        orig_table_offset,
        trans_table_offset,
        0,
        0,
    )

    out = bytearray(header)

    for length, offset in orig_table:
        out.extend(struct.pack('<2I', length, offset))

    for length, offset in trans_table:
        out.extend(struct.pack('<2I', length, offset))

    out.extend(string_data)
    return bytes(out)


def main(argv: list[str]) -> int:
    if len(argv) != 3:
        print('Usage: compile_mo.py input.po output.mo', file=sys.stderr)
        return 2

    po_path = Path(argv[1])
    mo_path = Path(argv[2])

    po_text = po_path.read_text(encoding='utf-8')
    entries = parse_po(po_text)
    mo_bytes = build_mo(entries)

    mo_path.write_bytes(mo_bytes)
    return 0


if __name__ == '__main__':
    raise SystemExit(main(sys.argv))
