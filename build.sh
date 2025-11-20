#!/bin/bash

# Function to handle copying and compressing
copy_and_compress() {
  local source_dir="$1"
  local destination_dir="$2"
  local copy_list=("${@:3}")

  # Delete existing files in the destination directory
  rm -rf "$destination_dir"

  # Ensure the destination directory exists
  mkdir -p "$destination_dir"

  # Copy selected folders and files
  for item in "${copy_list[@]}"; do
    source_path="$source_dir/$item"
    destination_path="$destination_dir/$item"

    if [ -e "$source_path" ]; then
      cp -r "$source_path" "$destination_path"
      echo "Copied: $item"
    else
      echo "Warning: $item does not exist in the source directory."
    fi
  done

  echo -e "\nCopy completed."

  # Run the zip command and suppress output

    #echo "Current working directory : $(pwd)"
    #// now got to destination_dir and zip it
    cd "$destination_dir"
    cd ..
    #local build_dir_basename=$(basename "`pwd`")
    local dest_dir_basename=$(basename "$destination_dir")

    #echo "Current working directory : $(basename "$current_dir_basename")"
    #echo "Current working directory : $(pwd)"
    zip -rq "${dest_dir_basename}.zip" "$dest_dir_basename" -x "*.DS_Store"

    cd .. # go back to fluent-crm plugin directory

  # Check for errors
  if [ $? -ne 0 ]; then
    echo "Error occurred while compressing."
    exit 1
  fi

  # Print completion message
  echo -e "\nCompressing Completed. builds/$(basename "$destination_dir").zip is ready. Check the builds directory. Thanks!\n"
}

# Function to handle webpack config and build
handle_block_editor_build() {
  local webpack_config="../fluent-crm-block-editor/webpack.config.js"
  local temp_file="../fluent-crm-block-editor/webpack.config.js.tmp"

  echo "Building block editor for assets path..."
  # Build for assets path first
  echo "Building block editor for assets path..."
  sed -i '' 's/^defaultConfig\.output\.path = defaultConfig\.output\.path + .*$/defaultConfig.output.path = defaultConfig.output.path + "\/..\/..\/fluent-crm\/assets\/block_editor";/' "$webpack_config"
  cd ../fluent-crm-block-editor
  npm run build
  cd ../fluent-crm

  # Then build for resources path
  echo "Building block editor for resources path..."
  sed -i '' 's/^defaultConfig\.output\.path = defaultConfig\.output\.path + .*$/defaultConfig.output.path = defaultConfig.output.path + "\/..\/..\/fluent-crm\/resources\/block_editor";/' "$webpack_config"
  cd ../fluent-crm-block-editor
  npm run build
  cd ../fluent-crm
}

# Get args from command line
nodeBuild=true
withPro=false

for arg in "$@"; do
  case "$arg" in
    "--node-build")
      nodeBuild=true
      ;;
    "--with-pro")
      withPro=true
      ;;
  esac
done

if "$nodeBuild"; then
  # Run block editor builds first
#  handle_block_editor_build

  echo -e "\nBuilding Main App\n"
  npx mix --production
  echo -e "\nBuild Completed"
fi

# Copy and compress Fluent CRM
copy_and_compress "." "builds/fluent-crm" "app" "assets" "boot" "config" "database" "includes" "language" "vendor" "composer.json" "fluent-crm.php" "index.php" "readme.txt"

## delete the directory after build
rm -rf builds/fluent-crm

if ! "$withPro"; then
  exit 0
fi

echo -e "\nReadying Pro Addon\n"

# Copy and compress Fluent Campaign Pro
copy_and_compress "../fluentcampaign-pro" "builds/fluentcampaign-pro" "app" "languages" "fluentcampaign-pro.php" "index.php" "readme.txt" "fluentcampaign_boot.php"

## delete the directories after build
rm -rf builds/fluentcampaign-pro

echo -e "Fluent CRMs are Ready. Check the builds directory. Thanks!\n"

exit 0
