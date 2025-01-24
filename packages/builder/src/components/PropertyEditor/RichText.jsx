import CompressIcon from '@ff/builder/assets/icons/compress-solid.svg';
import ExpandIcon from '@ff/builder/assets/icons/expand-solid.svg';
import PropTypes from 'prop-types';
import React from 'react';
import ReactQuill from 'react-quill-new';
import BasePropertyEditor from './BasePropertyEditor';
import TextProperty from './PropertyItems/TextProperty';
import { Button } from './Html';

export default class RichText extends BasePropertyEditor {
  static contextTypes = {
    ...BasePropertyEditor.contextTypes,
    hash: PropTypes.string.isRequired,
    properties: PropTypes.shape({
      type: PropTypes.string.isRequired,
      label: PropTypes.string.isRequired,
      value: PropTypes.string.isRequired,
    }).isRequired,
  };

  modules = {
    clipboard: {
      matchVisual: false,
    },
    toolbar: [
      [
        { header: [1, 2, 3, false] },
        'bold',
        'italic',
        'underline',
        'link',
        { list: 'ordered' },
        { list: 'bullet' },
        'clean',
      ],
    ],
  };

  constructor(props, context) {
    super(props, context);

    this.state = { fullscreen: false };
    this.editor = React.createRef();
  }

  render() {
    const { fullscreen } = this.state;
    const {
      hash,
      properties: { value },
    } = this.context;

    const style = !fullscreen
      ? { height: 200, marginBottom: 40 }
      : {
          width: 'auto',
          height: 'calc(100% - 42px)',
          position: 'absolute',
          zIndex: 1,
          top: 0,
          left: 0,
          right: 0,
          bottom: 0,
        };

    return (
      <div>
        <TextProperty
          label="Hash"
          instructions="Used to access this field on the frontend."
          name="handle"
          value={hash}
          className="code"
          readOnly={true}
        />

        <hr />

        <ReactQuill
          ref={this.editor}
          style={style}
          value={value}
          onChange={this.updateValue}
          theme="snow"
          modules={this.modules}
        />

        <Button
          className={`btn ${fullscreen && 'fullscreen'}`}
          onClick={() => {
            this.setState({ fullscreen: !fullscreen });
          }}
        >
          {fullscreen ? <CompressIcon /> : <ExpandIcon />}
          <span style={{ paddingLeft: 5 }}>{fullscreen ? 'Exit Fullscreen mode' : 'Edit in Fullscreen mode'}</span>
        </Button>
      </div>
    );
  }

  /**
   * Custom value update handler for ACE editor
   *
   * @param value
   */
  updateValue = (value) => {
    const { updateField } = this.context;

    console.log(value);

    updateField({
      value: value,
    });
  };
}
