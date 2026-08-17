<?php

declare(strict_types=1);

use FoundryHerald\Config;
use FoundryHerald\Repositories\PostRepository;
use FoundryHerald\Repositories\PublishingDestinationRepository;
use FoundryHerald\Services\FacebookPublisher;
use FoundryHerald\Services\AuthService;

define('APP_ROOT', dirname(__DIR__, 2));

require APP_ROOT . '/vendor/autoload.php';

Config::load(APP_ROOT);

AuthService::requireApi();

header('Content-Type: application/json; charset=utf-8');

function respond(array $data, int $status = 200): never
{
    http_response_code($status);

    echo json_encode(
        $data,
        JSON_UNESCAPED_SLASHES |
        JSON_UNESCAPED_UNICODE
    );

    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond([
        'success' => false,
        'error' => 'Method not allowed.',
    ], 405);
}

$postId = (int) ($_POST['id'] ?? 0);

$repository = null;
$claimed = false;

try {
    if ($postId <= 0) {
        throw new RuntimeException(
            'Invalid post ID.'
        );
    }

    $repository = new PostRepository();

    $post = $repository->findById($postId);

    if ($post === null) {
        throw new RuntimeException(
            'Post not found.'
        );
    }

    if ($post['status'] === 'published') {
        throw new RuntimeException(
            'This post has already been published.'
        );
    }

    if ($post['status'] !== 'approved') {
        throw new RuntimeException(
            'Only approved posts may be published.'
        );
    }

    /*
     * Resolve the selected publishing destination.
     * If the post does not yet have one assigned,
     * fall back to the active default destination.
     */
    $destinationRepository =
        new PublishingDestinationRepository();

    $destinationId = (int) (
        $post['publishing_destination_id'] ?? 0
    );

    if ($destinationId > 0) {
        $destination =
            $destinationRepository->findById(
                $destinationId
            );
    } else {
        $destination =
            $destinationRepository->findDefault();
    }

    if ($destination === null) {
        throw new RuntimeException(
            'No active publishing destination is available.'
        );
    }

    /*
     * Atomic server-side guardrail.
     * Only one request can move approved -> publishing.
     */
    $repository->claimForPublishing($postId);
    $claimed = true;

    $publisher = new FacebookPublisher();

    $facebookPostId =
        $publisher->publishText(
            $destination,
            (string) $post['content']
        );

    $repository->markPublished(
        $postId,
        $facebookPostId
    );

    $claimed = false;

    respond([
        'success' => true,
        'id' => $postId,
        'facebook_post_id' => $facebookPostId,
        'destination' => $destination['name'],
        'message' =>
            'Published to ' . $destination['name'] . '.',
    ]);

} catch (Throwable $e) {

    /*
     * If Facebook publishing fails after the post has
     * been claimed, restore it to Approved so it can
     * safely be retried.
     */
    if (
        $claimed &&
        $repository instanceof PostRepository
    ) {
        try {
            $repository->releasePublishClaim(
                $postId,
                $e->getMessage()
            );
        } catch (Throwable) {
            // Preserve the original publishing error.
        }
    }

    respond([
        'success' => false,
        'error' => $e->getMessage(),
    ], 500);
}
