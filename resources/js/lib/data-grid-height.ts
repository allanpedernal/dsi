const HEADER = 56;
const DEFAULT_ROW = 52;
const FOOTER = 52;
const MAX_ROWS_BEFORE_SCROLL = 10;

/**
 * Returns a px height such that up to MAX_ROWS_BEFORE_SCROLL rows fit without
 * scrolling, and larger pageSize values cause internal scrolling.
 *
 * Pass rowHeight override for grids that use compact or auto row heights.
 */
export function dataGridHeight(pageSize: number, rowHeight: number = DEFAULT_ROW): number {
    const rows = Math.min(pageSize, MAX_ROWS_BEFORE_SCROLL);
    return HEADER + rows * rowHeight + FOOTER;
}
