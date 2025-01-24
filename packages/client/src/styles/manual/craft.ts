import { createGlobalStyle } from 'styled-components';

const style = createGlobalStyle`
  #main-content {
    padding: 0;
  }

  footer#global-footer {
    display: none;
  }

  ul#crumb-list {
    li.crumb {
      > button {
        z-index: 2;
      }

      &:after {
        z-index: 1;
      }
    }
  }
`;

export default style;
