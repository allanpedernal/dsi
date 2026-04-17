import { useEffect, useState, useCallback } from 'react';
import { Head } from '@inertiajs/react';
import { Box, Button, Card, CardContent, InputAdornment, MenuItem, Stack, TextField, Typography } from '@mui/material';
import { DataGrid, GridColDef } from '@mui/x-data-grid';
import { DatePicker } from '@mui/x-date-pickers';
import { Print, Download, FilterAlt } from '@mui/icons-material';
import dayjs, { Dayjs } from 'dayjs';
import { api } from '@/lib/api';
import { dataGridHeight } from '@/lib/data-grid-height';
import { withDashFallback } from '@/lib/grid-utils';
import { CustomerFilter, type CustomerOption } from '@/components/customer-filter';

type Sale = { id: number; reference: string; total: number; status_label: string; created_at: string; customer?: { name: string }; cashier?: { name: string } };
type Aggregate = { count: number; subtotal: number; tax: number; discount: number; total: number };

type Props = { tenantScoped: boolean; customers: CustomerOption[] };

export default function ReportsSales({ tenantScoped, customers }: Props) {
    const [from, setFrom] = useState<Dayjs | null>(dayjs().subtract(30, 'day'));
    const [to, setTo] = useState<Dayjs | null>(dayjs());
    const [status, setStatus] = useState('');
    const [customerId, setCustomerId] = useState<number | null>(null);

    const [rows, setRows] = useState<Sale[]>([]);
    const [aggregate, setAggregate] = useState<Aggregate | null>(null);
    const [loading, setLoading] = useState(false);
    const [paginationModel, setPaginationModel] = useState({ page: 0, pageSize: 10 });
    const [total, setTotal] = useState(0);

    const buildParams = () => {
        const p = new URLSearchParams();
        if (from) p.set('from', from.format('YYYY-MM-DD'));
        if (to) p.set('to', to.format('YYYY-MM-DD'));
        if (status) p.set('status', status);
        if (customerId) p.set('customer_id', String(customerId));
        return p;
    };

    const load = useCallback(async () => {
        setLoading(true);
        try {
            const p = buildParams();
            p.set('page', String(paginationModel.page + 1));
            p.set('per_page', String(paginationModel.pageSize));
            const res = await api.get<{ data: { data: Sale[]; meta: { total: number }; aggregate: Aggregate } }>(`/reports/sales/data?${p}`);
            setRows(res.data.data);
            setTotal(res.data.meta.total);
            setAggregate(res.data.aggregate);
        } finally { setLoading(false); }
    }, [from, to, status, customerId, paginationModel]);

    useEffect(() => { load(); }, [load]);

    const downloadUrl = (kind: 'pdf' | 'excel', format?: string) => {
        const p = buildParams();
        if (format) p.set('format', format);
        return `/reports/sales/${kind}?${p}`;
    };

    const columns: GridColDef<Sale>[] = [
        { field: 'reference', headerName: 'Reference', width: 160 },
        { field: 'created_at', headerName: 'Date', width: 180, valueFormatter: (v) => v ? new Date(v as string).toLocaleString() : '' },
        { field: 'customer', headerName: 'Customer', flex: 1, valueGetter: (_, row) => row.customer?.name },
        { field: 'cashier', headerName: 'Cashier', width: 160, valueGetter: (_, row) => row.cashier?.name },
        { field: 'status_label', headerName: 'Status', width: 120 },
        { field: 'total', headerName: 'Total', width: 130, valueFormatter: (v) => '$' + Number(v).toFixed(2) },
    ];

    return (
        <>
            <Head title="Sales Report" />
            <Box sx={{ p: 3 }}>
                <Typography variant="h5" fontWeight={600} sx={{ mb: 2 }}>Sales Report</Typography>

                <Card sx={{ mb: 2 }}>
                    <CardContent>
                        <Stack direction={{ xs: 'column', md: 'row' }} spacing={2} alignItems="center">
                            <DatePicker label="From" value={from} onChange={setFrom} slotProps={{ textField: { size: 'small' } }} />
                            <DatePicker label="To" value={to} onChange={setTo} slotProps={{ textField: { size: 'small' } }} />
                            <TextField
                                size="small"
                                select
                                label="Status"
                                value={status}
                                onChange={(e) => setStatus(e.target.value)}
                                sx={{ width: 160 }}
                                slotProps={{ input: { startAdornment: <InputAdornment position="start"><FilterAlt fontSize="small" /></InputAdornment> } }}
                            >
                                <MenuItem value="">All</MenuItem>
                                <MenuItem value="paid">Paid</MenuItem>
                                <MenuItem value="pending">Pending</MenuItem>
                                <MenuItem value="refunded">Refunded</MenuItem>
                                <MenuItem value="cancelled">Cancelled</MenuItem>
                            </TextField>
                            <CustomerFilter options={customers} value={customerId} onChange={setCustomerId} locked={tenantScoped} />
                            <Box sx={{ flex: 1 }} />
                            <Button startIcon={<Print />} variant="outlined" component="a" href={downloadUrl('pdf')} target="_blank">Print PDF</Button>
                            <Button startIcon={<Download />} variant="outlined" component="a" href={downloadUrl('excel', 'xlsx')}>XLSX</Button>
                            <Button startIcon={<Download />} variant="outlined" component="a" href={downloadUrl('excel', 'csv')}>CSV</Button>
                        </Stack>
                    </CardContent>
                </Card>

                {aggregate && (
                    <Stack direction="row" spacing={2} sx={{ mb: 2 }}>
                        <Card sx={{ flex: 1 }}><CardContent><Typography variant="overline">Orders</Typography><Typography variant="h5">{aggregate.count}</Typography></CardContent></Card>
                        <Card sx={{ flex: 1 }}><CardContent><Typography variant="overline">Subtotal</Typography><Typography variant="h5">${aggregate.subtotal.toFixed(2)}</Typography></CardContent></Card>
                        <Card sx={{ flex: 1 }}><CardContent><Typography variant="overline">Tax</Typography><Typography variant="h5">${aggregate.tax.toFixed(2)}</Typography></CardContent></Card>
                        <Card sx={{ flex: 1 }}><CardContent><Typography variant="overline">Total</Typography><Typography variant="h5">${aggregate.total.toFixed(2)}</Typography></CardContent></Card>
                    </Stack>
                )}

                <Box sx={{ height: dataGridHeight(paginationModel.pageSize) }}>
                    <DataGrid rows={rows} columns={withDashFallback(columns)} loading={loading} rowCount={total}
                        paginationMode="server" paginationModel={paginationModel} onPaginationModelChange={setPaginationModel}
                        pageSizeOptions={[10, 25, 50, 100]} disableRowSelectionOnClick />
                </Box>
            </Box>
        </>
    );
}
