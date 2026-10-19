<?php

require_once __DIR__ . '/../../includes/auth.php';

requireLogin();

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/helpers.php';

$pageTitle = 'Edit Gallery Media';
$activeMenu = 'gallery';

$id = filter_input(
    INPUT_GET,
    'id',
    FILTER_VALIDATE_INT
);

if (!$id) {
    header('Location: index.php');
    exit;
}


$stmt = $pdo->prepare("
    SELECT *
    FROM gallery_items
    WHERE id = ?
    LIMIT 1
");

$stmt->execute([$id]);

$item = $stmt->fetch();

if (!$item) {
    http_response_code(404);
    die('Gallery item not found.');
}


$categories = $pdo
    ->query("
        SELECT id, name
        FROM gallery_categories
        ORDER BY name
    ")
    ->fetchAll();


$errors = [];


if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $type = $_POST['type'] ?? $item['type'];
    $categoryId = $_POST['category_id'] ?? '';
    $title = trim($_POST['title'] ?? '');
    $caption = trim($_POST['caption'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $dateLabel = trim($_POST['date_label'] ?? '');
    $isoDate = trim($_POST['iso_date'] ?? '');
    $assetCode = trim($_POST['asset_code'] ?? '');
    $videoDuration = trim($_POST['video_duration'] ?? '');
    $altText = trim($_POST['alt_text'] ?? '');
    $technicalSpecs = trim($_POST['technical_specs'] ?? '');
    $featuredSpan = $_POST['featured_span'] ?? 'standard';
    $isPublished = isset($_POST['is_published']) ? 1 : 0;


    if (!in_array($type, ['image', 'video'], true)) {
        $errors[] = 'Invalid media type.';
    }

    if (!$categoryId) {
        $errors[] = 'Please select a category.';
    }

    if ($title === '') {
        $errors[] = 'Title is required.';
    }

    if (!in_array($featuredSpan, ['wide', 'tall', 'standard'], true)) {
        $errors[] = 'Invalid featured layout.';
    }


    if ($isoDate !== '') {

        $date = DateTime::createFromFormat(
            'Y-m-d',
            $isoDate
        );

        if (
            !$date
            || $date->format('Y-m-d') !== $isoDate
        ) {
            $errors[] = 'Invalid date.';
        }
    }


    $newMediaPath = null;
    $newThumbnailPath = null;


    if (!$errors) {

        try {

            /*
             * Upload replacement media if selected.
             */

            if (
                isset($_FILES['media_file'])
                && $_FILES['media_file']['error'] !== UPLOAD_ERR_NO_FILE
            ) {

                $newMediaPath = galleryUploadFile(
                    $_FILES['media_file'],
                    $type,
                    false
                );
            }


            /*
             * Upload replacement thumbnail.
             */

            if (
                isset($_FILES['thumbnail_file'])
                && $_FILES['thumbnail_file']['error'] !== UPLOAD_ERR_NO_FILE
            ) {

                $newThumbnailPath = galleryUploadFile(
                    $_FILES['thumbnail_file'],
                    'image',
                    false
                );
            }


            $mediaPath = $newMediaPath
                ?: $item['media_url'];

            $thumbnailPath = $newThumbnailPath
                ?: $item['thumbnail_url'];


            /*
             * Optional thumbnail removal.
             */

            if (isset($_POST['remove_thumbnail'])) {

                if ($item['thumbnail_url']) {
                    galleryDeleteFile(
                        $item['thumbnail_url']
                    );
                }

                $thumbnailPath = null;
                $newThumbnailPath = null;
            }


            /*
             * Update.
             */

            $stmt = $pdo->prepare("
                UPDATE gallery_items
                SET
                    type = ?,
                    category_id = ?,
                    title = ?,
                    caption = ?,
                    location = ?,
                    date_label = ?,
                    iso_date = ?,
                    asset_code = ?,
                    thumbnail_url = ?,
                    media_url = ?,
                    video_duration = ?,
                    alt_text = ?,
                    technical_specs = ?,
                    featured_span = ?,
                    is_published = ?
                WHERE id = ?
            ");

            $stmt->execute([
                $type,
                $categoryId,
                $title,
                $caption ?: null,
                $location ?: null,
                $dateLabel ?: null,
                $isoDate ?: null,
                $assetCode ?: null,
                $thumbnailPath,
                $mediaPath,
                $videoDuration ?: null,
                $altText ?: null,
                $technicalSpecs ?: null,
                $featuredSpan,
                $isPublished,
                $id,
            ]);


            /*
             * Delete old media only AFTER successful DB update.
             */

            if (
                $newMediaPath
                && $item['media_url']
                && $item['media_url'] !== $newMediaPath
            ) {
                galleryDeleteFile(
                    $item['media_url']
                );
            }


            if (
                $newThumbnailPath
                && $item['thumbnail_url']
                && $item['thumbnail_url'] !== $newThumbnailPath
            ) {
                galleryDeleteFile(
                    $item['thumbnail_url']
                );
            }


            header(
                'Location: index.php?updated=1'
            );

            exit;

        } catch (Throwable $e) {

            /*
             * Remove newly uploaded files if update failed.
             */

            galleryDeleteFile($newMediaPath);
            galleryDeleteFile($newThumbnailPath);

            $errors[] = $e->getMessage();
        }
    }


    /*
     * Keep submitted values visible if validation fails.
     */

    $item['type'] = $type;
    $item['category_id'] = $categoryId;
    $item['title'] = $title;
    $item['caption'] = $caption;
    $item['location'] = $location;
    $item['date_label'] = $dateLabel;
    $item['iso_date'] = $isoDate;
    $item['asset_code'] = $assetCode;
    $item['video_duration'] = $videoDuration;
    $item['alt_text'] = $altText;
    $item['technical_specs'] = $technicalSpecs;
    $item['featured_span'] = $featuredSpan;
    $item['is_published'] = $isPublished;
}


require_once __DIR__ . '/../../includes/header.php';

?>


<div class="mx-auto max-w-5xl">


    <div class="mb-6">

        <a
            href="index.php"
            class="mb-4 inline-flex items-center gap-2 text-sm font-medium text-slate-500 hover:text-blue-600"
        >

            <i data-lucide="arrow-left" class="h-4 w-4"></i>

            Back to Gallery

        </a>

        <h2 class="text-2xl font-bold tracking-tight text-slate-900">
            Edit Gallery Media
        </h2>

        <p class="mt-1 text-sm text-slate-500">
            Update media information, files and publishing settings.
        </p>

    </div>


    <?php if ($errors): ?>

        <div class="mb-6 rounded-xl border border-red-200 bg-red-50 p-4">

            <div class="flex gap-3">

                <i
                    data-lucide="circle-alert"
                    class="h-5 w-5 text-red-600"
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


        <!-- Existing media -->

        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">

            <div class="mb-5">

                <h3 class="font-semibold text-slate-900">
                    Current Media
                </h3>

            </div>


            <div class="flex flex-col gap-4 sm:flex-row">


                <?php if ($item['thumbnail_url']): ?>

                    <img
                        src="<?= htmlspecialchars($item['thumbnail_url']) ?>"
                        alt=""
                        class="h-32 w-48 rounded-xl border border-slate-200 object-cover"
                    >

                <?php else: ?>

                    <div
                        class="flex h-32 w-48 items-center justify-center rounded-xl bg-slate-100 text-slate-400"
                    >

                        <i
                            data-lucide="<?= $item['type'] === 'video' ? 'video' : 'image' ?>"
                            class="h-8 w-8"
                        ></i>

                    </div>

                <?php endif; ?>


                <div>

                    <p class="font-semibold text-slate-900">
                        <?= htmlspecialchars($item['title']) ?>
                    </p>

                    <p class="mt-1 text-sm text-slate-500">
                        <?= htmlspecialchars($item['type']) ?>
                    </p>

                    <?php if ($item['media_url']): ?>

                        <a
                            href="<?= htmlspecialchars($item['media_url']) ?>"
                            target="_blank"
                            class="mt-3 inline-flex items-center gap-2 text-sm font-medium text-blue-600 hover:text-blue-700"
                        >

                            <i
                                data-lucide="external-link"
                                class="h-4 w-4"
                            ></i>

                            Open current media

                        </a>

                    <?php endif; ?>

                </div>

            </div>

        </section>


        <!-- Basic -->

        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">

            <h3 class="mb-6 font-semibold text-slate-900">
                Basic Information
            </h3>


            <div class="grid gap-5 md:grid-cols-2">


                <div>

                    <label class="mb-2 block text-sm font-medium text-slate-700">
                        Media Type
                    </label>

                    <select
                        name="type"
                        id="mediaType"
                        class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                    >

                        <option
                            value="image"
                            <?= $item['type'] === 'image' ? 'selected' : '' ?>
                        >
                            Image
                        </option>

                        <option
                            value="video"
                            <?= $item['type'] === 'video' ? 'selected' : '' ?>
                        >
                            Video
                        </option>

                    </select>

                </div>


                <div>

                    <label class="mb-2 block text-sm font-medium text-slate-700">
                        Category
                    </label>

                    <select
                        name="category_id"
                        required
                        class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                    >

                        <?php foreach ($categories as $category): ?>

                            <option
                                value="<?= $category['id'] ?>"
                                <?= $item['category_id'] == $category['id'] ? 'selected' : '' ?>
                            >
                                <?= htmlspecialchars($category['name']) ?>
                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>


                <div class="md:col-span-2">

                    <label class="mb-2 block text-sm font-medium text-slate-700">
                        Title
                    </label>

                    <input
                        type="text"
                        name="title"
                        required
                        maxlength="255"
                        value="<?= htmlspecialchars($item['title']) ?>"
                        class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                    >

                </div>


                <div class="md:col-span-2">

                    <label class="mb-2 block text-sm font-medium text-slate-700">
                        Caption
                    </label>

                    <textarea
                        name="caption"
                        rows="4"
                        class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                    ><?= htmlspecialchars($item['caption'] ?? '') ?></textarea>

                </div>


                <div>

                    <label class="mb-2 block text-sm font-medium text-slate-700">
                        Location
                    </label>

                    <input
                        type="text"
                        name="location"
                        value="<?= htmlspecialchars($item['location'] ?? '') ?>"
                        class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                    >

                </div>


                <div>

                    <label class="mb-2 block text-sm font-medium text-slate-700">
                        Asset Code
                    </label>

                    <input
                        type="text"
                        name="asset_code"
                        value="<?= htmlspecialchars($item['asset_code'] ?? '') ?>"
                        class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                    >

                </div>


                <div>

                    <label class="mb-2 block text-sm font-medium text-slate-700">
                        Display Date
                    </label>

                    <input
                        type="text"
                        name="date_label"
                        value="<?= htmlspecialchars($item['date_label'] ?? '') ?>"
                        placeholder="e.g. October 2026"
                        class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                    >

                </div>


                <div>

                    <label class="mb-2 block text-sm font-medium text-slate-700">
                        Actual Date
                    </label>

                    <input
                        type="date"
                        name="iso_date"
                        value="<?= htmlspecialchars($item['iso_date'] ?? '') ?>"
                        class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                    >

                </div>

            </div>

        </section>


        <!-- Files -->

        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">

            <h3 class="mb-6 font-semibold text-slate-900">
                Replace Files
            </h3>


            <div class="grid gap-5 md:grid-cols-2">


                <div>

                    <label class="mb-2 block text-sm font-medium text-slate-700">
                        Replace Main Media
                    </label>

                    <input
                        type="file"
                        name="media_file"
                        accept="image/jpeg,image/png,image/webp,image/gif,video/mp4,video/webm,video/quicktime"
                        class="block w-full rounded-xl border border-slate-200 bg-white text-sm file:mr-4 file:border-0 file:bg-slate-100 file:px-4 file:py-3 file:text-sm"
                    >

                    <p class="mt-2 text-xs text-slate-500">
                        Leave empty to keep current media.
                    </p>

                </div>


                <div>

                    <label class="mb-2 block text-sm font-medium text-slate-700">
                        Replace Thumbnail
                    </label>

                    <input
                        type="file"
                        name="thumbnail_file"
                        accept="image/jpeg,image/png,image/webp,image/gif"
                        class="block w-full rounded-xl border border-slate-200 bg-white text-sm file:mr-4 file:border-0 file:bg-slate-100 file:px-4 file:py-3 file:text-sm"
                    >

                    <p class="mt-2 text-xs text-slate-500">
                        Leave empty to keep current thumbnail.
                    </p>

                </div>


                <?php if ($item['thumbnail_url']): ?>

                    <div class="md:col-span-2">

                        <label class="flex items-center gap-3">

                            <input
                                type="checkbox"
                                name="remove_thumbnail"
                                value="1"
                                class="h-4 w-4 rounded border-slate-300 text-red-600"
                            >

                            <span class="text-sm text-slate-600">
                                Remove current thumbnail
                            </span>

                        </label>

                    </div>

                <?php endif; ?>

            </div>

        </section>


        <!-- Metadata -->

        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">

            <h3 class="mb-6 font-semibold text-slate-900">
                Metadata & Display
            </h3>


            <div class="grid gap-5 md:grid-cols-2">


                <div id="videoDurationField">

                    <label class="mb-2 block text-sm font-medium text-slate-700">
                        Video Duration
                    </label>

                    <input
                        type="text"
                        name="video_duration"
                        value="<?= htmlspecialchars($item['video_duration'] ?? '') ?>"
                        placeholder="e.g. 02:35"
                        class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                    >

                </div>


                <div>

                    <label class="mb-2 block text-sm font-medium text-slate-700">
                        Featured Layout
                    </label>

                    <select
                        name="featured_span"
                        class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm"
                    >

                        <option
                            value="standard"
                            <?= $item['featured_span'] === 'standard' ? 'selected' : '' ?>
                        >
                            Standard
                        </option>

                        <option
                            value="wide"
                            <?= $item['featured_span'] === 'wide' ? 'selected' : '' ?>
                        >
                            Wide
                        </option>

                        <option
                            value="tall"
                            <?= $item['featured_span'] === 'tall' ? 'selected' : '' ?>
                        >
                            Tall
                        </option>

                    </select>

                </div>


                <div class="md:col-span-2">

                    <label class="mb-2 block text-sm font-medium text-slate-700">
                        Alt Text
                    </label>

                    <input
                        type="text"
                        name="alt_text"
                        maxlength="500"
                        value="<?= htmlspecialchars($item['alt_text'] ?? '') ?>"
                        class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm"
                    >

                </div>


                <div class="md:col-span-2">

                    <label class="mb-2 block text-sm font-medium text-slate-700">
                        Technical Specifications
                    </label>

                    <textarea
                        name="technical_specs"
                        rows="4"
                        class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm"
                    ><?= htmlspecialchars($item['technical_specs'] ?? '') ?></textarea>

                </div>


                <div class="md:col-span-2">

                    <label class="flex items-center gap-3">

                        <input
                            type="checkbox"
                            name="is_published"
                            value="1"
                            <?= $item['is_published'] ? 'checked' : '' ?>
                            class="h-4 w-4 rounded border-slate-300 text-blue-600"
                        >

                        <span class="text-sm font-medium text-slate-700">
                            Published
                        </span>

                    </label>

                </div>

            </div>

        </section>


        <div class="flex justify-end gap-3">

            <a
                href="index.php"
                class="rounded-xl border border-slate-200 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50"
            >
                Cancel
            </a>

            <button
                type="submit"
                class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-blue-700"
            >

                <i data-lucide="save" class="h-4 w-4"></i>

                Save Changes

            </button>

        </div>

    </form>

</div>


<script>

const mediaType = document.getElementById('mediaType');
const videoDurationField =
    document.getElementById('videoDurationField');

function updateMediaFields() {

    videoDurationField.style.display =
        mediaType.value === 'video'
            ? 'block'
            : 'none';

}

mediaType.addEventListener(
    'change',
    updateMediaFields
);

updateMediaFields();

</script>


<?php require_once __DIR__ . '/../../includes/footer.php'; ?>