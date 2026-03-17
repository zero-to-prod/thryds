# ADR-004: Custom Rector Rules Over Manual Review

## Status
Accepted

## Context
Architectural patterns (naming conventions, route safety, OPcache-friendly code, structured logging) can be documented in style guides, but documentation alone doesn't prevent violations. Manual code review catches issues after they're written, not while they're being written.

## Decision
Encode architectural rules as 51 custom Rector rules in `utils/rector/src/`. Rules run as part of `./run lint:all` and the pre-commit hook. Each rule has a `mode` ('auto' for auto-fix, 'warn' for TODO annotation) and a descriptive `message`.

## Consequences
- **Rules are executable documentation.** Reading `rector.php` tells you what the project's conventions are and how they're enforced.
- **AI agents get immediate feedback.** An agent writes code, runs `./run lint:all`, and Rector either fixes the code or explains what's wrong via TODO comments.
- **Rules are isolated from application code.** Rules in `utils/rector/` never import from `src/` — all project-specific values (class names, trait FQNs) are passed via configuration. This keeps rules testable and potentially reusable.
- **Maintenance cost.** Each rule needs its own test suite with fixture files. The 51 rules represent significant investment, but the payoff is zero-cost enforcement on every commit.
