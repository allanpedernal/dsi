import { useEffect, useState, useCallback } from 'react';
import { Head } from '@inertiajs/react';
import {
    Box, Chip, Dialog, DialogContent, DialogTitle, Divider, IconButton, InputAdornment,
    MenuItem, Paper, Stack, Table, TableBody, TableCell, TableHead, TableRow,
    TextField, Typography,
} from '@mui/material';
import { DataGrid, GridColDef } from '@mui/x-data-grid';
import {
    Visibility, Search, FilterAlt, Category as CategoryIcon,
    Fingerprint, Computer, ArrowForward,
} from '@mui/icons-material';
import { api } from '@/lib/api';
import { dataGridHeight } from '@/lib/data-grid-height';
import { withDashFallback } from '@/lib/grid-utils';
import { CustomerFilter, type CustomerOption } from '@/components/customer-filter';

type Activity = {
    id: number;
    description: string;
    log_name: string;
    event: string;
    source: string | null;
    source_label: string | null;
    request_id: string | null;
    ip_address: string | null;
    user_agent: string | null;
    causer: { id: number; name: string; email: string } | null;
    changes: Record<string, unknown> | null;
    created_at: string;
};

const sourceColor = (s: string | null): 'primary' | 'secondary' | 'default' | 'warning' =>
    s === 'web' ? 'primary' : s === 'api' ? 'secondary' : s === 'console' ? 'warning' : 'default';

type Props = { tenantScoped: boolean; customers: CustomerOption[] };

export default function AuditLogIndex({ tenantScoped, customers }: Props) {
    const [rows, setRows] = useState<Activity[]>([]);
    const [total, setTotal] = useState(0);
    const [loading, setLoading] = useState(false);
    const [search, setSearch] = useState('');
    const [source, setSource] = useState('');
    const [logName, setLogName] = useState('');
    const [customerId, setCustomerId] = useState<number | null>(null);
    const [paginationModel, setPaginationModel] = useState({ page: 0, pageSize: 10 });
    const [open, setOpen] = useState<Activity | null>(null);

    const load = useCallback(async () => {
        setLoading(true);
        try {
            const params = new URLSearchParams({
                page: String(paginationModel.page + 1), per_page: String(paginationModel.pageSize),
                search, source, log_name: logName,
            });
            if (customerId) params.set('customer_id', String(customerId));
            const res = await api.get<{ data: Activity[]; meta: { total: number } }>(`/audit-log/data?${params}`);
            setRows(res.data); setTotal(res.meta.total);
        } finally { setLoading(false); }
    }, [paginationModel, search, source, logName, customerId]);

    useEffect(() => { load(); }, [load]);

    const columns: GridColDef<Activity>[] = [
        { field: 'created_at', headerName: 'When', width: 160, valueFormatter: (v) => new Date(v as string).toLocaleString() },
        { field: 'description', headerName: 'Description', flex: 2, minWidth: 220 },
        { field: 'log_name', headerName: 'Type', width: 110, renderCell: ({ value }) => <Chip size="small" variant="outlined" label={String(value ?? '')} /> },
        { field: 'source', headerName: 'Source', width: 110, renderCell: ({ row }) => row.source ? <Chip size="small" color={sourceColor(row.source)} label={row.source_label ?? row.source} /> : null },
        { field: 'causer', headerName: 'By', flex: 1, minWidth: 100, valueGetter: (_, row) => row.causer?.name ?? 'System' },
        { field: 'ip_address', headerName: 'IP', width: 100 },
        {
            field: 'actions', headerName: '', width: 48, sortable: false, filterable: false,
            renderCell: ({ row }) => <IconButton size="small" onClick={() => setOpen(row)} title="View details"><Visibility fontSize="small" /></IconButton>,
        },
    ];

    return (
        <>
            <Head title="Audit Log" />
            <Box sx={{ p: 3 }}>
                <Typography variant="h5" fontWeight={600} sx={{ mb: 2 }}>Audit Log</Typography>

                <Stack direction="row" spacing={2} sx={{ mb: 2 }}>
                    <TextField size="small" placeholder="Search description…" value={search} onChange={(e) => setSearch(e.target.value)} sx={{ width: 280 }}
                        slotProps={{ input: { startAdornment: <InputAdornment position="start"><Search fontSize="small" /></InputAdornment> } }} />
                    <TextField size="small" select label="Source" value={source} onChange={(e) => setSource(e.target.value)} sx={{ width: 160 }}
                        slotProps={{ input: { startAdornment: <InputAdornment position="start"><FilterAlt fontSize="small" /></InputAdornment> } }}>
                        <MenuItem value="">All</MenuItem>
                        <MenuItem value="web">Web App</MenuItem>
                        <MenuItem value="api">API</MenuItem>
                        <MenuItem value="console">Console</MenuItem>
                        <MenuItem value="system">System</MenuItem>
                    </TextField>
                    <TextField size="small" select label="Type" value={logName} onChange={(e) => setLogName(e.target.value)} sx={{ width: 160 }}
                        slotProps={{ input: { startAdornment: <InputAdornment position="start"><CategoryIcon fontSize="small" /></InputAdornment> } }}>
                        <MenuItem value="">All</MenuItem>
                        <MenuItem value="customer">Customer</MenuItem>
                        <MenuItem value="product">Product</MenuItem>
                        <MenuItem value="sale">Sale</MenuItem>
                        <MenuItem value="user">User</MenuItem>
                    </TextField>
                    <CustomerFilter options={customers} value={customerId} onChange={setCustomerId} locked={tenantScoped} />
                </Stack>

                <Box sx={{ height: dataGridHeight(paginationModel.pageSize) }}>
                    <DataGrid rows={rows} columns={withDashFallback(columns)} loading={loading} rowCount={total}
                        paginationMode="server" paginationModel={paginationModel} onPaginationModelChange={setPaginationModel}
                        pageSizeOptions={[10, 25, 50, 100]} disableRowSelectionOnClick />
                </Box>
            </Box>

            <Dialog open={!!open} onClose={() => setOpen(null)} fullWidth maxWidth="md">
                <DialogTitle>
                    <Stack direction="row" spacing={1} alignItems="center">
                        <Visibility color="primary" />
                        <span>Audit Detail</span>
                    </Stack>
                </DialogTitle>
                <DialogContent>
                    {open && <AuditDetail activity={open} />}
                </DialogContent>
            </Dialog>
        </>
    );
}

