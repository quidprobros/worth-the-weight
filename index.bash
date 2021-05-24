#!/usr/bin/env bash
if [ -z ${npm_package_name+x} ]
then
    printf 'Please run this script with npm\n'
fi

if ! command -v jq 2>&1 >/dev/null
then
    printf '`jq` is required\n https://stedolan.github.io/jq/\n'
    exit
fi

# may seem redundant, but needed if script not run with npm
if ! command jq empty ./package.json
then
    exit;
fi

declare -A config=(
    ssh_alias "${npm_package_config_ssh_alias}"
    ssh_user "${npm_package_config_ssh_user}"
    ssh_domain "${npm_package_config_ssh_domain}"
    ssh_remote_root "${npm_package_config_ssh_remote_root}"
    ssh_local_root "${npm_package_config_ssh_local_root}"
)

for key in "${!config[@]}"
do
    val="${config[$key]}"
    if ! [[ -n "$val" ]];
    then
        printf 'config.%s cannot be zero-length\n\n' "${key}"
        exit
    fi
done
unset key

declare -r ssh_target="${config[ssh_user]}@${config[ssh_domain]}:${config[ssh_remote_root]}"

printf 'Target destination: %s\n\n' "${ssh_target}"


# base-level exclusions (can extend)
declare -a exclusions=(
    ".DS_Store"
    "*#*"
    "*~"
)

read -ra var1 <<< $(<./package.json jq -er '.config.rsync_excluded_list | @sh')

for key in "${var1[@]}"
do
    exclusions+=("${key//\'/}")
done
unset key

# base-level protections (relative to remote root)
declare -a protection_and_perish=(
    "-p .DS_Store"
)

declare -a _protections=($(<./package.json jq -er '.config.rsync_protect_and_perish_list.protect[] | @sh'))

for key in "${_protections[@]}"
do
    protection_and_perish+=("P ${key//\'/}")
done

unset key

declare -a _perishes=($(<./package.json jq -er '.config.rsync_protect_and_perish_list.perish[] | @sh'))

for key in "${_perishes[@]}"
do
    protection_and_perish+=("-p ${key//\'/}")
done
unset key


rsync -e '/usr/bin/ssh -T -x' \
      --dry-run \
      --compress \
      --protect-args \
      --delete \
      --exclude-from <(printf '%s\n' "${exclusions[@]}") \
      --filter='merge '<(printf '%s\n' "${protection_and_perish[@]}") \
      --delete-excluded \
      --archive \
      --progress \
      --times \
      --copy-links \
      --verbose \
      "${config[ssh_local_root]}" \
      "${ssh_target?}"

#notes
# 1. base level exclusion and protection lists
# 2. default should be NOT to delete non-matching
# 3. protect "home" is relative to remote root: 'P /src' => /a/b/c/src
# 4. need to indicate if array config properties are missing, or allow for null lists
