import { useState, useEffect, useCallback } from 'react';

interface ExtensionStatus {
  installed: boolean;
  checking: boolean;
  version?: string;
}

declare global {
  interface Window {
    __screensenseExtensionInstalled?: boolean;
    __screensenseContentScriptLoaded?: boolean;
  }
}

export function useExtension(): ExtensionStatus {
  const [status, setStatus] = useState<ExtensionStatus>({
    installed: false,
    checking: true,
  });

  useEffect(() => {
    // Check 1: Window flag (fastest - set by content script)
    if (window.__screensenseExtensionInstalled || window.__screensenseContentScriptLoaded) {
      setStatus({
        installed: true,
        checking: false,
      });
      return;
    }

    // Check 2: DOM element marker (backup)
    const marker = document.getElementById('screensense-extension-installed');
    if (marker) {
      setStatus({
        installed: true,
        checking: false,
        version: marker.dataset.version,
      });
      return;
    }

    // Check 3: Listen for extension ready event (if extension loads after React)
    const handleExtensionReady = (e: CustomEvent) => {
      setStatus({
        installed: true,
        checking: false,
        version: e.detail?.version,
      });
    };

    window.addEventListener(
      'screensense:extension:ready',
      handleExtensionReady as EventListener
    );

    // Timeout - if no signal received, extension is not installed
    const timeout = setTimeout(() => {
      // Final check before marking as not installed
      if (window.__screensenseExtensionInstalled || window.__screensenseContentScriptLoaded) {
        setStatus({ installed: true, checking: false });
      } else {
        setStatus({ installed: false, checking: false });
      }
    }, 1500);

    return () => {
      window.removeEventListener(
        'screensense:extension:ready',
        handleExtensionReady as EventListener
      );
      clearTimeout(timeout);
    };
  }, []);

  return status;
}

// Hook to trigger recording via extension
export function useExtensionRecording() {
  const extension = useExtension();

  const startRecording = useCallback(() => {
    if (!extension.installed) {
      return false;
    }

    // Dispatch event for extension content script to catch
    window.dispatchEvent(new CustomEvent('screensense:website:showPanel'));
    return true;
  }, [extension.installed]);

  const sendCommand = useCallback((command: string, options?: Record<string, unknown>) => {
    if (!extension.installed) {
      return false;
    }

    window.dispatchEvent(
      new CustomEvent('screensense:website:command', {
        detail: { command, options },
      })
    );
    return true;
  }, [extension.installed]);

  return {
    ...extension,
    startRecording,
    sendCommand,
  };
}
