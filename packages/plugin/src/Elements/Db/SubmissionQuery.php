<?php

namespace Solspace\Freeform\Elements\Db;

use craft\db\Query;
use craft\db\Table;
use craft\elements\db\ElementQuery;
use craft\helpers\Db;
use Solspace\Freeform\Elements\Submission;
use Solspace\Freeform\Fields\Interfaces\NoStorageInterface;
use Solspace\Freeform\Form\Form;
use Solspace\Freeform\Freeform;
use Solspace\Freeform\Library\Helpers\SitesHelper;
use Solspace\Freeform\Records\Form\FormSiteRecord;
use Solspace\Freeform\Records\FormRecord;
use Solspace\Freeform\Records\SpamReasonRecord;
use Solspace\Freeform\Records\StatusRecord;

class SubmissionQuery extends ElementQuery
{
    public mixed $formId = null;
    public mixed $userId = null;
    public mixed $form = null;
    public mixed $statusId = null;
    public mixed $incrementalId = null;
    public ?string $token = null;
    public ?bool $isSpam = null;
    public ?bool $isHidden = false;
    public array $fieldSearch = [];
    public ?string $spamReason = null;
    public bool $skipContent = false;
    private mixed $freeformStatus = null;

    public function formId(mixed $value): self
    {
        $this->formId = $value;

        return $this;
    }

    public function userId(mixed $value): self
    {
        $this->userId = $value;

        return $this;
    }

    public function form(mixed $value): self
    {
        $this->form = $value;

        return $this;
    }

    public function statusId(mixed $value): self
    {
        $this->statusId = $value;

        return $this;
    }

    public function incrementalId(mixed $value): self
    {
        $this->incrementalId = $value;

        return $this;
    }

    public function token(string $value): self
    {
        $this->token = $value;

        return $this;
    }

    public function isSpam(?int $value = null): self
    {
        $this->isSpam = $value;

        return $this;
    }

    public function isHidden(?bool $value = null): self
    {
        $this->isHidden = $value;

        return $this;
    }

    public function skipContent(bool $value): self
    {
        $this->skipContent = true;

        return $this;
    }

    public function fieldSearch(array $fieldSearch = []): self
    {
        $this->fieldSearch = $fieldSearch;

        return $this;
    }

    /**
     * @param string $value
     */
    public function spamReason($value): self
    {
        $this->spamReason = $value;

        return $this;
    }

