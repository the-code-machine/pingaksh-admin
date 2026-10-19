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

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        <?= htmlspecialchars($pageTitle) ?> - Pingaksh Renewables
    </title>

    <!-- Tailwind CDN -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: {
                            50: '#eef6ff',
                            100: '#dbeafe',
                            500: '#2563eb',
                            600: '#1d4ed8',
                            700: '#1e40af',
                            900: '#0b1f3a'
                        }
                    }
                }
            }
        }
    </script>

    <style>

        * {
            scrollbar-width: thin;
            scrollbar-color: #cbd5e1 transparent;
        }

        body {
            font-family:
                Inter,
                ui-sans-serif,
                system-ui,
                -apple-system,
                BlinkMacSystemFont,
                "Segoe UI",
                sans-serif;
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


<body class="bg-slate-50 text-slate-900">

<div class="min-h-screen">


    <!-- Mobile overlay -->

    <div
        id="sidebarOverlay"
        class="fixed inset-0 z-40 hidden bg-slate-950/50 backdrop-blur-sm lg:hidden"
        onclick="closeSidebar()"
    ></div>


    <!-- Sidebar -->

    <aside
        id="sidebar"
        class="
            sidebar-transition
            fixed
            inset-y-0
            left-0
            z-50
            flex
            w-72
            -translate-x-full
            flex-col
            border-r
            border-slate-800
            bg-[#071b3a]
            text-white
            lg:translate-x-0
        "
    >

        <!-- Brand -->

        <div class="flex h-20 items-center border-b border-white/10 px-6">

            <div>

                <div class="text-xl font-bold tracking-tight">
                    Pingaksh
                </div>

                <div class="mt-0.5 text-xs font-medium text-blue-300">
                    RENEWABLES CMS
                </div>

            </div>

            <button
                onclick="closeSidebar()"
                class="ml-auto rounded-lg p-2 text-slate-300 hover:bg-white/10 lg:hidden"
            >
                <i data-lucide="x" class="h-5 w-5"></i>
            </button>

        </div>


        <!-- Navigation -->

        <div class="flex-1 overflow-y-auto px-4 py-6">

            <p class="mb-3 px-3 text-[11px] font-semibold uppercase tracking-wider text-slate-500">
                Management
            </p>

            <nav class="space-y-1">

                <?php foreach ($menuItems as $item): ?>

                    <?php
                        $isActive = $activeMenu === $item['key'];
                    ?>

                    <a
                        href="<?= htmlspecialchars($item['url']) ?>"
                        class="
                            group
                            flex
                            items-center
                            gap-3
                            rounded-xl
                            px-3
                            py-3
                            text-sm
                            font-medium
                            transition
                            <?= $isActive
                                ? 'bg-blue-600 text-white shadow-lg shadow-blue-950/30'
                                : 'text-slate-300 hover:bg-white/10 hover:text-white'
                            ?>
                        "
                    >

                        <i
                            data-lucide="<?= htmlspecialchars($item['icon']) ?>"
                            class="h-[18px] w-[18px] shrink-0"
                        ></i>

                        <span>
                            <?= htmlspecialchars($item['label']) ?>
                        </span>

                    </a>

                <?php endforeach; ?>

            </nav>

        </div>


        <!-- User / Logout -->

        <div class="border-t border-white/10 p-4">

            <div class="mb-3 flex items-center gap-3 rounded-xl bg-white/5 p-3">

                <div
                    class="
                        flex
                        h-9
                        w-9
                        shrink-0
                        items-center
                        justify-center
                        rounded-lg
                        bg-blue-600
                        text-sm
                        font-semibold
                    "
                >
                    <?= strtoupper(substr($adminName, 0, 1)) ?>
                </div>

                <div class="min-w-0">

                    <p class="truncate text-sm font-semibold">
                        <?= htmlspecialchars($adminName) ?>
                    </p>

                    <p class="text-xs text-slate-400">
                        Administrator
                    </p>

                </div>

            </div>

            <a
                href="<?= BASE_URL ?>/admin/logout.php"
                class="
                    flex
                    w-full
                    items-center
                    justify-center
                    gap-2
                    rounded-xl
                    border
                    border-red-500/20
                    bg-red-500/10
                    px-4
                    py-3
                    text-sm
                    font-medium
                    text-red-300
                    transition
                    hover:bg-red-500
                    hover:text-white
                "
            >

                <i data-lucide="log-out" class="h-4 w-4"></i>

                Sign out

            </a>

        </div>

    </aside>


    <!-- Main -->

    <div class="lg:pl-72">


        <!-- Topbar -->

        <header
            class="
                sticky
                top-0
                z-30
                flex
                h-20
                items-center
                justify-between
                border-b
                border-slate-200
                bg-white/90
                px-4
                backdrop-blur
                sm:px-6
                lg:px-8
            "
        >

            <div class="flex items-center gap-3">

                <button
                    onclick="openSidebar()"
                    class="
                        rounded-xl
                        border
                        border-slate-200
                        bg-white
                        p-2.5
                        text-slate-600
                        shadow-sm
                        hover:bg-slate-50
                        lg:hidden
                    "
                >

                    <i
                        data-lucide="menu"
                        class="h-5 w-5"
                    ></i>

                </button>


                <div>

                    <p class="text-xs font-medium text-slate-400">
                        Content Management System
                    </p>

                    <h1 class="text-lg font-bold text-slate-900">
                        <?= htmlspecialchars($pageTitle) ?>
                    </h1>

                </div>

            </div>


            <div class="hidden items-center gap-3 sm:flex">

                <div class="text-right">

                    <p class="text-sm font-semibold text-slate-800">
                        <?= htmlspecialchars($adminName) ?>
                    </p>

                    <p class="text-xs text-slate-400">
                        Admin
                    </p>

                </div>

                <div
                    class="
                        flex
                        h-10
                        w-10
                        items-center
                        justify-center
                        rounded-xl
                        bg-brand-50
                        font-semibold
                        text-brand-700
                    "
                >
                    <?= strtoupper(substr($adminName, 0, 1)) ?>
                </div>

            </div>

        </header>


        <!-- Page content -->

        <main class="min-h-[calc(100vh-80px)] p-4 sm:p-6 lg:p-8">