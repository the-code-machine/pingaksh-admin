<?php

require_once __DIR__ . '/../config/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$pageTitle = $pageTitle ?? 'Dashboard';
$activeMenu = $activeMenu ?? 'dashboard';

$adminName = $_SESSION['admin_name'] ?? 'Administrator';

$menuItems = [
    [
        'key' => 'dashboard',
        'label' => 'Dashboard',
        'url' => BASE_URL . '/admin/index.php',
        'icon' => 'layout-dashboard'
    ],
    [
        'key' => 'gallery',
        'label' => 'Gallery',
        'url' => BASE_URL . '/admin/gallery/index.php',
        'icon' => 'images'
    ],
    [
        'key' => 'projects',
        'label' => 'Projects',
        'url' => BASE_URL . '/admin/projects/index.php',
        'icon' => 'folder-kanban'
    ],
    [
        'key' => 'directors',
        'label' => 'Directors',
        'url' => BASE_URL . '/admin/directors/index.php',
        'icon' => 'users'
    ],
    [
        'key' => 'announcements',
        'label' => 'Announcements',
        'url' => BASE_URL . '/admin/announcements/index.php',
        'icon' => 'megaphone'
    ],
    [
        'key' => 'documents',
        'label' => 'Documents',
        'url' => BASE_URL . '/admin/documents/index.php',
        'icon' => 'file-text'
    ],
    [
        'key' => 'settings',
        'label' => 'Settings',
        'url' => '#',
        'icon' => 'settings'
    ],
];

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> - Pingaksh Renewables CMS</title>

    <!-- Google Fonts: Montserrat -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- Tailwind CDN -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Montserrat', 'ui-sans-serif', 'system-ui', 'sans-serif'],
                    },
                    colors: {
                        pingaksh: {
                            navy: '#121C4D',
                            navyDark: '#0A102E',
                            terracotta: '#972811',
                            terracottaHover: '#7D200D',
                            sand: '#EEDED4',
                            sandLight: '#FCF9F7',
                            sandBorder: '#E2C6B5',
                            cream: '#EFDBD8'
                        }
                    }
                }
            }
        }
    </script>

    <style>
        * {
            scrollbar-width: thin;
            scrollbar-color: #d1a68e transparent;
        }
        body {
            font-family: 'Montserrat', ui-sans-serif, system-ui, sans-serif;
        }
        .sidebar-transition {
            transition: transform 0.25s ease;
        }
        @media (min-width: 1024px) {
            .sidebar-desktop {
                transform: translateX(0) !important;
            }
        }
    </style>
</head>

<body class="bg-pingaksh-sandLight text-pingaksh-navy">

