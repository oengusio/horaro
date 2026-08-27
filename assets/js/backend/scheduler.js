import Item from './Item.js';
import ItemsViewModel from './ItemsViewModel.js';

export function initScheduler() {
  const items = [];

  for (let i = 0; i < itemData.length; i++) {
    const item = itemData[i];

    items.push(
      new Item(item[0], item[1], item[2], i + 1),
    );
  }

  window.viewModel = new ItemsViewModel(items);
}
