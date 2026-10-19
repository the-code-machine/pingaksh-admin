<?php

require_once __DIR__ . '/../../includes/auth.php';

requireLogin();

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/helpers.php';

$pageTitle = 'Add Gallery Media';
$activeMenu = 'gallery';

$categories = $pdo
    ->query("
        SELECT id, name
        FROM gallery_categories
        ORDER BY name
    ")
    ->fetchAll();

$errors = [];

$form = [
    'type' => 'image',
    'category_id' => '',
    'title' => '',
    'caption' => '',
    'location' => '',
    'date_label' => '',
    'iso_date' => '',
    'asset_code' => '',
    'video_duration' => '',
    'alt_text' => '',
    'technical_specs' => '',
    'featured_span' => 'standard',
    'is_published' => '1',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $form['type'] = $_POST['type'] ?? 'image';
    $form['category_id'] = $_POST['category_id'] ?? '';
    $form['title'] = trim($_POST['title'] ?? '');
    $form['caption'] = trim($_POST['caption'] ?? '');
    $form['location'] = trim($_POST['location'] ?? '');
    $form['date_label'] = trim($_POST['date_label'] ?? '');
    $form['iso_date'] = trim($_POST['iso_date'] ?? '');
    $form['asset_code'] = trim($_POST['asset_code'] ?? '');
    $form['video_duration'] = trim($_POST['video_duration'] ?? '');
    $form['alt_text'] = trim($_POST['alt_text'] ?? '');
    $form['technical_specs'] = trim($_POST['technical_specs'] ?? '');
    $form['featured_span'] = $_POST['featured_span'] ?? 'standard';
    $form['is_published'] = isset($_POST['is_published']) ? '1' : '0';


    /*
     * Validation
     */

    if (!in_array($form['type'], ['image', 'video'], true)) {
        $errors[] = 'Invalid media type.';
    }

    if (!in_array($form['featured_span'], ['wide', 'tall', 'standard'], true)) {
        $errors[] = 'Invalid featured layout.';
    }

    if ($form['title'] === '') {
        $errors[] = 'Title is required.';
    }

    if (!$form['category_id']) {
        $errors[] = 'Please select a category.';
    }

    if ($form['iso_date'] !== '') {

        $date = DateTime::createFromFormat(
            'Y-m-d',
            $form['iso_date']
        );

        if (!$date || $date->format('Y-m-d') !== $form['iso_date']) {
            $errors[] = 'Invalid date.';
        }
    }


    /*
     * File upload
     */

    $mediaPath = null;
    $thumbnailPath = null;

    if (!$errors) {

        try {

            $mediaPath = galleryUploadFile(
                $_FILES['media_file'] ?? [],
                $form['type'],
                true
            );


            /*
             * Thumbnail is optional for images/videos.
             */

            if (
                isset($_FILES['thumbnail_file'])
                && $_FILES['thumbnail_file']['error'] !== UPLOAD_ERR_NO_FILE
            ) {

                $thumbnailPath = galleryUploadFile(
                    $_FILES['thumbnail_file'],
                    'image',
                    false
                );
            }


            /*
             * Insert database record.
             */

            $stmt = $pdo->prepare("
                INSERT INTO gallery_items (
                    type,
                    category_id,
                    title,
                    caption,
                    location,
                    date_label,
                    iso_date,
                    asset_code,
                    thumbnail_url,
                    media_url,
                    video_duration,
                    alt_text,
                    technical_specs,
                    featured_span,
                    is_published
                )
                VALUES (
                    ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
                )
            ");

            $stmt->execute([
                $form['type'],
                $form['category_id'],
                $form['title'],
                $form['caption'] ?: null,
                $form['location'] ?: null,
                $form['date_label'] ?: null,
                $form['iso_date'] ?: null,
                $form['asset_code'] ?: null,
                $thumbnailPath,
                $mediaPath,
                $form['video_duration'] ?: null,
                $form['alt_text'] ?: null,
                $form['technical_specs'] ?: null,
                $form['featured_span'],
                $form['is_published'],
            ]);


            header('Location: index.php?created=1');

            exit;

        } catch (Throwable $e) {

            /*
             * Cleanup uploaded files if DB insertion fails.
             */

            galleryDeleteFile($mediaPath);
            galleryDeleteFile($thumbnailPath);

            $errors[] = $e->getMessage();
        }
    }
}

require_once __DIR__ . '/../../includes/header.php';

?>

<div class="mx-auto max-w-5xl">

    <!-- Header -->

    <div class="mb-6">

        <a
            href="index.php"
            class="mb-4 inline-flex items-center gap-2 text-sm font-medium text-slate-500 hover:text-blue-600"
        >
            <i data-lucide="arrow-left" class="h-4 w-4"></i>
            Back to Gallery
        </a>

        <h2 class="text-2xl font-bold tracking-tight text-slate-900">
            Add Gallery Media
        </h2>

        <p class="mt-1 text-sm text-slate-500">
            Upload and publish an image or video to the website gallery.
        </p>

    </div>


    <?php if ($errors): ?>

        <div class="mb-6 rounded-xl border border-red-200 bg-red-50 p-4">

            <div class="flex gap-3">

                <i
                    data-lucide="circle-alert"
                    class="mt-0.5 h-5 w-5 shrink-0 text-red-600"
                ></i>

                <div>

                    <p class="font-semibold text-red-800">
                        Please fix the following:
                    </p>

                    <ul class="mt-2 list-disc pl-5 text-sm text-red-700">

                        <?php foreach ($errors as $error): ?>

                            <li>
                                <?= htmlspecialchars($error) ?>
                            </li>

                        <?php endforeach; ?>

                    </ul>

                </div>

            </div>

        </div>

    <?php endif; ?>


    <form
        method="POST"
        enctype="multipart/form-data"
        class="space-y-6"
    >


        <!-- Basic information -->

        <section
            class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm"
        >

            <div class="mb-6">

                <h3 class="font-semibold text-slate-900">
                    Basic Information
                </h3>

                <p class="mt-1 text-sm text-slate-500">
                    Define the media type and basic gallery information.
                </p>

            </div>


            <div class="grid gap-5 md:grid-cols-2">


                <!-- Type -->

                <div>

                    <label class="mb-2 block text-sm font-medium text-slate-700">
                        Media Type
                    </label>

                    <select
                        name="type"
                        id="mediaType"
                        required
                        class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                    >

                        <option
                            value="image"
                            <?= $form['type'] === 'image' ? 'selected' : '' ?>
                        >
                            Image
                        </option>

                        <option
                            value="video"
                            <?= $form['type'] === 'video' ? 'selected' : '' ?>
                        >
                            Video
                        </option>

                    </select>

                </div>


                <!-- Category -->

                <div>

                    <label class="mb-2 block text-sm font-medium text-slate-700">
                        Category
                    </label>

                    <select
                        name="category_id"
                        required
                        class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                    >

                        <option value="">
                            Select category
                        </option>

                        <?php foreach ($categories as $category): ?>

                            <option
                                value="<?= $category['id'] ?>"
                                <?= $form['category_id'] == $category['id'] ? 'selected' : '' ?>
                            >
                                <?= htmlspecialchars($category['name']) ?>
                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>


                <!-- Title -->

                <div class="md:col-span-2">

                    <label class="mb-2 block text-sm font-medium text-slate-700">
                        Title <span class="text-red-500">*</span>
                    </label>

                    <input
                        type="text"
                        name="title"
                        value="<?= htmlspecialchars($form['title']) ?>"
                        required
                        maxlength="255"
                        placeholder="e.g. Hydroelectric Power Plant"
                        class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                    >

                </div>


                <!-- Caption -->

                <div class="md:col-span-2">

                    <label class="mb-2 block text-sm font-medium text-slate-700">
                        Caption
                    </label>

                    <textarea
                        name="caption"
                        rows="4"
                        placeholder="Short description of this media..."
                        class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                    ><?= htmlspecialchars($form['caption']) ?></textarea>

                </div>


                <!-- Location -->

                <div>

                    <label class="mb-2 block text-sm font-medium text-slate-700">
                        Location
                    </label>

                    <input
                        type="text"
                        name="location"
                        value="<?= htmlspecialchars($form['location']) ?>"
                        placeholder="e.g. Madhya Pradesh"
                        class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                    >

                </div>


                <!-- Asset code -->

                <div>

                    <label class="mb-2 block text-sm font-medium text-slate-700">
                        Asset Code
                    </label>

                    <input
                        type="text"
                        name="asset_code"
                        value="<?= htmlspecialchars($form['asset_code']) ?>"
                        placeholder="e.g. UBHP-GAL-001"
                        class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                    >

                </div>


                <!-- Date -->

                <div>

                    <label class="mb-2 block text-sm font-medium text-slate-700">
                        Display Date
                    </label>

                    <input
                        type="text"
                        name="date_label"
                        value="<?= htmlspecialchars($form['date_label']) ?>"
                        placeholder="e.g. October 2026"
                        class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                    >

                </div>


                <!-- ISO Date -->

                <div>

                    <label class="mb-2 block text-sm font-medium text-slate-700">
                        Actual Date
                    </label>

                    <input
                        type="date"
                        name="iso_date"
                        value="<?= htmlspecialchars($form['iso_date']) ?>"
                        class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                    >

                </div>

            </div>

        </section>


        <!-- Media -->

        <section
            class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm"
        >

            <div class="mb-6">

                <h3 class="font-semibold text-slate-900">
                    Media Files
                </h3>

                <p class="mt-1 text-sm text-slate-500">
                    Upload the main media and an optional thumbnail.
                </p>

            </div>


            <div class="grid gap-5 md:grid-cols-2">


                <!-- Main media -->

                <div>

                    <label class="mb-2 block text-sm font-medium text-slate-700">

                        Main Media
                        <span class="text-red-500">*</span>

                    </label>

                    <input
                        type="file"
                        name="media_file"
                        id="mediaFile"
                        required
                        accept="image/jpeg,image/png,image/webp,image/gif,video/mp4,video/webm,video/quicktime"
                        class="block w-full rounded-xl border border-slate-200 bg-white text-sm file:mr-4 file:border-0 file:bg-slate-100 file:px-4 file:py-3 file:text-sm file:font-medium"
                    >

                    <p
                        id="mediaHelp"
                        class="mt-2 text-xs text-slate-500"
                    >
                        Images up to 15 MB.
                    </p>

                </div>


                <!-- Thumbnail -->

                <div>

                    <label class="mb-2 block text-sm font-medium text-slate-700">
                        Thumbnail
                    </label>

                    <input
                        type="file"
                        name="thumbnail_file"
                        accept="image/jpeg,image/png,image/webp,image/gif"
                        class="block w-full rounded-xl border border-slate-200 bg-white text-sm file:mr-4 file:border-0 file:bg-slate-100 file:px-4 file:py-3 file:text-sm file:font-medium"
                    >

                    <p class="mt-2 text-xs text-slate-500">
                        Optional. Recommended for videos.
                    </p>

                </div>

            </div>

        </section>


        <!-- Metadata -->

        <section
            class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm"
        >

            <div class="mb-6">

                <h3 class="font-semibold text-slate-900">
                    Metadata & Display
                </h3>

            </div>


            <div class="grid gap-5 md:grid-cols-2">


                <!-- Video duration -->

                <div id="videoDurationField">

                    <label class="mb-2 block text-sm font-medium text-slate-700">
                        Video Duration
                    </label>

                    <input
                        type="text"
                        name="video_duration"
                        value="<?= htmlspecialchars($form['video_duration']) ?>"
                        placeholder="e.g. 02:35"
                        class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                    >

                </div>


                <!-- Featured span -->

                <div>

                    <label class="mb-2 block text-sm font-medium text-slate-700">
                        Featured Layout
                    </label>

                    <select
                        name="featured_span"
                        class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                    >

                        <option
                            value="standard"
                            <?= $form['featured_span'] === 'standard' ? 'selected' : '' ?>
                        >
                            Standard
                        </option>

                        <option
                            value="wide"
                            <?= $form['featured_span'] === 'wide' ? 'selected' : '' ?>
                        >
                            Wide
                        </option>

                        <option
                            value="tall"
                            <?= $form['featured_span'] === 'tall' ? 'selected' : '' ?>
                        >
                            Tall
                        </option>

                    </select>

                </div>


                <!-- Alt text -->

                <div class="md:col-span-2">

                    <label class="mb-2 block text-sm font-medium text-slate-700">
                        Alt Text
                    </label>

                    <input
                        type="text"
                        name="alt_text"
                        value="<?= htmlspecialchars($form['alt_text']) ?>"
                        maxlength="500"
                        placeholder="Describe the image for accessibility..."
                        class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                    >

                </div>


                <!-- Technical specs -->

                <div class="md:col-span-2">

                    <label class="mb-2 block text-sm font-medium text-slate-700">
                        Technical Specifications
                    </label>

                    <textarea
                        name="technical_specs"
                        rows="4"
                        placeholder="Project or media technical information..."
                        class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                    ><?= htmlspecialchars($form['technical_specs']) ?></textarea>

                </div>


                <!-- Published -->

                <div class="md:col-span-2">

                    <label class="flex cursor-pointer items-center gap-3">

                        <input
                            type="checkbox"
                            name="is_published"
                            value="1"
                            <?= $form['is_published'] === '1' ? 'checked' : '' ?>
                            class="h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                        >

                        <span>

                            <span class="block text-sm font-medium text-slate-800">
                                Publish immediately
                            </span>

                            <span class="block text-xs text-slate-500">
                                Published media can be displayed by the frontend.
                            </span>

                        </span>

                    </label>

                </div>

            </div>

        </section>


        <!-- Actions -->

        <div class="flex items-center justify-end gap-3">

            <a
                href="index.php"
                class="rounded-xl border border-slate-200 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50"
            >
                Cancel
            </a>

            <button
                type="submit"
                class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-700"
            >

                <i data-lucide="upload" class="h-4 w-4"></i>

                Upload Media

            </button>

        </div>

    </form>

</div>


<script>

const mediaType = document.getElementById('mediaType');
const videoDurationField = document.getElementById('videoDurationField');
const mediaHelp = document.getElementById('mediaHelp');

function updateMediaFields() {

    if (mediaType.value === 'video') {

        videoDurationField.style.display = 'block';

        mediaHelp.textContent =
            'Videos up to 100 MB. MP4, WebM or MOV.';

    } else {

        videoDurationField.style.display = 'none';

        mediaHelp.textContent =
            'Images up to 15 MB. JPG, PNG, WebP or GIF.';

    }

}

mediaType.addEventListener('change', updateMediaFields);

updateMediaFields();

</script>


<?php require_once __DIR__ . '/../../includes/footer.php'; ?>