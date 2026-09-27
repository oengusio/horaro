import $ from 'jquery';

export default class SpatialNavigation {
  #root;
  #codes = {
    KEY_LEFT: 37,
    KEY_UP: 38,
    KEY_RIGHT: 39,
    KEY_DOWN: 40,
  };

  #addBtn;

  constructor(root) {
    console.log(root);

    this.#root = root;
    this.#addBtn = $('#h-add-model');

    root.on('keydown', (e) => {
      const target = $(e.target);

      console.log(target);

      // do nothing on elements we don't care about
      if (!target.is('.editable') && !target.is('.h-co button')) {

        return;
      }

      let interesting = false;

      for (const c in this.#codes) {
        if (this.#codes[c] === e.keyCode) {
          e.preventDefault();
          e.stopPropagation();
          interesting = true;
          break;
        }
      }

      if (!interesting) {
        return;
      }

      const row = target.closest('tbody');
      const rows = root.find('tbody');
      let nodes = row.find('.h-primary a:visible, .h-primary .h-co button:visible');
      const x = nodes.index(target);
      const y = rows.index(row);
      const maxX = nodes.length - 1;
      const maxY = rows.length - 1;
      let newX = x;
      let newY = y;

      switch (e.keyCode) {
        case this.#codes.KEY_RIGHT:
          newX++;
          break;
        case this.#codes.KEY_DOWN:
          newY++;
          break;
        case this.#codes.KEY_LEFT:
          newX--;
          break;
        case this.#codes.KEY_UP:
          newY--;
          break;
      }

      // focus the add button when pressing down in the last row
      if (newY > maxY) {
        console.log('focust next button');
        this.#addBtn.focus();
        return;
      }

      if (newX > maxX) {
        return;
      }

      if (newY !== y) {
        nodes = $(rows[newY]).find('.h-primary a:visible, .h-primary button:visible');
      }

      $(nodes[newX]).focus();
    });

    this.#initUpNavigation();
  }

  #initUpNavigation() {
    $('body').on('keydown', '#h-add-model', (e) => {
      if (e.keyCode !== this.#codes.KEY_UP) {
        return false;
      }

      e.preventDefault();
      e.stopPropagation();

      const row = this.#root.find('tbody:last');

      if (row.length > 0) {
        row.find('a:visible:first').focus();
      }
    });
  }
}
