<?php

declare(strict_types=1);

use FoundryHerald\Config;
use FoundryHerald\Database;
use FoundryHerald\Services\KnowledgeLoader;

define('APP_ROOT', dirname(__DIR__));

require APP_ROOT . '/vendor/autoload.php';

Config::load(APP_ROOT);

$db = Database::connection();

$knowledgeLoader = new KnowledgeLoader(
    APP_ROOT . '/knowledge'
);

$heraldContext = $knowledgeLoader->buildContext([
    'house-dainislaav.md',
    'voice-and-tone.md',
    'content-rules.md',
    'content-voices.md',
]);

$postTypes = [
    'auto' => 'Let Herald Decide',
    'forge_reflection' => 'Forge Reflection',
    'song_promotion' => 'Song Promotion',
    'lyric_spotlight' => 'Lyric Spotlight',
    'behind_the_music' => 'Behind the Music',
    'mythic_adventures' => 'Mythic Adventures / LARP',
    'creator_developer' => 'Creator / Developer',
    'humor' => 'Humor / Meme',
    'engagement' => 'Engagement Question',
    'house_lore' => 'House / Iron Voice',
];

$imageOptions = [
    'auto' => 'Auto',
    'yes' => 'Yes',
    'no' => 'No',
];

$selectedPostType = $_POST['post_type'] ?? 'auto';
$topic = trim($_POST['topic'] ?? '');
$imagePreference = $_POST['image_preference'] ?? 'auto';



function e(string $value): string
{
    return htmlspecialchars(
        $value,
        ENT_QUOTES,
        'UTF-8'
    );
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Foundry Herald</title>

    <style>
        * {
            box-sizing: border-box;
        }

        :root {
            color-scheme: dark;

            --background: #0d0d0d;
            --surface: #171717;
            --surface-secondary: #1f1f1f;
            --border: #343434;

            --text: #f3f3f3;
            --muted: #969292;

            --accent: #b56a2d;
            --accent-hover: #cf7c35;

            --danger: #ff8b8b;
        }

        body {
            margin: 0;
            min-height: 100vh;

            background:
                radial-gradient(
                    circle at top,
                    #1c1510 0,
                    var(--background) 45%
                );

            color: var(--text);

            font-family:
                Arial,
                Helvetica,
                sans-serif;
        }

        .app {
            width: min(960px, calc(100% - 32px));

            margin: 0 auto;
            padding: 48px 0 80px;
        }

        .app-header {
            margin-bottom: 32px;
            text-align: center;
        }

        .app-header h1 {
            margin: 0 0 6px;

            font-size: 2.3rem;
            letter-spacing: .04em;
        }

        .app-header p {
            margin: 0;
            color: var(--muted);
        }

        .panel {
            margin-bottom: 24px;
            padding: 24px;

            background: var(--surface);

            border: 1px solid var(--border);
            border-radius: 8px;
        }

        .panel h2 {
            margin: 0 0 20px;

            font-size: 1rem;
            letter-spacing: .08em;
            text-transform: uppercase;
        }

        .form-grid {
            display: grid;

            grid-template-columns:
                repeat(2, minmax(0, 1fr));

            gap: 18px;
        }

        .field {
            display: flex;
            flex-direction: column;
            gap: 7px;
        }

        .field-full {
            grid-column: 1 / -1;
        }

        label {
            font-size: .85rem;
            font-weight: bold;
            color: #d4d1d1;
        }

        select,
        input,
        textarea {
            width: 100%;

            padding: 11px 12px;

            background: var(--surface-secondary);
            color: var(--text);

            border: 1px solid var(--border);
            border-radius: 4px;

            font: inherit;
        }

        select:focus,
        input:focus,
        textarea:focus {
            outline: none;
            border-color: var(--accent);
        }

        textarea {
            resize: vertical;
        }

        #topic {
            min-height: 90px;
        }

        #post-draft {
            min-height: 320px;

            line-height: 1.55;
        }

        .form-actions {
            display: flex;
            justify-content: flex-end;

            margin-top: 20px;
        }

        button {
            padding: 11px 22px;

            background: var(--accent);
            color: #fff;

            border: 0;
            border-radius: 4px;

            font-size: .9rem;
            font-weight: bold;

            cursor: pointer;
        }

        button:hover {
            background: var(--accent-hover);
        }

        .draft-meta {
            display: flex;
            flex-wrap: wrap;

            gap: 8px;

            margin-bottom: 14px;
        }

        .tag {
            padding: 5px 8px;

            background: var(--surface-secondary);
            color: var(--muted);

            border: 1px solid var(--border);
            border-radius: 3px;

            font-size: .75rem;
        }

        .error {
            margin-bottom: 24px;
            padding: 14px 16px;

            color: var(--danger);
            background: #251616;

            border: 1px solid #5e2929;
            border-radius: 5px;
        }

        .empty-state {
            color: var(--muted);
            line-height: 1.6;
        }

        .future-note {
            margin-top: 12px;

            color: var(--muted);
            font-size: .8rem;
        }

        body.is-busy {
            cursor: wait;
        }

        body.is-busy input,
        body.is-busy textarea,
        body.is-busy select {
            cursor: wait;
        }

        button:disabled {
            opacity: .55;
            cursor: not-allowed;
        }

        .generation-status {
            display: inline-block;
            margin-left: 12px;

            color: var(--muted);
            font-size: .85rem;
        }

        .generation-status.error {
            color: var(--danger);
        }

        #post-draft.is-generating {
            opacity: .6;
        }

        .draft-actions {
            display: flex;
            align-items: center;
            gap: 12px;

            margin-top: 16px;
        }

        .secondary-button {
            background: #343434;
        }

        .secondary-button:hover:not(:disabled) {
            background: #484848;
        }

        .danger-button {
            background: #632b2b;
        }

        .danger-button:hover:not(:disabled) {
            background: #7d3636;
        }

        @media (max-width: 700px) {
            .app {
                width: min(100% - 20px, 960px);
                padding-top: 24px;
            }

            .form-grid {
                grid-template-columns: 1fr;
            }

            .field-full {
                grid-column: auto;
            }
        }
    </style>
