export const OPENKAP_BASE_URL =
  process.env.OPENKAP_URL || "https://app.openkap.com";

/**
 * Extract a share token from various URL formats:
 *  - https://app.openkap.com/share/video/abc123
 *  - https://custom-domain.com/share/video/abc123
 *  - /share/video/abc123
 *  - abc123 (raw token)
 */
export function extractShareToken(input: string): string {
  const trimmed = input.trim();

  // Full URL or path: grab everything after /share/video/
  const match = trimmed.match(/\/share\/video\/([A-Za-z0-9]+)/);
  if (match) return match[1];

  // Already a raw token (alphanumeric string)
  if (/^[A-Za-z0-9]+$/.test(trimmed)) return trimmed;

  // Truncate input in error message to avoid leaking or reflecting large payloads
  const safe = trimmed.length > 80 ? trimmed.slice(0, 80) + "..." : trimmed;
  throw new Error(
    `Could not parse share token from: "${safe}". ` +
      `Expected a URL like https://app.openkap.com/share/video/TOKEN or a raw token.`
  );
}

export function formatTimestamp(seconds: number): string {
  const h = Math.floor(seconds / 3600);
  const m = Math.floor((seconds % 3600) / 60);
  const s = Math.floor(seconds % 60);
  if (h > 0)
    return `${h}:${m.toString().padStart(2, "0")}:${s.toString().padStart(2, "0")}`;
  return `${m}:${s.toString().padStart(2, "0")}`;
}

export function formatDuration(seconds: number | null): string {
  if (!seconds) return "unknown";
  return formatTimestamp(seconds);
}

export async function apiGet(path: string): Promise<unknown> {
  const url = `${OPENKAP_BASE_URL}/api${path}`;
  const res = await fetch(url, {
    headers: { Accept: "application/json" },
  });

  if (!res.ok) {
    // Map status codes to safe messages — never leak raw API error bodies
    // which could contain stack traces, file paths, or env vars in debug mode
    const safeMessages: Record<number, string> = {
      403: "This video is not available (private or expired share link).",
      404: "Video not found. Check that the share URL or token is correct.",
      429: "Too many requests. Please wait a moment and try again.",
    };
    const message =
      safeMessages[res.status] ||
      `Request failed (HTTP ${res.status}). Please try again later.`;
    throw new Error(message);
  }

  return res.json();
}

/**
 * Build the full transcription output markdown from a video object.
 */
export function buildTranscriptionOutput(v: Record<string, unknown>): string {
  let output = `# ${v.title || "Untitled Video"}\n\n`;
  output += `**Creator:** ${v.user_name || "Unknown"}\n`;
  output += `**Duration:** ${formatDuration(v.duration as number | null)}\n`;
  output += `**Views:** ${v.views_count || 0}\n`;
  output += `**Recorded:** ${v.created_at || "Unknown"}\n\n`;

  if (v.summary) {
    output += `## Summary\n\n${v.summary}\n\n`;
  }

  if (v.transcription) {
    output += `## Full Transcription\n\n${v.transcription}\n\n`;
  }

  if (
    v.transcription_segments &&
    Array.isArray(v.transcription_segments)
  ) {
    output += `## Timestamped Segments\n\n`;
    for (const seg of v.transcription_segments as Array<
      Record<string, unknown>
    >) {
      const start = formatTimestamp(seg.start as number);
      const end = formatTimestamp(seg.end as number);
      output += `[${start} → ${end}] ${seg.text}\n`;
    }
    output += "\n";
  }

  if (!v.transcription && !v.transcription_segments) {
    output +=
      "**No transcription available.** The video may not have audio, or transcription hasn't been generated yet.\n\n";
  }

  const comments = v.comments as
    | Array<Record<string, unknown>>
    | undefined;
  if (comments && comments.length > 0) {
    output += `## Comments (${comments.length})\n\n`;
    for (const c of comments) {
      const at = c.timestamp_seconds
        ? ` at ${formatTimestamp(c.timestamp_seconds as number)}`
        : "";
      output += `- **${c.author_name}**${at}: ${c.content}\n`;
    }
  }

  return output;
}

/**
 * Build formatted comments output from a comments array.
 */
export function buildCommentsOutput(
  comments: Array<Record<string, unknown>>
): string {
  if (comments.length === 0) {
    return "No comments on this video.";
  }

  let output = `## Comments (${comments.length})\n\n`;
  for (const c of comments) {
    const at =
      c.timestamp_seconds != null
        ? ` [at ${formatTimestamp(c.timestamp_seconds as number)}]`
        : "";
    const date = c.created_at
      ? ` — ${new Date(c.created_at as string).toLocaleDateString()}`
      : "";
    const user = c.user as Record<string, unknown> | undefined;
    output += `- **${c.author_name || user?.name || "Anonymous"}**${at}${date}: ${c.content}\n`;
  }

  return output;
}

/**
 * Build a video info summary object.
 */
export function buildVideoInfo(
  v: Record<string, unknown>,
  token: string
): Record<string, unknown> {
  return {
    title: v.title,
    description: v.description || null,
    creator: v.user_name,
    duration: formatDuration(v.duration as number | null),
    views: v.views_count || 0,
    created_at: v.created_at,
    has_transcription: !!v.transcription,
    has_summary: !!v.summary,
    comment_count: Array.isArray(v.comments) ? v.comments.length : 0,
    share_url: `${OPENKAP_BASE_URL}/share/video/${token}`,
  };
}
