<?php

/**
 * Freeform for Craft CMS.
 *
 * @author        Solspace, Inc.
 * @copyright     Copyright (c) 2008-2025, Solspace, Inc.
 *
 * @see           https://docs.solspace.com/craft/freeform
 *
 * @license       https://docs.solspace.com/license-agreement
 */

namespace Solspace\Freeform\controllers\api;

use Solspace\Freeform\controllers\BaseApiController;
use Solspace\Freeform\Library\DataObjects\FreeformFeed\Notification;
use Solspace\Freeform\Services\FreeformFeedService;
use Solspace\Freeform\Services\LoggerService;

class NoticesController extends BaseApiController
{
    public function __construct(
        $id,
        $module,
        $config,
        private FreeformFeedService $feedService,
        private LoggerService $loggerService,
    ) {
        parent::__construct($id, $module, $config ?? []);
    }

    protected function get(): array|object
    {
        $messages = $this->feedService->getUnreadFeedMessages();

        $notices = [];
        foreach ($messages as $message) {
            $data = $message->toArray();
            $data['conditions'] = json_decode($data['conditions'], true);

            $notices[] = new Notification($data);
        }

        usort(
            $notices,
            function (Notification $a, Notification $b) {
                $categorySortOrder = Notification::CATEGORY_SORT_ORDER;

                $aIndex = array_search($a->getType(), $categorySortOrder, true);
                $bIndex = array_search($b->getType(), $categorySortOrder, true);

                return $aIndex <=> $bIndex;
            }
        );

        return [
            'notices' => $notices,
            'errors' => $this->loggerService->getLogReader()->count(),
        ];
    }

    protected function delete(int $id): ?bool
    {
        return $this->feedService->markFeedMessageAsRead($id);
    }
}
