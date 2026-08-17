# Pull Request & Release Workflow Rule

For feature development, documentation updates, and releases in the `scry` repository (published on Packagist.org), follow this exact workflow:

1. **Feature Branching & Incremental Commits:**
   - Always create a dedicated git feature branch off `main` for new features, docs, or bug fixes (e.g., `feature/...`, `docs-...`, `fix/...`).
   - Never commit or push directly to `main` during feature development.
   - Commit and push incrementally per prompt/feature increment with conventional commit messages (`feat(...)`, `fix(...)`, `docs(...)`).

2. **Multi-Database Support & Quality Standards:**
   - All new database features, schema introspection queries, DDL builders, and migration utilities must support all 5 database drivers: **PostgreSQL**, **MySQL**, **MariaDB**, **SQLite**, and **SQL Server (`sqlsrv`)**.
   - Multi-statement SQL imports and DDL mutations must execute inside isolated database transactions (`DB::beginTransaction()` / `DB::rollBack()`).
   - All automated test suites (`./vendor/bin/phpunit`) must pass 100% prior to opening a PR.

3. **Packagist Sync & Emoji Prohibition:**
   - Ensure `composer.json` metadata (description, keywords, authors, license) is synchronized for Packagist.org search indexing.
   - NEVER use emojis anywhere in commit messages, PR titles, PR descriptions, release titles, release notes, code comments, documentation, UI components, or responses.

4. **Pull Request Workflow via `gh`:**
   - Commit changes and push the feature branch to `origin`.
   - Use `gh pr create` (or GitHub REST API fallback if GraphQL returns 503) to open a Pull Request with a structured summary targeting `main`.
   - Merge the PR into `main` using `gh pr merge <pr_number> --merge` (or REST API merge endpoint).
   - Switch local repository to `main` and pull latest changes from `origin/main`.

5. **Tagging, Release & Branch Cleanup:**
   - Determine the next semantic tag version using `git tag -l -n --sort=-v:refname`.
   - Tag the release commit on `main` (`git tag -a vX.Y.Z -m "..."`) and push the tag.
   - Create and publish the GitHub Release via `gh release create <tag> --title "<title>" --notes "<notes>"` to trigger Packagist webhooks.
   - Delete the feature branch both remotely (`git push origin --delete <branch>`) and locally (`git branch -D <branch>`).
