import type { GridColDef, GridValidRowModel } from '@mui/x-data-grid';

export const DASH = '—';

const isEmpty = (v: unknown): boolean =>
    v === null || v === undefined || (typeof v === 'string' && v.trim() === '');

/**
 * Ensures every column renders a dash ("—") when its value is null/undefined/empty.
 * Columns with `renderCell` are left alone (their own JSX decides what to render).
 * Columns with an existing `valueFormatter` have their output wrapped.
 */
export function withDashFallback<R extends GridValidRowModel>(
    columns: GridColDef<R>[],
): GridColDef<R>[] {
    return columns.map((col) => {
        if (col.renderCell) return col;
        const prev = col.valueFormatter;
        return {
            ...col,
            valueFormatter: prev
                ? (value, row, column, apiRef) => {
                    const out = prev(value, row, column, apiRef);
                    return isEmpty(out) ? DASH : out;
                  }
                : (value) => (isEmpty(value) ? DASH : (value as string | number)),
        };
    });
}
