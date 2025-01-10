import { useEffect } from 'react';

const setSelected = (element: HTMLLIElement, selected: boolean): void => {
  const child = <HTMLDivElement>element.children[0];

  if (selected) {
    child.classList.add('sel');
  } else {
    child.classList.remove('sel');
  }
};

export const useSidebarSelect = (urlPart: string): void => {
  const navItems = document.querySelectorAll<HTMLLIElement>(
    '#nav-freeform > ul > li'
  );

  useEffect(() => {
    navItems.forEach((item) => {
      const url = item.querySelector('a.sidebar-action')?.getAttribute('href');
      setSelected(item, url?.includes(urlPart));
    });

    return () => {
      navItems.forEach((item) => {
        setSelected(item, false);
      });

      setSelected(navItems[0], true);
    };
  }, [urlPart]);
};
