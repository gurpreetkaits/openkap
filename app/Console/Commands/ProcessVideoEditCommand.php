<?php

namespace App\Console\Commands;

use App\Jobs\ApplyVideoEditsJob;
use App\Models\VideoEdit;
use Illuminate\Console\Command;

class ProcessVideoEditCommand extends Command
{
    protected $signature = 'video:process-edit {editId}';

    protected $description = 'Process a video edit job synchronously in a background process';

    public function handle(): int
    {
        $edit = VideoEdit::find($this->argument('editId'));

        if (! $edit) {
            $this->error('Edit not found');

            return 1;
        }

        $this->info("Processing edit #{$edit->id} for video #{$edit->video_id}...");

        $job = new ApplyVideoEditsJob($edit);
        $job->handle();

        $edit->refresh();
        $this->info("Done. Status: {$edit->status}");

        return $edit->status === 'completed' ? 0 : 1;
    }
}