</head>

<body>

<div class="app">

    <header class="app-header">
        <h1>Foundry Herald</h1>

        <p>
            House Dainislaav Content Agent
        </p>
    </header>



    <section class="panel">

        <h2>Create Content</h2>

        <form id="content-form">

            <div class="form-grid">

                <div class="field">
                    <label for="post_type">
                        Post Type
                    </label>

                    <select
                        id="post_type"
                        name="post_type"
                    >
                        <?php foreach ($postTypes as $value => $label): ?>

                            <option
                                value="<?= e($value) ?>"
                                <?= $selectedPostType === $value
                                    ? 'selected'
                                    : '' ?>
                            >
                                <?= e($label) ?>
                            </option>

                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="field">
                    <label for="image_preference">
                        Image
                    </label>

                    <select
                        id="image_preference"
                        name="image_preference"
                    >
                        <?php foreach ($imageOptions as $value => $label): ?>

                            <option
                                value="<?= e($value) ?>"
                                <?= $imagePreference === $value
                                    ? 'selected'
                                    : '' ?>
                            >
                                <?= e($label) ?>
                            </option>

                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="field field-full">
                    <label for="topic">
                        Topic / Idea
                    </label>

                    <textarea
                        id="topic"
                        name="topic"
                        placeholder="Optional. Leave blank and let Herald choose."
                    ><?= e($topic) ?></textarea>
                </div>

            </div>

            <div class="form-actions">
                <button
                    type="submit"
                    id="generate-button"
                >
                    Generate Post
                </button>
                <span
                    id="generation-status"
                    class="generation-status"
                    aria-live="polite"
                ></span>
            </div>

        </form>

    </section>

    <section class="panel">
        <input
            type="hidden"
            id="draft-id"
            value=""
        >
    <h2>Draft</h2>

    <div
        id="draft-meta"
        class="draft-meta"
        hidden
    ></div>

    <textarea
        id="post-draft"
        name="post_draft"
        placeholder="Your generated post will appear here..."
    ></textarea>

    <div class="draft-actions">

        <button
            type="button"
            id="reject-button"
            class="danger-button"
            disabled
        >
            Reject
        </button>

        <button
            type="button"
            id="save-draft-button"
            class="secondary-button"
            disabled
        >
            Save Draft
        </button>

        <button
            type="button"
            id="approve-button"
            disabled
        >
            Approve
        </button>

        <span id="draft-status" class="generation-status" aria-live="polite"></span>

    </div>

</section>

</div>

