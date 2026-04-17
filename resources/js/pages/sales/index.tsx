import { useEffect, useState, useCallback } from 'react';
import { Head, Link, usePage } from '@inertiajs/react';
import { Box, Button, Chip, InputAdornment, MenuItem, Stack, TextField, Typography } from '@mui/material';
import { DataGrid, GridColDef } from '@mui/x-data-grid';
import { Add, Visibility, Search, FilterAlt } from '@mui/icons-material';
import { api } from '@/lib/api';
import { dataGridHeight } from '@/lib/data-grid-height';
import { withDashFallback } from '@/lib/grid-utils';
import { CustomerFilter, type CustomerOption } from '@/components/customer-filter';

type Sale = {
    id: number; reference: string; status: string; status_label: string;
    total: number; source: string; created_at: string;
    customer?: { id: number; name: string };
    cashier?: { id: number; name: string };
};

const statusColor = (s: string) =>
    s === 'paid' ? 'success' : s === 'pending' ? 'warning' : s === 'refunded' ? 'info' : 'default';

type Props = {
    statuses: Record<string, string>;
    tenantScoped: boolean;
    customers: CustomerOption[];
};

export default function SalesIndex({ statuses, tenantScoped, customers }: Props) {
    const page = usePage<{ auth: { permissions?: string[] } }>();
    const can = (p: string) => (page.props.auth?.permissions ?? []).includes(p);

    const [rows, setRows] = useState<Sale[]>([]);
    const [total, setTotal] = useState(0);
    const [loading, setLoading] = useState(false);
    const [search, setSearch] = useState('');
    const [status, setStatus] = useState('');
    const [customerId, setCustomerId] = useState<number | null>(null);
    const [paginationModel, setPaginationModel] = useState({ page: 0, pageSize: 10 });

    const load = useCallback(async () => {
        setLoading(true);
        try {
            const params = new URLSearchParams({
                page: String(paginationModel.page + 1), per_page: String(paginationModel.pageSize),
                search, status,
            });
            if (customerId) params.set('customer_id', String(customerId));
            const res = await api.get<{ data: Sale[]; meta: { total: number } }>(`/sales/data?${params}`);
            setRows(res.data); setTotal(res.meta.total);
        } finally { setLoading(false); }
    }, [paginationModel, search, status, customerId]);

    useEffect(() => { load(); }, [load]);

    const columns: GridColDef<Sale>[] = [
        { field: 'reference', headerName: 'Reference', width: 160 },
        { field: 'created_at', headerName: 'Date', width: 180, valueFormatter: (v) => v ? new Date(v as string).toLocaleString() : '' },
        { field: 'customer', headerName: 'Customer', flex: 1, minWidth: 180, valueGetter: (_, row) => row.customer?.name },
        { field: 'cashier', headerName: 'Cashier', width: 160, valueGetter: (_, row) => row.cashier?.name },
        { field: 'total', headerName: 'Total', width: 120, valueFormatter: (v) => '$' + Number(v).toFixed(2) },
        { field: 'status', headerName: 'Status', width: 120, renderCell: ({ row }) => <Chip size="small" color={statusColor(row.status) as any} label={row.status_label} /> },
        { field: 'source', headerName: 'Source', width: 90, renderCell: ({ value }) => <Chip size="small" variant="outlined" label={String(value)} /> },
        {
            field: 'actions', headerName: '', width: 80, sortable: false, filterable: false,
            renderCell: ({ row }) => (
                <Link href={`/sales/${row.id}`}><Visibility fontSize="small" /></Link>
            ),
        },
    ];

    return (
        <>
            <Head title="Sales" />
            <Box sx={{ p: 3 }}>
                <Box sx={{ mb: 2, display: 'flex', alignItems: 'center', gap: 2 }}>
                    <Typography variant="h5" fontWeight={600}>Sales</Typography>
                    <Box sx={{ flexGrow: 1 }} />
                    {can('sales.create') && (
                        <Button component={Link} href="/sales/create" startIcon={<Add />} variant="contained">New Sale</Button>
                    )}
                </Box>

                <Stack direction="row" spacing={2} sx={{ mb: 2 }}>
                    <TextField size="small" placeholder="Search by reference…" value={search} onChange={(e) => setSearch(e.target.value)} sx={{ width: 280 }}
                        slotProps={{ input: { startAdornment: <InputAdornment position="start"><Search fontSize="small" /></InputAdornment> } }} />
                    <CustomerFilter options={customers} value={customerId} onChange={setCustomerId} locked={tenantScoped} />
                    <TextField size="small" select label="Status" value={status} onChange={(e) => setStatus(e.target.value)} sx={{ width: 180 }}
                        slotProps={{ input: { startAdornment: <InputAdornment position="start"><FilterAlt fontSize="small" /></InputAdornment> } }}>
                        <MenuItem value="">All</MenuItem>
                        {Object.entries(statuses).map(([v, l]) => <MenuItem key={v} value={v}>{l}</MenuItem>)}
                    </TextField>
                </Stack>

                <Box sx={{ height: dataGridHeight(paginationModel.pageSize) }}>
                    <DataGrid rows={rows} columns={withDashFallback(columns)} loading={loading} rowCount={total}
                        paginationMode="server" paginationModel={paginationModel} onPaginationModelChange={setPaginationModel}
                        pageSizeOptions={[10, 25, 50]} disableRowSelectionOnClick />
                </Box>
            </Box>
        </>
    );
}
