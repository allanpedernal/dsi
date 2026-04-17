import { useEffect, useState, useCallback } from 'react';
import { Head, usePage } from '@inertiajs/react';
import {
    Box, Button, Chip, Dialog, DialogActions, DialogContent, DialogTitle,
    IconButton, InputAdornment, MenuItem, Stack, TextField, Typography,
} from '@mui/material';
import { DataGrid, GridColDef } from '@mui/x-data-grid';
import {
    Add, Edit, Delete, PersonAdd, Person, Email as EmailIcon,
    Badge, Lock, Close, Save as SaveIcon,
} from '@mui/icons-material';
import { toast } from 'sonner';
import { api } from '@/lib/api';
import { dataGridHeight } from '@/lib/data-grid-height';
import { withDashFallback } from '@/lib/grid-utils';
import { useConfirm } from '@/components/confirm-provider';

type Role = { value: string; label: string };
type User = { id: number; name: string; email: string; roles: string[]; created_at: string };

const empty = { name: '', email: '', password: '', password_confirmation: '', role: 'manager' };

export default function UsersIndex({ roles }: { roles: Role[] }) {
    const page = usePage<{ auth: { permissions?: string[] } }>();
    const can = (p: string) => (page.props.auth?.permissions ?? []).includes(p);
    const confirm = useConfirm();

    const [rows, setRows] = useState<User[]>([]);
    const [total, setTotal] = useState(0);
    const [loading, setLoading] = useState(false);
    const [paginationModel, setPaginationModel] = useState({ page: 0, pageSize: 10 });

    const [open, setOpen] = useState(false);
    const [editing, setEditing] = useState<User | null>(null);
    const [form, setForm] = useState(empty);
    const [errors, setErrors] = useState<Record<string, string[]>>({});
    const [saving, setSaving] = useState(false);

    const load = useCallback(async () => {
        setLoading(true);
        try {
            const params = new URLSearchParams({ page: String(paginationModel.page + 1), per_page: String(paginationModel.pageSize) });
            const res = await api.get<{ data: User[]; meta: { total: number } }>(`/users/data?${params}`);
            setRows(res.data); setTotal(res.meta.total);
        } finally { setLoading(false); }
    }, [paginationModel]);

    useEffect(() => { load(); }, [load]);

    const openCreate = () => { setEditing(null); setForm(empty); setErrors({}); setOpen(true); };
    const openEdit = (row: User) => {
        setEditing(row);
        setForm({ name: row.name, email: row.email, password: '', password_confirmation: '', role: row.roles[0] ?? 'manager' });
        setErrors({}); setOpen(true);
    };

    const submit = async () => {
        setSaving(true); setErrors({});
        try {
            if (editing) {
                await api.put(`/users/${editing.id}`, form);
                toast.success('User updated');
            } else {
                await api.post('/users', form);
                toast.success('User created');
            }
            setOpen(false); load();
        } catch (e) {
            const err = e as { errors?: Record<string, string[]>; message?: string };
            if (err.errors) setErrors(err.errors);
            else toast.error(err.message ?? 'Request failed');
        } finally { setSaving(false); }
    };

    const remove = async (row: User) => {
        const ok = await confirm({
            title: 'Delete user?',
            description: `This will permanently remove "${row.name}" (${row.email}).`,
            confirmText: 'Delete',
            tone: 'error',
        });
        if (!ok) return;
        try {
            await api.delete(`/users/${row.id}`);
            toast.success(`User ${row.name} deleted`);
            load();
        } catch (e) {
            const err = e as { message?: string };
            toast.error(err.message ?? 'Delete failed');
        }
    };

    const columns: GridColDef<User>[] = [
        { field: 'name', headerName: 'Name', flex: 1, minWidth: 180 },
        { field: 'email', headerName: 'Email', flex: 1, minWidth: 220 },
        {
            field: 'roles', headerName: 'Roles', width: 200, align: 'left', headerAlign: 'left',
            renderCell: ({ value }) => (
                <Stack direction="row" spacing={0.5} sx={{ width: '100%', height: '100%', alignItems: 'center' }}>
                    {(value as string[]).map((r) => <Chip key={r} size="small" label={r} />)}
                </Stack>
            ),
        },
        {
            field: 'actions', headerName: 'Actions', width: 130, sortable: false, filterable: false,
            renderCell: ({ row }) => (
                <Stack direction="row">
                    {can('users.update') && <IconButton size="small" onClick={() => openEdit(row)}><Edit fontSize="small" /></IconButton>}
                    {can('users.delete') && <IconButton size="small" color="error" onClick={() => remove(row)}><Delete fontSize="small" /></IconButton>}
                </Stack>
            ),
        },
    ];

    return (
        <>
            <Head title="Users" />
            <Box sx={{ p: 3 }}>
                <Box sx={{ mb: 2, display: 'flex', alignItems: 'center', gap: 2 }}>
                    <Typography variant="h5" fontWeight={600}>Users</Typography>
                    <Box sx={{ flexGrow: 1 }} />
                    {can('users.create') && <Button startIcon={<Add />} variant="contained" onClick={openCreate}>New User</Button>}
                </Box>

                <Box sx={{ height: dataGridHeight(paginationModel.pageSize) }}>
                    <DataGrid rows={rows} columns={withDashFallback(columns)} loading={loading} rowCount={total}
                        paginationMode="server" paginationModel={paginationModel} onPaginationModelChange={setPaginationModel}
                        pageSizeOptions={[10, 25, 50]} disableRowSelectionOnClick />
                </Box>
            </Box>

            <Dialog open={open} onClose={() => setOpen(false)} fullWidth maxWidth="sm">
                <DialogTitle>
                    <Stack direction="row" spacing={1} alignItems="center">
                        {editing ? <Edit color="primary" /> : <PersonAdd color="primary" />}
                        <span>{editing ? 'Edit User' : 'New User'}</span>
                    </Stack>
                </DialogTitle>
                <DialogContent>
                    <Stack spacing={2} sx={{ mt: 1 }}>
                        <TextField
                            label="Name" value={form.name}
                            onChange={(e) => setForm({ ...form, name: e.target.value })}
                            error={!!errors.name} helperText={errors.name?.[0]} required fullWidth
                            slotProps={{ input: { startAdornment: <InputAdornment position="start"><Person fontSize="small" /></InputAdornment> } }}
                        />
                        <TextField
                            label="Email" type="email" value={form.email}
                            onChange={(e) => setForm({ ...form, email: e.target.value })}
                            error={!!errors.email} helperText={errors.email?.[0]} required fullWidth
                            slotProps={{ input: { startAdornment: <InputAdornment position="start"><EmailIcon fontSize="small" /></InputAdornment> } }}
                        />
                        <TextField
                            label="Role" select value={form.role}
                            onChange={(e) => setForm({ ...form, role: e.target.value })}
                            required fullWidth
                            slotProps={{ input: { startAdornment: <InputAdornment position="start"><Badge fontSize="small" /></InputAdornment> } }}
                        >
                            {roles.map((r) => <MenuItem key={r.value} value={r.value}>{r.label}</MenuItem>)}
                        </TextField>
                        <TextField
                            label={editing ? 'New password (leave blank to keep)' : 'Password'}
                            type="password" value={form.password}
                            onChange={(e) => setForm({ ...form, password: e.target.value })}
                            error={!!errors.password} helperText={errors.password?.[0]}
                            required={!editing} fullWidth autoComplete="new-password"
                            slotProps={{ input: { startAdornment: <InputAdornment position="start"><Lock fontSize="small" /></InputAdornment> } }}
                        />
                        <TextField
                            label="Confirm password" type="password" value={form.password_confirmation}
                            onChange={(e) => setForm({ ...form, password_confirmation: e.target.value })}
                            fullWidth autoComplete="new-password"
                            slotProps={{ input: { startAdornment: <InputAdornment position="start"><Lock fontSize="small" /></InputAdornment> } }}
                        />
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
