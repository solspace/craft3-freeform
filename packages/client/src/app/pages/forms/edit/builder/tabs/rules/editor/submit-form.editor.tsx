import React from 'react';
import { useSelector } from 'react-redux';
import { useNavigate, useParams } from 'react-router-dom';
import { LoadingText } from '@components/loaders/loading-text/loading-text';
import { useAppDispatch } from '@editor/store';
import { submitFormRuleActions } from '@editor/store/slices/rules/submit-form';
import { submitFormRuleSelectors } from '@editor/store/slices/rules/submit-form/submit-form.selectors';
import { useQueryFormRules } from '@ff-client/queries/rules';
import translate from '@ff-client/utils/translations';

import { CombinatorSelect } from '../conditions/combinator/combinator';
import { ConditionTable } from '../conditions/table/condition-table';

import { Remove } from './remove-button/remove';
import { ConfigurationDescription, Label } from './editor.styles';
import { RulesEditorWrapper } from './field.editor.styles';

export const SubmitFormRulesEditor: React.FC = () => {
  const { formId } = useParams();
  const { isFetching } = useQueryFormRules(Number(formId || 0));

  const navigate = useNavigate();
  const dispatch = useAppDispatch();

  const rule = useSelector(submitFormRuleSelectors.one);

  if (!rule) {
    return (
      <RulesEditorWrapper>
        <Label>
          <LoadingText
            loadingText={translate('Loading data')}
            loading={isFetching}
          >
            {translate('Submit Form')}
          </LoadingText>
        </Label>
        {!isFetching && (
          <button
            className="btn add icon dashed"
            onClick={() => dispatch(submitFormRuleActions.add())}
          >
            {translate('Add rules')}
          </button>
        )}
      </RulesEditorWrapper>
    );
  }

  return (
    <RulesEditorWrapper>
      <Remove
        onClick={() => {
          dispatch(submitFormRuleActions.remove());
          navigate('..');
        }}
      />

      <Label>
        <LoadingText
          loadingText={translate('Loading data')}
          loading={isFetching}
        >
          {translate('Submit Form')}
        </LoadingText>
      </Label>
      {!isFetching && (
        <>
          <ConfigurationDescription>
            {translate('Submit this form when ')}

            <CombinatorSelect
              value={rule.combinator}
              onChange={(value) =>
                dispatch(submitFormRuleActions.modifyCombinator(value))
              }
            />

            {translate('of the following rules match:')}
          </ConfigurationDescription>

          <ConditionTable
            conditions={rule.conditions}
            onChange={(conditions) => {
              dispatch(submitFormRuleActions.modifyConditions(conditions));
            }}
          />
        </>
      )}
    </RulesEditorWrapper>
  );
};
