#!/usr/bin/env bash


# ----------------
# Check Bash
# ----------------

# Before anything else and in plain POSIX syntax, since `sh install.sh` would choke on the Bash-only parts below
if [ -z "${BASH_VERSION:-}" ]; then
    echo "Error: This installer needs Bash, but it was started with another shell." >&2
    echo "Run it with: bash install.sh" >&2
    exit 1
fi


# ----------------
# Output
# ----------------

bold=""; dim=""; red=""; green=""; reset=""

# Colors only on a terminal and not when NO_COLOR is set (https://no-color.org)
if [ -t 1 ] && [ -z "${NO_COLOR:-}" ]; then
    bold=$'\033[1m';
    dim=$'\033[2m';
    red=$'\033[31m';
    green=$'\033[32m';
    reset=$'\033[0m'
fi

# Start on a clean screen so the banner is at the top
if [ -t 1 ]; then
    printf '\033[H\033[2J'
fi

printf '%s' "$green"
cat <<'EOF'
 _____     _    _____    _
|__  /   _| |__|__  /___| |_
  / / | | | '_ \ / // _ \ __|
 / /| |_| | |_) / /|  __/ |_
/____\__,_|_.__/____\___|\__|
EOF
printf '%s\n' "$reset"
echo "${green}ℹ ${bold}Welcome to ZubZet!${reset}"
echo


# ----------------
# Spinner
# ----------------

# The installation output is hidden in a log. The spinner shows its last line below itself and
# the whole log only if something fails.
# Only on a terminal, in logs and pipes the installer prints a plain line instead.
log="$(mktemp)"
spinner_pid=""
tty_state=""

# Seconds as MM:SS
clock() {
    printf '%02d:%02d' "$(($1 / 60))" "$(($1 % 60))"
}

spinner_start() {
    if [ ! -t 1 ]; then
        echo "Installing dependencies..."
        return 0
    fi
    cols="$(tput cols 2>/dev/null || echo 80)"

    # Hide the cursor and reserve the line below the spinner for the log
    printf '\033[?25l\n\033[1A'

    # Keys pressed meanwhile (e.g. Enter) must not be echoed, they would move the spinner down
    if [ -t 0 ]; then
        tty_state="$(stty -g)"
        stty -echo
    fi

    (
        frames=('⠋' '⠙' '⠹' '⠸' '⠼' '⠴' '⠦' '⠧' '⠇' '⠏')
        start=$SECONDS
        i=0
        while true; do
            # Last line of the log, progress bars rewrite their line with \r
            last="$(tail -n 1 "$log" 2>/dev/null)"
            last="${last##*$'\r'}"
            last="${last//[^[:print:]]/}"
            last="${last#"${last%%[![:space:]]*}"}"

            printf '\r\033[K%s%s%s Installing dependencies... %s[%s]%s\n\033[K  %s%s%s\033[1A' \
                "$green" "${frames[i]}" "$reset" "$green" "$(clock "$((SECONDS - start))")" "$reset" \
                "$dim" "${last:0:$((cols - 4))}" "$reset"
            i=$(( (i + 1) % ${#frames[@]} ))
            sleep 0.08
        done
    ) &
    spinner_pid=$!
}

spinner_stop() {
    if [ -z "$spinner_pid" ]; then
        return 0
    fi
    kill "$spinner_pid" 2>/dev/null
    wait "$spinner_pid" 2>/dev/null
    spinner_pid=""

    # Drop what was typed meanwhile and restore the terminal
    if [ -n "$tty_state" ]; then
        while read -r -t 0.01 _; do :; done
        stty "$tty_state"
        tty_state=""
    fi

    # Clear both lines and show the cursor again
    printf '\r\033[K\n\033[K\033[1A\r\033[?25h'
}

# Also clean up when aborted with Ctrl+C or when a step fails
cleanup() {
    spinner_stop
    rm -f "$log"
}
trap cleanup EXIT
trap 'exit 130' INT TERM


# ----------------
# Check Environment
# ----------------

if ! command -v docker >/dev/null; then
    echo "${red}${bold}Error:${reset} Docker is required but was not found in your PATH." >&2
    echo "Install Docker: https://docs.docker.com/get-started/get-docker/" >&2
    echo "Then run this installer again." >&2
    exit 1
fi


# ----------------
# Project Directory
# ----------------

# Given as first argument (./install.sh my-project), otherwise asked for
if [ $# -ge 1 ]; then
    directory="$1"
else
    question="${bold}Where would you like to create your project?${reset}"
    echo "${green}?${reset} $question ${dim}(leave empty for current directory)${reset}"
    read -r -p "> " directory

    # Replace the question with its answer once it is given
    if [ -t 0 ] && [ -t 1 ]; then
        printf '\033[2A\r\033[J'
        echo "${green}✔${reset} $question"
        echo "${directory:-.}"
    fi
fi
directory="${directory:-.}"

mkdir -p "$directory"
directory="$(cd "$directory" && pwd)"
echo "${green}ℹ${reset} Creating a new project in ${green}$directory${reset}."

# The project belongs to the user running the installer, the application container takes over its uid.
# Apache refuses to run as root, so for root the project goes to uid 1000 instead.
owner="$(id -u):$(id -g)"
if [ "$(id -u)" = 0 ]; then
    owner="1000:1000"
    chown "$owner" "$directory"
fi

# ----------------
# Require Framework, Run Install Command
# ----------------

# The framework from source because the dist archive leaves out tests/, everything else as dist
install_start=$SECONDS
spinner_start
if ! docker run --rm -u "$owner" -e HOME=/tmp -v "$directory":/app --entrypoint sh ghcr.io/zubzet/php:8.5-apache -c '
    mkdir /tmp/installer && cd /tmp/installer &&
    echo "{\"config\":{\"preferred-install\":{\"zubzet/framework\":\"source\",\"*\":\"dist\"}}}" > composer.json &&
    composer require --no-ansi zubzet/framework:dev-develop &&
    cd vendor/zubzet/framework/tests/e2e && COMPOSER_VENDOR_DIR=/tmp/installer/vendor php index.php install /app
' > "$log" 2>&1; then
    spinner_stop
    echo "${red}${bold}Error:${reset} The installation failed:" >&2
    cat "$log" >&2
    exit 1
fi
spinner_stop

echo "${green}✔${reset} Done! ZubZet installed in ${bold}$directory.${reset} ${green}[$(clock "$((SECONDS - install_start))")]${reset}"


# ----------------
# Next Steps
# ----------------

# Laid out like the output of `info:startup` in the framework
rule="  ${dim}─────────────────────────────────────────────${reset}"

# Label in the muted color, value in bold
info_row() {
    printf '  %s%-8s%s %s\n' "$dim" "$1" "$reset" "$2"
}

echo
echo "$rule"
# Only when the project is not in the current directory
if [ "$directory" != "$PWD" ]; then
    info_row "Enter" "${bold}cd $(printf '%q' "$directory")${reset}"
fi
info_row "Start" "${bold}./project start${reset}"
info_row "Stop" "${bold}./project stop${reset}"
echo
info_row "Docs" "${green}https://zubzet.com/docs${reset}"
info_row "Issues" "${green}https://github.com/zubzet/framework/issues${reset}"
echo "$rule"
echo
