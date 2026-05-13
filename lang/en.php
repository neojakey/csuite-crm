<?php
return [

    // Navigation
    'nav.dashboard'     => 'Dashboard',
    'nav.contacts'      => 'Contacts',
    'nav.agents'        => 'AI Agents',
    'nav.notes'         => 'Notes',
    'nav.tasks'         => 'Tasks',
    'nav.settings'      => 'Settings',
    'nav.logout'        => 'Log out',

    // Auth
    'auth.password'         => 'Password',
    'auth.login'            => 'Log in',
    'auth.error'            => 'Incorrect password.',
    'auth.logout_success'   => 'You have been logged out.',

    // Dashboard
    'dashboard.title'               => 'Dashboard',
    'dashboard.sprint_week'         => 'Sprint week',
    'dashboard.checkpoint'          => 'Checkpoint',
    'dashboard.checkpoint_date'     => 'Checkpoint date',
    'dashboard.criteria.inbound'    => 'Inbound signal',
    'dashboard.criteria.product'    => 'Product signal',
    'dashboard.criteria.energy'     => 'Energy signal',
    'dashboard.recent_sessions'     => 'Recent agent sessions',
    'dashboard.open_tasks'          => 'Open tasks',
    'dashboard.crm_summary'         => 'CRM summary',
    'dashboard.quick_launch'        => 'Quick launch',
    'dashboard.no_sessions'         => 'No agent sessions yet.',
    'dashboard.no_tasks'            => 'No open tasks.',

    // Contacts
    'contacts.title'            => 'Contacts',
    'contacts.add'              => 'Add contact',
    'contacts.edit'             => 'Edit contact',
    'contacts.view'             => 'View contact',
    'contacts.delete'           => 'Delete contact',
    'contacts.company_name'     => 'Company name',
    'contacts.contact_name'     => 'Contact name',
    'contacts.email'            => 'Email',
    'contacts.phone'            => 'Phone',
    'contacts.website'          => 'Website',
    'contacts.source'           => 'Source',
    'contacts.status'           => 'Status',
    'contacts.pipeline_stage'   => 'Pipeline stage',
    'contacts.notes'            => 'Notes',
    'contacts.created'          => 'Created',
    'contacts.none'             => 'No contacts yet.',
    'contacts.confirm_delete'   => 'Are you sure you want to delete this contact? This cannot be undone.',
    'contacts.gdpr_notice'      => 'Deleting a contact permanently removes all their personal data. This supports GDPR right to erasure.',

    // Contact statuses
    'contacts.status.prospect'  => 'Prospect',
    'contacts.status.warm'      => 'Warm',
    'contacts.status.active'    => 'Active',
    'contacts.status.customer'  => 'Customer',
    'contacts.status.dormant'   => 'Dormant',
    'contacts.status.lost'      => 'Lost',

    // Contact sources
    'contacts.source.linkedin'  => 'LinkedIn',
    'contacts.source.referral'  => 'Referral',
    'contacts.source.outbound'  => 'Outbound',
    'contacts.source.inbound'   => 'Inbound',
    'contacts.source.event'     => 'Event',
    'contacts.source.other'     => 'Other',

    // Agents
    'agents.title'              => 'AI Agents',
    'agents.run'                => 'Run agent',
    'agents.thinking'           => 'Thinking...',
    'agents.copy'               => 'Copy output',
    'agents.save_task'          => 'Save as task',
    'agents.placeholder'        => 'Paste your context or prompt here. Be specific — the more detail you provide, the better the output.',
    'agents.output_empty'       => 'Output will appear here.',
    'agents.history'            => 'Session history',
    'agents.no_history'         => 'No previous sessions.',
    'agents.gdpr_notice'        => 'Do not include personal data (names, emails, addresses) in agent prompts unless strictly necessary. See GDPR.md for guidance.',

    // Agent roles
    'agents.role.CEO'   => 'CEO',
    'agents.role.CTO'   => 'CTO',
    'agents.role.CFO'   => 'CFO',
    'agents.role.CMO'   => 'CMO',
    'agents.role.CPO'   => 'CPO',
    'agents.role.COO'   => 'COO',

    // Agent modes — CEO
    'agents.mode.ceo.strategy'      => 'Strategy decision',
    'agents.mode.ceo.sprint'        => 'Sprint review',
    'agents.mode.ceo.linkedin'      => 'LinkedIn post idea',
    'agents.mode.ceo.partnership'   => 'Partnership analysis',
    'agents.mode.ceo.positioning'   => 'Market positioning',

    // Agent modes — CTO
    'agents.mode.cto.architecture'  => 'Architecture review',
    'agents.mode.cto.code'          => 'Code review',
    'agents.mode.cto.security'      => 'Security audit',
    'agents.mode.cto.debt'          => 'Tech debt analysis',
    'agents.mode.cto.agentic'       => 'Agentic pipeline design',

    // Agent modes — CFO
    'agents.mode.cfo.forecast'      => 'Revenue forecast',
    'agents.mode.cfo.pricing'       => 'Pricing model',
    'agents.mode.cfo.cost'          => 'Cost analysis',
    'agents.mode.cfo.runway'        => 'Runway review',
    'agents.mode.cfo.decision'      => 'Financial decision',

    // Agent modes — CMO
    'agents.mode.cmo.linkedin'      => 'LinkedIn post',
    'agents.mode.cmo.email'         => 'Email campaign',
    'agents.mode.cmo.calendar'      => 'Content calendar',
    'agents.mode.cmo.seo'           => 'SEO brief',
    'agents.mode.cmo.messaging'     => 'Messaging strategy',

    // Agent modes — CPO
    'agents.mode.cpo.feature'       => 'Feature specification',
    'agents.mode.cpo.roadmap'       => 'Roadmap prioritisation',
    'agents.mode.cpo.feedback'      => 'User feedback analysis',
    'agents.mode.cpo.competitor'    => 'Competitor gap',
    'agents.mode.cpo.onboarding'    => 'Onboarding flow',

    // Agent modes — COO
    'agents.mode.coo.process'       => 'Process design',
    'agents.mode.coo.workflow'      => 'Workflow optimisation',
    'agents.mode.coo.risk'          => 'Risk review',
    'agents.mode.coo.automation'    => 'Automation brief',
    'agents.mode.coo.priorities'    => 'Daily priorities',

    // Notes
    'notes.title'           => 'Notes',
    'notes.add'             => 'Add note',
    'notes.edit'            => 'Edit note',
    'notes.delete'          => 'Delete note',
    'notes.note_title'      => 'Title',
    'notes.body'            => 'Body',
    'notes.linked_contact'  => 'Linked contact',
    'notes.none'            => 'No notes yet.',
    'notes.confirm_delete'  => 'Delete this note?',

    // Tasks
    'tasks.title'           => 'Tasks',
    'tasks.add'             => 'Add task',
    'tasks.edit'            => 'Edit task',
    'tasks.delete'          => 'Delete task',
    'tasks.task_title'      => 'Title',
    'tasks.description'     => 'Description',
    'tasks.status'          => 'Status',
    'tasks.priority'        => 'Priority',
    'tasks.due_date'        => 'Due date',
    'tasks.linked_contact'  => 'Linked contact',
    'tasks.mark_done'       => 'Mark as done',
    'tasks.none'            => 'No tasks yet.',
    'tasks.confirm_delete'  => 'Delete this task?',
    'tasks.show_all'        => 'Show all tasks',
    'tasks.show_open'       => 'Show open tasks',

    // Task statuses
    'tasks.status.todo'         => 'To do',
    'tasks.status.in_progress'  => 'In progress',
    'tasks.status.done'         => 'Done',

    // Task priorities
    'tasks.priority.low'    => 'Low',
    'tasks.priority.medium' => 'Medium',
    'tasks.priority.high'   => 'High',

    // Settings
    'settings.title'                => 'Settings',
    'settings.sprint_week'          => 'Current sprint week',
    'settings.sprint_total'         => 'Total sprint weeks',
    'settings.checkpoint_date'      => 'Checkpoint date',
    'settings.checkpoint_criteria'  => 'Checkpoint criteria',
    'settings.api_test'             => 'Test API connection',
    'settings.api_test_ok'          => 'API connection successful.',
    'settings.api_test_fail'        => 'API connection failed. Check your API key in .env.',
    'settings.password_change'      => 'Change password',
    'settings.password_current'     => 'Current password',
    'settings.password_new'         => 'New password',
    'settings.password_confirm'     => 'Confirm new password',
    'settings.language'             => 'Language',

    // Flash messages
    'flash.saved'           => 'Saved successfully.',
    'flash.deleted'         => 'Deleted successfully.',
    'flash.error'           => 'Something went wrong. Please try again.',
    'flash.required'        => 'Please fill in all required fields.',
    'flash.csrf'            => 'Security token mismatch. Please try again.',

    // General UI
    'ui.save'               => 'Save',
    'ui.cancel'             => 'Cancel',
    'ui.edit'               => 'Edit',
    'ui.delete'             => 'Delete',
    'ui.view'               => 'View',
    'ui.back'               => 'Back',
    'ui.search'             => 'Search',
    'ui.filter'             => 'Filter',
    'ui.all'                => 'All',
    'ui.none'               => 'None',
    'ui.yes'                => 'Yes',
    'ui.no'                 => 'No',
    'ui.loading'            => 'Loading...',
    'ui.required'           => 'Required',
    'ui.optional'           => 'Optional',
    'ui.actions'            => 'Actions',
    'ui.created'            => 'Created',
    'ui.updated'            => 'Updated',
    'ui.no_results'         => 'No results found.',
    'ui.page'               => 'Page',
    'ui.previous'           => 'Previous',
    'ui.next'               => 'Next',
    'ui.language_en'        => 'EN',
    'ui.language_es'        => 'ES',

];
