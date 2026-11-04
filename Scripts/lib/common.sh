#!/bin/bash

SCRIPT_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"

resolve_script_path() {
    local script_name="${1:?Debe indicar un script}"
    local candidates=()

    candidates+=("${SCRIPT_ROOT}/${script_name}")

    if [[ "$script_name" == *[A-Z]* ]]; then
        candidates+=("${SCRIPT_ROOT}/${script_name,,}")
    fi

    local normalized="${script_name//[[:space:]]/_}"
    if [[ "$normalized" != "$script_name" ]]; then
        candidates+=("${SCRIPT_ROOT}/${normalized}")
    fi

    local candidate
    for candidate in "${candidates[@]}"; do
        if [[ -f "$candidate" ]]; then
            printf '%s\n' "$candidate"
            return 0
        fi
    done

    local case_insensitive
    case_insensitive="$(find "$SCRIPT_ROOT" -maxdepth 1 -type f -iname "${script_name}" 2>/dev/null | head -n 1)"
    if [[ -n "$case_insensitive" ]]; then
        printf '%s\n' "$case_insensitive"
        return 0
    fi

    return 1
}

run_script() {
    local script_name="${1:?Debe indicar un script}"
    local script_path

    script_path="$(resolve_script_path "$script_name")" || {
        echo "No se encontró el script '$script_name' en $SCRIPT_ROOT" >&2
        return 1
    }

    bash "$script_path"
}
