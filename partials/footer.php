<?php if ( ! empty( $_SESSION['authenticated'] ) ) : ?>

<!-- ── CRM Assistant chat widget ─────────────────────────────────────────── -->
<div id="chat-widget">

    <!-- Floating button -->
    <button id="chat-toggle"
            aria-label="Open CRM Assistant"
            class="fixed bottom-5 right-5 z-50 w-13 h-13 bg-cyan-500 hover:bg-cyan-400 text-slate-900 rounded-full shadow-lg flex items-center justify-center transition-colors"
            style="width:3.25rem;height:3.25rem;">
        <svg id="chat-icon-open" xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/>
        </svg>
        <svg id="chat-icon-close" xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
        </svg>
    </button>

    <!-- Panel -->
    <div id="chat-panel"
         class="fixed bottom-20 right-5 z-50 hidden flex-col bg-slate-900 border border-slate-700 rounded-xl shadow-2xl"
         style="width:380px;height:520px;">

        <!-- Header -->
        <div class="flex items-center justify-between px-4 py-3 border-b border-slate-700 flex-shrink-0">
            <div class="flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-cyan-400"></span>
                <span class="text-sm font-semibold text-slate-100">CRM Assistant</span>
            </div>
            <button id="chat-clear" class="text-xs text-slate-500 hover:text-slate-300 transition-colors">Clear</button>
        </div>

        <!-- Messages -->
        <div id="chat-messages" class="flex-1 overflow-y-auto p-4 space-y-3 text-sm">
            <div class="text-xs text-slate-500 text-center">Ask me anything or tell me what to do — I can read and update your CRM.</div>
        </div>

        <!-- Thinking indicator -->
        <div id="chat-thinking" class="hidden px-4 pb-2 flex-shrink-0">
            <div class="flex items-center gap-2 text-xs text-slate-500">
                <span class="flex gap-0.5">
                    <span class="w-1.5 h-1.5 bg-slate-500 rounded-full animate-bounce" style="animation-delay:0ms"></span>
                    <span class="w-1.5 h-1.5 bg-slate-500 rounded-full animate-bounce" style="animation-delay:150ms"></span>
                    <span class="w-1.5 h-1.5 bg-slate-500 rounded-full animate-bounce" style="animation-delay:300ms"></span>
                </span>
                <span>Thinking...</span>
            </div>
        </div>

        <!-- Input -->
        <div class="flex gap-2 px-3 pb-3 pt-2 border-t border-slate-700 flex-shrink-0">
            <textarea id="chat-input"
                      rows="1"
                      placeholder="Ask or instruct..."
                      class="flex-1 bg-slate-800 border border-slate-600 rounded-lg px-3 py-2 text-sm text-slate-100 placeholder-slate-500 focus:outline-none focus:border-cyan-500 resize-none"
                      style="max-height:96px;overflow-y:auto;"></textarea>
            <button id="chat-send"
                    class="bg-cyan-500 hover:bg-cyan-400 disabled:opacity-40 disabled:cursor-not-allowed text-slate-900 font-medium px-3 py-2 rounded-lg text-sm transition-colors flex-shrink-0">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                </svg>
            </button>
        </div>
    </div>
</div>

