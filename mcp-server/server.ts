#!/usr/bin/env node

import { Server } from "@modelcontextprotocol/sdk/server/index.js";
import { StdioServerTransport } from "@modelcontextprotocol/sdk/server/stdio.js";
import {
  ListToolsRequestSchema,
  CallToolRequestSchema,
} from "@modelcontextprotocol/sdk/types.js";
import {
  extractShareToken,
  formatTimestamp,
  formatDuration,
  buildTranscriptionOutput,
  buildCommentsOutput,
  buildVideoInfo,
  apiGet,
  OPENKAP_BASE_URL,
} from "./lib.js";

// --- Server ---

const server = new Server(
  { name: "openkap", version: "1.0.0" },
  {
    capabilities: { tools: {} },
    instructions: `OpenKap MCP Server — Read video transcriptions, summaries, and comments from shared OpenKap videos.

Users share a video URL (e.g. https://openkap.com/share/video/TOKEN) or a share token. You can then:
- Fetch the full transcription of the video
- Read time-stamped segments to find what was said at specific moments
- Get the AI summary of the video
- Read comments and reactions from viewers
- Understand what issues, topics, or bugs were discussed in the video

No authentication is required — this works with any publicly shared OpenKap video.`,
  }
);

// --- Tools ---

server.setRequestHandler(ListToolsRequestSchema, async () => ({
  tools: [
    {
      name: "get_video_transcription",
      description:
        "Get the full transcription of a shared OpenKap video. Accepts a share URL (e.g. https://openkap.com/share/video/TOKEN) or just the share token. Returns the transcription text, time-stamped segments, summary, video metadata, and comments.",
      inputSchema: {
        type: "object" as const,
        properties: {
          url_or_token: {
            type: "string",
            description:
              "The OpenKap share URL (e.g. https://openkap.com/share/video/abc123) or the share token directly",
          },
        },
        required: ["url_or_token"],
      },
    },
    {
      name: "get_video_comments",
      description:
        "Get all comments on a shared OpenKap video. Comments are timestamped and tied to specific moments in the video. Useful for understanding viewer feedback, bug reports, or discussion points.",
      inputSchema: {
        type: "object" as const,
        properties: {
          url_or_token: {
            type: "string",
            description:
              "The OpenKap share URL or share token",
          },
        },
        required: ["url_or_token"],
      },
    },
    {
      name: "get_video_info",
      description:
        "Get metadata about a shared OpenKap video — title, description, duration, view count, creator name, and transcription/summary availability. Use this for a quick overview before fetching the full transcription.",
      inputSchema: {
        type: "object" as const,
        properties: {
          url_or_token: {
            type: "string",
            description:
              "The OpenKap share URL or share token",
          },
        },
        required: ["url_or_token"],
      },
    },
  ],
}));

// --- Handlers ---

server.setRequestHandler(CallToolRequestSchema, async (req) => {
  const args = (req.params.arguments ?? {}) as Record<string, unknown>;

  try {
    switch (req.params.name) {
      case "get_video_transcription": {
        const token = extractShareToken(String(args.url_or_token));
        const data = (await apiGet(`/share/video/${token}`)) as {
          video: Record<string, unknown>;
        };
        return {
          content: [{ type: "text", text: buildTranscriptionOutput(data.video) }],
        };
      }

      case "get_video_comments": {
        const token = extractShareToken(String(args.url_or_token));
        const data = (await apiGet(`/share/video/${token}/comments`)) as {
          comments?: Array<Record<string, unknown>>;
          data?: Array<Record<string, unknown>>;
        };
        const comments = data.comments || data.data || [];
        return {
          content: [{ type: "text", text: buildCommentsOutput(comments) }],
        };
      }

      case "get_video_info": {
        const token = extractShareToken(String(args.url_or_token));
        const data = (await apiGet(`/share/video/${token}`)) as {
          video: Record<string, unknown>;
        };
        return {
          content: [
            {
              type: "text",
              text: JSON.stringify(buildVideoInfo(data.video, token), null, 2),
            },
          ],
        };
      }

      default:
        return {
          isError: true,
          content: [{ type: "text", text: `Unknown tool: ${req.params.name}` }],
        };
    }
  } catch (err) {
    const message = err instanceof Error ? err.message : String(err);
    return {
      isError: true,
      content: [{ type: "text", text: `Error: ${message}` }],
    };
  }
});

// --- Start ---

async function main() {
  const transport = new StdioServerTransport();
  await server.connect(transport);
}

main().catch((err) => {
  console.error("Failed to start OpenKap MCP server:", err);
  process.exit(1);
});
