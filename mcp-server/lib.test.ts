import { describe, it, expect } from "vitest";
import {
  extractShareToken,
  formatTimestamp,
  formatDuration,
  buildTranscriptionOutput,
  buildCommentsOutput,
  buildVideoInfo,
} from "./lib.js";

// --- extractShareToken ---

describe("extractShareToken", () => {
  it("extracts token from full HTTPS URL", () => {
    expect(
      extractShareToken("https://app.openkap.com/share/video/abc123def456")
    ).toBe("abc123def456");
  });

  it("extracts token from HTTP URL", () => {
    expect(
      extractShareToken("http://localhost:8000/share/video/testToken99")
    ).toBe("testToken99");
  });

  it("extracts token from custom domain URL", () => {
    expect(
      extractShareToken("https://videos.mycompany.com/share/video/XyZ789")
    ).toBe("XyZ789");
  });

  it("extracts token from relative path", () => {
    expect(extractShareToken("/share/video/myToken")).toBe("myToken");
  });

  it("returns raw alphanumeric token as-is", () => {
    expect(extractShareToken("abc123")).toBe("abc123");
  });

  it("handles leading/trailing whitespace", () => {
    expect(extractShareToken("  abc123  ")).toBe("abc123");
    expect(
      extractShareToken("  https://app.openkap.com/share/video/tok  ")
    ).toBe("tok");
  });

  it("handles long 64-char tokens", () => {
    const token = "a".repeat(64);
    expect(extractShareToken(token)).toBe(token);
  });

  it("throws on empty string", () => {
    expect(() => extractShareToken("")).toThrow("Could not parse share token");
  });

  it("throws on invalid URL without share path", () => {
    expect(() =>
      extractShareToken("https://app.openkap.com/videos/123")
    ).toThrow("Could not parse share token");
  });

  it("throws on string with special characters", () => {
    expect(() => extractShareToken("abc-123_def")).toThrow(
      "Could not parse share token"
    );
  });

  it("throws on URL with empty token", () => {
    expect(() =>
      extractShareToken("https://app.openkap.com/share/video/")
    ).toThrow("Could not parse share token");
  });
});

// --- formatTimestamp ---

describe("formatTimestamp", () => {
  it("formats 0 seconds", () => {
    expect(formatTimestamp(0)).toBe("0:00");
  });

  it("formats seconds under a minute", () => {
    expect(formatTimestamp(5)).toBe("0:05");
    expect(formatTimestamp(30)).toBe("0:30");
    expect(formatTimestamp(59)).toBe("0:59");
  });

  it("formats minutes and seconds", () => {
    expect(formatTimestamp(60)).toBe("1:00");
    expect(formatTimestamp(90)).toBe("1:30");
    expect(formatTimestamp(125)).toBe("2:05");
  });

  it("formats hours", () => {
    expect(formatTimestamp(3600)).toBe("1:00:00");
    expect(formatTimestamp(3661)).toBe("1:01:01");
    expect(formatTimestamp(7384)).toBe("2:03:04");
  });

  it("handles fractional seconds by flooring", () => {
    expect(formatTimestamp(5.7)).toBe("0:05");
    expect(formatTimestamp(90.9)).toBe("1:30");
  });
});

// --- formatDuration ---

describe("formatDuration", () => {
  it("returns 'unknown' for null", () => {
    expect(formatDuration(null)).toBe("unknown");
  });

  it("returns 'unknown' for 0", () => {
    expect(formatDuration(0)).toBe("unknown");
  });

  it("formats valid duration", () => {
    expect(formatDuration(120)).toBe("2:00");
    expect(formatDuration(3661)).toBe("1:01:01");
  });
});

// --- buildTranscriptionOutput ---

