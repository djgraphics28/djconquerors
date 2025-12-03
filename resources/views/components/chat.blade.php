  {{-- <!-- Floating Chat Button -->
<style>
    .chat-fab {
        position: fixed;
        right: 24px;
        bottom: 24px;
        z-index: 60
    }

    .chat-fab a {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: auto;
        padding: 0 24px 0 20px;
        height: 64px;
        border-radius: 9999px;
        background: linear-gradient(135deg, #2563eb, #3b82f6);
        color: white;
        box-shadow: 0 10px 20px rgba(37, 99, 235, 0.2);
        transition: transform .18s ease, box-shadow .18s ease;
        text-decoration: none;
        gap: 12px;
    }

    .chat-fab a:hover {
        transform: translateY(-6px);
        box-shadow: 0 18px 30px rgba(37, 99, 235, 0.25);
        background: linear-gradient(135deg, #1d4ed8, #2563eb);
    }

    .chat-pulse {
        position: absolute;
        inset: 0;
        border-radius: 9999px;
        background: radial-gradient(circle at 30% 30%, rgba(219, 234, 254, 0.15), transparent 40%);
        animation: chat-pulse 2s infinite
    }

    @keyframes chat-pulse {
        0% {
            transform: scale(1);
            opacity: 1
        }

        50% {
            transform: scale(1.25);
            opacity: .6
        }

        100% {
            transform: scale(1.6);
            opacity: 0
        }
    }

    .chat-icon {
        width: 28px;
        height: 28px
    }
</style>
<div class="chat-fab">
    <a href="/chatify" aria-label="Open chat">
        <span class="chat-pulse" aria-hidden></span>
        <svg class="chat-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M21 15a2 2 0 0 1-2 2H8l-5 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v10z" fill="white" />
        </svg>
        <span>DJ Messenger</span>
    </a>
</div> --}}
