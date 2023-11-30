# Contributing and Maintaining

The following is a set of instructions for our maintenance and release process.

### Pull requests

Pull requests represent a proposed solution to a specified problem.  They should always reference an issue that describes the problem and contains discussion about the problem itself.  Discussion on pull requests should be limited to the pull request itself, i.e. code review.

For more on how 10up writes and manages code, check out our [10up Engineering Best Practices](https://10up.github.io/Engineering-Best-Practices/).

## Workflow

The `develop` branch is the development branch which means it contains the next version to be released. `master` contains the corresponding stable development version. Always branch off the `develop` branch and open up PRs against `develop`.

## Release instructions

1. Branch: Starting from `develop`, create a new branch following the format `release/X.Y.Z`.
1. Version bump: Update the plugin version in `readme.txt`, `package.json`, `package-lock.json`, `brightcove-video-connect.php` in plugin version and `BRIGHTCOVE_VERSION` constant.
1. Changelog: Add/update the changelog in `CHANGELOG.md` and `readme.txt`, ensuring to link the [X.Y.Z] release reference in the footer of `CHANGELOG.md` (e.g., https://github.com/10up/brightcove-video-connect/compare/X.Y.Z-1...X.Y.Z).
1. New files: Check to be sure any new files/paths that are unnecessary in the production version are included in `.gitattributes`.
1. If applicable, bump the `Tested up to` field in `readme.txt`.
1. Release date: Double check the release date in both changelog files.
1. Run `npm run build` to generate updated translation files.
1. Commit your changes and open a PR to `develop`.
1. Once changes are merged, open a PR from `develop` to `master`.
1. Test: Once changes are merged, checkout the `master` branch locally and test for functionality.
1. Release: Once merged, draft a [new release](https://github.com/10up/brightcove-video-connect/releases/new) naming the release with the new version number, creating a new label `X.Y.Z`, and selecting as target `master`. Paste the release changelog from `CHANGELOG.md` into the body of the release and include a link to the closed issues on the [milestone](https://github.com/10up/brightcove-video-connect/#?closed=1).
1. SVN: Wait for the [GitHub Action](https://github.com/10up/brightcove-video-connect/actions/workflows/push-deploy.yml) to finish deploying to the WordPress.org repository. If all goes well, users with SVN commit access for that plugin will receive an emailed diff of changes.
1. Check WordPress.org: Ensure that the changes are live on https://wordpress.org/plugins/brightcove-video-connect/. This may take a few minutes.
1. Close milestone: Edit the [milestone](https://github.com/10up/brightcove-video-connect/milestone/#) with release date (in the `Due date (optional)` field) and link to GitHub release (in the `Description` field), then close the milestone.
1. Punt incomplete items: If any open issues or PRs which were milestoned for `X.Y.Z` do not make it into the release, update their milestone to `X.Y.Z+1`, `X.Y+1.0`, `X+1.0.0` or `Future Release`.
