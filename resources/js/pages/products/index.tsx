import { useEffect, useState, useCallback } from 'react';
import { Head, usePage } from '@inertiajs/react';
import {
    Box, Button, Chip, Dialog, DialogActions, DialogContent, DialogTitle,
    FormControlLabel, IconButton, InputAdornment, MenuItem, Stack, Switch, TextField, Typography,
} from '@mui/material';
import { DataGrid, GridColDef } from '@mui/x-data-grid';
import {
    Add, Edit, Delete, AddBox, QrCode, LocalOffer, Category as CategoryIcon,
    Description, AttachMoney, Inventory, Warning as WarningIcon, Search,
    Close, Save as SaveIcon,
} from '@mui/icons-material';
import { toast } from 'sonner';
import { api } from '@/lib/api';
import { dataGridHeight } from '@/lib/data-grid-height';
import { withDashFallback } from '@/lib/grid-utils';
import { useConfirm } from '@/components/confirm-provider';
import { CustomerFilter, type CustomerOption } from '@/components/customer-filter';

type Category = { id: number; name: string };
type Product = {
    id: number; sku: string; name: string; category: string | null; category_id: number | null;
    price: number; cost: number; stock: number; reorder_level: number; is_active: boolean; is_low_stock: boolean;
    description: string | null;
};

const empty = { sku: '', name: '', category_id: '', customer_id: '', description: '', price: '', cost: '', stock: '0', reorder_level: '10', is_active: true } as Record<string, any>;

type Props = {
    categories: Category[];
    tenantScoped: boolean;
    customers: CustomerOption[];
};

