#!/bin/bash
# Claude Code hook logger — captures all agent/subagent output and user input.
# Each session gets its own sequential JSONL file with a slug from the first user prompt.
#
# File naming: NNN-slug.jsonl (e.g., 001-create-user-migration.jsonl)
# Events before the first prompt buffer to a temp file, then merge when the slug is known.

LOG_DIR="${CLAUDE_PROJECT_DIR:-.}/logs/agent"
STATE_DIR="${LOG_DIR}/.state"
mkdir -p "$LOG_DIR" "$STATE_DIR"

INPUT=$(cat)
SESSION_ID=$(echo "$INPUT" | jq -r '.session_id // empty')
EVENT=$(echo "$INPUT" | jq -r '.hook_event_name // empty')

[ -z "$SESSION_ID" ] && exit 0

# Format the log entry
ENTRY=$(echo "$INPUT" | jq -c '{
  timestamp: (now | todate),
  event: .hook_event_name,
  session_id: .session_id,
  agent_id: (.agent_id // null),
  agent_type: (.agent_type // null),
  data: (
    if .hook_event_name == "UserPromptSubmit" then { prompt: .prompt }
    elif .hook_event_name == "PreToolUse" then { tool: .tool_name, input: .tool_input }
    elif .hook_event_name == "PostToolUse" then { tool: .tool_name, input: .tool_input, duration_ms: .tool_execution_time_ms }
    elif .hook_event_name == "PostToolUseFailure" then { tool: .tool_name, input: .tool_input, error: .tool_error }
    elif .hook_event_name == "SubagentStart" then { agent_name: .agent_name, prompt: .prompt }
    elif .hook_event_name == "SubagentStop" then { agent_name: .agent_name, final_message: .final_message }
    elif .hook_event_name == "Stop" then { stop_hook_active: .stop_hook_active }
    elif .hook_event_name == "SessionStart" then { source: .source }
    elif .hook_event_name == "SessionEnd" then { reason: .reason }
    else del(.hook_event_name, .session_id, .cwd, .permission_mode, .transcript_path)
    end
  )
}' 2>/dev/null)

[ -z "$ENTRY" ] && exit 0

# Session-to-file mapping
SESSION_MAP="${STATE_DIR}/${SESSION_ID}"
BUFFER_FILE="${STATE_DIR}/${SESSION_ID}.buf"

# If we already have a log file for this session, append and exit
if [ -f "$SESSION_MAP" ]; then
  LOG_FILE=$(cat "$SESSION_MAP")
  echo "$ENTRY" >> "$LOG_FILE"
  exit 0
fi

# No log file yet — if this is the first UserPromptSubmit, create the named file
if [ "$EVENT" = "UserPromptSubmit" ]; then
  PROMPT=$(echo "$INPUT" | jq -r '.prompt // "unnamed"')

  # Generate slug: lowercase, keep alphanum/spaces, replace spaces with hyphens, truncate
  SLUG=$(echo "$PROMPT" \
    | tr '[:upper:]' '[:lower:]' \
    | sed 's/[^a-z0-9 ]//g' \
    | sed 's/  */ /g' \
    | sed 's/ /-/g' \
    | cut -c1-60 \
    | sed 's/-$//')
  [ -z "$SLUG" ] && SLUG="unnamed"

  # Next sequential number
  LAST=$(ls -1 "$LOG_DIR" 2>/dev/null | grep -E '^[0-9]{3}-' | sort -r | head -1 | grep -oE '^[0-9]+')
  if [ -z "$LAST" ]; then
    SEQ="001"
  else
    SEQ=$(printf "%03d" $((10#$LAST + 1)))
  fi

  LOG_FILE="${LOG_DIR}/${SEQ}-${SLUG}.jsonl"

  # Flush any buffered events first
  if [ -f "$BUFFER_FILE" ]; then
    cat "$BUFFER_FILE" >> "$LOG_FILE"
    rm -f "$BUFFER_FILE"
  fi

  # Append current entry
  echo "$ENTRY" >> "$LOG_FILE"

  # Record session → file mapping
  echo "$LOG_FILE" > "$SESSION_MAP"
  exit 0
fi

# No log file yet and not a UserPromptSubmit — buffer the entry
echo "$ENTRY" >> "$BUFFER_FILE"
exit 0
