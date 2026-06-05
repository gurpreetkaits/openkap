import { ref, computed } from "vue";
import videoService from "@/services/videoService";

// Shared global state across all component instances
const downloads = ref(loadFromStorage());
let pollInterval = null;
let pollRefCount = 0;

function loadFromStorage() {
    try {
        const stored = localStorage.getItem("openkap_downloads_v2");
        if (stored) {
            const parsed = JSON.parse(stored);
            const cutoff = Date.now() - 24 * 60 * 60 * 1000;
            return parsed.filter((d) => d.timestamp > cutoff);
        }
    } catch {}
    return [];
}

function saveToStorage() {
    localStorage.setItem(
        "openkap_downloads_v2",
        JSON.stringify(downloads.value),
    );
}

function startPolling() {
    if (pollInterval) return;
    pollInterval = setInterval(pollAllProgress, 3000);
    pollAllProgress();
}

function stopPolling() {
    if (pollInterval) {
        clearInterval(pollInterval);
        pollInterval = null;
    }
}

async function pollAllProgress() {
    const processing = downloads.value.filter(
        (d) =>
            d.status !== "ready" &&
            d.status !== "failed" &&
            d.status !== "expired",
    );
    if (processing.length === 0) {
        stopPolling();
        return;
    }

    for (const dl of processing) {
        try {
            const token = localStorage.getItem("auth_token");
            const headers = { Accept: "application/json" };
            if (token) {
                headers["Authorization"] = `Bearer ${token}`;
            }

            const baseUrl = import.meta.env.VITE_API_BASE_URL || "";
            const response = await fetch(
                `${baseUrl}/api/downloads/progress/${dl.downloadId}`,
                { headers },
            );

            if (!response.ok) continue;

            const data = await response.json();

            if (data.status === "ready") {
                dl.status = "ready";
                dl.progress = 100;
                if (data.token) dl.token = data.token;
            } else if (data.status === "failed") {
                dl.status = "failed";
                dl.errorMessage = data.error_message || "Conversion failed";
            } else {
                dl.status = data.status || dl.status;
                dl.progress = data.progress || dl.progress;
            }
        } catch (err) {
            console.error("Download progress poll error:", err);
        }
    }

    saveToStorage();
}

export function useDownloadProgress() {
    function startTracking() {
        pollRefCount++;
        const hasProcessing = downloads.value.some(
            (d) =>
                d.status !== "ready" &&
                d.status !== "failed" &&
                d.status !== "expired",
        );
        if (hasProcessing) {
            startPolling();
        }
    }

    function stopTracking() {
        pollRefCount--;
        if (pollRefCount <= 0) {
            pollRefCount = 0;
            stopPolling();
        }
    }

    async function requestDownload(videoId, videoTitle) {
        const existing = downloads.value.find(
            (d) =>
                d.videoId === videoId &&
                d.status !== "failed" &&
                d.status !== "expired",
        );
        if (existing) {
            return existing;
        }

        const result = await videoService.requestUnifiedDownload(videoId);

        const download = {
            downloadId: result.id,
            videoId: videoId,
            videoTitle: videoTitle || "Untitled Video",
            token: result.token,
            status: result.status,
            progress: result.progress || 0,
            timestamp: Date.now(),
        };

        downloads.value.push(download);
        saveToStorage();
        startPolling();

        return download;
    }

    async function triggerFileDownload(download) {
        try {
            const token = localStorage.getItem("auth_token");
            const headers = {};
            if (token) {
                headers["Authorization"] = `Bearer ${token}`;
            }

            const baseUrl = import.meta.env.VITE_API_BASE_URL || "";
            const response = await fetch(
                `${baseUrl}/api/downloads/${download.token}/file`,
                { headers },
            );

            if (!response.ok) {
                const err = await response.json().catch(() => ({}));
                throw new Error(err.message || "Download failed");
            }

            const blob = await response.blob();
            const blobUrl = window.URL.createObjectURL(blob);
            const link = document.createElement("a");
            link.href = blobUrl;
            link.download = `${download.videoTitle || "video"}.mp4`;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            window.URL.revokeObjectURL(blobUrl);

            removeDownload(download.downloadId);
        } catch (err) {
            console.error("Failed to trigger download:", err);
            throw err;
        }
    }

    function removeDownload(downloadId) {
        downloads.value = downloads.value.filter(
            (d) => d.downloadId !== downloadId,
        );
        saveToStorage();
    }

    function clearAll() {
        downloads.value = [];
        saveToStorage();
        stopPolling();
    }

    const processingCount = computed(
        () =>
            downloads.value.filter(
                (d) =>
                    d.status !== "ready" &&
                    d.status !== "failed" &&
                    d.status !== "expired",
            ).length,
    );

    const readyCount = computed(
        () => downloads.value.filter((d) => d.status === "ready").length,
    );

    const badgeCount = computed(() => processingCount.value + readyCount.value);

    return {
        downloads,
        requestDownload,
        triggerFileDownload,
        removeDownload,
        clearAll,
        startTracking,
        stopTracking,
        processingCount,
        readyCount,
        badgeCount,
    };
}
