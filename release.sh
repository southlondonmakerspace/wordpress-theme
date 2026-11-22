#!/bin/sh
# Releases a new version of the theme: ./release.sh 3.0.1
#
# Sets the version in style.css (which WordPress reads), package.json and the
# README, commits that to master, tags it v3.0.1 and pushes both. The tag
# makes GitHub build the release and its zip (see .github/workflows/release.yml).
set -e
cd "$( dirname "$0" )"

version=$1
if ! echo "$version" | grep -Eq '^[0-9]+\.[0-9]+\.[0-9]+$'; then
	echo "Usage: ./release.sh 1.2.3" >&2
	exit 1
fi
if [ "$( git branch --show-current )" != master ]; then
	echo "Release from master" >&2
	exit 1
fi
if [ -n "$( git status --porcelain )" ]; then
	echo "Commit or stash your changes first" >&2
	exit 1
fi
if git rev-parse -q --verify "refs/tags/v$version" > /dev/null; then
	echo "v$version is already tagged" >&2
	exit 1
fi

sed -i.bak -E "s/^([[:space:]]*Version:[[:space:]]*).*/\1$version/" style.css
sed -i.bak -E "s/^(  \"version\": \")[^\"]*/\1$version/" package.json
sed -i.bak -E "s/^(The WordPress theme for .* Version )[0-9.]+,/\1$version,/" README.md
rm style.css.bak package.json.bak README.md.bak

git commit -q -m "Releases $version" style.css package.json README.md
git tag -a "v$version" -m "Version $version"
git push --atomic origin master "v$version"
echo "Pushed v$version. GitHub is building the release: https://github.com/southlondonmakerspace/wordpress-theme/releases"