type Changes = {
    attributes?: Record<string, unknown>;
    old?: Record<string, unknown>;
} | Record<string, unknown> | null;

function isDiffShape(c: Changes): c is { attributes?: Record<string, unknown>; old?: Record<string, unknown> } {
    if (!c || typeof c !== 'object') return false;
    return 'attributes' in c || 'old' in c;
}

const renderValue = (v: unknown): string => {
    if (v === null || v === undefined) return '—';
    if (typeof v === 'object') return JSON.stringify(v);
    return String(v);
};

function AuditDetail({ activity }: { activity: Activity }) {
    const changes = activity.changes as Changes;
    let rows: { field: string; from: unknown; to: unknown }[] = [];

    if (isDiffShape(changes)) {
        const keys = new Set<string>([
            ...Object.keys(changes.old ?? {}),
            ...Object.keys(changes.attributes ?? {}),
        ]);
        rows = Array.from(keys).map((field) => ({
            field,
            from: changes.old?.[field],
            to: changes.attributes?.[field],
        }));
    } else if (changes && typeof changes === 'object') {
        rows = Object.entries(changes as Record<string, unknown>).map(([field, to]) => ({
            field, from: undefined, to,
        }));
    }

    const hidden = ['password', 'remember_token', 'two_factor_secret', 'two_factor_recovery_codes'];
    rows = rows.map((r) => hidden.includes(r.field) ? { ...r, from: r.from !== undefined ? '••••••' : r.from, to: r.to !== undefined ? '••••••' : r.to } : r);

    return (
        <Stack spacing={2}>
            <Box>
                <Typography variant="body1" fontWeight={500}>{activity.description}</Typography>
                <Stack direction="row" spacing={1} sx={{ mt: 0.5 }} flexWrap="wrap" useFlexGap>
                    <Chip size="small" label={new Date(activity.created_at).toLocaleString()} />
                    <Chip size="small" variant="outlined" label={`${activity.log_name} · ${activity.event}`} />
                    {activity.source && <Chip size="small" color={activity.source === 'api' ? 'secondary' : 'primary'} label={activity.source_label ?? activity.source} />}
                    {activity.causer && <Chip size="small" variant="outlined" label={`by ${activity.causer.name}`} />}
                </Stack>
            </Box>

            <Divider />

            <Stack direction={{ xs: 'column', sm: 'row' }} spacing={2}>
                <Stack direction="row" spacing={1} alignItems="center">
                    <Fingerprint fontSize="small" color="action" />
                    <Typography variant="caption" color="text.secondary" sx={{ wordBreak: 'break-all' }}>
                        {activity.request_id ?? '—'}
                    </Typography>
                </Stack>
                <Stack direction="row" spacing={1} alignItems="center">
                    <Computer fontSize="small" color="action" />
                    <Typography variant="caption" color="text.secondary">
                        {activity.ip_address ?? '—'}
                    </Typography>
                </Stack>
            </Stack>

            {activity.user_agent && (
                <Typography variant="caption" color="text.secondary" sx={{ display: 'block' }}>
                    {activity.user_agent}
                </Typography>
            )}

            <Divider />

            <Typography variant="subtitle2">Changes</Typography>

            {rows.length === 0 ? (
                <Typography variant="body2" color="text.secondary">No attribute changes recorded.</Typography>
            ) : (
                <Paper variant="outlined">
                    <Table size="small">
                        <TableHead>
                            <TableRow>
                                <TableCell sx={{ fontWeight: 600, width: '25%' }}>Field</TableCell>
                                <TableCell sx={{ fontWeight: 600 }}>Before</TableCell>
                                <TableCell width={32} />
                                <TableCell sx={{ fontWeight: 600 }}>After</TableCell>
                            </TableRow>
                        </TableHead>
                        <TableBody>
                            {rows.map((r) => (
                                <TableRow key={r.field}>
                                    <TableCell><code>{r.field}</code></TableCell>
                                    <TableCell sx={{ color: 'error.main', wordBreak: 'break-all' }}>{renderValue(r.from)}</TableCell>
                                    <TableCell><ArrowForward fontSize="small" color="action" /></TableCell>
                                    <TableCell sx={{ color: 'success.main', wordBreak: 'break-all' }}>{renderValue(r.to)}</TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                </Paper>
            )}
        </Stack>
    );
}