    protected function beforePrepare(): bool
    {
        static $forms;
        static $formHandleToIdMap;
        static $formIdToHandleMap;

        if (null === $formHandleToIdMap) {
            $forms = Freeform::getInstance()->forms->getAllForms();
            foreach ($forms as $form) {
                $formHandleToIdMap[$form->getHandle()] = $form->getId();
                $formIdToHandleMap[$form->getId()] = $form->getHandle();
                $forms[$form->getId()] = $form;
            }
        }

        $table = Submission::TABLE_STD;
        $formTable = FormRecord::TABLE_STD;
        $statusTable = StatusRecord::TABLE_STD;
        $spamReasonTable = SpamReasonRecord::TABLE_STD;

        $this->joinElementTable($table);

        $this->query->innerJoin(FormRecord::TABLE.' '.$formTable, "{$formTable}.[[id]] = {$table}.[[formId]]");
        $this->query->innerJoin(StatusRecord::TABLE.' '.$statusTable, "{$statusTable}.[[id]] = {$table}.[[statusId]]");
        $this->subQuery->innerJoin(StatusRecord::TABLE.' sub_'.$statusTable, "sub_{$statusTable}.[[id]] = {$table}.[[statusId]]");

        if ($this->form instanceof Form) {
            $this->form = $this->form->getHandle();
        }

        if ($this->form && $formHandleToIdMap[$this->form]) {
            $this->formId = $formHandleToIdMap[$this->form];
        }

        if (!$this->skipContent && !$this->formId && ($this->id || $this->token)) {
            if ($this->token) {
                $param = Db::parseParam('token', $this->token);
            } else {
                $param = Db::parseParam('id', $this->id);
            }

            $this->formId = (int) (new Query())
                ->select(['formId'])
                ->from(Submission::TABLE)
                ->where($param)
                ->scalar()
            ;
        }

        $select = [
            $table.'.[[formId]]',
            $table.'.[[userId]]',
            $table.'.[[statusId]]',
            $table.'.[[incrementalId]]',
            $table.'.[[token]]',
            $table.'.[[isSpam]]',
            $table.'.[[ip]]',
        ];

        $joinedForms = [];
        if (!$this->skipContent) {
            $joinFormIds = [];
            if ($this->formId) {
                if (\is_array($this->formId)) {
                    $joinFormIds = $this->formId;
                } else {
                    $joinFormIds[] = $this->formId;
                }
            } else {
                $joinFormIds = array_values($formHandleToIdMap ?? []);
            }

            $joinFormIds = $this->extractFormIdsWithContent($joinFormIds);

            foreach ($joinFormIds as $formId) {
                $form = $forms[$formId];
                $joinedForms[] = $form;
                $contentTable = Submission::getContentTableName($form);

                $this->query->leftJoin("{$contentTable} fc{$formId}", "[[fc{$formId}]].[[id]] = [[{$table}]].[[id]]");
                $this->subQuery->leftJoin("{$contentTable} fc{$formId}", "[[fc{$formId}]].[[id]] = [[{$table}]].[[id]]");

                $storableFields = $form->getLayout()->getFields()->getExcludedList(NoStorageInterface::class);
                foreach ($storableFields as $field) {
                    $fieldHandle = Submission::getFieldColumnName($field);
                    $select[] = "[[fc{$formId}]].[[{$fieldHandle}]] as [[form_{$formId}__{$fieldHandle}]]";
                }
            }
        }

        if (null !== $this->formId) {
            $this->subQuery->andWhere(Db::parseParam($table.'.[[formId]]', $this->formId));
        }

        if (SitesHelper::isEnabled()) {
            $site = SitesHelper::getCurrentCpSite();
            $this->subQuery->innerJoin(
                FormSiteRecord::TABLE.' form_sites',
                'form_sites.[[formId]] = '.$table.'.[[formId]] AND form_sites.[[siteId]] = :siteId',
                ['siteId' => $site->id]
            );
        }

        $this->query->select($select);

        $request = \Craft::$app->request;

        $isEmptyFormId = empty($this->formId);
        $isCpRequest = $request->getIsCpRequest();
        $isIndex = !$request->getIsConsoleRequest() && 'index' === $request->post('context');
        if ($isEmptyFormId && $isCpRequest && $isIndex) {
            $allowedFormIds = Freeform::getInstance()->submissions->getAllowedReadFormIds();
            $this->subQuery->andWhere([$table.'.[[formId]]' => $allowedFormIds]);
        }

        if ($this->statusId) {
            $this->subQuery->andWhere(Db::parseParam($table.'.[[statusId]]', $this->statusId));
        }

        if ($this->userId) {
            $this->subQuery->andWhere(Db::parseParam($table.'.[[userId]]', $this->userId));
        }

        if ($this->incrementalId) {
            $this->subQuery->andWhere(Db::parseParam($table.'.[[incrementalId]]', $this->incrementalId));
        }

        if (null !== $this->token) {
            $this->subQuery->andWhere(Db::parseParam($table.'.[[token]]', $this->token));
        }

        if (null !== $this->isSpam) {
            $this->subQuery->andWhere(Db::parseParam($table.'.[[isSpam]]', $this->isSpam));
        }

        if (null !== $this->isHidden) {
            $this->subQuery->andWhere(Db::parseParam($table.'.[[isHidden]]', $this->isHidden));
        }

        if (!empty($this->spamReason)) {
            $this->query->innerJoin(
                SpamReasonRecord::TABLE." {$spamReasonTable}",
                "{$spamReasonTable}.[[submissionId]] = {$table}.[[id]] AND {$spamReasonTable}.[[reasonType]] = :spamReason",
                ['spamReason' => $this->spamReason]
            );
        }

        if ($this->status) {
            $this->freeformStatus = $this->status;
            $this->status = null;

            if (\is_array($this->freeformStatus)) {
                if (isset($this->freeformStatus[0]) && 'enabled' === $this->freeformStatus[0]) {
                    $this->freeformStatus = null;
                }
            }
        }

        if ($this->freeformStatus) {
            $this->subQuery->andWhere(Db::parseParam("sub_{$statusTable}.[[handle]]", $this->freeformStatus));
        }

        $customSortTables = [
            'status' => "{$statusTable}.[[name]]",
            'form' => "{$formTable}.[[name]]",
        ];

        if (\is_array($this->orderBy)) {
            // reset the order by array to a single element
            $this->orderBy = \array_slice($this->orderBy, 0, 1, true);
        }

        foreach ($customSortTables as $column => $columnUpdate) {
            if (isset($this->orderBy[$column])) {
                $sortOrder = $this->orderBy[$column];

                unset($this->orderBy[$column]);
                $this->query->orderBy([$columnUpdate => $sortOrder]);
            }
        }

        $this->prepareOrderBy($joinedForms);
        $this->prepareFieldSearch($joinedForms);

        return parent::beforePrepare();
    }