<script>
(function () {
    var toggle    = document.getElementById('chat-toggle');
    var panel     = document.getElementById('chat-panel');
    var iconOpen  = document.getElementById('chat-icon-open');
    var iconClose = document.getElementById('chat-icon-close');
    var messages  = document.getElementById('chat-messages');
    var input     = document.getElementById('chat-input');
    var sendBtn   = document.getElementById('chat-send');
    var clearBtn  = document.getElementById('chat-clear');
    var thinking  = document.getElementById('chat-thinking');
    var baseUrl   = window.CSUITE ? window.CSUITE.baseUrl : '/';
    var busy      = false;

    // ── Toggle panel ──────────────────────────────────────────────────────────
    toggle.addEventListener('click', function () {
        var isOpen = !panel.classList.contains('hidden');
        panel.classList.toggle('hidden', isOpen);
        panel.classList.toggle('flex', !isOpen);
        iconOpen.classList.toggle('hidden', !isOpen);
        iconClose.classList.toggle('hidden', isOpen);
        if (!isOpen) {
            setTimeout(function () { input.focus(); }, 50);
        }
    });

    // ── Render a basic subset of markdown ────────────────────────────────────
    function renderMarkdown(text) {
        var escaped = text
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;');

        // Bold, italic, inline code
        escaped = escaped
            .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
            .replace(/\*(.*?)\*/g,     '<em>$1</em>')
            .replace(/`([^`]+)`/g,     '<code class="bg-slate-700 px-1 rounded text-xs font-mono">$1</code>');

        // Bullet lists — lines starting with - or •
        var lines = escaped.split('\n');
        var out   = [];
        var inList = false;
        lines.forEach(function (line) {
            var bullet = line.match(/^[\-•]\s+(.+)/);
            if (bullet) {
                if (!inList) { out.push('<ul class="list-disc list-inside space-y-0.5 my-1">'); inList = true; }
                out.push('<li>' + bullet[1] + '</li>');
            } else {
                if (inList) { out.push('</ul>'); inList = false; }
                out.push(line || '');
            }
        });
        if (inList) out.push('</ul>');

        return out.join('<br>').replace(/<br>(<\/?ul>|<\/?li>)/g, '$1').replace(/(<\/ul>)<br>/g, '$1');
    }

    // ── Append a message bubble ───────────────────────────────────────────────
    function appendMessage(role, text) {
        var div = document.createElement('div');
        if (role === 'user') {
            div.className = 'flex justify-end';
            div.innerHTML = '<div class="bg-cyan-500/20 border border-cyan-500/30 text-slate-100 rounded-xl rounded-tr-sm px-3 py-2 max-w-[85%] text-sm">' + renderMarkdown(text) + '</div>';
        } else {
            div.className = 'flex justify-start';
            div.innerHTML = '<div class="bg-slate-800 border border-slate-700 text-slate-200 rounded-xl rounded-tl-sm px-3 py-2 max-w-[85%] text-sm leading-relaxed">' + renderMarkdown(text) + '</div>';
        }
        messages.appendChild(div);
        messages.scrollTop = messages.scrollHeight;
    }

    function appendError(text) {
        var div = document.createElement('div');
        div.className = 'text-xs text-red-400 text-center py-1';
        div.textContent = text;
        messages.appendChild(div);
        messages.scrollTop = messages.scrollHeight;
    }

    // ── Send a message ────────────────────────────────────────────────────────
    async function sendMessage() {
        if (busy) return;
        var text = input.value.trim();
        if (!text) return;

        busy = true;
        input.value = '';
        input.style.height = 'auto';
        sendBtn.disabled = true;

        appendMessage('user', text);
        thinking.classList.remove('hidden');
        messages.scrollTop = messages.scrollHeight;

        try {
            var res  = await fetch(baseUrl + 'api/chat.php', {
                method:  'POST',
                headers: { 'Content-Type': 'application/json' },
                body:    JSON.stringify({ action: 'chat', message: text }),
            });
            var data = await res.json();

            thinking.classList.add('hidden');

            if (data.error) {
                appendError(data.error);
            } else if (data.reply) {
                appendMessage('assistant', data.reply);
            }
        } catch (err) {
            thinking.classList.add('hidden');
            appendError('Network error. Please try again.');
        }

        busy = false;
        sendBtn.disabled = false;
        input.focus();
    }

    sendBtn.addEventListener('click', sendMessage);

    input.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            sendMessage();
        }
    });

    // Auto-grow textarea
    input.addEventListener('input', function () {
        this.style.height = 'auto';
        this.style.height = Math.min(this.scrollHeight, 96) + 'px';
    });

    // ── Clear conversation ────────────────────────────────────────────────────
    clearBtn.addEventListener('click', async function () {
        await fetch(baseUrl + 'api/chat.php', {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify({ action: 'clear' }),
        });
        messages.innerHTML = '<div class="text-xs text-slate-500 text-center">Ask me anything or tell me what to do — I can read and update your CRM.</div>';
    });
})();
</script>

<?php endif; ?>
<script src="<?= BASE_URL ?>assets/js/app.js"></script>
</body>
</html>
