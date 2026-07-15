#!/bin/bash

# Set variables
SVN_REPO_URL="https://plugins.svn.wordpress.org/squad-modules-for-divi/"
GITHUB_REPO_URL="https://github.com/thewpsquad/squad-modules-lite"
WORK_DIR="./../../"

# Create working directory
mkdir -p $WORK_DIR
cd $WORK_DIR

# Clone the GitHub repository if it doesn't exist
if [ ! -d ".git" ]; then
    git clone $GITHUB_REPO_URL ./
fi

# Function to copy a specific version from SVN to Git
copy_version() {
    VERSION=$1
    echo "Copying version $VERSION"

    # Delete all files and folders except specified ones
    find . -mindepth 1 -maxdepth 1 \
        ! -name '.git' \
        ! -name '.github' \
        ! -name 'README.md' \
        ! -name 'LICENSE' \
        ! -name 'CODE_OF_CONDUCT.md' \
        -exec rm -rf {} +

    svn export $SVN_REPO_URL/tags/$VERSION tags/$VERSION
    rsync -av --exclude='.svn' tags/$VERSION/ ./
    rm -rf tags/$VERSION
    git add .
    git commit -m "Import version $VERSION from SVN"
    git tag $VERSION
}

# Get all SVN tags, excluding trunk
SVN_TAGS=$(svn ls $SVN_REPO_URL/tags | grep -v '^trunk/' | sort -V)

# Get all Git tags
GIT_TAGS=$(git tag | sort -V)

# Compare and sync new tags
for SVN_TAG in $SVN_TAGS
do
    SVN_TAG=${SVN_TAG%/}
    if [[ "$SVN_TAG" > "1.0.0" || "$SVN_TAG" == "1.0.0" ]]
    then
        if ! echo "$GIT_TAGS" | grep -q "^${SVN_TAG}$"; then
            copy_version $SVN_TAG
        else
            echo "Skipping existing tag $SVN_TAG"
        fi
    fi
done

# Push changes to GitHub
git push origin main --tags

echo "Sync completed successfully!"
