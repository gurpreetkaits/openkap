import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Chrome, Video, Sparkles, Zap } from 'lucide-react';

interface InstallExtensionModalProps {
  isOpen: boolean;
  onClose: () => void;
  onUseWebRecorder?: () => void;
}

const CHROME_EXTENSION_URL =
  'https://chromewebstore.google.com/detail/screensense/nnchnlkilgfemhpcohmgdpcmkjedjkfm';

export function InstallExtensionModal({
  isOpen,
  onClose,
  onUseWebRecorder,
}: InstallExtensionModalProps) {
  const handleInstallClick = () => {
    window.open(CHROME_EXTENSION_URL, '_blank');
  };

  return (
    <Dialog open={isOpen} onOpenChange={onClose}>
      <DialogContent className="sm:max-w-md">
        <DialogHeader className="text-center sm:text-center">
          <div className="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-gradient-to-br from-orange-500 to-red-500">
            <Video className="h-8 w-8 text-white" />
          </div>
          <DialogTitle className="text-xl">Install ScreenSense Extension</DialogTitle>
          <DialogDescription className="text-base">
            Get the best recording experience with our Chrome extension
          </DialogDescription>
        </DialogHeader>

        <div className="space-y-3 py-4">
          <div className="flex items-start gap-3 rounded-lg border p-3">
            <div className="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-blue-100">
              <Zap className="h-4 w-4 text-blue-600" />
            </div>
            <div>
              <p className="font-medium text-sm">Record from any tab</p>
              <p className="text-muted-foreground text-xs">
                Start recording instantly from any webpage
              </p>
            </div>
          </div>

          <div className="flex items-start gap-3 rounded-lg border p-3">
            <div className="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-purple-100">
              <Sparkles className="h-4 w-4 text-purple-600" />
            </div>
            <div>
              <p className="font-medium text-sm">Picture-in-picture camera</p>
              <p className="text-muted-foreground text-xs">
                Show your face while recording your screen
              </p>
            </div>
          </div>

          <div className="flex items-start gap-3 rounded-lg border p-3">
            <div className="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-green-100">
              <Chrome className="h-4 w-4 text-green-600" />
            </div>
            <div>
              <p className="font-medium text-sm">System audio capture</p>
              <p className="text-muted-foreground text-xs">
                Record tab audio along with your microphone
              </p>
            </div>
          </div>
        </div>

        <div className="flex flex-col gap-2">
          <Button
            onClick={handleInstallClick}
            className="w-full bg-gradient-to-r from-orange-500 to-red-500 hover:from-orange-600 hover:to-red-600"
          >
            <Chrome className="mr-2 h-4 w-4" />
            Add to Chrome - It's Free
          </Button>

          {onUseWebRecorder && (
            <Button variant="ghost" onClick={onUseWebRecorder} className="w-full">
              Use web recorder instead
            </Button>
          )}
        </div>

        <p className="text-center text-xs text-muted-foreground">
          After installing, refresh this page to start recording
        </p>
      </DialogContent>
    </Dialog>
  );
}
