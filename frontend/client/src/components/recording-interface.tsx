import { useState, useRef, useEffect } from 'react';
import { Video, Square, Pause, Play, Download, Monitor, Camera, Users, Mic, ZoomIn } from 'lucide-react';
import { useScreenRecording, type RecordingState } from '@/hooks/use-screen-recording';
import { RecordingOptions, downloadVideo, type RecordingType } from '@/lib/recording-utils';

export const RecordingInterface = () => {
  const {
    state,
    formattedTime,
    currentVideo,
    stream,
    startRecording,
    stopRecording,
    pauseRecording,
    resumeRecording,
    clearRecording,
    isSupported,
  } = useScreenRecording();

  const [options, setOptions] = useState<RecordingOptions>({
    recordingType: 'screen',
    includeAudio: true,
    showCursor: false,
    countdownTimer: false,
    zoomOnClick: true,
    audioDeviceId: undefined,
  });

  const [audioDevices, setAudioDevices] = useState<MediaDeviceInfo[]>([]);

  const previewVideoRef = useRef<HTMLVideoElement>(null);

  // Enumerate audio devices
  useEffect(() => {
    const getAudioDevices = async () => {
      try {
        const devices = await navigator.mediaDevices.enumerateDevices();
        const audioInputs = devices.filter(device => device.kind === 'audioinput');
        setAudioDevices(audioInputs);
      } catch (error) {
        console.error('Failed to enumerate audio devices:', error);
      }
    };

    getAudioDevices();

    // Listen for device changes
    navigator.mediaDevices.addEventListener('devicechange', getAudioDevices);
    return () => {
      navigator.mediaDevices.removeEventListener('devicechange', getAudioDevices);
    };
  }, []);

  // Setup preview stream
  useEffect(() => {
    if (previewVideoRef.current && stream) {
      previewVideoRef.current.srcObject = stream;
    }
  }, [stream]);

  const handleStartRecording = async () => {
    await startRecording(options);
  };

  const handleDownload = () => {
    if (currentVideo) {
      const filename = `recording-${new Date().toISOString().slice(0, 10)}.webm`;
      downloadVideo(currentVideo.blob, filename);
    }
  };

  const getStatusInfo = (): { color: string; text: string; pulse: boolean } => {
    switch (state) {
      case 'recording':
        return { color: 'bg-error', text: 'Recording', pulse: true };
      case 'paused':
        return { color: 'bg-warning', text: 'Paused', pulse: false };
      case 'completed':
        return { color: 'bg-success', text: 'Complete', pulse: false };
      default:
        return { color: 'bg-base-300', text: 'Ready to record', pulse: false };
    }
  };

  const statusInfo = getStatusInfo();

  if (!isSupported) {
    return (
      <div className="text-center py-12">
        <div className="card max-w-md mx-auto bg-base-100 shadow-xl">
          <div className="card-body">
            <Monitor className="w-16 h-16 mx-auto mb-4 text-base-content opacity-60" />
            <h2 className="text-xl font-semibold mb-2">Screen Recording Not Supported</h2>
            <p className="text-base-content opacity-60">
              Please use a modern browser like Chrome, Firefox, or Safari to record your screen.
            </p>
          </div>
        </div>
      </div>
    );
  }

  return (
    <div className="space-y-8">
      {/* Recording Status Header */}
      <div className="text-center">
        <div 
          className={`inline-flex items-center space-x-2 px-4 py-2 rounded-full bg-base-200 ${statusInfo.pulse ? 'recording-pulse' : ''}`}
          data-testid="recording-status"
        >
          <div 
            className={`w-3 h-3 rounded-full ${statusInfo.color} ${statusInfo.pulse ? 'recording-dot' : ''}`}
          />
          <span className="text-sm font-medium">{statusInfo.text}</span>
        </div>
        <div className="mt-2">
          <span 
            className="text-2xl font-mono text-base-content opacity-60" 
            data-testid="recording-timer"
          >
            {formattedTime}
          </span>
        </div>
      </div>

      {/* Main Recording Area */}
      <div className="card bg-base-100 shadow-xl overflow-hidden">
        {/* Preview Area */}
        <div className="relative bg-base-200 aspect-video flex items-center justify-center">
          {stream && state !== 'completed' ? (
            <video
              ref={previewVideoRef}
              autoPlay
              muted
              className="w-full h-full object-contain"
              data-testid="preview-video"
            />
          ) : currentVideo && state === 'completed' ? (
            <video
              src={currentVideo.url}
              controls
              className="w-full h-full object-contain"
              data-testid="recorded-video"
            />
          ) : (
            <div className="text-center" data-testid="preview-placeholder">
              <div className="w-16 h-16 mx-auto mb-4 rounded-full bg-base-300 flex items-center justify-center">
                <Monitor className="w-8 h-8 text-base-content opacity-60" />
              </div>
              <p className="text-base-content font-medium">
                Click "Start Recording" to begin
              </p>
              <p className="text-sm text-base-content opacity-60 mt-1">
                Your screen will appear here during recording
              </p>
            </div>
          )}
        </div>

        {/* Recording Controls */}
        <div className="card-body border-t border-base-300">
          <div className="flex items-center justify-center space-x-4">
            {/* Start/Stop Recording Button */}
            {state === 'idle' || state === 'completed' ? (
              <button
                onClick={state === 'idle' ? handleStartRecording : clearRecording}
                className="btn btn-primary px-6"
                data-testid="button-record"
              >
                <Video className="w-5 h-5 mr-2" />
                {state === 'completed' ? 'New Recording' : 'Start Recording'}
              </button>
            ) : (
              <button
                onClick={stopRecording}
                className="btn btn-error px-6"
                data-testid="button-stop"
              >
                <Square className="w-5 h-5 mr-2" />
                Stop Recording
              </button>
            )}

            {/* Pause/Resume Button */}
            {(state === 'recording' || state === 'paused') && (
              <button
                onClick={state === 'recording' ? pauseRecording : resumeRecording}
                className="btn btn-secondary px-4"
                data-testid="button-pause-resume"
              >
                {state === 'recording' ? (
                  <>
                    <Pause className="w-5 h-5 mr-2" />
                    Pause
                  </>
                ) : (
                  <>
                    <Play className="w-5 h-5 mr-2" />
                    Resume
                  </>
                )}
              </button>
            )}

            {/* Download Button */}
            {state === 'completed' && currentVideo && (
              <button
                onClick={handleDownload}
                className="btn btn-secondary px-4"
                data-testid="button-download"
              >
                <Download className="w-5 h-5 mr-2" />
                Download
              </button>
            )}
          </div>

          {/* Recording Options */}
          {state === 'idle' && (
            <div className="mt-6 space-y-4">
              {/* Recording Type Selection */}
              <div className="flex items-center justify-center">
                <div className="join">
                  <button
                    onClick={() => setOptions(prev => ({ ...prev, recordingType: 'screen' }))}
                    className={`join-item btn ${
                      options.recordingType === 'screen' 
                        ? 'btn-active btn-primary' 
                        : 'btn-outline'
                    }`}
                    data-testid="button-record-screen"
                  >
                    <Monitor className="w-4 h-4 mr-2" />
                    Screen
                  </button>
                  <button
                    onClick={() => setOptions(prev => ({ ...prev, recordingType: 'camera' }))}
                    className={`join-item btn ${
                      options.recordingType === 'camera' 
                        ? 'btn-active btn-primary' 
                        : 'btn-outline'
                    }`}
                    data-testid="button-record-camera"
                  >
                    <Camera className="w-4 h-4 mr-2" />
                    Camera
                  </button>
                  <button
                    onClick={() => setOptions(prev => ({ ...prev, recordingType: 'screen+camera' }))}
                    className={`join-item btn ${
                      options.recordingType === 'screen+camera' 
                        ? 'btn-active btn-primary' 
                        : 'btn-outline'
                    }`}
                    data-testid="button-record-both"
                  >
                    <Users className="w-4 h-4 mr-2" />
                    Both
                  </button>
                </div>
              </div>

              {/* Minimal Recording Options */}
              <div className="space-y-3 max-w-md mx-auto">
                {/* Microphone Toggle */}
                <div className="flex items-center justify-between p-3 bg-base-200 rounded-lg">
                  <div className="flex items-center space-x-2">
                    <Mic className="w-4 h-4 text-base-content opacity-70" />
                    <span className="text-sm font-medium">Microphone</span>
                  </div>
                  <input
                    type="checkbox"
                    className="toggle toggle-primary toggle-sm"
                    checked={options.includeAudio}
                    onChange={(e) =>
                      setOptions(prev => ({ ...prev, includeAudio: e.target.checked }))
                    }
                    data-testid="checkbox-audio"
                  />
                </div>

                {/* Audio Input Dropdown */}
                {options.includeAudio && audioDevices.length > 0 && (
                  <div className="flex items-center space-x-3 p-3 bg-base-200 rounded-lg">
                    <label className="text-sm font-medium whitespace-nowrap">Audio Input:</label>
                    <select
                      className="select select-sm select-bordered flex-1"
                      value={options.audioDeviceId || ''}
                      onChange={(e) =>
                        setOptions(prev => ({ ...prev, audioDeviceId: e.target.value || undefined }))
                      }
                      data-testid="select-audio-device"
                    >
                      <option value="">Default</option>
                      {audioDevices.map(device => (
                        <option key={device.deviceId} value={device.deviceId}>
                          {device.label || `Microphone ${device.deviceId.substring(0, 8)}`}
                        </option>
                      ))}
                    </select>
                  </div>
                )}

                {/* Zoom on Click Toggle */}
                <div className="flex items-center justify-between p-3 bg-orange-50 dark:bg-orange-950/20 rounded-lg border border-orange-200 dark:border-orange-900">
                  <div className="flex items-center space-x-2">
                    <ZoomIn className="w-4 h-4 text-orange-600 dark:text-orange-400" />
                    <div>
                      <div className="text-sm font-medium">Zoom on Click</div>
                      <div className="text-xs text-base-content opacity-60">Auto-zoom when you click</div>
                    </div>
                  </div>
                  <input
                    type="checkbox"
                    className="toggle toggle-warning toggle-sm"
                    checked={options.zoomOnClick ?? true}
                    onChange={(e) =>
                      setOptions(prev => ({ ...prev, zoomOnClick: e.target.checked }))
                    }
                    data-testid="checkbox-zoom"
                  />
                </div>
              </div>
            </div>
          )}
        </div>
      </div>
    </div>
  );
};
