'use strict';

(function () {

    // ── Helpers ──────────────────────────────────────────────────────────────
    const baseUrl = (window.CSUITE && window.CSUITE.baseUrl) ? window.CSUITE.baseUrl : '/';

    // ── Flash message auto-dismiss ───────────────────────────────────────────
    document.querySelectorAll('.flash-message').forEach(function (el) {
        setTimeout(function () {
            el.style.transition = 'opacity 0.4s';
            el.style.opacity    = '0';
            setTimeout(function () { el.remove(); }, 400);
        }, 4000);
    });

    // ── Mobile sidebar toggle ────────────────────────────────────────────────
    var sidebar         = document.getElementById('sidebar');
    var sidebarOverlay  = document.getElementById('sidebar-overlay');
    var mobileToggle    = document.getElementById('mobile-menu-toggle');

    if (mobileToggle && sidebar && sidebarOverlay) {
        mobileToggle.addEventListener('click', function () {
            sidebar.classList.toggle('-translate-x-full');
            sidebarOverlay.classList.toggle('hidden');
        });
        sidebarOverlay.addEventListener('click', function () {
            sidebar.classList.add('-translate-x-full');
            sidebarOverlay.classList.add('hidden');
        });
    }

    // ── Agent tab switching ──────────────────────────────────────────────────
    var agentTabs = document.querySelectorAll('.agent-tab');

    agentTabs.forEach(function (tab) {
        tab.addEventListener('click', function () {
            var role = this.dataset.role;

            // Update tab styles
            agentTabs.forEach(function (t) {
                t.classList.remove('bg-slate-800', 'border-slate-700', 'border-b-slate-800', 'text-cyan-400');
                t.classList.add('text-slate-400');
            });
            this.classList.add('bg-slate-800', 'border-slate-700', 'border-b-slate-800', 'text-cyan-400');
            this.classList.remove('text-slate-400');

            // Show/hide panels
            document.querySelectorAll('.agent-panel').forEach(function (panel) {
                panel.classList.toggle('hidden', panel.dataset.role !== role);
            });

            // Update URL without reload
            var url = new URL(window.location.href);
            url.searchParams.set('role', role);
            window.history.replaceState({}, '', url.toString());
        });
    });

    // ── Mode chip selection ──────────────────────────────────────────────────
    var selectedModes = {};

    document.querySelectorAll('.mode-chip').forEach(function (chip) {
        chip.addEventListener('click', function () {
            var role = this.dataset.role;
            var mode = this.dataset.mode;

            // Deselect all chips in this role
            document.querySelectorAll('.mode-chip[data-role="' + role + '"]').forEach(function (c) {
                c.classList.remove('border-cyan-500', 'text-cyan-400', 'bg-cyan-500/10');
                c.classList.add('border-slate-600', 'text-slate-400');
            });

            if (selectedModes[role] === mode) {
                // Toggle off
                delete selectedModes[role];
            } else {
                // Select this chip
                selectedModes[role] = mode;
                this.classList.add('border-cyan-500', 'text-cyan-400', 'bg-cyan-500/10');
                this.classList.remove('border-slate-600', 'text-slate-400');
            }
        });
    });

    // ── Run agent ────────────────────────────────────────────────────────────
    document.querySelectorAll('.run-agent-btn').forEach(function (btn) {
        btn.addEventListener('click', async function () {
            var role       = this.dataset.role;
            var promptEl   = document.querySelector('.agent-prompt[data-role="' + role + '"]');
            var contactEl  = document.querySelector('.agent-contact[data-role="' + role + '"]');
            var providerEl = document.querySelector('.agent-provider[data-role="' + role + '"]:checked');
            var loadingEl  = document.querySelector('.agent-loading[data-role="' + role + '"]');
            var errorEl    = document.querySelector('.agent-error[data-role="' + role + '"]');
            var outputEl   = document.querySelector('.agent-output[data-role="' + role + '"]');
            var copyBtn    = document.querySelector('.copy-output-btn[data-role="' + role + '"]');
            var saveBtn    = document.querySelector('.save-task-btn[data-role="' + role + '"]');

            var prompt = promptEl ? promptEl.value.trim() : '';
            if (!prompt) {
                if (promptEl) promptEl.focus();
                return;
            }

            // Show loading
            btn.disabled = true;
            if (loadingEl) loadingEl.classList.remove('hidden');
            if (loadingEl) loadingEl.classList.add('flex');
            if (errorEl)   errorEl.classList.add('hidden');
            if (outputEl)  outputEl.textContent = '';
            if (copyBtn)   copyBtn.classList.add('hidden');
            if (saveBtn)   saveBtn.classList.add('hidden');

            var payload = {
                role:       role,
                mode:       selectedModes[role] || '',
                prompt:     prompt,
                contact_id: contactEl ? (parseInt(contactEl.value) || 0) : 0,
                provider:   providerEl ? providerEl.value : 'claude',
            };

            try {
                var res  = await fetch(baseUrl + 'api/agent.php', {
                    method:  'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body:    JSON.stringify(payload),
                });
                var data = await res.json();

                if (data.success && data.output) {
                    if (outputEl) {
                        outputEl.textContent = data.output;
                        outputEl.classList.remove('text-slate-400');
                        outputEl.classList.add('text-slate-100');
                    }
                    if (copyBtn) copyBtn.classList.remove('hidden');
                    if (saveBtn) saveBtn.classList.remove('hidden');
                    if (saveBtn) {
                        saveBtn.dataset.output    = data.output;
                        saveBtn.dataset.sessionId = data.session_id || '';
                    }
                } else {
                    if (errorEl) {
                        errorEl.textContent = data.error || 'Unknown error';
                        errorEl.classList.remove('hidden');
                    }
                }
            } catch (e) {
                if (errorEl) {
                    errorEl.textContent = 'Network error. Please try again.';
                    errorEl.classList.remove('hidden');
                }
            } finally {
                btn.disabled = false;
                if (loadingEl) loadingEl.classList.add('hidden');
                if (loadingEl) loadingEl.classList.remove('flex');
            }
        });
    });

    // ── Copy output ──────────────────────────────────────────────────────────
    document.querySelectorAll('.copy-output-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var role     = this.dataset.role;
            var outputEl = document.querySelector('.agent-output[data-role="' + role + '"]');
            if (!outputEl || !outputEl.textContent) return;

            navigator.clipboard.writeText(outputEl.textContent).then(function () {
                btn.textContent = '✓ Copied';
                setTimeout(function () { btn.textContent = btn.dataset.origLabel || 'Copy output'; }, 2000);
            }).catch(function () {
                // Fallback for older browsers
                var ta = document.createElement('textarea');
                ta.value = outputEl.textContent;
                ta.style.position = 'fixed';
                ta.style.opacity  = '0';
                document.body.appendChild(ta);
                ta.select();
                document.execCommand('copy');
                document.body.removeChild(ta);
                btn.textContent = '✓ Copied';
                setTimeout(function () { btn.textContent = btn.dataset.origLabel || 'Copy output'; }, 2000);
            });
        });
    });

    // Store original label for copy buttons
    document.querySelectorAll('.copy-output-btn').forEach(function (btn) {
        btn.dataset.origLabel = btn.textContent;
    });

    // ── Save as task ─────────────────────────────────────────────────────────
    document.querySelectorAll('.save-task-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var role     = this.dataset.role;
            var promptEl = document.querySelector('.agent-prompt[data-role="' + role + '"]');
            var title    = promptEl ? promptEl.value.substring(0, 80) : 'Agent task';
            var desc     = this.dataset.output || '';

            var url = baseUrl + 'index.php?page=tasks&action=add'
                + '&title='       + encodeURIComponent(title)
                + '&description=' + encodeURIComponent(desc.substring(0, 1000));
            window.location.href = url;
        });
    });

    // ── Session history toggle ────────────────────────────────────────────────
    document.querySelectorAll('.history-toggle').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var role    = this.dataset.role;
            var panel   = document.querySelector('.history-panel[data-role="' + role + '"]');
            var chevron = document.querySelector('.history-chevron[data-role="' + role + '"]');

            if (panel) {
                var isHidden = panel.style.display === 'none' || panel.dataset.collapsed === '1';
                panel.style.display       = isHidden ? '' : 'none';
                panel.dataset.collapsed   = isHidden ? '0' : '1';
                if (chevron) {
                    chevron.style.transform = isHidden ? 'rotate(0deg)' : 'rotate(-90deg)';
                }
            }
        });
    });

    // ── Load history item into prompt ─────────────────────────────────────────
    document.querySelectorAll('.history-item').forEach(function (item) {
        item.addEventListener('click', function () {
            var role     = this.dataset.role;
            var promptEl = document.querySelector('.agent-prompt[data-role="' + role + '"]');
            var outputEl = document.querySelector('.agent-output[data-role="' + role + '"]');
            var copyBtn  = document.querySelector('.copy-output-btn[data-role="' + role + '"]');

            if (promptEl) promptEl.value    = this.dataset.prompt || '';
            if (outputEl) {
                outputEl.textContent = this.dataset.output || '';
                outputEl.classList.remove('text-slate-400');
                outputEl.classList.add('text-slate-100');
            }
            if (copyBtn && this.dataset.output) copyBtn.classList.remove('hidden');
        });
    });

    // ── Dashboard checkpoint toggles ─────────────────────────────────────────
    document.querySelectorAll('.checkpoint-toggle').forEach(function (btn) {
        btn.addEventListener('click', async function () {
            var key = this.dataset.key;
            var toggle = this;

            try {
                var res  = await fetch(baseUrl + 'api/settings.php', {
                    method:  'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body:    JSON.stringify({ action: 'toggle_checkpoint', key: key }),
                });
                var data = await res.json();

                if (data.success !== undefined) {
                    var isOn     = data.value === 1;
                    var knob     = toggle.querySelector('span');
                    toggle.classList.toggle('bg-cyan-500', isOn);
                    toggle.classList.toggle('bg-slate-600', !isOn);
                    if (knob) {
                        knob.classList.toggle('translate-x-5', isOn);
                        knob.classList.toggle('translate-x-0', !isOn);
                    }
                }
            } catch (e) {
                // Silently fail — the toggle will revert on page reload
            }
        });
    });

    // ── Boardroom Debate Mode ────────────────────────────────────────────────
    var runBoardroomBtn = document.getElementById('run-boardroom-btn');
    if (runBoardroomBtn) {
        runBoardroomBtn.addEventListener('click', async function () {
            var btn      = this;
            var promptEl = document.getElementById('agent-prompt-Boardroom');
            var roleEl   = document.querySelector('.agent-role[data-role="Boardroom"]');
            var loading  = document.getElementById('boardroom-loading');
            var errorEl  = document.getElementById('boardroom-error');
            var outputs  = document.getElementById('boardroom-outputs');
            var statusList = document.getElementById('boardroom-status-list');

            var prompt = promptEl ? promptEl.value.trim() : '';
            var role   = roleEl ? roleEl.value : 'CEO';

            if (!prompt) {
                if (promptEl) promptEl.focus();
                return;
            }

            btn.disabled = true;
            if (loading) { loading.classList.remove('hidden'); loading.classList.add('flex'); }
            if (errorEl) errorEl.classList.add('hidden');
            if (outputs) {
                outputs.innerHTML = '';
                outputs.classList.remove('hidden');
            }

            var providers = window.CSUITE?.availableProviders || ['claude'];
            var results   = {};

            // Reset statuses
            providers.forEach(p => {
                var el = statusList.querySelector('[data-provider="' + p + '"]');
                if (el) el.textContent = p.charAt(0).toUpperCase() + p.slice(1) + ': Thinking...';
            });
            var synthStatus = statusList.querySelector('[data-provider="synthesis"]');
            if (synthStatus) synthStatus.textContent = 'Synthesis: Waiting';

            var fetchProvider = async (p) => {
                try {
                    var res = await fetch(baseUrl + 'api/agent.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ role: role, mode: 'debate', prompt: prompt, provider: p, contact_id: 0 })
                    });
                    var data = await res.json();
                    if (data.success && data.output) {
                        results[p] = data.output;
                        var el = statusList.querySelector('[data-provider="' + p + '"]');
                        if (el) el.textContent = p.charAt(0).toUpperCase() + p.slice(1) + ': Done ✓';
                        
                        // Append to UI
                        var div = document.createElement('div');
                        div.className = 'bg-slate-800 border border-slate-700 rounded-lg p-4';
                        div.innerHTML = '<h4 class="text-xs font-semibold text-slate-400 uppercase tracking-wide mb-2">' + p + '</h4>' +
                                        '<div class="text-sm text-slate-300 whitespace-pre-wrap">' + data.output.replace(/</g, '&lt;') + '</div>';
                        outputs.appendChild(div);
                    } else {
                        results[p] = 'Error: ' + (data.error || 'Failed');
                        var el = statusList.querySelector('[data-provider="' + p + '"]');
                        if (el) el.textContent = p.charAt(0).toUpperCase() + p.slice(1) + ': Failed ✗';
                    }
                } catch (e) {
                    results[p] = 'Error: Network failure';
                    var el = statusList.querySelector('[data-provider="' + p + '"]');
                    if (el) el.textContent = p.charAt(0).toUpperCase() + p.slice(1) + ': Failed ✗';
                }
            };

            await Promise.all(providers.map(p => fetchProvider(p)));

            // Synthesis step
            if (synthStatus) synthStatus.textContent = 'Synthesis: Thinking...';

            var synthPrompt = "Original problem:\n" + prompt + "\n\n";
            providers.forEach(p => {
                synthPrompt += "--- " + p.toUpperCase() + " PERSPECTIVE ---\n" + results[p] + "\n\n";
            });
            synthPrompt += "Synthesize these perspectives into the single best, comprehensive solution. Draw from the best ideas of each.";

            try {
                var res = await fetch(baseUrl + 'api/agent.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ role: role, mode: 'synthesis', prompt: synthPrompt, provider: 'claude', contact_id: 0 })
                });
                var data = await res.json();
                if (data.success && data.output) {
                    if (synthStatus) synthStatus.textContent = 'Synthesis: Done ✓';
                    var div = document.createElement('div');
                    div.className = 'bg-cyan-900/20 border border-cyan-800 rounded-lg p-4 mt-6';
                    div.innerHTML = '<h4 class="text-xs font-semibold text-cyan-400 uppercase tracking-wide mb-2">Final Synthesis (Claude)</h4>' +
                                    '<div class="text-sm text-slate-100 whitespace-pre-wrap">' + data.output.replace(/</g, '&lt;') + '</div>';
                    outputs.appendChild(div);
                } else {
                    if (synthStatus) synthStatus.textContent = 'Synthesis: Failed ✗';
                }
            } catch (e) {
                if (synthStatus) synthStatus.textContent = 'Synthesis: Failed ✗';
            }

            btn.disabled = false;
            if (loading) { loading.classList.add('hidden'); loading.classList.remove('flex'); }
        });
    }

})();
