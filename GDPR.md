# GDPR Guide for csuite-crm Operators

This document is guidance, not legal advice. If you are processing personal data in the course of a business, you should consult a data protection officer or solicitor qualified in your jurisdiction.

---

## Who is the data controller?

You are. The organisation running csuite-crm on its own infrastructure is the data controller under UK GDPR / EU GDPR. Anthropic (the AI provider) and the csuite-crm project are not data controllers for your data.

---

## What personal data is stored?

The following data may be stored in your MySQL database on your own server:

- **Contacts table** — company name, contact name, email address, phone number, website, pipeline notes
- **Notes table** — free-text notes, optionally linked to a contact
- **Tasks table** — task titles and descriptions, optionally linked to a contact
- **Agent sessions table** — the prompts you send to AI agents and the responses returned

All of this data lives exclusively on your server. csuite-crm does not transmit it to any third party except as described below.

---

## What data leaves your server?

When you run an AI agent, the contents of the prompt textarea — plus the company context from `config/company.php` — are sent to the Anthropic API over HTTPS. The agent session is then saved to your database.

**What this means for GDPR:**

- Do not paste personal data (names, email addresses, phone numbers, home addresses) into agent prompts unless you have a legal basis to process it and have assessed the transfer to Anthropic.
- `config/company.php` is intended for business context only. Do not add personal data to it.
- The in-app GDPR notice on the Agents page reminds users of this at the point of use.

---

## Anthropic as a data processor

If any personal data is sent to the Anthropic API, Anthropic acts as a data processor under Article 28 UK GDPR / EU GDPR. You must:

1. Review Anthropic's Data Processing Agreement at [anthropic.com/legal](https://www.anthropic.com/legal)
2. Ensure the transfer of personal data to Anthropic has a lawful basis
3. Record this processing activity in your Article 30 records

---

## Right to erasure (Article 17)

To fulfil a subject access request for erasure:

1. Go to **Contacts** and find the individual's record
2. Click **Delete contact** — this performs a hard delete and permanently removes the contact record plus any linked notes, tasks (contact link nullified), and agent sessions (contact link nullified) from your database
3. Review agent_sessions manually for any sessions containing the individual's data in the prompt text, and delete those rows directly in MySQL if required

The delete action in csuite-crm is a hard delete. There is no soft delete, no recycle bin, and no recovery. This is a deliberate design decision to support the right to erasure.

---

## Records of processing (Article 30)

The `agent_sessions` table serves as a partial Article 30 log of AI processing activities. It records:

- The agent role used
- The mode
- The user prompt
- The agent output
- The timestamp
- The linked contact (if any)

You may wish to export this table periodically as part of your broader records of processing.

---

## Data residency

For UK GDPR compliance, Anthropic operates under an international transfer mechanism. For EU GDPR, check the current status of Anthropic's Standard Contractual Clauses.

For the data stored in your MySQL database, we recommend running csuite-crm on a server physically located in the UK or EU. UK-based VPS providers include Mythic Beasts, Bytemark, and 34SP. EU-based providers include Hetzner (Germany/Finland) and OVHcloud (France).

---

## What csuite-crm does NOT do

- No analytics or tracking scripts
- No telemetry or crash reporting
- No third-party fonts or CDN resources loaded in production
- No cookies beyond the PHP session cookie (httponly, samesite=Strict)
- No data sent anywhere except the Anthropic API call you explicitly trigger

---

*This document is guidance only. Operators are responsible for their own GDPR compliance.*
