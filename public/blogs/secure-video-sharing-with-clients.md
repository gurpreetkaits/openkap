---
title: "How to Share Confidential Screen Recordings with External Clients Without Losing Control"
slug: "secure-video-sharing-with-clients"
excerpt: "Sharing internal videos with clients via email or public links is risky. Here are the four controls you need — and a step-by-step workflow."
author: "OpenKap Team"
category: "Workflows"
tags:
    - secure-sharing
    - confidential-video
    - client-collaboration
    - expiring-links
meta_title: "Share Confidential Screen Recordings with Clients Securely | OpenKap"
meta_description: "Four controls every team needs to share confidential recordings with external clients: authentication, expiration, access logs, and revocation."
read_time: 6
is_published: true
published_at: "2026-04-16 09:00:00"
---

Agencies, consultants, accounting firms, and legal teams routinely send screen recordings to clients — design walkthroughs, financial reviews, document explainers. The default workflow (record → upload → email a link) is also the worst case for security: the link lives in inboxes forever, can be forwarded to anyone, and you have no idea who actually watched.

This is the playbook for sharing confidential recordings with external clients while keeping control.

## Why the common approaches fail

**Public links** become permanent. Even if you delete the recording later, the URL may live in email threads, Slack, or as a screenshot somewhere. There's no audit trail of who accessed it.

**Email attachments** are worse: most attachments above 25 MB bounce, and once attached, the file is on every recipient's mail server, potentially archived for years under their retention rules.

**Cloud storage shares** (Drive, Dropbox) work, but force the client into your tool's auth and permission model. The added friction kills viewership and the client just asks you to "send it another way."

## The four controls you need

### 1. Authentication or access tokens

The share URL alone should not be enough to view. Either require the client to authenticate, or use a long random token (64+ characters) that's effectively unguessable.

### 2. Expiration

Every external share should have a default expiration — 7, 14, or 30 days depending on the use case. After expiry the link returns 404 even if someone still has the URL.

### 3. Access logs

You need to know who watched, when, and for how long. Without logs, "did the client see it?" becomes a Slack message instead of a verifiable record.

### 4. Revocation

If a client relationship ends, or the recording was sent in error, you need a one-click way to invalidate the link without deleting the underlying recording (which you may still need for your records).

## How to set this up in OpenKap

OpenKap supports all four out of the box.

**Tokens**: every share link uses a 64-character random token, generated per video. URLs look like `/share/video/<token>` — not guessable.

**Expiration**: set per video. Default to 14 days for client work; extend manually if needed.

**Access logs**: every view records timestamp, region, and watch duration. Visible in the video's analytics view.

**Revocation**: toggle a video from public to private, or regenerate the share token. Old links stop working immediately.

## Step-by-step: sharing a confidential video with a client

1. **Record** the walkthrough.
2. **Review** the video — scan for any visible PII not meant for the client (internal pricing, other client names). Re-record if needed.
3. **Set expiration**: open the video, set the share link to expire in 14 days.
4. **Mark private** if your use case requires viewer authentication.
5. **Generate** the share link.
6. **Send** the link via your normal channel. Reference the video by name; the client clicks through.
7. **Track**: check the analytics tab to confirm the client viewed.
8. **Archive or revoke** after delivery, based on the engagement type.

## Common scenarios

### Design agency presenting concepts to a client

- Mark as private, expire in 30 days.
- Use the commenting feature for client feedback anchored to specific moments.
- Revoke when the project concludes.

### Accounting firm walking through year-end financials

- Set expiration to match the engagement window (often 7 days post-meeting).
- Confirm viewing via the access log before closing the engagement.

### Legal counsel explaining contract redlines

- Private, short expiration (3–7 days).
- Include the client's name in the recording itself for chain-of-custody purposes.

## Anti-patterns to avoid

- **Don't reuse share links across clients.** Each client gets their own video link, even for the same underlying content.
- **Don't disable expiration "for convenience".** The friction of generating a new link is the point — it forces you to think about access each time.
- **Don't forget to revoke.** Build revocation into your project close-out checklist so it isn't dependent on memory.

---

For workspace-wide team controls, see [affordable team screen recording](/blog/screen-recording-affordable-teams). For broader compliance context, see our [GDPR screen recording guide](/blog/gdpr-compliant-screen-recording).
