#!/bin/bash
set -euo pipefail

BACKUP_ROOT="${BACKUP_ROOT:-/root}"
BACKUP_BD_DIR="${BACKUP_BD_DIR:-${BACKUP_ROOT}/respaldos_bd}"
BACKUP_LOGS_DIR="${BACKUP_LOGS_DIR:-${BACKUP_ROOT}/respaldos_logs}"
MYSQL_DATABASE="${MYSQL_DATABASE:-sigsm}"
MYSQL_HOST="${MYSQL_HOST:-${MYSQL_BACKUP_HOST:-127.0.0.1}}"
MYSQL_PORT="${MYSQL_PORT:-3306}"
MYSQL_USER="${MYSQL_USER:-root}"
BACKUP_ROLE="${BACKUP_ROLE:-${SIGSM_ROLE:-}}"

require_backup_role() {
    local role="${BACKUP_ROLE:-}"

    if [[ -z "$role" ]] && [[ "$(id -u 2>/dev/null || echo 0)" == "0" ]]; then
        role="Administrador General"
    fi

    if [[ -z "$role" ]]; then
        echo "Acceso denegado: defina BACKUP_ROLE='Administrador General' o BACKUP_ROLE='IT' antes de ejecutar respaldos." >&2
        exit 1
    fi

    case "$role" in
        "Administrador General"|"IT")
            ;;
        *)
            echo "Acceso denegado: solo los roles 'Administrador General' e 'IT' pueden gestionar respaldos." >&2
            exit 1
            ;;
    esac
}

ensure_backup_dirs() {
    mkdir -p "$BACKUP_BD_DIR" "$BACKUP_LOGS_DIR"
}

create_database_backup() {
    require_backup_role
    ensure_backup_dirs

    local stamp
    stamp="$(date +%Y-%m-%d_%H-%M-%S)"
    local output_file="${BACKUP_BD_DIR}/${stamp}-${MYSQL_DATABASE}_bd_backup.sql"

    echo "Generando respaldo de la base de datos '$MYSQL_DATABASE' en $output_file..."

    if [[ -n "${MYSQL_PASSWORD:-}" ]]; then
        export MYSQL_PWD="$MYSQL_PASSWORD"
        mysqldump --host="$MYSQL_HOST" --port="$MYSQL_PORT" --user="$MYSQL_USER" --databases "$MYSQL_DATABASE" --single-transaction --routines --triggers --events > "$output_file"
    else
        mysqldump --host="$MYSQL_HOST" --port="$MYSQL_PORT" --user="$MYSQL_USER" -p --databases "$MYSQL_DATABASE" --single-transaction --routines --triggers --events > "$output_file"
    fi

    echo "Respaldo creado: $output_file"
}

