import Column from './Column.js';
import ColumnsViewModel from './ColumnsViewModel.js';

export function initColumnist() {
  const columns = [];

  for (let i = 0; i < window.columnData.length; i++) {
    const column = window.columnData[i];

    columns.push(
      new Column(column[0], column[1], column[2], column[3], column[4]),
    );
  }

  window.viewModel = new ColumnsViewModel(columns);
}