<script>
(() => {

    const form =
        document.getElementById('content-form');

    const generateButton =
        document.getElementById('generate-button');

    const status =
        document.getElementById('generation-status');

    const draft =
        document.getElementById('post-draft');

    const draftMeta =
        document.getElementById('draft-meta');

    const saveDraftButton =
        document.getElementById('save-draft-button');

    const draftStatus =
        document.getElementById('draft-status');

    const draftId =
        document.getElementById('draft-id');

    const approveButton =
        document.getElementById('approve-button');

    const rejectButton =
        document.getElementById('reject-button');

    if (
        !form ||
        !generateButton ||
        !status ||
        !draft
    ) {
        return;
    }

    let generating = false;

    form.addEventListener('submit', async (event) => {

        event.preventDefault();

        if (generating) {
            return;
        }

        generating = true;

        const originalButtonText =
            generateButton.textContent;

        generateButton.disabled = true;
        generateButton.textContent = 'Generating...';

        document.body.classList.add('is-busy');
        draft.classList.add('is-generating');

        status.classList.remove('error');
        status.textContent =
            'Herald is preparing a draft...';

        const formData = new FormData(form);

        const postTypeSelect =
            document.getElementById('post_type');

        const selectedPostType =
            postTypeSelect.options[
                postTypeSelect.selectedIndex
            ].text;

        formData.set(
            'post_type',
            selectedPostType
        );

        try {

            const response = await fetch(
                '/api/generate-post.php',
                {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'Accept': 'application/json'
                    }
                }
            );

            const data = await response.json();

            if (!response.ok || !data.success) {

                throw new Error(
                    data.error ||
                    'Herald was unable to generate a post.'
                );
            }

            draft.value = data.post;

            draftId.value = '';

            saveDraftButton.disabled = false;
            saveDraftButton.disabled = false;
            approveButton.disabled = false;
            rejectButton.disabled = false;

            draft.readOnly = false;

            draftStatus.classList.remove('error');

            draftStatus.textContent =
                'Unsaved draft.';

            draftMeta.hidden = false;

            draftMeta.innerHTML = '';

            const typeTag =
                document.createElement('span');

            typeTag.className = 'tag';
            typeTag.textContent = selectedPostType;

            const imageTag =
                document.createElement('span');

            imageTag.className = 'tag';

            imageTag.textContent =
                'Image: ' +
                (
                    data.imagePreference
                        ? data.imagePreference
                            .charAt(0)
                            .toUpperCase()
                          + data.imagePreference.slice(1)
                        : 'Auto'
                );

            draftMeta.appendChild(typeTag);
            draftMeta.appendChild(imageTag);

            status.textContent =
                'Draft ready.';

        } catch (error) {

            console.error(error);

            status.classList.add('error');

            status.textContent =
                error.message ||
                'Something went wrong.';

        } finally {

            generating = false;

            generateButton.disabled = false;

            generateButton.textContent =
                originalButtonText;

            document.body.classList.remove(
                'is-busy'
            );

            draft.classList.remove(
                'is-generating'
            );
        }
    });

    async function saveCurrentDraft() {

        if (!draft.value.trim()) {
            throw new Error(
                'Draft content cannot be empty.'
            );
        }

        const postTypeSelect =
            document.getElementById('post_type');

        const imagePreferenceSelect =
            document.getElementById(
                'image_preference'
            );

        const topicField =
            document.getElementById('topic');

        const formData = new FormData();

        formData.set('id', draftId.value);

        formData.set(
            'post_type',
            postTypeSelect.value
        );

        formData.set(
            'topic',
            topicField.value
        );

        formData.set(
            'image_preference',
            imagePreferenceSelect.value
        );

        formData.set(
            'content',
            draft.value
        );

        const response = await fetch(
            '/api/save-draft.php',
            {
                method: 'POST',
                body: formData,
                headers: {
                    'Accept': 'application/json'
                }
            }
        );

        const data = await response.json();

        if (!response.ok || !data.success) {
            throw new Error(
                data.error ||
                'Unable to save draft.'
            );
        }

        draftId.value = data.id;

        return data;
    }

    async function changePostStatus(status) {

        if (!draft.value.trim()) {
            return;
        }

        const actionButton =
            status === 'approved'
                ? approveButton
                : rejectButton;

        const originalText =
            actionButton.textContent;

        try {

            saveDraftButton.disabled = true;
            approveButton.disabled = true;
            rejectButton.disabled = true;

            actionButton.textContent =
                status === 'approved'
                    ? 'Approving...'
                    : 'Rejecting...';

            document.body.classList.add('is-busy');

            draftStatus.classList.remove('error');

            draftStatus.textContent =
                'Saving current version...';

            /*
            * Always save first.
            * This guarantees we're approving/rejecting
            * exactly what's currently in the textarea.
            */
            await saveCurrentDraft();

            draftStatus.textContent =
                status === 'approved'
                    ? 'Approving post...'
                    : 'Rejecting post...';

            const formData = new FormData();

            formData.set(
                'id',
                draftId.value
            );

            formData.set(
                'status',
                status
            );

            const response = await fetch(
                '/api/set-post-status.php',
                {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'Accept': 'application/json'
                    }
                }
            );

            const data = await response.json();

            if (!response.ok || !data.success) {
                throw new Error(
                    data.error ||
                    'Unable to update post status.'
                );
            }

            draft.readOnly = true;

            draftStatus.textContent =
                data.message;

            /*
            * Keep all actions disabled after
            * approval/rejection.
            */
            saveDraftButton.disabled = true;
            approveButton.disabled = true;
            rejectButton.disabled = true;

        } catch (error) {

            console.error(error);

            draftStatus.classList.add('error');

            draftStatus.textContent =
                error.message ||
                'Something went wrong.';

            /*
            * Since the operation failed, allow
            * the user to try again.
            */
            saveDraftButton.disabled = false;
            approveButton.disabled = false;
            rejectButton.disabled = false;

        } finally {

            actionButton.textContent =
                originalText;

            document.body.classList.remove(
                'is-busy'
            );
        }
    }

    approveButton.addEventListener(
        'click',
        () => changePostStatus('approved')
    );

    rejectButton.addEventListener(
        'click',
        () => changePostStatus('rejected')
    );

    draft.addEventListener('input', () => {

        if (!draft.value.trim()) {
            saveDraftButton.disabled = true;
            draftStatus.textContent = '';
            return;
        }

        saveDraftButton.disabled = false;

        if (draftId.value) {
            draftStatus.classList.remove('error');

            draftStatus.textContent =
                'Unsaved changes.';
        }
    });

})();
</script>

</body>
</html>