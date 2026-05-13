# csuite-crm

An open-source, self-hosted CRM and AI agent command centre for businesses of any size.

## What it does

- **CRM assistant** — a floating chat widget on every page. Tell it what to do in plain English: *"Add Acme Ltd as a warm prospect from LinkedIn and create a follow-up task for Friday"*. It calls Claude, which reads and writes your live database using tool use.
- **Pipeline board** — drag-and-drop Kanban board (Lead → Qualified → Proposal → Negotiation → Won → Lost) with live saves and an Unassigned column for contacts not yet staged
- **Six C-suite AI agents** (CEO, CTO, CFO, CMO, CPO, COO) — role-specific modes for strategy, revenue, code review, LinkedIn posts, roadmap prioritisation, and more
- **Boardroom Debate mode** — when multiple API keys are configured, all providers respond to your prompt in parallel and Claude synthesises the best combined answer
- **Multi-provider AI** — Anthropic Claude, Google Gemini, and Perplexity all supported; keys managed in Settings and tested without leaving the page
- **Streaming responses** — the CRM assistant streams tokens word by word using Server-Sent Events; tool calls show as live status labels as they execute
- **Audit log** — every write action taken by the assistant is logged to the database and shown on the dashboard so you always know what it changed
- **CRM** — contacts with status and pipeline stage, notes, tasks, and agent session history all linked to each record
- **Sprint tracker** — dashboard with sprint week progress bar, checkpoint date, and three configurable signal criteria
- **Fully yours** — all data lives in your MySQL database on your own server; no telemetry, no third-party scripts

## Why self-hosted

Your data stays on your server. No SaaS vendor holds your business context, your pipeline, or your AI conversations. GDPR compliance is architectural — hard delete for erasure requests, no outbound calls except the AI API requests you explicitly make.

## Requirements

- PHP 8.x (shared hosting, VPS, Laragon, XAMPP, MAMP)
- MySQL 8.x
- At least one AI API key:
  - Anthropic — [console.anthropic.com](https://console.anthropic.com) *(required for the CRM assistant and Boardroom synthesis)*
  - Google Gemini — [aistudio.google.com](https://aistudio.google.com/app/apikey) *(optional)*
  - Perplexity — [perplexity.ai/settings/api](https://www.perplexity.ai/settings/api) *(optional)*
- Tailwind CSS standalone binary (for CSS compilation — pre-built `output.css` included)

## Quick start

See [SETUP.md](SETUP.md) for the full walkthrough. Short version:

1. Create MySQL database `csuite_crm` and run `sql/schema.sql`
2. Copy `.env.example` → `.env`, add your DB credentials (and optionally your Anthropic key)
3. Copy `config/company.example.php` → `config/company.php`, fill in your business details
4. Copy `config/auth.example.php` → `config/auth.php`, generate a bcrypt hash and paste it in
5. Build CSS: `./tailwindcss -i assets/css/input.css -o assets/css/output.css --minify`
6. Point a vhost at the project root and visit the URL
7. Add your API keys in **Settings → API Keys**

## Usage

**1. Set your company context**

Open `config/company.php` and fill in your business details — name, product, market, stage, challenges, competitors. This is injected automatically into every AI call, making output relevant to your business rather than generic. Keep it updated as your situation changes.

**2. Use the CRM assistant**

Click the orange chat button in the bottom-right corner of any page. Type in plain English:

- *"Show me all my warm prospects"*
- *"Add Harbour Creative as an active customer, contact is Steve, steve@harbour.co"*
- *"Move Acme to the negotiation stage and create a high-priority task to send a contract by Thursday"*
- *"Run a CFO agent and ask it to review our pricing model"*
- *"What does my pipeline look like?"*

The assistant calls your live database directly — it doesn't just describe actions, it performs them.

**3. Manage the pipeline**

Go to **Pipeline** to see all contacts on a Kanban board. Drag cards between stages — changes save instantly. Contacts with free-text stages appear in the Unassigned column until moved to a canonical stage.

**4. Run an AI agent**

Go to **Agents**, pick a role (CEO, CTO, CFO, CMO, CPO, COO), select a provider from the dropdown, choose a mode chip (e.g. "Strategy decision", "Revenue forecast"), paste your context, and click **Run agent**. Copy the output or save it as a task directly from the panel.

If two or more providers are configured, a **Boardroom** tab appears — all providers respond in parallel, then Claude synthesises the best combined answer.

**5. Manage contacts, notes, and tasks**

Contacts have status, pipeline stage, linked notes, linked tasks, and a full agent session history. Tasks set from agent output carry the session link automatically. The dashboard surfaces open tasks sorted by priority and recent agent sessions.

**6. Track your sprint**

The dashboard shows your sprint week progress bar, checkpoint date, and three signal criteria (Inbound, Product, Energy) you toggle as you hit them. Update sprint settings in **Settings**.

## Language support

English and Spanish are included. Adding a new language takes one file and two lines of config. See [lang/README.md](lang/README.md).

## GDPR

csuite-crm is designed for UK and EU operators. See [GDPR.md](GDPR.md) for a plain-language guide to your obligations as the data controller.

## Licence

MIT — see [LICENSE](LICENSE).

## Contributing

Pull requests welcome. See [CONTRIBUTING.md](CONTRIBUTING.md).

## Built by

Paul Jacobs / MonakTech — [pauljacobs.dev](https://pauljacobs.dev)
