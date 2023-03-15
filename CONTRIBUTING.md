# Contributing and Maintaining

The following is a set of instructions for our maintenance and release process.

### Pull requests

Pull requests represent a proposed solution to a specified problem.  They should always reference an issue that describes the problem and contains discussion about the problem itself.  Discussion on pull requests should be limited to the pull request itself, i.e. code review.

For more on how 10up writes and manages code, check out our [10up Engineering Best Practices](https://10up.github.io/Engineering-Best-Practices/).

## Workflow

The `develop` branch is the development branch which means it contains the next version to be released. `master` contains the corresponding stable development version. Always branch off the `develop` branch and open up PRs against `develop`.

## Release instructions
1. Create a new branch following the format `release/x.y.z`
2. Changelog: Add/update the changelog in `CHANGELOG.md` and `readme.txt`, ensuring to link the [X.Y.Z] release reference in the footer of `CHANGELOG.md` (e.g., https://github.com/10up/brightcove-video-connect/compare/X.Y.Z-1...X.Y.Z)
3. Update the plugin version in `readme.txt`, `package.json`, `brightcove-video-connect.php` in plugin version and `BRIGHTCOVE_VERSION` constant 
4. If applicable, bump the `tested up to` field in `readme.txt`
5. Run `npm run build` to generate updated translation files
6. Commit your changes and open a PR to `develop`
7. Once changes are merged, open a PR from `develop` to `master`
8. Once changes are merged, draft a new release https://github.com/10up/brightcove-video-connect/releases/new selecting as target `master`
9. Check the deploy to WordPres.org GitHub action ran correctly https://github.com/10up/brightcove-video-connect/actions
10. Verify the WordPress.org repository is updated. This may take a few minutes
