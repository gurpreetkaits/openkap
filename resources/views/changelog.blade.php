@extends('layouts.app')

@section('title', 'Changelog | ScreenSense')
@section('meta_description', 'See what\'s new in ScreenSense. Track our latest updates, features, and improvements week by week.')

@push('styles')
<style>
    .page-hero {
        padding: 4rem 0 2rem;
        text-align: center;
        border-bottom: 1px solid rgba(255, 255, 255, 0.06);
    }

    .page-hero h1 {
        font-size: 3rem;
        font-weight: 700;
        color: #ffffff;
        margin-bottom: 1rem;
    }

    .page-hero p {
        font-size: 1.25rem;
        color: rgba(255, 255, 255, 0.6);
        max-width: 600px;
        margin: 0 auto;
    }

    .changelog-container {
        display: flex;
        max-width: 1100px;
        margin: 0 auto;
        padding: 2rem 1.5rem;
        gap: 3rem;
    }

    /* Sidebar */
    .changelog-sidebar {
        width: 220px;
        flex-shrink: 0;
        position: sticky;
        top: 5rem;
        height: fit-content;
        max-height: calc(100vh - 8rem);
        overflow-y: auto;
    }

    .sidebar-title {
        font-size: 0.7rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.1em;
        color: rgba(255, 255, 255, 0.4);
        margin-bottom: 1rem;
        padding-left: 0.75rem;
    }

    .week-list {
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .week-item {
        margin-bottom: 0.125rem;
    }

    .week-link {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 0.625rem 0.75rem;
        border-radius: 8px;
        color: rgba(255, 255, 255, 0.6);
        text-decoration: none;
        font-size: 0.875rem;
        transition: all 0.2s;
        border-left: 2px solid transparent;
    }

    .week-link:hover {
        background: rgba(255, 255, 255, 0.03);
        color: rgba(255, 255, 255, 0.9);
    }

    .week-link.active {
        background: rgba(234, 88, 12, 0.08);
        color: #ea580c;
        border-left-color: #ea580c;
        font-weight: 500;
    }

    .week-indicator {
        display: flex;
        gap: 0.25rem;
    }

    .week-dot {
        width: 6px;
        height: 6px;
        border-radius: 50%;
        opacity: 0.6;
    }

    .week-dot.feature {
        background: #22c55e;
    }

    .week-dot.fix {
        background: #ea580c;
    }

    .week-link.active .week-dot {
        opacity: 1;
    }

    /* Main Content */
    .changelog-content {
        flex: 1;
        min-width: 0;
    }

    .week-header {
        margin-bottom: 2.5rem;
    }

    .week-label {
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.1em;
        color: #ea580c;
        margin-bottom: 0.5rem;
    }

    .week-header h2 {
        font-size: 1.75rem;
        font-weight: 700;
        color: #ffffff;
        margin-bottom: 0.5rem;
        line-height: 1.3;
    }

    .week-date-range {
        font-size: 0.9rem;
        color: rgba(255, 255, 255, 0.5);
    }

    /* Change Sections */
    .change-section {
        margin-bottom: 2.5rem;
    }

    .change-section:last-child {
        margin-bottom: 0;
    }

    .section-header {
        display: flex;
        align-items: center;
        gap: 0.625rem;
        margin-bottom: 1rem;
    }

    .section-icon {
        width: 28px;
        height: 28px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .section-icon i {
        width: 14px;
        height: 14px;
    }

    .section-icon.features {
        background: rgba(34, 197, 94, 0.15);
        color: #22c55e;
    }

    .section-icon.improvements {
        background: rgba(59, 130, 246, 0.15);
        color: #3b82f6;
    }

    .section-icon.fixes {
        background: rgba(234, 88, 12, 0.15);
        color: #ea580c;
    }

    .section-icon.other {
        background: rgba(255, 255, 255, 0.08);
        color: rgba(255, 255, 255, 0.6);
    }

    .section-title {
        font-size: 1rem;
        font-weight: 600;
        color: #ffffff;
    }

    .change-list {
        list-style: none;
        padding: 0;
        margin: 0;
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    }

    .change-item {
        display: flex;
        align-items: flex-start;
        gap: 0.875rem;
        padding: 0.875rem 1rem;
        background: rgba(255, 255, 255, 0.02);
        border: 1px solid rgba(255, 255, 255, 0.04);
        border-radius: 10px;
        transition: all 0.2s;
    }

    .change-item:hover {
        background: rgba(255, 255, 255, 0.04);
        border-color: rgba(255, 255, 255, 0.08);
    }

    .change-bullet {
        width: 6px;
        height: 6px;
        border-radius: 50%;
        margin-top: 0.5rem;
        flex-shrink: 0;
    }

    .change-bullet.features {
        background: #22c55e;
    }

    .change-bullet.improvements {
        background: #3b82f6;
    }

    .change-bullet.fixes {
        background: #ea580c;
    }

    .change-bullet.other {
        background: rgba(255, 255, 255, 0.4);
    }

    .change-text {
        flex: 1;
        font-size: 0.925rem;
        color: rgba(255, 255, 255, 0.85);
        line-height: 1.6;
    }

    .empty-state {
        text-align: center;
        padding: 4rem 2rem;
        color: rgba(255, 255, 255, 0.4);
    }

    .empty-state i {
        width: 48px;
        height: 48px;
        margin-bottom: 1rem;
        opacity: 0.4;
    }

    .empty-state p {
        font-size: 1rem;
    }

    .empty-week {
        text-align: center;
        padding: 3rem 2rem;
        background: rgba(255, 255, 255, 0.02);
        border: 1px dashed rgba(255, 255, 255, 0.1);
        border-radius: 12px;
        color: rgba(255, 255, 255, 0.4);
    }

    .empty-week p {
        margin: 0;
    }

    /* Mobile */
    @media (max-width: 768px) {
        .page-hero h1 {
            font-size: 2rem;
        }

        .page-hero p {
            font-size: 1rem;
        }

        .changelog-container {
            flex-direction: column;
            padding: 1.5rem 1rem;
            gap: 1.5rem;
        }

        .changelog-sidebar {
            width: 100%;
            position: relative;
            top: 0;
            max-height: none;
            border-bottom: 1px solid rgba(255, 255, 255, 0.06);
            padding-bottom: 1.5rem;
        }

        .week-list {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
        }

        .week-item {
            margin-bottom: 0;
        }

        .week-link {
            padding: 0.5rem 0.875rem;
            font-size: 0.8rem;
            border-left: none;
            border-radius: 9999px;
            background: rgba(255, 255, 255, 0.03);
        }

        .week-link.active {
            background: rgba(234, 88, 12, 0.15);
        }

        .week-header h2 {
            font-size: 1.5rem;
        }

        .change-item {
            padding: 0.75rem;
        }
    }
</style>
@endpush

@section('content')
    <!-- Hero -->
    <div class="page-hero">
        <div class="container">
            <h1>Changelog</h1>
            <p>See what we've been building, week by week.</p>
        </div>
    </div>

    <!-- Content -->
    <div class="changelog-container">
        <!-- Sidebar -->
        <aside class="changelog-sidebar">
            <div class="sidebar-title">Timeline</div>
            <ul class="week-list">
                @forelse($weeklyChangelogs as $week)
                    <li class="week-item">
                        <a href="?week={{ $week['week_start']->format('Y-m-d') }}"
                           class="week-link {{ $selectedWeek === $week['week_start']->format('Y-m-d') ? 'active' : '' }}">
                            <span>{{ $week['label'] }}</span>
                            <span class="week-indicator">
                                @if($week['changes']['features']->isNotEmpty())
                                    <span class="week-dot feature" title="New features"></span>
                                @endif
                                @if($week['changes']['fixes']->isNotEmpty())
                                    <span class="week-dot fix" title="Bug fixes"></span>
                                @endif
                            </span>
                        </a>
                    </li>
                @empty
                    <li class="week-item">
                        <span class="week-link">No updates yet</span>
                    </li>
                @endforelse
            </ul>
        </aside>

        <!-- Main Content -->
        <main class="changelog-content">
            @if($currentWeekData)
                <div class="week-header">
                    <div class="week-label">{{ $currentWeekData['label'] }}</div>
                    <h2>{{ $currentWeekData['summary'] }}</h2>
                    <div class="week-date-range">
                        {{ $currentWeekData['week_start']->format('F j') }} – {{ $currentWeekData['week_end']->format('F j, Y') }}
                    </div>
                </div>

                @if($currentWeekData['changes']['features']->isNotEmpty())
                    <div class="change-section">
                        <div class="section-header">
                            <div class="section-icon features">
                                <i data-lucide="sparkles"></i>
                            </div>
                            <h3 class="section-title">New Features</h3>
                        </div>
                        <ul class="change-list">
                            @foreach($currentWeekData['changes']['features'] as $change)
                                <li class="change-item">
                                    <span class="change-bullet features"></span>
                                    <span class="change-text">{{ $change['text'] }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if($currentWeekData['changes']['improvements']->isNotEmpty())
                    <div class="change-section">
                        <div class="section-header">
                            <div class="section-icon improvements">
                                <i data-lucide="trending-up"></i>
                            </div>
                            <h3 class="section-title">Improvements</h3>
                        </div>
                        <ul class="change-list">
                            @foreach($currentWeekData['changes']['improvements'] as $change)
                                <li class="change-item">
                                    <span class="change-bullet improvements"></span>
                                    <span class="change-text">{{ $change['text'] }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if($currentWeekData['changes']['fixes']->isNotEmpty())
                    <div class="change-section">
                        <div class="section-header">
                            <div class="section-icon fixes">
                                <i data-lucide="bug"></i>
                            </div>
                            <h3 class="section-title">Bug Fixes</h3>
                        </div>
                        <ul class="change-list">
                            @foreach($currentWeekData['changes']['fixes'] as $change)
                                <li class="change-item">
                                    <span class="change-bullet fixes"></span>
                                    <span class="change-text">{{ $change['text'] }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if($currentWeekData['changes']['other']->isNotEmpty())
                    <div class="change-section">
                        <div class="section-header">
                            <div class="section-icon other">
                                <i data-lucide="package"></i>
                            </div>
                            <h3 class="section-title">Other Changes</h3>
                        </div>
                        <ul class="change-list">
                            @foreach($currentWeekData['changes']['other'] as $change)
                                <li class="change-item">
                                    <span class="change-bullet other"></span>
                                    <span class="change-text">{{ $change['text'] }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if($currentWeekData['changes']['features']->isEmpty() &&
                    $currentWeekData['changes']['improvements']->isEmpty() &&
                    $currentWeekData['changes']['fixes']->isEmpty() &&
                    $currentWeekData['changes']['other']->isEmpty())
                    <div class="empty-week">
                        <p>No notable changes this week.</p>
                    </div>
                @endif
            @else
                <div class="empty-state">
                    <i data-lucide="inbox"></i>
                    <p>No changelog entries found.</p>
                </div>
            @endif
        </main>
    </div>
@endsection
