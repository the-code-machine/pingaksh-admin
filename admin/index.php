<?php

require_once __DIR__ . '/../includes/auth.php';

requireLogin();

require_once __DIR__ . '/../config/database.php';

$pageTitle = 'Dashboard';
$activeMenu = 'dashboard';

$galleryCount = 0;
$projectCount = 0;
$directorCount = 0;
$announcementCount = 0;

try {
    $galleryCount = (int) $pdo
        ->query("SELECT COUNT(*) FROM gallery_items")
        ->fetchColumn();
} catch (Exception $e) {}

try {
    $projectCount = (int) $pdo
        ->query("SELECT COUNT(*) FROM projects")
        ->fetchColumn();
} catch (Exception $e) {}

try {
    $directorCount = (int) $pdo
        ->query("SELECT COUNT(*) FROM directors")
        ->fetchColumn();
} catch (Exception $e) {}

try {
    $announcementCount = (int) $pdo
        ->query("SELECT COUNT(*) FROM announcements")
        ->fetchColumn();
} catch (Exception $e) {}

require_once __DIR__ . '/../includes/header.php';

?>


<div class="mx-auto max-w-7xl">


    <!-- Welcome -->

    <div class="mb-8">

        <p class="mb-2 text-sm font-medium text-blue-600">
            Overview
        </p>

        <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">

            <div>

                <h2 class="text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">
                    Welcome back
                </h2>

                <p class="mt-1 text-sm text-slate-500">
                    Manage your Pingaksh Renewables website content from one place.
                </p>

            </div>

            <a
                href="<?= BASE_URL ?>/admin/gallery/create.php"
                class="
                    inline-flex
                    items-center
                    justify-center
                    gap-2
                    rounded-xl
                    bg-blue-600
                    px-4
                    py-2.5
                    text-sm
                    font-semibold
                    text-white
                    shadow-sm
                    transition
                    hover:bg-blue-700
                "
            >

                <i data-lucide="plus" class="h-4 w-4"></i>

                Add Media

            </a>

        </div>

    </div>


    <!-- Stats -->

    <div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-4">


        <!-- Gallery -->

        <a
            href="<?= BASE_URL ?>/admin/gallery/index.php"
            class="
                group
                rounded-2xl
                border
                border-slate-200
                bg-white
                p-5
                shadow-sm
                transition
                hover:-translate-y-0.5
                hover:border-blue-200
                hover:shadow-md
            "
        >

            <div class="flex items-start justify-between">

                <div
                    class="
                        flex
                        h-11
                        w-11
                        items-center
                        justify-center
                        rounded-xl
                        bg-blue-50
                        text-blue-600
                    "
                >
                    <i data-lucide="images" class="h-5 w-5"></i>
                </div>

                <i
                    data-lucide="arrow-up-right"
                    class="
                        h-4
                        w-4
                        text-slate-300
                        transition
                        group-hover:text-blue-600
                    "
                ></i>

            </div>

            <p class="mt-5 text-sm font-medium text-slate-500">
                Gallery Items
            </p>

            <p class="mt-1 text-3xl font-bold text-slate-900">
                <?= $galleryCount ?>
            </p>

        </a>


        <!-- Projects -->

        <div
            class="
                rounded-2xl
                border
                border-slate-200
                bg-white
                p-5
                shadow-sm
            "
        >

            <div
                class="
                    flex
                    h-11
                    w-11
                    items-center
                    justify-center
                    rounded-xl
                    bg-emerald-50
                    text-emerald-600
                "
            >
                <i data-lucide="folder-kanban" class="h-5 w-5"></i>
            </div>

            <p class="mt-5 text-sm font-medium text-slate-500">
                Projects
            </p>

            <p class="mt-1 text-3xl font-bold text-slate-900">
                <?= $projectCount ?>
            </p>

        </div>


        <!-- Directors -->

        <div
            class="
                rounded-2xl
                border
                border-slate-200
                bg-white
                p-5
                shadow-sm
            "
        >

            <div
                class="
                    flex
                    h-11
                    w-11
                    items-center
                    justify-center
                    rounded-xl
                    bg-violet-50
                    text-violet-600
                "
            >
                <i data-lucide="users" class="h-5 w-5"></i>
            </div>

            <p class="mt-5 text-sm font-medium text-slate-500">
                Directors
            </p>

            <p class="mt-1 text-3xl font-bold text-slate-900">
                <?= $directorCount ?>
            </p>

        </div>


        <!-- Announcements -->

        <div
            class="
                rounded-2xl
                border
                border-slate-200
                bg-white
                p-5
                shadow-sm
            "
        >

            <div
                class="
                    flex
                    h-11
                    w-11
                    items-center
                    justify-center
                    rounded-xl
                    bg-amber-50
                    text-amber-600
                "
            >
                <i data-lucide="megaphone" class="h-5 w-5"></i>
            </div>

            <p class="mt-5 text-sm font-medium text-slate-500">
                Announcements
            </p>

            <p class="mt-1 text-3xl font-bold text-slate-900">
                <?= $announcementCount ?>
            </p>

        </div>

    </div>


    <!-- Quick actions -->

    <div
        class="
            mt-8
            rounded-2xl
            border
            border-slate-200
            bg-white
            p-6
            shadow-sm
        "
    >

        <div class="mb-5">

            <h3 class="text-lg font-bold text-slate-900">
                Quick actions
            </h3>

            <p class="mt-1 text-sm text-slate-500">
                Frequently used content management actions.
            </p>

        </div>


        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">


            <a
                href="<?= BASE_URL ?>/admin/gallery/create.php"
                class="
                    group
                    flex
                    items-center
                    gap-4
                    rounded-xl
                    border
                    border-slate-200
                    p-4
                    transition
                    hover:border-blue-200
                    hover:bg-blue-50/50
                "
            >

                <div
                    class="
                        flex
                        h-10
                        w-10
                        items-center
                        justify-center
                        rounded-lg
                        bg-blue-50
                        text-blue-600
                    "
                >
                    <i data-lucide="image-plus" class="h-5 w-5"></i>
                </div>

                <div class="flex-1">

                    <p class="text-sm font-semibold text-slate-900">
                        Add gallery media
                    </p>

                    <p class="text-xs text-slate-500">
                        Upload image or video
                    </p>

                </div>

                <i
                    data-lucide="chevron-right"
                    class="h-4 w-4 text-slate-400"
                ></i>

            </a>


            <a
                href="#"
                class="
                    group
                    flex
                    items-center
                    gap-4
                    rounded-xl
                    border
                    border-slate-200
                    p-4
                    transition
                    hover:border-emerald-200
                    hover:bg-emerald-50/50
                "
            >

                <div
                    class="
                        flex
                        h-10
                        w-10
                        items-center
                        justify-center
                        rounded-lg
                        bg-emerald-50
                        text-emerald-600
                    "
                >
                    <i data-lucide="folder-plus" class="h-5 w-5"></i>
                </div>

                <div class="flex-1">

                    <p class="text-sm font-semibold text-slate-900">
                        Add project
                    </p>

                    <p class="text-xs text-slate-500">
                        Create project content
                    </p>

                </div>

                <i
                    data-lucide="chevron-right"
                    class="h-4 w-4 text-slate-400"
                ></i>

            </a>


            <a
                href="#"
                class="
                    group
                    flex
                    items-center
                    gap-4
                    rounded-xl
                    border
                    border-slate-200
                    p-4
                    transition
                    hover:border-amber-200
                    hover:bg-amber-50/50
                "
            >

                <div
                    class="
                        flex
                        h-10
                        w-10
                        items-center
                        justify-center
                        rounded-lg
                        bg-amber-50
                        text-amber-600
                    "
                >
                    <i data-lucide="megaphone" class="h-5 w-5"></i>
                </div>

                <div class="flex-1">

                    <p class="text-sm font-semibold text-slate-900">
                        New announcement
                    </p>

                    <p class="text-xs text-slate-500">
                        Publish website update
                    </p>

                </div>

                <i
                    data-lucide="chevron-right"
                    class="h-4 w-4 text-slate-400"
                ></i>

            </a>

        </div>

    </div>


</div>


<?php require_once __DIR__ . '/../includes/footer.php'; ?>