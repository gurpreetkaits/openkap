@extends('layouts.app')

@section('title', 'ClipForge - Extract Video Clips from YouTube & Local Files | ScreenSense')
@section('meta_description', 'Free online tool to extract clips from YouTube videos or local files. Download as MP4, WebM, GIF, or MP3. No signup required.')
@section('meta_keywords', 'video clip extractor, youtube clip maker, video trimmer, youtube to gif, youtube to mp3, video cutter online, extract video clip')
@section('og_title', 'ClipForge - Free Video Clip Extractor')
@section('og_description', 'Extract clips from YouTube videos or local files. Export as MP4, WebM, GIF, or MP3 instantly.')

@push('styles')
<style>
    .cf-card {
        background: rgba(255,255,255,0.03);
        border: 1px solid rgba(255,255,255,0.06);
        border-radius: 16px;
        backdrop-filter: blur(12px);
    }
    .cf-card-light { background: #fff; border: 1px solid #e5e7eb; border-radius: 16px; }
    .cf-input {
        background: rgba(255,255,255,0.05);
        border: 1px solid rgba(255,255,255,0.1);
        color: #fff;
        border-radius: 10px;
        padding: 10px 14px;
        font-size: 14px;
        width: 100%;
        outline: none;
        transition: border-color 0.2s;
    }
    .cf-input:focus { border-color: #f97316; }
    .cf-input::placeholder { color: rgba(255,255,255,0.3); }
    .cf-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        padding: 10px 20px;
        font-size: 14px;
        font-weight: 600;
        border-radius: 10px;
        transition: all 0.2s;
        cursor: pointer;
        border: none;
    }
    .cf-btn-primary {
        background: linear-gradient(135deg, #f97316, #ea580c);
        color: #fff;
        box-shadow: 0 0 20px -5px rgba(249,115,22,0.4);
    }
    .cf-btn-primary:hover { box-shadow: 0 0 30px -5px rgba(249,115,22,0.6); transform: translateY(-1px); }
    .cf-btn-primary:disabled { opacity: 0.5; cursor: not-allowed; transform: none; box-shadow: none; }
    .cf-btn-secondary {
        background: rgba(255,255,255,0.05);
        color: #d4d4d4;
        border: 1px solid rgba(255,255,255,0.1);
    }
    .cf-btn-secondary:hover { background: rgba(255,255,255,0.1); color: #fff; }
    .cf-tab { padding: 8px 16px; font-size: 13px; font-weight: 500; border-radius: 8px; cursor: pointer; transition: all 0.2s; border: none; }
    .cf-tab-active { background: rgba(249,115,22,0.15); color: #f97316; }
    .cf-tab-inactive { background: transparent; color: rgba(255,255,255,0.4); }
    .cf-tab-inactive:hover { color: rgba(255,255,255,0.7); background: rgba(255,255,255,0.05); }
    .cf-format-card {
        padding: 12px 16px;
        border-radius: 10px;
        border: 1px solid rgba(255,255,255,0.08);
        cursor: pointer;
        transition: all 0.2s;
        background: rgba(255,255,255,0.02);
    }
    .cf-format-card:hover { border-color: rgba(255,255,255,0.15); background: rgba(255,255,255,0.04); }
    .cf-format-card.selected { border-color: #f97316; background: rgba(249,115,22,0.08); }
    .cf-progress-bar { height: 6px; border-radius: 3px; background: rgba(255,255,255,0.08); overflow: hidden; }
    .cf-progress-fill { height: 100%; border-radius: 3px; background: linear-gradient(90deg, #f97316, #fb923c); transition: width 0.3s ease; }
    .cf-drop-zone {
        border: 2px dashed rgba(255,255,255,0.1);
        border-radius: 12px;
        padding: 40px 20px;
        text-align: center;
        transition: all 0.2s;
        cursor: pointer;
    }
    .cf-drop-zone:hover, .cf-drop-zone.dragover { border-color: #f97316; background: rgba(249,115,22,0.05); }
    .cf-toast {
        position: fixed;
        bottom: 24px;
        right: 24px;
        padding: 12px 20px;
        border-radius: 10px;
        font-size: 14px;
        font-weight: 500;
        z-index: 9999;
        transform: translateY(100px);
        opacity: 0;
        transition: all 0.3s ease;
    }
    .cf-toast.show { transform: translateY(0); opacity: 1; }
    .cf-toast-error { background: #dc2626; color: #fff; }
    .cf-toast-success { background: #16a34a; color: #fff; }
    .cf-video-container { position: relative; border-radius: 12px; overflow: hidden; background: #000; }
    .cf-video-container video { width: 100%; max-height: 400px; display: block; }
    .cf-spinner { width: 20px; height: 20px; border: 2px solid rgba(255,255,255,0.2); border-top-color: #fff; border-radius: 50%; animation: cf-spin 0.6s linear infinite; }
    @keyframes cf-spin { to { transform: rotate(360deg); } }
    .cf-fade-in { animation: cf-fadeIn 0.3s ease forwards; }
    @keyframes cf-fadeIn { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: translateY(0); } }
    .cf-time-input { width: 100px; text-align: center; font-family: 'Plus Jakarta Sans', monospace; letter-spacing: 0.5px; }
</style>
@endpush

@section('content')
<div class="max-w-4xl mx-auto px-6 py-12">
    <!-- Hero -->
    <div class="text-center mb-10">
        <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-brand-500/10 border border-brand-500/20 text-brand-400 text-xs font-semibold mb-4">
            <i data-lucide="scissors" class="size-3.5"></i>
            Free Tool
        </div>
        <h1 class="text-3xl md:text-4xl font-bold text-white mb-3">ClipForge</h1>
        <p class="text-neutral-400 text-base max-w-xl mx-auto">
            Extract clips from YouTube videos or local files. Download as MP4, WebM, GIF, or MP3.
        </p>
    </div>

    <!-- Source Tabs -->
    <div class="flex items-center justify-center gap-2 mb-6">
        <button class="cf-tab cf-tab-active" id="tab-youtube" onclick="switchTab('youtube')">
            <span class="flex items-center gap-2"><i data-lucide="youtube" class="size-4"></i> YouTube URL</span>
        </button>
        <button class="cf-tab cf-tab-inactive" id="tab-upload" onclick="switchTab('upload')">
            <span class="flex items-center gap-2"><i data-lucide="upload" class="size-4"></i> Upload File</span>
        </button>
    </div>

    <!-- YouTube Input -->
    <div id="panel-youtube" class="cf-card p-6 mb-6">
        <label class="block text-xs font-semibold text-neutral-400 uppercase tracking-wider mb-2">YouTube URL</label>
        <div class="flex gap-3">
            <input type="text" id="youtube-url" class="cf-input flex-1" placeholder="https://www.youtube.com/watch?v=..." />
            <button class="cf-btn cf-btn-primary" id="btn-fetch" onclick="fetchYouTube()">
                <i data-lucide="download" class="size-4"></i>
                Fetch
            </button>
        </div>
        <div id="youtube-status" class="mt-3 text-sm text-neutral-500 hidden"></div>
    </div>

    <!-- Upload Input -->
    <div id="panel-upload" class="cf-card p-6 mb-6 hidden">
        <div class="cf-drop-zone" id="drop-zone" onclick="document.getElementById('file-input').click()">
            <i data-lucide="upload-cloud" class="size-10 text-neutral-500 mx-auto mb-3"></i>
            <p class="text-sm text-neutral-300 font-medium mb-1">Drop your video here or click to browse</p>
            <p class="text-xs text-neutral-500">MP4, WebM, MOV, AVI, MKV &bull; Max 500MB</p>
        </div>
        <input type="file" id="file-input" class="hidden" accept="video/*" onchange="handleFileSelect(event)" />
        <div id="upload-progress" class="mt-4 hidden">
            <div class="flex items-center justify-between text-xs text-neutral-400 mb-2">
                <span id="upload-filename"></span>
                <span id="upload-percent">0%</span>
            </div>
            <div class="cf-progress-bar">
                <div class="cf-progress-fill" id="upload-progress-bar" style="width: 0%"></div>
            </div>
        </div>
    </div>

    <!-- Video Preview (hidden until video loaded) -->
    <div id="preview-section" class="cf-card p-6 mb-6 hidden cf-fade-in">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h3 class="text-white font-semibold text-sm" id="video-title">Video Title</h3>
                <p class="text-xs text-neutral-500 mt-0.5" id="video-meta">Duration: 0:00 &bull; Size: 0 MB</p>
            </div>
            <button class="cf-btn cf-btn-secondary text-xs py-1.5 px-3" onclick="resetAll()">
                <i data-lucide="x" class="size-3.5"></i> Clear
            </button>
        </div>
        <div class="cf-video-container">
            <video id="video-player" controls preload="metadata"></video>
        </div>
    </div>

    <!-- Clip Controls (hidden until video loaded) -->
    <div id="controls-section" class="cf-card p-6 mb-6 hidden cf-fade-in">
        <!-- Timestamps -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6">
            <div>
                <label class="block text-xs font-semibold text-neutral-400 uppercase tracking-wider mb-2">Start Time</label>
                <div class="flex items-center gap-2">
                    <input type="text" id="start-time" class="cf-input cf-time-input" value="0:00" placeholder="0:00" />
                    <button class="cf-btn cf-btn-secondary text-xs py-2 px-3" onclick="setCurrentTime('start')" title="Use current player time">
                        <i data-lucide="clock" class="size-3.5"></i>
                    </button>
                </div>
            </div>
            <div>
                <label class="block text-xs font-semibold text-neutral-400 uppercase tracking-wider mb-2">End Time</label>
                <div class="flex items-center gap-2">
                    <input type="text" id="end-time" class="cf-input cf-time-input" value="0:00" placeholder="0:00" />
                    <button class="cf-btn cf-btn-secondary text-xs py-2 px-3" onclick="setCurrentTime('end')" title="Use current player time">
                        <i data-lucide="clock" class="size-3.5"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- Format Selector -->
        <label class="block text-xs font-semibold text-neutral-400 uppercase tracking-wider mb-3">Output Format</label>
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-6">
            <div class="cf-format-card selected" data-format="mp4" onclick="selectFormat('mp4')">
                <div class="flex items-center gap-2">
                    <i data-lucide="film" class="size-4 text-brand-400"></i>
                    <span class="text-sm font-semibold text-white">MP4</span>
                </div>
                <p class="text-[11px] text-neutral-500 mt-1">H.264 video</p>
            </div>
            <div class="cf-format-card" data-format="webm" onclick="selectFormat('webm')">
                <div class="flex items-center gap-2">
                    <i data-lucide="file-video" class="size-4 text-blue-400"></i>
                    <span class="text-sm font-semibold text-white">WebM</span>
                </div>
                <p class="text-[11px] text-neutral-500 mt-1">VP9 video</p>
            </div>
            <div class="cf-format-card" data-format="gif" onclick="selectFormat('gif')">
                <div class="flex items-center gap-2">
                    <i data-lucide="image" class="size-4 text-purple-400"></i>
                    <span class="text-sm font-semibold text-white">GIF</span>
                </div>
                <p class="text-[11px] text-neutral-500 mt-1">Animated image</p>
            </div>
            <div class="cf-format-card" data-format="mp3" onclick="selectFormat('mp3')">
                <div class="flex items-center gap-2">
                    <i data-lucide="music" class="size-4 text-green-400"></i>
                    <span class="text-sm font-semibold text-white">MP3</span>
                </div>
                <p class="text-[11px] text-neutral-500 mt-1">Audio only</p>
            </div>
        </div>

        <!-- Extract Button -->
        <button class="cf-btn cf-btn-primary w-full" id="btn-extract" onclick="extractClip()">
            <i data-lucide="scissors" class="size-4"></i>
            Extract Clip
        </button>
    </div>

    <!-- Processing Indicator (hidden) -->
    <div id="processing-section" class="cf-card p-6 mb-6 hidden cf-fade-in">
        <div class="flex items-center gap-4">
            <div class="cf-spinner"></div>
            <div>
                <p class="text-sm font-semibold text-white" id="processing-text">Processing your clip...</p>
                <p class="text-xs text-neutral-500 mt-0.5">This may take a moment depending on the clip length and format.</p>
            </div>
        </div>
        <div class="cf-progress-bar mt-4">
            <div class="cf-progress-fill" id="processing-bar" style="width: 0%"></div>
        </div>
    </div>

    <!-- Result (hidden) -->
    <div id="result-section" class="cf-card p-6 mb-6 hidden cf-fade-in">
        <div class="flex items-center gap-3 mb-4">
            <div class="w-10 h-10 rounded-full bg-green-500/10 flex items-center justify-center">
                <i data-lucide="check-circle" class="size-5 text-green-500"></i>
            </div>
            <div>
                <p class="text-sm font-semibold text-white">Clip ready!</p>
                <p class="text-xs text-neutral-500" id="result-meta">MP4 &bull; 0 MB</p>
            </div>
        </div>
        <a id="result-download" href="#" class="cf-btn cf-btn-primary w-full" download>
            <i data-lucide="download" class="size-4"></i>
            Download Clip
        </a>
        <button class="cf-btn cf-btn-secondary w-full mt-3" onclick="extractAnother()">
            <i data-lucide="repeat" class="size-4"></i>
            Extract Another Clip
        </button>
    </div>

    <!-- SEO Content -->
    <div class="mt-16 space-y-8 text-sm text-neutral-400 leading-relaxed">
        <div>
            <h2 class="text-lg font-semibold text-white mb-3">How to Extract Video Clips</h2>
            <ol class="list-decimal list-inside space-y-2">
                <li>Paste a YouTube URL or upload a video file from your device.</li>
                <li>Preview the video and set your desired start and end timestamps.</li>
                <li>Choose your output format: MP4, WebM, GIF, or MP3.</li>
                <li>Click "Extract Clip" and download your file when it's ready.</li>
            </ol>
        </div>
        <div>
            <h2 class="text-lg font-semibold text-white mb-3">Supported Formats</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="cf-card p-4">
                    <h3 class="text-white font-medium mb-1">MP4 (H.264)</h3>
                    <p>The most widely compatible video format. Works on all devices and platforms. Perfect for sharing on social media.</p>
                </div>
                <div class="cf-card p-4">
                    <h3 class="text-white font-medium mb-1">WebM (VP9)</h3>
                    <p>Open-source format optimized for web. Excellent quality-to-size ratio. Ideal for embedding on websites.</p>
                </div>
                <div class="cf-card p-4">
                    <h3 class="text-white font-medium mb-1">GIF (Animated)</h3>
                    <p>Create animated GIFs from any video. Great for memes, reactions, and quick previews. Automatically optimized.</p>
                </div>
                <div class="cf-card p-4">
                    <h3 class="text-white font-medium mb-1">MP3 (Audio)</h3>
                    <p>Extract just the audio from any video. High-quality VBR encoding. Perfect for podcasts and music clips.</p>
                </div>
            </div>
        </div>
        <div>
            <h2 class="text-lg font-semibold text-white mb-3">Frequently Asked Questions</h2>
            <div class="space-y-4">
                <div>
                    <h3 class="text-white font-medium mb-1">Is ClipForge free to use?</h3>
                    <p>Yes, ClipForge is completely free. No signup or account required.</p>
                </div>
                <div>
                    <h3 class="text-white font-medium mb-1">What's the maximum clip length?</h3>
                    <p>You can extract clips up to 10 minutes long. For longer clips, consider downloading the full video first.</p>
                </div>
                <div>
                    <h3 class="text-white font-medium mb-1">What video formats can I upload?</h3>
                    <p>We support MP4, WebM, MOV, AVI, and MKV files up to 500MB.</p>
                </div>
                <div>
                    <h3 class="text-white font-medium mb-1">Are my files stored on your servers?</h3>
                    <p>Files are temporarily stored for processing and automatically deleted after 30 minutes.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Toast -->
<div id="toast" class="cf-toast"></div>
@endsection

@push('scripts')
<script>
(function() {
    const API = '{{ rtrim(config('app.url'), '/') }}/api/clipforge';

    let state = {
        tab: 'youtube',
        format: 'mp4',
        sourceFilename: null,
        videoDuration: 0,
    };

    // --- Tab Switching ---
    window.switchTab = function(tab) {
        state.tab = tab;
        document.getElementById('tab-youtube').className = 'cf-tab ' + (tab === 'youtube' ? 'cf-tab-active' : 'cf-tab-inactive');
        document.getElementById('tab-upload').className = 'cf-tab ' + (tab === 'upload' ? 'cf-tab-active' : 'cf-tab-inactive');
        document.getElementById('panel-youtube').classList.toggle('hidden', tab !== 'youtube');
        document.getElementById('panel-upload').classList.toggle('hidden', tab !== 'upload');
        lucide.createIcons();
    };

    // --- Format Selector ---
    window.selectFormat = function(format) {
        state.format = format;
        document.querySelectorAll('.cf-format-card').forEach(card => {
            card.classList.toggle('selected', card.dataset.format === format);
        });
    };

    // --- Timestamp Helpers ---
    function parseTimestamp(str) {
        str = str.trim();
        const parts = str.split(':').map(Number);
        if (parts.some(isNaN)) return NaN;
        if (parts.length === 3) return parts[0] * 3600 + parts[1] * 60 + parts[2];
        if (parts.length === 2) return parts[0] * 60 + parts[1];
        if (parts.length === 1) return parts[0];
        return NaN;
    }

    function formatTimestamp(seconds) {
        seconds = Math.floor(seconds);
        const m = Math.floor(seconds / 60);
        const s = seconds % 60;
        return m + ':' + String(s).padStart(2, '0');
    }

    window.setCurrentTime = function(which) {
        const player = document.getElementById('video-player');
        if (!player || !player.duration) return;
        const ts = formatTimestamp(player.currentTime);
        document.getElementById(which + '-time').value = ts;
    };

    // --- Toast ---
    function showToast(msg, type) {
        const el = document.getElementById('toast');
        el.textContent = msg;
        el.className = 'cf-toast cf-toast-' + type + ' show';
        setTimeout(() => { el.classList.remove('show'); }, 4000);
    }

    // --- Show/Hide Sections ---
    function showVideoLoaded(data) {
        state.sourceFilename = data.filename;
        state.videoDuration = data.duration;

        document.getElementById('video-title').textContent = data.title || 'Untitled';
        document.getElementById('video-meta').textContent =
            'Duration: ' + formatTimestamp(data.duration) + ' \u2022 Size: ' + (data.size / 1048576).toFixed(1) + ' MB';

        const player = document.getElementById('video-player');
        player.src = API + '/video/' + encodeURIComponent(data.filename);
        player.load();

        document.getElementById('start-time').value = '0:00';
        document.getElementById('end-time').value = formatTimestamp(data.duration);

        document.getElementById('preview-section').classList.remove('hidden');
        document.getElementById('controls-section').classList.remove('hidden');
        document.getElementById('result-section').classList.add('hidden');
        document.getElementById('processing-section').classList.add('hidden');

        lucide.createIcons();
    }

    // --- YouTube Fetch ---
    window.fetchYouTube = async function() {
        const url = document.getElementById('youtube-url').value.trim();
        if (!url) { showToast('Please enter a YouTube URL', 'error'); return; }

        const btn = document.getElementById('btn-fetch');
        const statusEl = document.getElementById('youtube-status');
        btn.disabled = true;
        btn.innerHTML = '<span class="cf-spinner"></span> Fetching...';
        statusEl.classList.remove('hidden');
        statusEl.textContent = 'Downloading video from YouTube... This may take a minute.';

        try {
            const res = await fetch(API + '/youtube', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify({ url }),
            });
            const data = await res.json();

            if (!res.ok || !data.success) {
                throw new Error(data.message || 'Failed to fetch video');
            }

            statusEl.classList.add('hidden');
            showVideoLoaded(data.video);
            showToast('Video loaded successfully', 'success');
        } catch (err) {
            showToast(err.message, 'error');
            statusEl.classList.add('hidden');
        } finally {
            btn.disabled = false;
            btn.innerHTML = '<i data-lucide="download" class="size-4"></i> Fetch';
            lucide.createIcons();
        }
    };

    // --- File Upload ---
    const dropZone = document.getElementById('drop-zone');
    if (dropZone) {
        ['dragenter', 'dragover'].forEach(evt => {
            dropZone.addEventListener(evt, e => { e.preventDefault(); dropZone.classList.add('dragover'); });
        });
        ['dragleave', 'drop'].forEach(evt => {
            dropZone.addEventListener(evt, e => { e.preventDefault(); dropZone.classList.remove('dragover'); });
        });
        dropZone.addEventListener('drop', e => {
            const files = e.dataTransfer.files;
            if (files.length > 0) uploadFile(files[0]);
        });
    }

    window.handleFileSelect = function(event) {
        const file = event.target.files[0];
        if (file) uploadFile(file);
    };

    async function uploadFile(file) {
        if (file.size > 500 * 1024 * 1024) {
            showToast('File too large. Maximum size is 500MB.', 'error');
            return;
        }

        const progressSection = document.getElementById('upload-progress');
        const progressBar = document.getElementById('upload-progress-bar');
        const percentEl = document.getElementById('upload-percent');
        const filenameEl = document.getElementById('upload-filename');

        progressSection.classList.remove('hidden');
        filenameEl.textContent = file.name;
        progressBar.style.width = '0%';
        percentEl.textContent = '0%';

        const formData = new FormData();
        formData.append('video', file);

        try {
            const data = await new Promise((resolve, reject) => {
                const xhr = new XMLHttpRequest();
                xhr.open('POST', API + '/upload');
                xhr.setRequestHeader('Accept', 'application/json');

                xhr.upload.onprogress = (e) => {
                    if (e.lengthComputable) {
                        const pct = Math.round((e.loaded / e.total) * 100);
                        progressBar.style.width = pct + '%';
                        percentEl.textContent = pct + '%';
                    }
                };

                xhr.onload = () => {
                    try {
                        const resp = JSON.parse(xhr.responseText);
                        if (xhr.status >= 200 && xhr.status < 300 && resp.success) {
                            resolve(resp.video);
                        } else {
                            reject(new Error(resp.message || 'Upload failed'));
                        }
                    } catch { reject(new Error('Failed to parse response')); }
                };

                xhr.onerror = () => reject(new Error('Network error'));
                xhr.send(formData);
            });

            progressSection.classList.add('hidden');
            showVideoLoaded(data);
            showToast('Video uploaded successfully', 'success');
        } catch (err) {
            showToast(err.message, 'error');
            progressSection.classList.add('hidden');
        }
    }

    // --- Extract Clip ---
    window.extractClip = async function() {
        if (!state.sourceFilename) { showToast('No video loaded', 'error'); return; }

        const startSec = parseTimestamp(document.getElementById('start-time').value);
        const endSec = parseTimestamp(document.getElementById('end-time').value);

        if (isNaN(startSec) || isNaN(endSec)) {
            showToast('Invalid timestamp format. Use M:SS or H:MM:SS', 'error');
            return;
        }
        if (endSec <= startSec) {
            showToast('End time must be after start time', 'error');
            return;
        }
        if (endSec - startSec > 600) {
            showToast('Clip cannot exceed 10 minutes', 'error');
            return;
        }

        const btn = document.getElementById('btn-extract');
        btn.disabled = true;
        btn.innerHTML = '<span class="cf-spinner"></span> Extracting...';

        document.getElementById('controls-section').classList.add('hidden');
        document.getElementById('processing-section').classList.remove('hidden');

        // Fake progress animation
        let progress = 0;
        const progressBar = document.getElementById('processing-bar');
        const progressInterval = setInterval(() => {
            progress = Math.min(progress + Math.random() * 8, 90);
            progressBar.style.width = progress + '%';
        }, 500);

        try {
            const res = await fetch(API + '/clip', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify({
                    source: state.sourceFilename,
                    start: startSec,
                    end: endSec,
                    format: state.format,
                }),
            });
            const data = await res.json();

            clearInterval(progressInterval);
            progressBar.style.width = '100%';

            if (!res.ok || !data.success) {
                throw new Error(data.message || 'Failed to extract clip');
            }

            // Show result
            setTimeout(() => {
                document.getElementById('processing-section').classList.add('hidden');
                document.getElementById('result-section').classList.remove('hidden');

                const clip = data.clip;
                document.getElementById('result-meta').textContent =
                    clip.format.toUpperCase() + ' \u2022 ' + (clip.size / 1048576).toFixed(1) + ' MB \u2022 ' + formatTimestamp(clip.duration);
                document.getElementById('result-download').href = API + '/download/' + encodeURIComponent(clip.filename);

                lucide.createIcons();
                showToast('Clip extracted successfully!', 'success');
            }, 500);
        } catch (err) {
            clearInterval(progressInterval);
            document.getElementById('processing-section').classList.add('hidden');
            document.getElementById('controls-section').classList.remove('hidden');
            showToast(err.message, 'error');
        } finally {
            btn.disabled = false;
            btn.innerHTML = '<i data-lucide="scissors" class="size-4"></i> Extract Clip';
            lucide.createIcons();
        }
    };

    // --- Extract Another ---
    window.extractAnother = function() {
        document.getElementById('result-section').classList.add('hidden');
        document.getElementById('controls-section').classList.remove('hidden');
        lucide.createIcons();
    };

    // --- Reset All ---
    window.resetAll = function() {
        state.sourceFilename = null;
        state.videoDuration = 0;

        const player = document.getElementById('video-player');
        player.pause();
        player.removeAttribute('src');
        player.load();

        document.getElementById('preview-section').classList.add('hidden');
        document.getElementById('controls-section').classList.add('hidden');
        document.getElementById('processing-section').classList.add('hidden');
        document.getElementById('result-section').classList.add('hidden');
        document.getElementById('youtube-url').value = '';
        document.getElementById('file-input').value = '';
    };
})();
</script>
@endpush