create_logs_backup() {
    require_backup_role
    ensure_backup_dirs

    local stamp
    local output_file
    local log_root="${LOG_BACKUP_ROOT:-/var/log}"
    local -a safe_log_files=()

    stamp="$(date +%Y-%m-%d_%H-%M-%S)"
    output_file="${BACKUP_LOGS_DIR}/${stamp}-logs_sistema_backup.tar.gz"

    while IFS= read -r log_file; do
        case "$log_file" in
            journal/*|private/*|apt/*|installer/*)
                continue
                ;;
            *)
                safe_log_files+=("$log_file")
                ;;
        esac
    done < <(find "$log_root" -type f -readable -printf '%P\n' 2>/dev/null | sort)

    if (( ${#safe_log_files[@]} == 0 )); then
        echo "No se encontraron archivos de log legibles en '$log_root'. Se creará un archivo vacío." >&2
        tar -czf "$output_file" -C "$log_root" --files-from=/dev/null
    else
        tar -czf "$output_file" -C "$log_root" "${safe_log_files[@]}"
    fi

    echo "Respaldo de logs creado: $output_file"
}

list_backups() {
    echo "Respaldos de BD:"
    if [[ -d "$BACKUP_BD_DIR" ]]; then
        ls -l "$BACKUP_BD_DIR" 2>/dev/null || true
    else
        echo "No existe el directorio de respaldo de base de datos."
    fi

    echo
    echo "Respaldos de logs:"
    if [[ -d "$BACKUP_LOGS_DIR" ]]; then
        ls -l "$BACKUP_LOGS_DIR" 2>/dev/null || true
    else
        echo "No existe el directorio de respaldo de logs."
    fi
}

restore_database_backup() {
    require_backup_role
    local backup_file="${1:-}"

    if [[ -z "$backup_file" ]]; then
        echo "Debe indicar el archivo SQL a restaurar."
        return 1
    fi

    if [[ ! -f "$backup_file" ]]; then
        echo "El archivo '$backup_file' no existe."
        return 1
    fi

    if [[ -n "${MYSQL_PASSWORD:-}" ]]; then
        export MYSQL_PWD="$MYSQL_PASSWORD"
        mysql --host="$MYSQL_HOST" --port="$MYSQL_PORT" --user="$MYSQL_USER" < "$backup_file"
    else
        mysql --host="$MYSQL_HOST" --port="$MYSQL_PORT" --user="$MYSQL_USER" -p < "$backup_file"
    fi

    echo "Respaldo de BD restaurado: $backup_file"
}

restore_logs_backup() {
    require_backup_role
    local backup_file="${1:-}"

    if [[ -z "$backup_file" ]]; then
        echo "Debe indicar el archivo TAR.GZ a restaurar."
        return 1
    fi

    if [[ ! -f "$backup_file" ]]; then
        echo "El archivo '$backup_file' no existe."
        return 1
    fi

    mkdir -p /root/logs_restaurados
    tar -xzf "$backup_file" -C /root/logs_restaurados
    echo "Respaldo de logs restaurado: $backup_file"
}

send_remote_backup() {
    require_backup_role

    local source_path="${1:-}"
    local remote_user="${REMOTE_USER:-}"
    local remote_host="${REMOTE_HOST:-}"
    local remote_dir="${REMOTE_DIR:-}"
    local ssh_port="${SSH_PORT:-22}"
    local ssh_key="${SSH_KEY_PATH:-}"

    if [[ -z "$source_path" ]]; then
        read -p "Ruta local del archivo o directorio a respaldar: " source_path
    fi
    source_path="${source_path//\\//}"

    if [[ ! -e "$source_path" ]]; then
        echo "Error: La ruta '$source_path' no existe."
        return 1
    fi

    if [[ -z "$remote_user" ]]; then
        read -p "Usuario remoto: " remote_user
    fi
    if [[ -z "$remote_host" ]]; then
        read -p "Host remoto: " remote_host
    fi
    if [[ -z "$remote_dir" ]]; then
        read -p "Ruta remota de destino (ej. /home/usuario/backups): " remote_dir
    fi
    if [[ -z "$ssh_port" ]]; then
        read -p "Puerto SSH [22]: " ssh_port
        ssh_port="${ssh_port:-22}"
    fi

    if ! command -v tar >/dev/null 2>&1; then
        echo "Error: tar no está instalado en este sistema."
        return 1
    fi
    if ! command -v ssh >/dev/null 2>&1 || ! command -v scp >/dev/null 2>&1; then
        echo "Error: SSH no está instalado en este sistema."
        return 1
    fi

    local archive_name
    archive_name="$(basename "$source_path")_$(date +%Y%m%d_%H%M%S).tar.gz"
    local archive_path="/tmp/${archive_name}"

    tar -czf "$archive_path" -C "$(dirname "$source_path")" "$(basename "$source_path")"

    local ssh_opts=( -o BatchMode=yes -o StrictHostKeyChecking=no -o ConnectTimeout=10 -p "$ssh_port" )
    local scp_opts=( -o BatchMode=yes -o StrictHostKeyChecking=no -P "$ssh_port" )

    if [[ -n "$ssh_key" ]]; then
        ssh_opts+=( -i "$ssh_key" )
        scp_opts+=( -i "$ssh_key" )
    fi

    ssh "${ssh_opts[@]}" "${remote_user}@${remote_host}" "mkdir -p '$remote_dir'"
    scp "${scp_opts[@]}" "$archive_path" "${remote_user}@${remote_host}:${remote_dir}/"

    rm -f "$archive_path"
    echo "Respaldo enviado correctamente a ${remote_user}@${remote_host}:${remote_dir}"
}

configure_backup_cron() {
    require_backup_role
    local cron_script="${1:-${SCRIPT_DIR:-/root}/Scripts/enviarRespaldoRemoto.sh}"
    local cron_min="${CRON_MIN:-0}"
    local cron_hour="${CRON_HOUR:-2}"
    local cron_day="${CRON_DAY:-*}"
    local cron_month="${CRON_MONTH:-*}"
    local cron_weekday="${CRON_WEEKDAY:-*}"

    local cron_line="${cron_min} ${cron_hour} ${cron_day} ${cron_month} ${cron_weekday} bash ${cron_script}"
    local tmp_cron
    tmp_cron="$(mktemp)"

    if crontab -l >/dev/null 2>&1; then
        crontab -l > "$tmp_cron"
    fi

    if ! grep -Fqx "$cron_line" "$tmp_cron"; then
        echo "$cron_line" >> "$tmp_cron"
    fi

    crontab "$tmp_cron"
    rm -f "$tmp_cron"
    echo "Cron configurado: $cron_line"
}