    private function extractFormIdsWithContent(array $formIds): array
    {
        $distinct = (new Query())
            ->select('formId')
            ->groupBy('formId')
            ->distinct('formId')
            ->from(Submission::TABLE.' s')
            ->innerJoin(Table::ELEMENTS.' e', '[[e]].[[id]] = [[s]].[[id]]')
            ->where([
                's.[[isSpam]]' => (bool) $this->isSpam,
                's.[[formId]]' => $formIds,
                'e.[[dateDeleted]]' => null,
            ])
            ->column()
        ;

        return \array_slice($distinct, 0, 50);
    }

    private function prepareOrderBy(array $joinedForms): void
    {
        if (empty($this->orderBy) || !\is_array($this->orderBy)) {
            return;
        }

        $orderExceptions = ['title', 'score'];

        $prefixedOrderList = [];
        foreach ($this->orderBy as $key => $sortDirection) {
            if (preg_match('/\(\)$/', $key)) {
                $prefixedOrderList[$key] = $sortDirection;

                continue;
            }

            if (\in_array($key, $orderExceptions, true) || preg_match('/^[a-z0-9_]+\./i', $key)) {
                $prefixedOrderList[$key] = $sortDirection;

                continue;
            }

            if ('spamReasons' === $key) {
                continue;
            }

            $column = $this->extractColumnName($joinedForms, $key);
            if ($column) {
                $prefixedOrderList[$column] = $sortDirection;
            } else {
                $prefixedOrderList[Submission::TABLE_STD.'.[['.$key.']]'] = $sortDirection;
            }
        }

        $this->orderBy = $prefixedOrderList;
    }

    /**
     * Parses the fieldSearch variable and attaches the WHERE conditions to the query.
     *
     * @param Form[] $joinedForms
     */
    private function prepareFieldSearch(array $joinedForms): void
    {
        if (!$this->fieldSearch) {
            return;
        }

        foreach ($this->fieldSearch as $handle => $term) {
            $columns = $this->extractMatchingColumnNames($joinedForms, $handle);

            $condition = array_map(fn ($column) => Db::parseParam($column, $term), $columns);
            if (\count($condition)) {
                $condition = array_merge(['or'], $condition);

                $this->subQuery->andWhere($condition);
            }
        }
    }

    /**
     * @param Form[] $joinedForms
     */
    private function extractColumnName(array $joinedForms, ?string $handle): ?string
    {
        $matching = $this->extractMatchingColumnNames($joinedForms, $handle);

        return reset($matching);
    }

    private function extractMatchingColumnNames(array $joinedForms, ?string $handle): array
    {
        $matchingColumnNames = [];
        foreach ($joinedForms as $form) {
            $field = $form->get($handle);
            if (!$field) {
                continue;
            }

            $tableName = 'fc'.$form->getId();
            $columnName = Submission::getFieldColumnName($field);

            $matchingColumnNames[] = "[[{$tableName}]].[[{$columnName}]]";
        }

        return $matchingColumnNames;
    }
}
