# csuite-crm

An open-source, self-hosted CRM and AI agent command centre for businesses of any size.

## What it does

- **Six C-suite AI agents** (CEO, CTO, CFO, CMO, CPO, COO) — paste a context prompt, get expert-level strategic, technical, financial, marketing, product, or operational output
- **CRM** — manage contacts through a full pipeline with notes, tasks, and agent session history linked to each record
- **Sprint tracker** — dashboard with sprint week progress, checkpoint date, and three configurable signal criteria
- **Fully yours** — all data lives in your MySQL database on your own server; the only outbound call is the Anthropic API request you explicitly make

## Why self-hosted

Your data stays on your server. No SaaS vendor holds your business context, your pipeline, or your agent conversations. GDPR compliance is architectural — no telemetry, no third-party scripts, hard delete for erasure requests.

## Requirements

- PHP 8.x (shared hosting, VPS, Laragon, XAMPP, MAMP)
- MySQL 8.x
- Anthropic API key — [console.anthropic.com](https://console.anthropic.com)
- Tailwind CSS standalone binary (for CSS compilation — pre-built `output.css` included on first install)

## Quick start

See [SETUP.md](SETUP.md) for the full walkthrough. Short version:

1. Create MySQL database `csuite_crm` and run `sql/schema.sql`
2. Copy `.env.example` → `.env`, add your API key and DB credentials
3. Copy `config/company.example.php` → `config/company.php`, fill in your business details
4. Copy `config/auth.example.php` → `config/auth.php`, generate a bcrypt hash and paste it in
5. Build CSS: `./tailwindcss -i assets/css/input.css -o assets/css/output.css --minify`
6. Point a vhost at the project root and visit the URL

## Usage

**1. Set your company context**

Open `config/company.php` and fill in your business details — name, product, market, stage, challenges, competitors, and so on. This context is injected automatically into every agent call. It's what makes the output relevant to your business rather than generic. Keep it updated as your situation changes.

**2. Add contacts**

Go to **Contacts → Add contact**. Set a status (Prospect, Warm, Active, Customer, Dormant, Lost) and a pipeline stage. Notes, tasks, and agent sessions all link back to a contact record.

**3. Run an AI agent**

Go to **Agents**, pick a role (CEO, CTO, CFO, CMO, CPO, COO), click a mode chip to set the task type (e.g. "Strategy decision", "LinkedIn post", "Revenue forecast"), paste your context or question into the textarea, and click **Run agent**. The response appears in the output panel. From there you can copy it to clipboard or save it directly as a task.

**4. Manage tasks**

Tasks created from agent output, or added manually, live in the **Tasks** module. Set priority (High/Medium/Low) and a due date. The dashboard surfaces your open tasks sorted by priority.

**5. Track your sprint**

The dashboard shows your sprint week progress bar, checkpoint date, and three signal criteria (Inbound, Product, Energy) that you toggle on as you hit them. Update the sprint week and checkpoint date in **Settings**.

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
