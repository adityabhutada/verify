# Repository Cleanup

Large or sensitive files previously tracked by git have been removed from the working tree and added to `.gitignore` so they will no longer be committed. These include:

- `db.sqlite`
- `logs/`
- `submit_error.log`
- the `vendor/` directory
- any `*.zip` archives

These files still exist in past commits. If complete removal from history is required, run tools such as `git filter-repo` or `bfg-repo-cleaner` on your clone before pushing the rewritten history.
