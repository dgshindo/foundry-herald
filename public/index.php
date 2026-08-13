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

        .panel-heading-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;

            margin-bottom: 20px;
        }

        .panel-heading-row h2 {
            margin: 0;
        }

        .compact-button {
            padding: 7px 12px;
            font-size: .78rem;
        }

        .recent-content {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .history-item {
            display: grid;

            grid-template-columns:
                minmax(0, 1fr)
                auto;

            gap: 16px;

            padding: 14px 16px;

            background: var(--surface-secondary);

            border: 1px solid var(--border);
            border-radius: 5px;
        }

        .history-main {
            min-width: 0;
        }

        .history-top {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 8px;

            margin-bottom: 5px;
        }

        .history-type {
            font-weight: bold;
            color: var(--text);
        }

        .history-topic {
            margin-top: 5px;

            color: #d4d1d1;
            font-size: .9rem;
        }

        .history-preview {
            margin-top: 8px;

            color: var(--muted);
            font-size: .85rem;
            line-height: 1.45;
        }

        .history-date {
            margin-top: 7px;

            color: #777;
            font-size: .75rem;
        }

        .history-actions {
            display: flex;
            align-items: center;
        }

        .status-badge {
            padding: 4px 7px;

            border-radius: 3px;

            font-size: .7rem;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: .05em;
        }

        .status-draft {
            background: #39330f;
            color: #e0c765;
        }

        .status-approved {
            background: #173a22;
            color: #75cf91;
        }

        .status-rejected {
            background: #462020;
            color: #dc8585;
        }

        .status-published {
            background: #163348;
            color: #80bfe3;
        }

        .status-failed {
            background: #462020;
            color: #dc8585;
        }

        .publish-button {
            background: #365899;
        }

        .publish-button:hover:not(:disabled) {
            background: #4267b2;
        }

        @media (max-width: 700px) {
            .history-item {
                grid-template-columns: 1fr;
            }

            .history-actions {
                justify-content: flex-start;
            }
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

        <button
            type="button"
            id="publish-button"
            class="publish-button"
            hidden
        >
            Publish to Facebook
        </button>

        <span id="draft-status" class="generation-status" aria-live="polite"></span>

    </div>

</section>

<section class="panel">

    <div class="panel-heading-row">
        <h2>Recent Content</h2>

        <button
            type="button"
            id="refresh-history-button"
            class="secondary-button compact-button"
        >
            Refresh
        </button>
    </div>

    <div
        id="recent-content"
        class="recent-content"
    >
        <div class="empty-state">
            Loading recent posts...
        </div>
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

    const recentContent =
        document.getElementById('recent-content');

    const refreshHistoryButton =
        document.getElementById(
            'refresh-history-button'
        );

    const publishButton =
        document.getElementById(
            'publish-button'
        );

    const postTypeLabels = {
        auto: 'Let Herald Decide',
        forge_reflection: 'Forge Reflection',
        song_promotion: 'Song Promotion',
        lyric_spotlight: 'Lyric Spotlight',
        behind_the_music: 'Behind the Music',
        mythic_adventures: 'Mythic Adventures / LARP',
        creator_developer: 'Creator / Developer',
        humor: 'Humor / Meme',
        engagement: 'Engagement Question',
        house_lore: 'House / Iron Voice'
    };

    if (
        !form ||
        !generateButton ||
        !status ||
        !draft ||
        !draftMeta ||
        !saveDraftButton ||
        !draftStatus ||
        !draftId ||
        !approveButton ||
        !rejectButton ||
        !recentContent ||
        !refreshHistoryButton
    ) {
        console.error(
            'Foundry Herald UI initialization failed.',
            {
                form,
                generateButton,
                status,
                draft,
                draftMeta,
                saveDraftButton,
                draftStatus,
                draftId,
                approveButton,
                rejectButton,
                recentContent,
                refreshHistoryButton
            }
        );

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

            publishButton.hidden = true;
            publishButton.disabled = true;

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


    saveDraftButton.addEventListener(
        'click',
        async () => {

            if (
                !draft.value.trim() ||
                saveDraftButton.disabled
            ) {
                return;
            }

            const originalText =
                saveDraftButton.textContent;

            try {
                saveDraftButton.disabled = true;
                approveButton.disabled = true;
                rejectButton.disabled = true;

                saveDraftButton.textContent = 'Saving...';

                document.body.classList.add('is-busy');

                draftStatus.classList.remove('error');
                draftStatus.textContent = 'Saving draft...';

                await saveCurrentDraft();

                draftStatus.textContent = 'Draft saved.';

                await loadRecentPosts();

            } catch (error) {

                console.error(error);

                draftStatus.classList.add('error');
                draftStatus.textContent =
                    error.message ||
                    'Unable to save draft.';

            } finally {

                saveDraftButton.textContent =
                    originalText;

                saveDraftButton.disabled = false;
                approveButton.disabled = false;
                rejectButton.disabled = false;

                document.body.classList.remove(
                    'is-busy'
                );
            }
        }
    );

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

            if (status === 'approved') {
                publishButton.hidden = false;
                publishButton.disabled = false;
            }
            if (status === 'rejected') {
                publishButton.hidden = true;
                publishButton.disabled = true;
            }

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
        await loadRecentPosts();
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

    function escapeHtml(value) {

        return String(value ?? '')
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');
    }

    function postPreview(content, length = 180) {

        const text =
            String(content ?? '')
                .replace(/\s+/g, ' ')
                .trim();

        if (text.length <= length) {
            return text;
        }

        return text.slice(0, length).trim() + '...';
    }

    async function loadRecentPosts() {

        if (!recentContent) {
            return;
        }

        recentContent.innerHTML =
            '<div class="empty-state">'
            + 'Loading recent posts...'
            + '</div>';

        try {

            const response = await fetch(
                '/api/recent-posts.php?limit=20',
                {
                    headers: {
                        'Accept': 'application/json'
                    }
                }
            );

            const data = await response.json();

            if (!response.ok || !data.success) {
                throw new Error(
                    data.error ||
                    'Unable to load recent posts.'
                );
            }

            renderRecentPosts(data.posts);

        } catch (error) {

            console.error(error);

            recentContent.innerHTML =
                '<div class="error">'
                + escapeHtml(
                    error.message ||
                    'Unable to load recent posts.'
                )
                + '</div>';
        }
    }

    function renderRecentPosts(posts) {

        if (!posts || posts.length === 0) {

            recentContent.innerHTML =
                '<div class="empty-state">'
                + 'No saved content yet.'
                + '</div>';

            return;
        }

        recentContent.innerHTML =
            posts.map((post) => {

                const status =
                    String(post.status || 'draft');

                const actionLabel =
                    status === 'draft'
                        ? 'Open'
                        : 'View';

                const topic =
                    post.topic
                        ? escapeHtml(post.topic)
                        : 'No topic provided';

                const preview =
                    escapeHtml(
                        postPreview(post.content)
                    );

                const date =
                    post.updated_at
                        ? new Date(
                            post.updated_at.replace(' ', 'T')
                        ).toLocaleString()
                        : '';

                return `
                    <article class="history-item">

                        <div class="history-main">

                            <div class="history-top">

                                <span class="history-type">
                                    ${escapeHtml(postTypeLabels[post.post_type] || post.post_type)}
                                </span>

                                <span
                                    class="
                                        status-badge
                                        status-${escapeHtml(status)}
                                    "
                                >
                                    ${escapeHtml(status)}
                                </span>

                            </div>

                            <div class="history-topic">
                                ${topic}
                            </div>

                            <div class="history-preview">
                                ${preview}
                            </div>

                            <div class="history-date">
                                ${escapeHtml(date)}
                            </div>

                        </div>

                        <div class="history-actions">

                            <button
                                type="button"
                                class="
                                    secondary-button
                                    compact-button
                                    open-post-button
                                "
                                data-post-id="${Number(post.id)}"
                            >
                                ${actionLabel}
                            </button>

                        </div>

                    </article>
                `;

            }).join('');
    }

    recentContent.addEventListener(
        'click',
        async (event) => {

            const button =
                event.target.closest(
                    '.open-post-button'
                );

            if (!button) {
                return;
            }

            const id =
                Number(button.dataset.postId);

            if (!id) {
                return;
            }

            await loadPost(id);
        }
    );

    async function loadPost(id) {

        try {

            document.body.classList.add('is-busy');

            const response = await fetch(
                '/api/get-post.php?id='
                + encodeURIComponent(id),
                {
                    headers: {
                        'Accept': 'application/json'
                    }
                }
            );

            const data = await response.json();

            if (!response.ok || !data.success) {
                throw new Error(
                    data.error ||
                    'Unable to load post.'
                );
            }

            const post = data.post;

            draftId.value = post.id;
            draft.value = post.content ?? '';

            const topicField =
                document.getElementById('topic');

            const postTypeSelect =
                document.getElementById('post_type');

            const imagePreferenceSelect =
                document.getElementById(
                    'image_preference'
                );
            const canPublish =
                post.status === 'approved';

            publishButton.hidden =
                !canPublish;

            publishButton.disabled =
                !canPublish;

            topicField.value =
                post.topic ?? '';

            imagePreferenceSelect.value =
                post.image_preference ?? 'auto';

            /*
            * Our database stores the option VALUE,
            * so restore it directly when possible.
            */
            if (
                postTypeSelect.querySelector(
                    `option[value="${CSS.escape(
                        post.post_type
                    )}"]`
                )
            ) {
                postTypeSelect.value =
                    post.post_type;
            }

            const isDraft =
                post.status === 'draft';

            draft.readOnly = !isDraft;

            saveDraftButton.disabled = !isDraft;
            approveButton.disabled = !isDraft;
            rejectButton.disabled = !isDraft;

            draftStatus.classList.remove('error');

            if (isDraft) {
                draftStatus.textContent =
                    'Draft loaded.';
            } else {
                draftStatus.textContent =
                    post.status
                        .charAt(0)
                        .toUpperCase()
                    + post.status.slice(1)
                    + ' post.';
            }

            /*
            * Rebuild the metadata chips.
            */
            draftMeta.hidden = false;
            draftMeta.innerHTML = '';

            const typeTag =
                document.createElement('span');

            typeTag.className = 'tag';

            typeTag.textContent =
                postTypeLabels[post.post_type]
                || post.post_type;

            const imageTag =
                document.createElement('span');

            imageTag.className = 'tag';

            imageTag.textContent =
                'Image: '
                + (
                    post.image_preference
                        ? post.image_preference
                            .charAt(0)
                            .toUpperCase()
                        + post.image_preference.slice(1)
                        : 'Auto'
                );

            const statusTag =
                document.createElement('span');

            statusTag.className = 'tag';

            statusTag.textContent =
                'Status: '
                + post.status
                    .charAt(0)
                    .toUpperCase()
                + post.status.slice(1);

            draftMeta.appendChild(typeTag);
            draftMeta.appendChild(imageTag);
            draftMeta.appendChild(statusTag);

            /*
            * Bring the loaded post into view.
            */
            draft.scrollIntoView({
                behavior: 'smooth',
                block: 'center'
            });

        } catch (error) {

            console.error(error);

            draftStatus.classList.add('error');

            draftStatus.textContent =
                error.message ||
                'Unable to load post.';

        } finally {

            document.body.classList.remove(
                'is-busy'
            );
        }
    }

    refreshHistoryButton.addEventListener(
        'click',
        async () => {

            const originalText =
                refreshHistoryButton.textContent;

            refreshHistoryButton.disabled = true;
            refreshHistoryButton.textContent =
                'Refreshing...';

            try {
                await loadRecentPosts();
            } finally {
                refreshHistoryButton.disabled = false;
                refreshHistoryButton.textContent =
                    originalText;
            }
        }
    );

    loadRecentPosts();

    publishButton.addEventListener(
        'click',
        async () => {

            if (
                !draftId.value ||
                publishButton.disabled
            ) {
                return;
            }

            const confirmed = window.confirm(
                'Publish this post to the House Dainislaav Facebook Page?'
            );

            if (!confirmed) {
                return;
            }

            const originalText =
                publishButton.textContent;

            try {
                publishButton.disabled = true;
                publishButton.textContent =
                    'Publishing...';

                document.body.classList.add(
                    'is-busy'
                );

                draftStatus.classList.remove(
                    'error'
                );

                draftStatus.textContent =
                    'Publishing to Facebook...';

                const formData =
                    new FormData();

                formData.set(
                    'id',
                    draftId.value
                );

                const response = await fetch(
                    '/api/publish-post.php',
                    {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'Accept': 'application/json'
                        }
                    }
                );

                const data =
                    await response.json();

                if (
                    !response.ok ||
                    !data.success
                ) {
                    throw new Error(
                        data.error ||
                        'Unable to publish post.'
                    );
                }

                draftStatus.textContent =
                    data.message;

                publishButton.hidden = true;
                publishButton.disabled = true;

                await loadRecentPosts();

            } catch (error) {

                console.error(error);

                draftStatus.classList.add(
                    'error'
                );

                draftStatus.textContent =
                    error.message ||
                    'Unable to publish post.';

                publishButton.disabled = false;

            } finally {

                publishButton.textContent =
                    originalText;

                document.body.classList.remove(
                    'is-busy'
                );
            }
        }
    );

})();
</script>

</body>
</html>