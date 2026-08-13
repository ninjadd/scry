# Pull Request & Release Workflow Rule

For feature development, documentation updates, and releases in the `scry` repository, follow this exact workflow:

1. **Feature Branching:**
   - Always create a dedicated git feature branch off `main` for new features, docs, or bug fixes (e.g., `feature/...`, `docs-...`, `fix/...`).

2. **Outreach & Marketing Copy:**
   - Keep community outreach materials, launch announcements (Reddit, Twitter/X, Laravel News), and internal notes in local artifacts or private conversation files.
   - Do NOT commit promotional outreach copy into the public repository or Packagist package.

3. **Pull Request Workflow via `gh`:**
   - Commit changes to the feature branch.
   - Push the feature branch to `origin`.
   - Use `gh pr create` to open a professional Pull Request with a structured summary targeting `main`.
   - Merge the PR into `main` using `gh pr merge <pr_number> --merge`.
   - Switch local repository to `main` and pull latest changes from `origin/main`.

4. **Tagging & Release via `gh`:**
   - Determine the next semantic tag version (e.g., increment patch version `v1.0.6`).
   - Create and publish the tag and release using `gh release create <tag> --title "<title>" --notes "<release_notes>"`.

5. **Strict Emoji Prohibition:**
   - NEVER use emojis anywhere in commit messages, PR titles, PR descriptions, release titles, release notes, code comments, documentation, or agent responses.
