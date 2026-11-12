const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');
const { test } = require('node:test');
const { createHash } = require('node:crypto');

const blockDir = path.resolve(__dirname, '..');
const bundle = fs.readFileSync(path.join(blockDir, 'index.js'), 'utf8');

function editor(galleryID = '', galleries = [{ id: '123', name: 'First' }, { id: '456', name: 'Second' }]) {
  let block;
  const context = {
    wp: {
      element: { createElement: (type, props, ...children) => ({ type, props: props || {}, children }) },
      blocks: { registerBlockType: (name, settings) => {
        assert.equal(name, 'maxgalleria/gallery');
        block = settings;
      } },
      i18n: { __: text => text }
    },
    mg_block_info: { galleries }
  };
  // Intentionally omit a global event: React supplies the change event argument.
  vm.runInNewContext(bundle, context);
  const attributes = { galleryID };
  const props = {
    attributes,
    className: 'wp-block-maxgalleria-gallery',
    setAttributes: changes => Object.assign(attributes, changes)
  };
  return { block, attributes, props, render: () => block.edit(props) };
}

test('an unconfigured block selects the disabled placeholder', () => {
  const { block, render } = editor();
  assert.equal(block.attributes.galleryID.default, '');
  assert.equal(block.attributes.galleryID.type, 'string');
  assert.equal(block.attributes.galleryID.source, undefined);
  const select = render().children[1];
  assert.equal(select.type, 'select');
  assert.equal(select.props.value, '');
  assert.equal(select.children[0].props.value, '');
  assert.equal(select.children[0].props.disabled, true);
  assert.equal(select.children[0].children[0], 'Select a gallery');
  assert.ok(select.children[1].every(option => !('selected' in option.props)));
});

test('selection and switching update attributes, the controlled value, and the shortcode', () => {
  const { block, props, attributes, render } = editor();
  for (const id of ['456', '123']) {
    render().children[1].props.onChange({ target: { value: id } });
    assert.equal(attributes.galleryID, id);
    assert.equal(render().children[1].props.value, id);
    const saved = block.save(props);
    assert.equal(saved.props.className, 'wp-block-maxgalleria-gallery');
    assert.equal(saved.children[0], `[maxgallery id="${id}"]`);
  }
});

test('reopening saved attributes restores the dropdown and preserves valid saved markup', () => {
  const first = editor();
  first.render().children[1].props.onChange({ target: { value: '456' } });
  const savedAttributes = JSON.parse(JSON.stringify(first.attributes));
  const reopened = editor(savedAttributes.galleryID);
  assert.equal(reopened.render().children[1].props.value, '456');
  const saved = reopened.block.save(reopened.props);
  assert.equal(`<div class="${saved.props.className}">${saved.children[0]}</div>`,
    '<div class="wp-block-maxgalleria-gallery">[maxgallery id="456"]</div>');
});

test('an empty gallery list displays the empty message and preserves existing IDs', () => {
  for (const id of ['', '123']) {
    const { block, props, attributes, render } = editor(id, []);
    const message = render().children[1];
    assert.equal(message.type, 'div');
    assert.equal(message.children[0], 'No galleries found');
    assert.equal(attributes.galleryID, id);
    assert.equal(block.save(props).children[0], `[maxgallery id="${id}"]`);
  }
});

test('asset metadata declares runtime dependencies and versions the shipped bytes', () => {
  const asset = fs.readFileSync(path.join(blockDir, 'index.asset.php'), 'utf8');
  for (const dependency of ['wp-blocks', 'wp-element', 'wp-i18n']) {
    assert.ok(asset.includes(`'${dependency}'`));
  }
  assert.ok(asset.includes(`'version' => '${createHash('md5').update(bundle).digest('hex')}'`));
});
