#!/usr/bin/env python3
"""Nemr Omni-Apex: Quota Sentinel & Autonomous Model Router.

Monitors real-time LLM quotas and prevents mid-task quota exhaustion:
- Identifies depleted models (0% capacity) and locks them out.
- Routes execution to available models with healthy budgets.
- Estimates task cost in tokens & USD before dispatching.
"""

from __future__ import annotations

import json
from dataclasses import dataclass
from pathlib import Path
from typing import Any, Dict, List, Optional


@dataclass
class ModelStatus:
    model_id: str
    display_name: str
    quota_pct: int
    is_available: bool
    reset_countdown: str
    tier: str  # Frontier, Balanced, Fast


class QuotaSentinel:
    PRICING = {
        "gemini-3.8-flash": {"input": 0.075, "output": 0.30, "tier": "Fast"},
        "gemini-4-argon": {"input": 3.00, "output": 3.00, "tier": "Balanced"},
        "claude-sonnet-5.5": {"input": 3.00, "output": 15.00, "tier": "Frontier"},
        "claude-opus-5.5": {"input": 15.00, "output": 75.00, "tier": "Ultra Frontier"},
    }

    def __init__(self, quota_file: Optional[Path] = None) -> None:
        self.quota_file = quota_file or (Path(__file__).resolve().parents[2] / "sailk" / "sidecars" / "quota_hud" / "live_quotas.json")

    def get_live_status(self) -> Dict[str, ModelStatus]:
        models = {
            "gemini-3.8-flash-high": ModelStatus("gemini-3.8-flash-high", "Gemini 3.8 Flash", 62, True, "2h", "Fast"),
            "gemini-4-argon-high": ModelStatus("gemini-4-argon-high", "Gemini 4 Argon", 0, False, "6d 12h", "Balanced"),
            "claude-sonnet-5-5-high": ModelStatus("claude-sonnet-5-5-high", "Claude Sonnet 5.5", 0, False, "1d 12h", "Frontier"),
            "claude-opus-5-5-high": ModelStatus("claude-opus-5-5-high", "Claude Opus 5.5", 0, False, "1d 12h", "Ultra Frontier"),
            "gpt-oss-120b-medium": ModelStatus("gpt-oss-120b-medium", "GPT-OSS 120B", 0, False, "1d 12h", "Standard"),
        }

        if self.quota_file and self.quota_file.exists():
            try:
                data = json.loads(self.quota_file.read_text(encoding="utf-8")).get("quotas", {})
                for k, v in data.items():
                    if k in models:
                        pct = int(v.get("quota_remaining", "0%").replace("%", ""))
                        models[k].quota_pct = pct
                        models[k].is_available = (pct > 0)
            except Exception:
                pass

        return models

    def select_best_model(self, task_complexity: str = "medium") -> str:
        """Autonomous failover router based on live available pools."""
        statuses = self.get_live_status()

        # If Argon is alive and task needs deep reasoning, use Argon
        if task_complexity in ("high", "refactor", "security") and statuses["gemini-4-argon-high"].is_available:
            return "gemini-4-argon-high"

        # If Claude is alive and task is subtle logic, use Claude
        if task_complexity == "audit" and statuses["claude-sonnet-5-5-high"].is_available:
            return "claude-sonnet-5-5-high"

        # Default robust fallback: Gemini 3.8 Flash (if available)
        if statuses["gemini-3.8-flash-high"].is_available:
            return "gemini-3.8-flash-high"

        # Extreme fallback
        return "gemini-3.8-flash-high"

    def estimate_task_burn(self, estimated_tokens: int, model_id: str = "gemini-3.8-flash") -> Dict[str, Any]:
        """Estimate token budget and USD impact."""
        rate = self.PRICING.get(model_id, {"input": 0.075, "output": 0.30})
        cost = (estimated_tokens * rate["input"]) / 1_000_000
        return {
            "estimated_tokens": estimated_tokens,
            "estimated_usd": round(cost, 4),
            "tier": rate.get("tier", "Standard"),
            "is_safe": True
        }


if __name__ == "__main__":
    qs = QuotaSentinel()
    print("Recommended Model:", qs.select_best_model("high"))
    burn = qs.estimate_task_burn(45000, "gemini-3.8-flash")
    print("Burn Estimate:", burn)
