<?php
$nav_items = [
    'dashboard' => [
        'label' => __( 'nav.dashboard' ),
        'icon'  => '<svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>',
    ],
    'contacts'  => [
        'label' => __( 'nav.contacts' ),
        'icon'  => '<svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>',
    ],
    'pipeline'  => [
        'label' => __( 'nav.pipeline' ),
        'icon'  => '<svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2"/></svg>',
    ],
    'agent'     => [
        'label' => __( 'nav.agents' ),
        'icon'  => '<svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>',
    ],
    'notes'     => [
        'label' => __( 'nav.notes' ),
        'icon'  => '<svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>',
    ],
    'tasks'     => [
        'label' => __( 'nav.tasks' ),
        'icon'  => '<svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>',
    ],
    'settings'  => [
        'label' => __( 'nav.settings' ),
        'icon'  => '<svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>',
    ],
];
?>
<!-- Mobile hamburger -->
<div class="md:hidden fixed top-0 left-0 right-0 z-20 bg-zinc-950 border-b border-zinc-800 flex items-center justify-between px-4 py-3">
    <span class="text-orange-400 font-bold">csuite</span><span class="text-zinc-400 font-bold">-crm</span>
    <button id="mobile-menu-toggle" class="text-zinc-400 hover:text-zinc-100" aria-label="Menu">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
        </svg>
    </button>
</div>

<!-- Sidebar -->
<nav id="sidebar" class="w-56 bg-zinc-950 border-r border-zinc-800 min-h-screen fixed left-0 top-0 flex flex-col z-10 -translate-x-full md:translate-x-0 transition-transform duration-200">
    <div class="p-4 border-b border-zinc-800 hidden md:block">
        <span class="text-orange-400 font-bold text-lg">csuite</span><span class="text-zinc-400 font-bold text-lg">-crm</span>
    </div>
    <div class="flex-1 p-3 space-y-1 mt-14 md:mt-0">
        <?php foreach ( $nav_items as $nav_page => $item ) :
            $active = ( $page === $nav_page )
                ? 'bg-orange-500/10 text-orange-400'
                : 'text-zinc-400 hover:text-zinc-100 hover:bg-zinc-900';
        ?>
        <a href="<?= BASE_URL ?>index.php?page=<?= $nav_page ?>"
           class="flex items-center gap-3 px-3 py-2 rounded-md text-sm transition-colors <?= $active ?>">
            <?= $item['icon'] ?>
            <span><?= $item['label'] ?></span>
        </a>
        <?php endforeach; ?>
    </div>
    <div class="p-3 border-t border-zinc-800 space-y-2">
        <!-- Language switcher -->
        <div class="flex gap-1">
            <?php foreach ( [ 'en', 'es' ] as $lang_code ) : ?>
            <form method="post" action="<?= BASE_URL ?>api/lang.php" class="flex-1">
                <input type="hidden" name="lang" value="<?= $lang_code ?>">
                <button type="submit"
                    class="w-full text-xs px-2 py-1 rounded transition-colors <?= ( $_SESSION['lang'] ?? 'en' ) === $lang_code ? 'bg-orange-500/20 text-orange-400 font-medium' : 'text-zinc-500 hover:text-zinc-300' ?>">
                    <?= __( 'ui.language_' . $lang_code ) ?>
                </button>
            </form>
            <?php endforeach; ?>
        </div>
        <!-- Logout -->
        <a href="<?= BASE_URL ?>index.php?page=logout"
           class="flex items-center gap-2 px-3 py-2 text-sm text-zinc-400 hover:text-red-400 rounded-md transition-colors">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
            </svg>
            <span><?= __( 'nav.logout' ) ?></span>
        </a>
    </div>
</nav>

<!-- Overlay for mobile -->
<div id="sidebar-overlay" class="fixed inset-0 bg-black/50 z-[9] hidden md:hidden"></div>
