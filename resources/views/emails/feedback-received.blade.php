<x-mail::message>
# New Feedback Received

A user has submitted feedback on ScreenSense.

**From:** {{ $user->name }} ({{ $user->email }})

**Title:** {{ $feedback->title }}

**Description:**
{{ $feedback->description }}

**Submitted:** {{ $feedback->created_at->format('M d, Y \a\t h:i A') }}

<x-mail::button :url="config('app.url') . '/admin/feedback'">
View in Admin
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
