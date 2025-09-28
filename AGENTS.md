# Message Bus Agent Instructions

## Static Analysis Assumptions

- This project treats static analysis as the primary guardrail for type and shape invariants (PHPStan, Psalm-style phpdoc generics, list/non-empty-string contracts).
- Do not add runtime defensive checks only to enforce phpdoc-level type invariants when static analysis already guarantees them.
- Prefer keeping runtime code lean and focused on business/domain validation, not duplicate type validation.
- Add runtime checks only when they protect against real untyped boundaries (I/O, external payloads, DB/network data, user input) or when explicitly requested.
