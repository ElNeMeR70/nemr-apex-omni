#!/usr/bin/env python3
"""Unit test suite for Nemr Omni-Apex Core Engines."""

import sys
from pathlib import Path

# Add project root to sys.path
PROJECT_ROOT = Path(__file__).resolve().parents[1]
if str(PROJECT_ROOT) not in sys.path:
    sys.path.insert(0, str(PROJECT_ROOT))

import tempfile
import unittest

from core.architect import create_saas_blueprint
from core.code_surgeon import CodeSurgeon
from core.quota_sentinel import QuotaSentinel
from core.security_shield import audit_directory
from core.php_dev_toolkit import PhpDevToolkit


class TestNemrOmniApex(unittest.TestCase):
    def test_architect_blueprint(self):
        bp = create_saas_blueprint(
            name="test-saas",
            title_ar="منظومة اختبار",
            title_en="Test SaaS",
            description="Testing blueprint generator"
        )
        self.assertEqual(bp.name, "test-saas")
        self.assertGreaterEqual(len(bp.tables), 3)
        self.assertIn("CREATE TABLE IF NOT EXISTS `users`", bp.generate_all_ddl())

    def test_security_shield_clean(self):
        with tempfile.TemporaryDirectory() as tmpdir:
            p = Path(tmpdir) / "index.php"
            p.write_text("<?php echo 'Hello World'; ?>", encoding="utf-8")
            rep = audit_directory(Path(tmpdir))
            self.assertEqual(rep.score, 100)
            self.assertEqual(len(rep.findings), 0)

    def test_security_shield_catches_sqli_and_secret(self):
        with tempfile.TemporaryDirectory() as tmpdir:
            p = Path(tmpdir) / "vulnerable.php"
            p.write_text("""<?php
                $secret = 'sk-abcdef1234567890abcdef1234567890';
                $q = "SELECT * FROM users WHERE id = " . $_GET['id'];
            ?>""", encoding="utf-8")
            rep = audit_directory(Path(tmpdir))
            self.assertLess(rep.score, 100)
            self.assertGreaterEqual(len(rep.findings), 1)

    def test_code_surgeon_ast_replacement(self):
        with tempfile.NamedTemporaryFile("w+", suffix=".py", delete=False) as tmp:
            tmp.write("""def calculate_total(a, b):
    return a + b

def other_func():
    return 42
""")
            tmp_path = Path(tmp.name)

        try:
            res = CodeSurgeon.replace_python_function(
                tmp_path,
                "calculate_total",
                """def calculate_total(a, b):
    # Surgical fix
    return (a + b) * 2"""
            )
            self.assertTrue(res.success)
            content = tmp_path.read_text(encoding="utf-8")
            self.assertIn("(a + b) * 2", content)
            self.assertIn("def other_func():", content)
        finally:
            tmp_path.unlink(missing_ok=True)

    def test_quota_sentinel(self):
        qs = QuotaSentinel()
        burn = qs.estimate_task_burn(10000, "gemini-3.8-flash")
        self.assertEqual(burn["estimated_tokens"], 10000)
        self.assertGreater(burn["estimated_usd"], 0)

    def test_php_dev_toolkit_scaffold(self):
        files = PhpDevToolkit.scaffold_crud_module("Invoice", {"amount": "float", "customer": "string"})
        self.assertIn("src/Models/Invoice.php", files)
        self.assertIn("readonly class Invoice", files["src/Models/Invoice.php"])
        self.assertIn("src/Controllers/InvoiceController.php", files)

    def test_php_dev_toolkit_audit(self):
        vuln_code = "<?php echo $_GET['user']; $q = 'SELECT * FROM users WHERE id = ' . $_POST['id']; ?>"
        issues = PhpDevToolkit.audit_php_code(vuln_code)
        self.assertGreaterEqual(len(issues), 2)
        issue_types = [i["type"] for i in issues]
        self.assertIn("SQL_INJECTION", issue_types)
        self.assertIn("REFLECTED_XSS", issue_types)


if __name__ == "__main__":
    unittest.main()