export default function ProductsIndex({ categories, tenantScoped, customers }: Props) {
    const page = usePage<{ auth: { permissions?: string[] } }>();
    const can = (p: string) => (page.props.auth?.permissions ?? []).includes(p);
    const confirm = useConfirm();

    const [rows, setRows] = useState<Product[]>([]);
    const [total, setTotal] = useState(0);
    const [loading, setLoading] = useState(false);
    const [search, setSearch] = useState('');
    const [customerId, setCustomerId] = useState<number | null>(null);
    const [paginationModel, setPaginationModel] = useState({ page: 0, pageSize: 10 });

    const [open, setOpen] = useState(false);
    const [editing, setEditing] = useState<Product | null>(null);
    const [form, setForm] = useState<Record<string, any>>(empty);
    const [errors, setErrors] = useState<Record<string, string[]>>({});
    const [saving, setSaving] = useState(false);

    const load = useCallback(async () => {
        setLoading(true);
        try {
            const params = new URLSearchParams({ page: String(paginationModel.page + 1), per_page: String(paginationModel.pageSize), search });
            if (customerId) params.set('customer_id', String(customerId));
            const res = await api.get<{ data: Product[]; meta: { total: number } }>(`/products/data?${params}`);
            setRows(res.data); setTotal(res.meta.total);
        } finally { setLoading(false); }
    }, [paginationModel, search, customerId]);

    useEffect(() => { load(); }, [load]);

    const openCreate = () => { setEditing(null); setForm(empty); setErrors({}); setOpen(true); };
    const openEdit = (row: Product) => {
        setEditing(row);
        setForm({
            sku: row.sku, name: row.name, category_id: row.category_id ?? '', description: row.description ?? '',
            price: row.price, cost: row.cost, stock: row.stock, reorder_level: row.reorder_level, is_active: row.is_active,
        });
        setErrors({}); setOpen(true);
    };

    const submit = async () => {
        setSaving(true); setErrors({});
        try {
            const payload = { ...form, category_id: form.category_id || null, customer_id: form.customer_id || null };
            if (editing) {
                await api.put(`/products/${editing.id}`, payload);
                toast.success('Product updated');
            } else {
                await api.post('/products', payload);
                toast.success('Product created');
            }
            setOpen(false); load();
        } catch (e) {
            const err = e as { errors?: Record<string, string[]>; message?: string };
            if (err.errors) setErrors(err.errors);
            else toast.error(err.message ?? 'Request failed');
        } finally { setSaving(false); }
    };

    const remove = async (row: Product) => {
        const ok = await confirm({
            title: 'Delete product?',
            description: `This will permanently remove "${row.name}" (${row.sku}).`,
            confirmText: 'Delete',
            tone: 'error',
        });
        if (!ok) return;
        try {
            await api.delete(`/products/${row.id}`);
            toast.success(`Product ${row.name} deleted`);
            load();
        } catch (e) {
            const err = e as { message?: string };
            toast.error(err.message ?? 'Delete failed');
        }
    };

    const columns: GridColDef<Product>[] = [
        { field: 'sku', headerName: 'SKU', width: 140 },
        { field: 'name', headerName: 'Name', flex: 1, minWidth: 180 },
        { field: 'category', headerName: 'Category', width: 140 },
        { field: 'price', headerName: 'Price', width: 110, valueFormatter: (v) => '$' + Number(v).toFixed(2) },
        {
            field: 'stock', headerName: 'Stock', width: 130,
            renderCell: ({ row }) => row.is_low_stock
                ? <Chip size="small" color="warning" label={`${row.stock} (low)`} />
                : <span>{row.stock}</span>,
        },
        { field: 'is_active', headerName: 'Active', width: 100, renderCell: ({ value }) => value ? <Chip size="small" color="success" label="Yes" /> : <Chip size="small" label="No" /> },
        {
            field: 'actions', headerName: 'Actions', width: 130, sortable: false, filterable: false,
            renderCell: ({ row }) => (
                <Stack direction="row">
                    {can('products.update') && <IconButton size="small" onClick={() => openEdit(row)}><Edit fontSize="small" /></IconButton>}
                    {can('products.delete') && <IconButton size="small" color="error" onClick={() => remove(row)}><Delete fontSize="small" /></IconButton>}
                </Stack>
            ),
        },
    ];

    return (
        <>
            <Head title="Products" />
            <Box sx={{ p: 3 }}>
                <Box sx={{ mb: 2, display: 'flex', alignItems: 'center', gap: 2 }}>
                    <Typography variant="h5" fontWeight={600}>Products</Typography>
                    <Box sx={{ flexGrow: 1 }} />
                    {can('products.create') && <Button startIcon={<Add />} variant="contained" onClick={openCreate}>New Product</Button>}
                </Box>

                <Stack direction="row" spacing={2} sx={{ mb: 2 }} alignItems="center">
                    <TextField size="small" placeholder="Search by SKU or name…" value={search} onChange={(e) => setSearch(e.target.value)} sx={{ width: 360 }}
                        slotProps={{ input: { startAdornment: <InputAdornment position="start"><Search fontSize="small" /></InputAdornment> } }} />
                    <CustomerFilter options={customers} value={customerId} onChange={setCustomerId} locked={tenantScoped} />
                </Stack>

                <Box sx={{ height: dataGridHeight(paginationModel.pageSize) }}>
                    <DataGrid rows={rows} columns={withDashFallback(columns)} loading={loading} rowCount={total}
                        paginationMode="server" paginationModel={paginationModel} onPaginationModelChange={setPaginationModel}
                        pageSizeOptions={[10, 25, 50]} disableRowSelectionOnClick />
                </Box>
            </Box>

            <Dialog open={open} onClose={() => setOpen(false)} fullWidth maxWidth="sm">
                <DialogTitle>
                    <Stack direction="row" spacing={1} alignItems="center">
                        {editing ? <Edit color="primary" /> : <AddBox color="primary" />}
                        <span>{editing ? 'Edit Product' : 'New Product'}</span>
                    </Stack>
                </DialogTitle>
                <DialogContent>
                    <Stack spacing={2} sx={{ mt: 1 }}>
                        <TextField label="SKU" value={form.sku} onChange={(e) => setForm({ ...form, sku: e.target.value })} error={!!errors.sku} helperText={errors.sku?.[0]} required fullWidth
                            slotProps={{ input: { startAdornment: <InputAdornment position="start"><QrCode fontSize="small" /></InputAdornment> } }} />
                        <TextField label="Name" value={form.name} onChange={(e) => setForm({ ...form, name: e.target.value })} error={!!errors.name} helperText={errors.name?.[0]} required fullWidth
                            slotProps={{ input: { startAdornment: <InputAdornment position="start"><LocalOffer fontSize="small" /></InputAdornment> } }} />
                        <TextField label="Category" select value={form.category_id} onChange={(e) => setForm({ ...form, category_id: e.target.value })} fullWidth
                            slotProps={{ input: { startAdornment: <InputAdornment position="start"><CategoryIcon fontSize="small" /></InputAdornment> } }}>
                            <MenuItem value="">— None —</MenuItem>
                            {categories.map((c) => <MenuItem key={c.id} value={c.id}>{c.name}</MenuItem>)}
                        </TextField>
                        {!tenantScoped && (
                            <TextField label="Customer (tenant)" select value={form.customer_id} onChange={(e) => setForm({ ...form, customer_id: e.target.value })} fullWidth
                                helperText="Leave blank for global/system-wide product">
                                <MenuItem value="">— System-wide —</MenuItem>
                                {customers.map((c) => <MenuItem key={c.id} value={c.id}>{c.label}</MenuItem>)}
                            </TextField>
                        )}
                        <TextField label="Description" value={form.description} onChange={(e) => setForm({ ...form, description: e.target.value })} multiline rows={2} fullWidth
                            slotProps={{ input: { startAdornment: <InputAdornment position="start" sx={{ alignSelf: 'flex-start', mt: 1.5 }}><Description fontSize="small" /></InputAdornment> } }} />
                        <Stack direction="row" spacing={2}>
                            <TextField label="Price" type="number" value={form.price} onChange={(e) => setForm({ ...form, price: e.target.value })} required fullWidth
                                slotProps={{ input: { startAdornment: <InputAdornment position="start"><AttachMoney fontSize="small" /></InputAdornment> } }} />
                            <TextField label="Cost" type="number" value={form.cost} onChange={(e) => setForm({ ...form, cost: e.target.value })} fullWidth
                                slotProps={{ input: { startAdornment: <InputAdornment position="start"><AttachMoney fontSize="small" /></InputAdornment> } }} />
                        </Stack>
                        <Stack direction="row" spacing={2}>
                            <TextField label="Stock" type="number" value={form.stock} onChange={(e) => setForm({ ...form, stock: e.target.value })} required fullWidth
                                slotProps={{ input: { startAdornment: <InputAdornment position="start"><Inventory fontSize="small" /></InputAdornment> } }} />
                            <TextField label="Reorder Level" type="number" value={form.reorder_level} onChange={(e) => setForm({ ...form, reorder_level: e.target.value })} fullWidth
                                slotProps={{ input: { startAdornment: <InputAdornment position="start"><WarningIcon fontSize="small" /></InputAdornment> } }} />
                        </Stack>
                        <FormControlLabel control={<Switch checked={!!form.is_active} onChange={(e) => setForm({ ...form, is_active: e.target.checked })} />} label="Active" />
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
