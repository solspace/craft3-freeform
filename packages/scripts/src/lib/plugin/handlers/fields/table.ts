import type Freeform from '@components/front-end/plugin/freeform';
import events from '@lib/plugin/constants/event-types';
import { removeElement } from '@lib/plugin/helpers/elements';
import type { FreeformHandler } from 'types/form';

class Table implements FreeformHandler {
  PATTERN = /([^[]+)\[(\d+)\](\[\d+\])$/g;
  ID_PATTERN = /^(labeled-.*)-(\d+)-(\d+)-(\d+)$/g;

  freeform: Freeform;

  addButton: HTMLButtonElement;

  constructor(freeform: Freeform) {
    this.freeform = freeform;
    this.reload();
  }

  reload = () => {
    const tables = this.freeform.form.querySelectorAll('[data-freeform-table]');
    tables.forEach((table) => {
      this.addButton = table.parentNode.querySelector<HTMLButtonElement>('[data-freeform-table-add-row]');

      const removeRowButtons = table.querySelectorAll('[data-freeform-table-remove-row]');
      for (let j = 0; j < removeRowButtons.length; j++) {
        const removeButton = removeRowButtons[j];
        removeButton.addEventListener('click', this.removeRow);
      }

      this.toggleAddButton(table);

      if (this.addButton) {
        const getNextMaxIndex = () => {
          const inputs = table.querySelectorAll<HTMLInputElement>('textarea, input, select');
          let maxIndex = 0;
          for (let i = 0; i < inputs.length; i++) {
            const input = inputs[i];
            const matches = this.PATTERN.exec(input.name);
            this.PATTERN.lastIndex = 0;
            if (!matches) {
              continue;
            }

            const index = parseInt(matches[2]);
            maxIndex = Math.max(maxIndex, index);
          }

          return ++maxIndex;
        };

        this.addButton.addEventListener('click', () => {
          const referenceRow = table.querySelector<HTMLTableRowElement>('tbody > tr:last-child');

          if (referenceRow) {
            const cloneRow = referenceRow.cloneNode(true) as HTMLTableRowElement;
            const inputs = cloneRow.querySelectorAll<HTMLInputElement>('textarea, input, select');
            const maxIndex = getNextMaxIndex();
            for (let i = 0; i < inputs.length; i++) {
              const item = inputs[i];
              let defaultValue = item.dataset.defaultValue || '';
              item.name = item.name.replace(this.PATTERN, `$1[${maxIndex}]$3`);
              if (item.tagName === 'SELECT') {
                const firstOption = item.querySelector<HTMLOptionElement>('option:first-child');
                if (firstOption) {
                  defaultValue = firstOption.value;
                }
              } else {
                item.checked = false;
              }

              if (item.id && this.ID_PATTERN.test(item.id)) {
                item.id = item.id.replace(this.ID_PATTERN, `$1-${maxIndex}-$3-$4`);
                const label = item.nextSibling as HTMLLabelElement;
                label.htmlFor = item.id;
              }

              item.value = defaultValue;
            }

            const removeRowButton = cloneRow.querySelector<HTMLButtonElement>('[data-freeform-table-remove-row]');
            if (removeRowButton) {
              removeRowButton.addEventListener('click', this.removeRow);
            }

            this.freeform._dispatchEvent(events.table.onAddRow, {
              table,
              row: cloneRow,
            });

            table.querySelector('tbody').appendChild(cloneRow);

            this.freeform._dispatchEvent(events.table.afterRowAdded, {
              table,
              row: cloneRow,
            });

            this.toggleAddButton(table);
          }
        });
      }
    });
  };

  removeRow = (event: Event) => {
    const target = event.target as HTMLTableRowElement;

    if (target.closest('tbody').querySelectorAll('tr').length === 1) {
      return;
    }

    const table = target.closest('table');
    const row = target.closest('tr');

    this.freeform._dispatchEvent(events.table.onRemoveRow, { table, row });
    removeElement(row);
    this.freeform._dispatchEvent(events.table.afterRemoveRow, { table });

    this.toggleAddButton(table);
  };

  toggleAddButton = (table: Element) => {
    const maxRows: number | string = table.getAttribute('data-max-rows');
    const totalRows = table.querySelectorAll<HTMLTableRowElement>('tbody > tr').length;

    if (maxRows && this.addButton) {
      this.addButton.style.display = totalRows >= parseInt(maxRows, 10) ? 'none' : 'block';
    }
  };
}

export default Table;
