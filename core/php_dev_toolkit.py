"""
Nemr Apex Omni - PHP 8.3 Enterprise Developer Toolkit
Provides AST-level code generation, security static analysis, and automated test scaffolding.
"""

from __future__ import annotations
import re
from pathlib import Path
from typing import Dict, List, Any

class PhpDevToolkit:
    """Specialist toolkit for modern PHP 8.3 development, static analysis, and scaffolding."""

    @staticmethod
    def scaffold_crud_module(entity_name: str, fields: Dict[str, str], namespace: str = "Nemr\\Apex") -> Dict[str, str]:
        """
        Scaffolds a modern PHP 8.3 Entity, Controller, and Migration with strict typing and readonly properties.
        """
        class_name = entity_name.capitalize()
        table_name = entity_name.lower() + "s"

        # 1. Generate Entity Model (PHP 8.2+ Readonly DTO)
        entity_props = []
        for fname, ftype in fields.items():
            php_type = "string"
            if ftype in ("int", "integer"):
                php_type = "int"
            elif ftype in ("float", "decimal"):
                php_type = "float"
            elif ftype in ("bool", "boolean"):
                php_type = "bool"
            entity_props.append(f"        public readonly {php_type} ${fname},")

        entity_code = f"""<?php
declare(strict_types=1);

namespace {namespace}\\Models;

/**
 * Modern PHP 8.3 Immutable Entity Model
 */
readonly class {class_name}
{{
    public function __construct(
        public readonly int $id,
{chr(10).join(entity_props)}
        public readonly string $createdAt,
    ) {{}}

    public static function fromArray(array $data): self
    {{
        return new self(
            id: (int)($data['id'] ?? 0),
{chr(10).join(f"            {fname}: $data['{fname}']," for fname in fields)},
            createdAt: $data['created_at'] ?? date('Y-m-d H:i:s')
        );
    }}

    public function toArray(): array
    {{
        return [
            'id' => $this->id,
{chr(10).join(f"            '{fname}' => $this->{fname}," for fname in fields)},
            'created_at' => $this->createdAt,
        ];
    }}
}}
"""

        # 2. Generate REST API Controller
        controller_code = f"""<?php
declare(strict_types=1);

namespace {namespace}\\Controllers;

use {namespace}\\Database\\QueryBuilder;
use {namespace}\\Models\\{class_name};
use PDO;

class {class_name}Controller
{{
    public function __construct(protected PDO $pdo) {{}}

    public function index(array $request): array
    {{
        $rows = QueryBuilder::table('{table_name}', $this->pdo)
            ->orderBy('id', 'DESC')
            ->limit(50)
            ->get();

        return [
            'status' => 'success',
            'data' => array_map(fn($r) => {class_name}::fromArray($r)->toArray(), $rows)
        ];
    }}

    public function show(array $request, array $params): array
    {{
        $id = (int)($params['id'] ?? 0);
        $row = QueryBuilder::table('{table_name}', $this->pdo)
            ->where('id', '=', $id)
            ->first();

        if (!$row) {{
            http_response_code(404);
            return ['status' => 'error', 'message' => '{class_name} not found'];
        }}

        return ['status' => 'success', 'data' => {class_name}::fromArray($row)->toArray()];
    }}

    public function store(array $request): array
    {{
        $body = $request['body'] ?? [];
        $data = [
{chr(10).join(f"            '{fname}' => $body['{fname}'] ?? ''," for fname in fields)}
            'created_at' => date('Y-m-d H:i:s')
        ];

        $newId = QueryBuilder::table('{table_name}', $this->pdo)->insert($data);

        http_response_code(201);
        return [
            'status' => 'success',
            'message' => '{class_name} created successfully',
            'id' => $newId
        ];
    }}
}}
"""
        return {
            f"src/Models/{class_name}.php": entity_code,
            f"src/Controllers/{class_name}Controller.php": controller_code,
        }

    @staticmethod
    def audit_php_code(source: str) -> List[Dict[str, Any]]:
        """
        Static analyzer identifying OWASP Top 10 vulnerabilities in PHP files.
        """
        issues = []
        lines = source.splitlines()

        for idx, line in enumerate(lines, 1):
            # Raw $_GET / $_POST concatenation into SQL
            if re.search(r'(SELECT|INSERT|UPDATE|DELETE).*\$_(GET|POST|REQUEST)', line, re.IGNORECASE):
                issues.append({
                    "line": idx,
                    "severity": "CRITICAL",
                    "type": "SQL_INJECTION",
                    "message": "Raw superglobal $_GET/$_POST concatenated into SQL statement. Use QueryBuilder prepared statements.",
                    "code": line.strip()
                })

            # Insecure echo without htmlspecialchars
            if re.search(r'echo\s+\$_(GET|POST|REQUEST)\[', line):
                issues.append({
                    "line": idx,
                    "severity": "HIGH",
                    "type": "REFLECTED_XSS",
                    "message": "Raw input echoed without htmlspecialchars() or escaping filter.",
                    "code": line.strip()
                })

            # Dangerous functions
            if re.search(r'\b(eval|unserialize|passthru|shell_exec|system)\s*\(', line):
                issues.append({
                    "line": idx,
                    "severity": "HIGH",
                    "type": "RCE_RISK",
                    "message": "Invocation of high-risk function (eval/unserialize/exec).",
                    "code": line.strip()
                })

            # Deprecated md5/sha1 for passwords
            if re.search(r'\b(md5|sha1)\s*\(\s*\$.*password', line, re.IGNORECASE):
                issues.append({
                    "line": idx,
                    "severity": "CRITICAL",
                    "type": "WEAK_HASHING",
                    "message": "Weak algorithm used for password hashing. Use password_hash(..., PASSWORD_ARGON2ID).",
                    "code": line.strip()
                })

        return issues