describe("buildTranscriptionOutput", () => {
  it("builds output for video with full data", () => {
    const video = {
      title: "Bug Report: Login Failing",
      user_name: "John Doe",
      duration: 185,
      views_count: 42,
      created_at: "2026-04-10T12:00:00Z",
      summary: "User demonstrates a login bug on the staging server.",
      transcription:
        "So if you look here, the login button doesn't respond when I click it.",
      transcription_segments: [
        { start: 0, end: 5.2, text: "So if you look here," },
        {
          start: 5.2,
          end: 12.8,
          text: "the login button doesn't respond when I click it.",
        },
      ],
      comments: [
        {
          author_name: "Jane",
          timestamp_seconds: 6,
          content: "Same issue on my machine",
        },
      ],
    };

    const output = buildTranscriptionOutput(video);

    expect(output).toContain("# Bug Report: Login Failing");
    expect(output).toContain("**Creator:** John Doe");
    expect(output).toContain("**Duration:** 3:05");
    expect(output).toContain("**Views:** 42");
    expect(output).toContain("## Summary");
    expect(output).toContain("login bug on the staging server");
    expect(output).toContain("## Full Transcription");
    expect(output).toContain("login button doesn't respond");
    expect(output).toContain("## Timestamped Segments");
    expect(output).toContain("[0:00 → 0:05]");
    expect(output).toContain("[0:05 → 0:12]");
    expect(output).toContain("## Comments (1)");
    expect(output).toContain("**Jane** at 0:06: Same issue on my machine");
  });

  it("builds output for video without transcription", () => {
    const video = {
      title: "Quick Demo",
      duration: 30,
      views_count: 0,
      created_at: "2026-04-14T00:00:00Z",
    };

    const output = buildTranscriptionOutput(video);

    expect(output).toContain("# Quick Demo");
    expect(output).toContain("**No transcription available.**");
    expect(output).not.toContain("## Full Transcription");
    expect(output).not.toContain("## Timestamped Segments");
    expect(output).not.toContain("## Comments");
  });

  it("handles missing title and creator", () => {
    const video = {};
    const output = buildTranscriptionOutput(video);

    expect(output).toContain("# Untitled Video");
    expect(output).toContain("**Creator:** Unknown");
    expect(output).toContain("**Duration:** unknown");
    expect(output).toContain("**Views:** 0");
  });

  it("includes transcription but omits empty comments", () => {
    const video = {
      title: "Test",
      transcription: "Hello world",
      comments: [],
    };

    const output = buildTranscriptionOutput(video);

    expect(output).toContain("## Full Transcription");
    expect(output).toContain("Hello world");
    expect(output).not.toContain("## Comments");
  });

  it("handles comments without timestamps", () => {
    const video = {
      title: "Test",
      transcription: "Hello",
      comments: [
        { author_name: "Alice", content: "Nice video" },
      ],
    };

    const output = buildTranscriptionOutput(video);

    expect(output).toContain("- **Alice**: Nice video");
    expect(output).not.toContain(" at ");
  });
});

// --- buildCommentsOutput ---

describe("buildCommentsOutput", () => {
  it("returns no-comments message for empty array", () => {
    expect(buildCommentsOutput([])).toBe("No comments on this video.");
  });

  it("formats comments with timestamps and dates", () => {
    const comments = [
      {
        author_name: "Bob",
        timestamp_seconds: 45,
        created_at: "2026-04-12T10:00:00Z",
        content: "This part is confusing",
      },
      {
        author_name: "Alice",
        timestamp_seconds: null,
        created_at: "2026-04-13T15:00:00Z",
        content: "Great walkthrough!",
      },
    ];

    const output = buildCommentsOutput(comments);

    expect(output).toContain("## Comments (2)");
    expect(output).toContain("**Bob** [at 0:45]");
    expect(output).toContain("This part is confusing");
    expect(output).toContain("**Alice**");
    expect(output).toContain("Great walkthrough!");
    // Alice has no timestamp
    expect(output).not.toContain("**Alice** [at");
  });

  it("falls back to user.name when author_name is missing", () => {
    const comments = [
      {
        author_name: null,
        user: { name: "Charlie" },
        content: "From user object",
      },
    ];

    const output = buildCommentsOutput(comments);
    expect(output).toContain("**Charlie**");
  });

  it("falls back to Anonymous when no name available", () => {
    const comments = [
      {
        author_name: null,
        user: null,
        content: "Mystery comment",
      },
    ];

    const output = buildCommentsOutput(comments);
    expect(output).toContain("**Anonymous**");
  });
});

// --- buildVideoInfo ---

describe("buildVideoInfo", () => {
  it("builds complete info object", () => {
    const video = {
      title: "API Walkthrough",
      description: "Shows the new endpoints",
      user_name: "Dev Team",
      duration: 300,
      views_count: 15,
      created_at: "2026-04-14T08:00:00Z",
      transcription: "Full text here",
      summary: "A summary of the API changes",
      comments: [{ id: 1 }, { id: 2 }, { id: 3 }],
    };

    const info = buildVideoInfo(video, "myToken123");

    expect(info).toEqual({
      title: "API Walkthrough",
      description: "Shows the new endpoints",
      creator: "Dev Team",
      duration: "5:00",
      views: 15,
      created_at: "2026-04-14T08:00:00Z",
      has_transcription: true,
      has_summary: true,
      comment_count: 3,
      share_url: expect.stringContaining("/share/video/myToken123"),
    });
  });

  it("handles video without optional fields", () => {
    const video = {
      title: "Minimal",
      views_count: 0,
    };

    const info = buildVideoInfo(video, "tok");

    expect(info.description).toBeNull();
    expect(info.creator).toBeUndefined();
    expect(info.duration).toBe("unknown");
    expect(info.has_transcription).toBe(false);
    expect(info.has_summary).toBe(false);
    expect(info.comment_count).toBe(0);
  });
});
