import type { Page, PageButton } from '@editor/builder/types/layout';

type ButtonGroups = Array<Array<PageButton>>;

export const getButtonGroups = (page: Page): ButtonGroups => {
  const layout = page.buttons?.layout || 'save back|submit';
  const groups = layout.split(' ');

  const buttonGroups: Array<Array<PageButton>> = [];
  groups.forEach((group) => {
    const buttons = group.split('|');
    const buttonGroup: PageButton[] = [];
    buttons.forEach((buttonHandle) => {
      if (buttonHandle === 'back' && page.order === 0) {
        return;
      }

      switch (buttonHandle) {
        case 'submit':
          buttonGroup.push({
            handle: 'submit',
            label: page.buttons.submitLabel,
            enabled: true,
          });

          break;

        case 'back':
          if (page.buttons.back) {
            buttonGroup.push({
              handle: 'back',
              label: page.buttons.backLabel,
              enabled: page.buttons.back,
            });
          }

          break;

        case 'save':
          if (page.buttons.save) {
            buttonGroup.push({
              handle: 'save',
              label: page.buttons.saveLabel,
              enabled: page.buttons.save,
            });
          }

          break;

        default:
          return;
      }
    });

    buttonGroups.push(buttonGroup);
  });

  return buttonGroups;
};
