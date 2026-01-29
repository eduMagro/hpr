@props([
    'title' => '',
    'subtitle' => '',
    'icon' => null,
    'breadcrumb' => null,
])

<div class="page-header">
    <div class="page-header-content">
        @if($icon)
            <div class="page-header-icon">
                {!! $icon !!}
            </div>
        @endif
        <div class="page-header-text">
            <h1 class="page-header-title">{{ $title }}</h1>
            @if($subtitle)
                <p class="page-header-subtitle">{{ $subtitle }}</p>
            @endif
        </div>
    </div>
    @if($slot->isNotEmpty())
        <div class="page-header-actions">
            {{ $slot }}
        </div>
    @endif
</div>

<style>
    /* Modo claro (por defecto) */
    .page-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 1rem;
        padding: 1rem 1.5rem;
        background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
        border-bottom: 1px solid #e2e8f0;
        position: sticky;
        top: 0;
        z-index: 40;
    }

    .page-header-content {
        display: flex;
        align-items: center;
        gap: 0.875rem;
    }

    .page-header-icon {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 2.5rem;
        height: 2.5rem;
        background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
        border-radius: 0.5rem;
        color: white;
        box-shadow: 0 2px 8px rgba(59, 130, 246, 0.25);
    }

    .page-header-icon svg {
        width: 1.25rem;
        height: 1.25rem;
    }

    .page-header-text {
        display: flex;
        flex-direction: column;
        gap: 0.125rem;
    }

    .page-header-title {
        font-size: 1.125rem;
        font-weight: 600;
        color: #1e293b;
        margin: 0;
        line-height: 1.3;
        letter-spacing: -0.01em;
    }

    .page-header-subtitle {
        font-size: 0.8125rem;
        color: #64748b;
        margin: 0;
        line-height: 1.4;
    }

    .page-header-actions {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        flex-wrap: wrap;
    }

    /* Modo oscuro */
    :root.dark .page-header,
    .dark .page-header {
        background: linear-gradient(135deg, #1e293b 0%, #334155 100%);
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    }

    :root.dark .page-header-icon,
    .dark .page-header-icon {
        box-shadow: 0 2px 8px rgba(59, 130, 246, 0.4);
    }

    :root.dark .page-header-title,
    .dark .page-header-title {
        color: #f8fafc;
    }

    :root.dark .page-header-subtitle,
    .dark .page-header-subtitle {
        color: #94a3b8;
    }

    /* Responsive */
    @media (max-width: 640px) {
        .page-header {
            padding: 0.875rem 1rem;
        }

        .page-header-icon {
            width: 2.25rem;
            height: 2.25rem;
        }

        .page-header-icon svg {
            width: 1.125rem;
            height: 1.125rem;
        }

        .page-header-title {
            font-size: 1rem;
        }

        .page-header-subtitle {
            font-size: 0.75rem;
        }

        .page-header-actions {
            width: 100%;
            justify-content: flex-start;
        }
    }
</style>
