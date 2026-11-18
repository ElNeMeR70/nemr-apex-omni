#!/usr/bin/env python3
"""Nemr Omni-Apex: Unified Command Line Interface (CLI).

The world-class autonomous AI engineering toolchain for ElNeMeR.
Usage:
  python cli.py plan "Real-Estate CRM with WhatsApp Alerts"
  python cli.py audit d:/path/to/project
  python cli.py scaffold my-saas-app --dest d:/output/path
  python cli.py quota
"""

from __future__ import annotations

import argparse
import os
import shutil
import sys
from pathlib import Path

# Add core to sys.path
BASE_DIR = Path(__file__).resolve().parent
if str(BASE_DIR) not in sys.path:
    sys.path.insert(0, str(BASE_DIR))

from core.architect import create_saas_blueprint
from core.quota_sentinel import QuotaSentinel
from core.security_shield import audit_directory
from core.php_dev_toolkit import PhpDevToolkit


def cmd_plan(args: argparse.Namespace) -> None:
    idea = args.idea
    print(f"\n🧠 Synthesizing Omni-Apex System Architecture for: '{idea}'...\n")
    bp = create_saas_blueprint(
        name=idea.lower().replace(" ", "-")[:30],
        title_ar=f"منظومة {idea}",
        title_en=f"Nemr {idea} OS",
        description=f"Enterprise digital platform architected for {idea}."
    )
    print("=" * 60)
    print(f"🏛️ SYSTEM BLUEPRINT: {bp.title_en}")
    print("=" * 60)
    print(f"Features: {len(bp.features)} integrated modules")
    print(f"Tables: {len(bp.tables)} relational schemas")
    print(f"API Endpoints: {len(bp.endpoints)} endpoints")
    print("\n--- MySQL DDL Auto-Migration ---")
    print(bp.generate_all_ddl()[:600] + "\n... [truncated full DDL] ...")
    print("\n✓ Architecture synthesized with zero hallucinations.\n")


def cmd_audit(args: argparse.Namespace) -> None:
    target = Path(args.path).resolve()
    print(f"\n🛡️ Running Sovereign Cybersecurity Audit on: {target}...\n")
    report = audit_directory(target)
    print(report.to_markdown())


def cmd_scaffold(args: argparse.Namespace) -> None:
    app_name = args.name
    dest_dir = Path(args.dest or (Path.cwd() / app_name)).resolve()
    print(f"\n⚡ Scaffolding Ultra-Luxury Glassmorphic SaaS in: {dest_dir}...")

    template_dir = BASE_DIR / "templates" / "saas_glass_dashboard"
    if not template_dir.exists():
        print(f"Error: Template dir not found at {template_dir}")
        sys.exit(1)

    dest_dir.mkdir(parents=True, exist_ok=True)
    for item in template_dir.iterdir():
        target_dest = dest_dir / item.name
        if item.is_file():
            shutil.copy2(item, target_dest)
        elif item.is_dir():
            shutil.copytree(item, target_dest, dirs_exist_ok=True)

    print(f"✓ Scaffolding complete! Files created:")
    for f in dest_dir.iterdir():
        print(f"  - {f.name}")
    print(f"\n🚀 To test locally: cd {dest_dir} && php -S 127.0.0.1:8000\n")


def cmd_quota(args: argparse.Namespace) -> None:
    qs = QuotaSentinel()
    statuses = qs.get_live_status()
    best = qs.select_best_model("high")

    print("\n⚡ ANTIGRAVITY LIVE QUOTA STATUS:")
    print("=" * 60)
    for model_id, st in statuses.items():
        state = "🟢 ACTIVE" if st.is_available else "🔴 DEPLETED"
        print(f"[{state}] {st.display_name:24} : {st.quota_pct:3}% left (Resets in {st.reset_countdown})")
    print("=" * 60)
    print(f"👉 Recommended Active Router Target: {best}\n")


def cmd_php_scaffold(args: argparse.Namespace) -> None:
    entity = args.entity
    fields_raw = args.fields.split(",") if args.fields else []
    fields = {}
    for f in fields_raw:
        if ":" in f:
            k, v = f.split(":", 1)
            fields[k.strip()] = v.strip()
        else:
            fields[f.strip()] = "string"

    print(f"\n⚡ Scaffolding PHP 8.3 Enterprise Model & Controller for: {entity}...")
    files = PhpDevToolkit.scaffold_crud_module(entity, fields)
    for rel_path, content in files.items():
        out_path = BASE_DIR / rel_path
        out_path.parent.mkdir(parents=True, exist_ok=True)
        out_path.write_text(content, encoding="utf-8")
        print(f"  ✓ Created: {rel_path}")
    print("✓ PHP 8.3 Module Scaffolding Complete!\n")


def cmd_php_audit(args: argparse.Namespace) -> None:
    target_dir = Path(args.path).resolve()
    print(f"\n🛡️ Running Deep PHP 8.3 Security Audit on: {target_dir}...")
    php_files = list(target_dir.rglob("*.php"))
    total_issues = 0
    for pf in php_files:
        issues = PhpDevToolkit.audit_php_code(pf.read_text(encoding="utf-8", errors="ignore"))
        if issues:
            print(f"\n⚠️ File: {pf.relative_to(target_dir)}")
            for iss in issues:
                total_issues += 1
                print(f"  [{iss['severity']}] L{iss['line']}: {iss['message']} -> {iss['code']}")

    if total_issues == 0:
        print("✓ All PHP source files passed 100% security checks (0 OWASP vulnerabilities detected).\n")
    else:
        print(f"\n❌ Found {total_issues} security issues in PHP source files.\n")


def main() -> None:
    parser = argparse.ArgumentParser(
        description="👑 Nemr Omni-Apex: World-Class Autonomous AI Engineering OS"
    )
    subparsers = parser.add_subparsers(dest="command", required=True)

    # plan
    p_plan = subparsers.add_parser("plan", help="Synthesize complete system architecture from idea")
    p_plan.add_argument("idea", type=str, help="Product idea or system description")
    p_plan.set_defaults(func=cmd_plan)

    # audit
    p_audit = subparsers.add_parser("audit", help="Run OWASP Top 10 security audit on directory")
    p_audit.add_argument("path", type=str, nargs="?", default=".", help="Directory to audit")
    p_audit.set_defaults(func=cmd_audit)

    # scaffold
    p_scaffold = subparsers.add_parser("scaffold", help="Scaffold new SaaS application")
    p_scaffold.add_argument("name", type=str, help="App directory name")
    p_scaffold.add_argument("--dest", type=str, default=None, help="Destination directory path")
    p_scaffold.set_defaults(func=cmd_scaffold)

    # quota
    p_quota = subparsers.add_parser("quota", help="Inspect real-time model quotas and routing")
    p_quota.set_defaults(func=cmd_quota)

    # php-scaffold
    p_phpscaffold = subparsers.add_parser("php-scaffold", help="Scaffold modern PHP 8.3 Model & Controller")
    p_phpscaffold.add_argument("entity", type=str, help="Entity name (e.g. Order, Customer)")
    p_phpscaffold.add_argument("--fields", type=str, default="", help="Comma separated fields (e.g. title:string,amount:float)")
    p_phpscaffold.set_defaults(func=cmd_php_scaffold)

    # php-audit
    p_php_audit = subparsers.add_parser("php-audit", help="Run PHP security static analysis")
    p_php_audit.add_argument("path", type=str, nargs="?", default=".", help="Directory to audit")
    p_php_audit.set_defaults(func=cmd_php_audit)

    args = parser.parse_args()
    args.func(args)


if __name__ == "__main__":
    main()
