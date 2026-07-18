#!/usr/bin/env python3
"""Read-only stdio MCP connector for the NeoGiga public AI catalog."""

from __future__ import annotations

import json
import os
import sys
from typing import Any
from urllib.error import HTTPError, URLError
from urllib.parse import quote, urlencode
from urllib.request import Request, urlopen


API_BASE = os.environ.get(
    "NEOGIGA_CATALOG_API_BASE", "https://neogiga.com/api/v1/ai-catalog"
).rstrip("/")
TIMEOUT_SECONDS = min(max(int(os.environ.get("NEOGIGA_MCP_TIMEOUT_SECONDS", "12")), 1), 30)

TOOLS = [
    {
        "name": "catalog_manifest",
        "description": "Discover NeoGiga's read-only AI catalog contract and regional commercial-data rules.",
        "inputSchema": {"type": "object", "properties": {}, "additionalProperties": False},
    },
    {
        "name": "catalog_marketplaces",
        "description": "List visible NeoGiga marketplaces and their public edition metadata.",
        "inputSchema": {"type": "object", "properties": {}, "additionalProperties": False},
    },
    {
        "name": "catalog_search",
        "description": "Search published NeoGiga products by product name, MPN, SKU, package, or catalog text. Results are advisory only.",
        "inputSchema": {
            "type": "object",
            "properties": {
                "query": {"type": "string", "minLength": 2, "maxLength": 120},
                "per_page": {"type": "integer", "minimum": 1, "maximum": 25},
                "package": {"type": "string", "maxLength": 120},
                "quality": {"type": "string", "enum": ["high", "needs_review"]},
            },
            "required": ["query"],
            "additionalProperties": False,
        },
    },
    {
        "name": "catalog_product",
        "description": "Retrieve one published NeoGiga product by its canonical slug. This does not return live price or stock.",
        "inputSchema": {
            "type": "object",
            "properties": {"slug": {"type": "string", "minLength": 1, "maxLength": 180}},
            "required": ["slug"],
            "additionalProperties": False,
        },
    },
]


def fetch(path: str, query: dict[str, Any] | None = None) -> dict[str, Any]:
    url = f"{API_BASE}/{path.lstrip('/')}"
    if query:
        url = f"{url}?{urlencode(query)}"

    request = Request(url, headers={"Accept": "application/json", "User-Agent": "NeoGigaCatalogMCP/1.0"})
    try:
        with urlopen(request, timeout=TIMEOUT_SECONDS) as response:
            payload = json.loads(response.read().decode("utf-8"))
    except HTTPError as error:
        message = error.read().decode("utf-8", errors="replace")[:1000]
        raise RuntimeError(f"NeoGiga API returned HTTP {error.code}: {message}") from error
    except URLError as error:
        raise RuntimeError(f"NeoGiga API is unavailable: {error.reason}") from error

    if not isinstance(payload, dict):
        raise RuntimeError("NeoGiga API returned an invalid response.")
    return payload


def call_tool(name: str, arguments: dict[str, Any]) -> dict[str, Any]:
    if name == "catalog_manifest":
        return fetch("manifest")
    if name == "catalog_marketplaces":
        return fetch("marketplaces")
    if name == "catalog_search":
        query = str(arguments.get("query", "")).strip()
        if len(query) < 2:
            raise ValueError("catalog_search requires query with at least two characters.")
        params: dict[str, Any] = {"q": query}
        for key in ("per_page", "package", "quality"):
            if key in arguments and arguments[key] not in (None, ""):
                params[key] = arguments[key]
        return fetch("products/search", params)
    if name == "catalog_product":
        slug = str(arguments.get("slug", "")).strip()
        if not slug:
            raise ValueError("catalog_product requires a product slug.")
        return fetch(f"products/{quote(slug, safe='._-')}")
    raise ValueError(f"Unknown tool: {name}")


def response(message_id: Any, result: dict[str, Any]) -> dict[str, Any]:
    return {"jsonrpc": "2.0", "id": message_id, "result": result}


def error_response(message_id: Any, code: int, message: str) -> dict[str, Any]:
    return {"jsonrpc": "2.0", "id": message_id, "error": {"code": code, "message": message}}


def handle(message: dict[str, Any]) -> dict[str, Any] | None:
    method = message.get("method")
    message_id = message.get("id")

    if method == "notifications/initialized":
        return None
    if method == "initialize":
        return response(message_id, {
            "protocolVersion": "2024-11-05",
            "capabilities": {"tools": {}},
            "serverInfo": {"name": "neogiga-catalog", "version": "1.0.0"},
            "instructions": "Read-only public product discovery. Confirm live commercial data on the selected regional storefront.",
        })
    if method == "tools/list":
        return response(message_id, {"tools": TOOLS})
    if method == "tools/call":
        params = message.get("params") or {}
        name = params.get("name")
        arguments = params.get("arguments") or {}
        if not isinstance(name, str) or not isinstance(arguments, dict):
            return error_response(message_id, -32602, "tools/call requires a tool name and object arguments.")
        try:
            payload = call_tool(name, arguments)
            return response(message_id, {
                "content": [{"type": "text", "text": json.dumps(payload, ensure_ascii=True)}],
                "structuredContent": payload,
            })
        except (RuntimeError, ValueError) as error:
            return response(message_id, {
                "content": [{"type": "text", "text": str(error)}],
                "isError": True,
            })

    return error_response(message_id, -32601, f"Method not found: {method}")


def main() -> int:
    for line in sys.stdin:
        try:
            message = json.loads(line)
            if not isinstance(message, dict):
                raise ValueError("JSON-RPC message must be an object.")
            result = handle(message)
            if result is not None:
                print(json.dumps(result, ensure_ascii=True), flush=True)
        except (json.JSONDecodeError, ValueError) as error:
            print(json.dumps(error_response(None, -32700, str(error))), flush=True)
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
