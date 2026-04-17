import { useEffect, useState, useCallback } from 'react';
import { Head, usePage } from '@inertiajs/react';
import {
    Box, Button, Dialog, DialogActions, DialogContent, DialogTitle,
    IconButton, InputAdornment, MenuItem, Stack, TextField, Typography,
} from '@mui/material';
import { DataGrid, GridColDef } from '@mui/x-data-grid';
import {
    Add, Edit, Delete, PersonAdd, Person, Email as EmailIcon,
    Phone, Home, LocationCity, Public, Notes, Search, Close, Save as SaveIcon,
} from '@mui/icons-material';
import { toast } from 'sonner';
import { api } from '@/lib/api';
import { dataGridHeight } from '@/lib/data-grid-height';
import { withDashFallback } from '@/lib/grid-utils';
import { useConfirm } from '@/components/confirm-provider';

type Customer = {
    id: number;
    code: string;
    full_name: string;
    first_name: string;
    last_name: string;
    email: string;
    phone: string | null;
    city: string | null;
    country: string | null;
    notes: string | null;
};

const empty = { first_name: '', last_name: '', email: '', phone: '', address: '', city: '', country: '', notes: '' };

type CountryOption = { value: string; label: string };

export default function CustomersIndex({ countries = [] }: { countries?: CountryOption[] }) {
    const page = usePage<{ auth: { permissions?: string[] } }>();
    const can = (p: string) => (page.props.auth?.permissions ?? []).includes(p);
    const confirm = useConfirm();

    const [rows, setRows] = useState<Customer[]>([]);
    const [total, setTotal] = useState(0);
    const [loading, setLoading] = useState(false);
    const [search, setSearch] = useState('');
    const [paginationModel, setPaginationModel] = useState({ page: 0, pageSize: 10 });

    const [open, setOpen] = useState(false);
    const [editing, setEditing] = useState<Customer | null>(null);
    const [form, setForm] = useState<Record<string, string>>(empty);
    const [errors, setErrors] = useState<Record<string, string[]>>({});
    const [saving, setSaving] = useState(false);

    const load = useCallback(async () => {
        setLoading(true);
        try {
            const params = new URLSearchParams({
                page: String(paginationModel.page + 1),
                per_page: String(paginationModel.pageSize),
                search,
            });
            const res = await api.get<{ data: Customer[]; meta: { total: number } }>(`/customers/data?${params}`);
            setRows(res.data);
            setTotal(res.meta.total);
        } finally {
            setLoading(false);
        }
    }, [paginationModel, search]);

    useEffect(() => { load(); }, [load]);

    const openCreate = () => { setEditing(null); setForm(empty); setErrors({}); setOpen(true); };
    const openEdit = (row: Customer) => {
        setEditing(row);
        setForm({ ...empty, ...Object.fromEntries(Object.entries(row).map(([k, v]) => [k, v ?? ''])) });
        setErrors({});
        setOpen(true);
    };

    const submit = async () => {
        setSaving(true);
        setErrors({});
        try {
            if (editing) {
                await api.put(`/customers/${editing.id}`, form);
                toast.success('Customer updated');
            } else {
                await api.post('/customers', form);
                toast.success('Customer created');
            }
            setOpen(false);
            load();
        } catch (e) {
            const err = e as { errors?: Record<string, string[]>; message?: string };
            if (err.errors) setErrors(err.errors);
            else toast.error(err.message ?? 'Request failed');
        } finally {
            setSaving(false);
        }
    };

    const remove = async (row: Customer) => {
        const ok = await confirm({
            title: 'Delete customer?',
            description: `This will permanently remove "${row.full_name}" (${row.code}).`,
            confirmText: 'Delete',
            tone: 'error',
        });
        if (!ok) return;
        try {
            await api.delete(`/customers/${row.id}`);
            toast.success(`Customer ${row.full_name} deleted`);
            load();
        } catch (e) {
            const err = e as { message?: string };
            toast.error(err.message ?? 'Delete failed');
        }
    };

    const countryLabel = (code: string | null) =>
        countries.find((c) => c.value === code)?.label ?? code;

    const columns: GridColDef<Customer>[] = [
        { field: 'code', headerName: 'Code', width: 130 },
        { field: 'full_name', headerName: 'Name', flex: 1, minWidth: 180 },
        { field: 'email', headerName: 'Email', flex: 1, minWidth: 200 },
        { field: 'phone', headerName: 'Phone', width: 150 },
        { field: 'city', headerName: 'City', width: 140 },
        { field: 'country', headerName: 'Country', width: 140, valueFormatter: (v) => countryLabel(v as string | null) },
        {
            field: 'actions', headerName: 'Actions', width: 130, sortable: false, filterable: false,
            renderCell: ({ row }) => (
                <Stack direction="row">
                    {can('customers.update') && <IconButton size="small" onClick={() => openEdit(row)}><Edit fontSize="small" /></IconButton>}
                    {can('customers.delete') && <IconButton size="small" color="error" onClick={() => remove(row)}><Delete fontSize="small" /></IconButton>}
                </Stack>
            ),
        },
    ];

    return (
        <>
            <Head title="Customers" />
            <Box sx={{ p: 3 }}>
                <Box sx={{ mb: 2, display: 'flex', alignItems: 'center', gap: 2 }}>
                    <Typography variant="h5" fontWeight={600}>Customers</Typography>
                    <Box sx={{ flexGrow: 1 }} />
                    {can('customers.create') && (
                        <Button startIcon={<Add />} variant="contained" onClick={openCreate}>New Customer</Button>
                    )}
                </Box>

                <TextField
                    size="small" placeholder="Search by name, email, code…"
                    value={search} onChange={(e) => setSearch(e.target.value)}
                    sx={{ mb: 2, width: 360 }}
                    slotProps={{ input: { startAdornment: <InputAdornment position="start"><Search fontSize="small" /></InputAdornment> } }}
                />

                <Box sx={{ height: dataGridHeight(paginationModel.pageSize) }}>
                    <DataGrid
                        rows={rows} columns={withDashFallback(columns)} loading={loading}
                        rowCount={total}
                        paginationMode="server"
                        paginationModel={paginationModel}
                        onPaginationModelChange={setPaginationModel}
                        pageSizeOptions={[10, 25, 50]}
                        disableRowSelectionOnClick
                    />
                </Box>
            </Box>

            <Dialog open={open} onClose={() => setOpen(false)} fullWidth maxWidth="sm">
                <DialogTitle>
                    <Stack direction="row" spacing={1} alignItems="center">
                        {editing ? <Edit color="primary" /> : <PersonAdd color="primary" />}
                        <span>{editing ? 'Edit Customer' : 'New Customer'}</span>
                    </Stack>
                </DialogTitle>
                <DialogContent>
                    <Stack spacing={2} sx={{ mt: 1 }}>
                        <TextField label="First name" value={form.first_name} onChange={(e) => setForm({ ...form, first_name: e.target.value })}
                            error={!!errors.first_name} helperText={errors.first_name?.[0]} required fullWidth
                            slotProps={{ input: { startAdornment: <InputAdornment position="start"><Person fontSize="small" /></InputAdornment> } }} />
                        <TextField label="Last name" value={form.last_name} onChange={(e) => setForm({ ...form, last_name: e.target.value })}
                            error={!!errors.last_name} helperText={errors.last_name?.[0]} required fullWidth
                            slotProps={{ input: { startAdornment: <InputAdornment position="start"><Person fontSize="small" /></InputAdornment> } }} />
                        <TextField label="Email" type="email" value={form.email} onChange={(e) => setForm({ ...form, email: e.target.value })}
                            error={!!errors.email} helperText={errors.email?.[0]} required fullWidth
                            slotProps={{ input: { startAdornment: <InputAdornment position="start"><EmailIcon fontSize="small" /></InputAdornment> } }} />
                        <TextField label="Phone" value={form.phone} onChange={(e) => setForm({ ...form, phone: e.target.value })} fullWidth
                            slotProps={{ input: { startAdornment: <InputAdornment position="start"><Phone fontSize="small" /></InputAdornment> } }} />
                        <TextField label="Address" value={form.address} onChange={(e) => setForm({ ...form, address: e.target.value })} fullWidth
                            slotProps={{ input: { startAdornment: <InputAdornment position="start"><Home fontSize="small" /></InputAdornment> } }} />
                        <Stack direction="row" spacing={2}>
                            <TextField label="City" value={form.city} onChange={(e) => setForm({ ...form, city: e.target.value })} fullWidth
                                slotProps={{ input: { startAdornment: <InputAdornment position="start"><LocationCity fontSize="small" /></InputAdornment> } }} />
                            <TextField label="Country" select value={form.country} onChange={(e) => setForm({ ...form, country: e.target.value })} fullWidth
                                slotProps={{ input: { startAdornment: <InputAdornment position="start"><Public fontSize="small" /></InputAdornment> } }}>
                                <MenuItem value="">— None —</MenuItem>
                                {countries.map((c) => <MenuItem key={c.value} value={c.value}>{c.label}</MenuItem>)}
                            </TextField>
                        </Stack>
                        <TextField label="Notes" value={form.notes} onChange={(e) => setForm({ ...form, notes: e.target.value })} multiline rows={2} fullWidth
                            slotProps={{ input: { startAdornment: <InputAdornment position="start" sx={{ alignSelf: 'flex-start', mt: 1.5 }}><Notes fontSize="small" /></InputAdornment> } }} />
                    </Stack>
                </DialogContent>
                <DialogActions>
                    <Button onClick={() => setOpen(false)} startIcon={<Close />}>Cancel</Button>
                    <Button onClick={submit} variant="contained" disabled={saving} startIcon={<SaveIcon />}>Save</Button>
                </DialogActions>
            </Dialog>
        </>
    );
}