<div class="min-h-screen">

    <!-- Mobile overlay -->
    <div
        id="sidebarOverlay"
        class="fixed inset-0 z-40 hidden bg-pingaksh-navyDark/60 backdrop-blur-sm lg:hidden"
        onclick="closeSidebar()"
    ></div>

    <!-- Sidebar (Pingaksh Corporate Navy #121C4D) -->
    <aside
        id="sidebar"
        class="sidebar-transition fixed inset-y-0 left-0 z-50 flex w-72 -translate-x-full flex-col border-r border-white/10 bg-pingaksh-navy text-white lg:translate-x-0"
    >
        <!-- Brand Header -->
        <div class="flex h-20 items-center border-b border-white/10 px-6">
            <div>
                <div class="text-xl font-bold tracking-wider text-pingaksh-sand uppercase">
                    PINGAKSH
                </div>
                <div class="mt-0.5 text-[10px] font-semibold tracking-[0.22em] text-pingaksh-sand/75 uppercase">
                    RENEWABLES CMS
                </div>
            </div>

            <button
                onclick="closeSidebar()"
                class="ml-auto rounded-lg p-2 text-pingaksh-sand/80 hover:bg-white/10 lg:hidden"
            >
                <i data-lucide="x" class="h-5 w-5"></i>
            </button>
        </div>

        <!-- Navigation -->
        <div class="flex-1 overflow-y-auto px-4 py-6">
            <p class="mb-3 px-3 text-[11px] font-bold uppercase tracking-widest text-pingaksh-sand/50">
                Portal Management
            </p>

            <nav class="space-y-1.5">
           <?php foreach ($menuItems as $menuItem): ?>
                    <?php $isActive = $activeMenu === $menuItem['key']; ?>
                    <a
                        href="<?= htmlspecialchars($menuItem['url']) ?>"
                        class="group flex items-center gap-3 rounded-xl px-3.5 py-3 text-sm font-medium transition <?= $isActive
                            ? 'bg-pingaksh-terracotta text-white shadow-lg shadow-black/25 font-semibold'
                            : 'text-pingaksh-sand/80 hover:bg-white/10 hover:text-white'
                        ?>"
                    >
                        <i
                            data-lucide="<?= htmlspecialchars($menuItem['icon']) ?>"
                            class="h-[18px] w-[18px] shrink-0 <?= $isActive ? 'text-pingaksh-sand' : '' ?>"
                        ></i>
                        <span><?= htmlspecialchars($menuItem['label']) ?></span>
                    </a>
                <?php endforeach; ?>
            </nav>
        </div>

        <!-- User / Logout -->
        <div class="border-t border-white/10 bg-pingaksh-navyDark/50 p-4">
            <div class="mb-3 flex items-center gap-3 rounded-xl bg-white/5 p-3 border border-white/10">
                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-pingaksh-sand text-sm font-bold text-pingaksh-navy">
                    <?= strtoupper(substr($adminName, 0, 1)) ?>
                </div>
                <div class="min-w-0">
                    <p class="truncate text-sm font-semibold text-white">
                        <?= htmlspecialchars($adminName) ?>
                    </p>
                    <p class="text-xs text-pingaksh-sand/70">
                        Administrator
                    </p>
                </div>
            </div>

            <a
                href="<?= BASE_URL ?>/admin/logout.php"
                class="flex w-full items-center justify-center gap-2 rounded-xl border border-pingaksh-terracotta/40 bg-pingaksh-terracotta/20 px-4 py-2.5 text-sm font-semibold text-pingaksh-sand transition hover:bg-pingaksh-terracotta hover:text-white"
            >
                <i data-lucide="log-out" class="h-4 w-4"></i>
                Sign out
            </a>
        </div>
    </aside>

    <!-- Main Content Area -->
    <div class="lg:pl-72">
        <!-- Topbar -->
        <header class="sticky top-0 z-30 flex h-20 items-center justify-between border-b border-pingaksh-sandBorder/60 bg-white/95 px-4 backdrop-blur sm:px-6 lg:px-8">
            <div class="flex items-center gap-3">
                <button
                    onclick="openSidebar()"
                    class="rounded-xl border border-pingaksh-sandBorder bg-white p-2.5 text-pingaksh-navy shadow-sm hover:bg-pingaksh-sand/30 lg:hidden"
                >
                    <i data-lucide="menu" class="h-5 w-5"></i>
                </button>

                <div>
                    <p class="text-[11px] font-bold uppercase tracking-widest text-pingaksh-terracotta">
                        Pingaksh Group Admin
                    </p>
                    <h1 class="text-lg font-bold text-pingaksh-navy">
                        <?= htmlspecialchars($pageTitle) ?>
                    </h1>
                </div>
            </div>

            <div class="hidden items-center gap-3 sm:flex">
                <div class="text-right">
                    <p class="text-sm font-bold text-pingaksh-navy">
                        <?= htmlspecialchars($adminName) ?>
                    </p>
                    <p class="text-xs text-slate-500">
                        CMS Portal
                    </p>
                </div>
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-pingaksh-sand font-bold text-pingaksh-navy border border-pingaksh-sandBorder">
                    <?= strtoupper(substr($adminName, 0, 1)) ?>
                </div>
            </div>
        </header>

        <!-- Page content -->
        <main class="min-h-[calc(100vh-80px)] p-4 sm:p-6 lg:p-8">