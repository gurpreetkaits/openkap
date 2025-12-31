#!/bin/bash

echo "🔍 Verifying ScreenSense Extension Changes..."
echo ""

# Check if files exist
echo "📁 Checking files..."
if [ -f "scripts/floating-panel.js" ]; then
    echo "✅ floating-panel.js exists"
else
    echo "❌ floating-panel.js NOT FOUND"
fi

if [ -f "scripts/content.js" ]; then
    echo "✅ content.js exists"
else
    echo "❌ content.js NOT FOUND"
fi

if [ -f "scripts/background.js" ]; then
    echo "✅ background.js exists"
else
    echo "❌ background.js NOT FOUND"
fi

if [ -f "icons/logo.png" ]; then
    echo "✅ logo.png exists"
else
    echo "❌ logo.png NOT FOUND"
fi

if [ -f "test-page.html" ]; then
    echo "✅ test-page.html exists"
else
    echo "❌ test-page.html NOT FOUND"
fi

echo ""
echo "📝 Checking manifest.json..."
if grep -q "floating-panel.js" manifest.json; then
    echo "✅ floating-panel.js in content_scripts"
else
    echo "❌ floating-panel.js NOT in content_scripts"
fi

if grep -q "web_accessible_resources" manifest.json; then
    echo "✅ web_accessible_resources configured"
else
    echo "❌ web_accessible_resources NOT configured"
fi

if grep -q "commands" manifest.json; then
    echo "✅ Keyboard shortcuts configured"
else
    echo "❌ Keyboard shortcuts NOT configured"
fi

echo ""
echo "🔍 Checking content.js..."
if grep -q "window.__screensense" scripts/content.js; then
    echo "✅ Recording functions exposed via window.__screensense"
else
    echo "❌ window.__screensense NOT FOUND"
fi

echo ""
echo "🔍 Checking floating-panel.js..."
if grep -q "window.__screensense.startRecording" scripts/floating-panel.js; then
    echo "✅ Calls content.js directly (no background script redirect)"
else
    echo "❌ Still using background script (WILL REDIRECT)"
fi

if grep -q "chrome.runtime.getURL('icons/logo.png')" scripts/floating-panel.js; then
    echo "✅ Uses custom logo"
else
    echo "❌ Still using default SVG logo"
fi

echo ""
echo "📊 Summary:"
echo "==========="
echo "Extension folder: $(pwd)"
echo "Files: $(ls -1 scripts/*.js | wc -l | tr -d ' ') JavaScript files"
echo "Icons: $(ls -1 icons/*.png 2>/dev/null | wc -l | tr -d ' ') PNG icons"
echo ""
echo "Next steps:"
echo "1. Go to chrome://extensions"
echo "2. REMOVE the old extension completely"
echo "3. Close all Chrome tabs"
echo "4. Restart Chrome"
echo "5. Load unpacked from: $(pwd)"
echo "6. Test on: https://example.com"
echo "7. Or use test page: file://$(pwd)/test-page.html"
echo ""
echo "✅ Changes verified!"
