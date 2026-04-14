export declare const OPENKAP_BASE_URL: string;
/**
 * Extract a share token from various URL formats:
 *  - https://openkap.com/share/video/abc123
 *  - https://custom-domain.com/share/video/abc123
 *  - /share/video/abc123
 *  - abc123 (raw token)
 */
export declare function extractShareToken(input: string): string;
export declare function formatTimestamp(seconds: number): string;
export declare function formatDuration(seconds: number | null): string;
export declare function apiGet(path: string): Promise<unknown>;
/**
 * Build the full transcription output markdown from a video object.
 */
export declare function buildTranscriptionOutput(v: Record<string, unknown>): string;
/**
 * Build formatted comments output from a comments array.
 */
export declare function buildCommentsOutput(comments: Array<Record<string, unknown>>): string;
/**
 * Build a video info summary object.
 */
export declare function buildVideoInfo(v: Record<string, unknown>, token: string): Record<string, unknown>;
