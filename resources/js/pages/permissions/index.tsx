import { useEffect, useState, useCallback, useMemo, type ReactNode } from 'react';
import { Head, usePage } from '@inertiajs/react';
import {
    Accordion, AccordionDetails, AccordionSummary, Avatar, Badge, Box, Button, Card,
    CardContent, Chip, CircularProgress, Dialog, DialogActions, DialogContent, DialogTitle,
    Grid, InputAdornment, MenuItem, Stack, TextField, Tooltip, Typography,
} from '@mui/material';
import {
    Add, Edit, Delete, Key, Shield, Search, Close, Save as SaveIcon, VpnKey, ExpandMore,
    AdminPanelSettings, Inventory, Category as CategoryIcon,
} from '@mui/icons-material';
import { toast } from 'sonner';
import { api } from '@/lib/api';
import { useConfirm } from '@/components/confirm-provider';

type Permission = {
    id: number;
    name: string;
    guard_name: string;
    roles_count: number | null;
    created_at: string;
};

type GroupedPermissions = { resource: string; items: Permission[] };

const UNGROUPED = '__ungrouped__';

function groupPermissions(perms: Permission[]): GroupedPermissions[] {
    const map = new Map<string, Permission[]>();
    for (const p of perms) {
        const parts = p.name.split('.');
        const resource = parts.length > 1 ? parts[0] : UNGROUPED;
        if (!map.has(resource)) map.set(resource, []);
        map.get(resource)!.push(p);
    }
    return Array.from(map.entries())
        .sort(([a], [b]) => (a === UNGROUPED ? 1 : b === UNGROUPED ? -1 : a.localeCompare(b)))
        .map(([resource, items]) => ({ resource, items: items.sort((a, b) => a.name.localeCompare(b.name)) }));
}

const actionOf = (name: string): string => {
    const parts = name.split('.');
    return parts.length > 1 ? parts.slice(1).join('.') : name;
};

const RESOURCE_ICONS: Record<string, ReactNode> = {
    dashboard: <AdminPanelSettings />,
    customers: <AdminPanelSettings />,
    products: <Inventory />,
    sales: <Inventory />,
    users: <AdminPanelSettings />,
    roles: <Shield />,
    permissions: <Key />,
    reports: <CategoryIcon />,
    audit: <CategoryIcon />,
};

export default function PermissionsIndex() {
    const page = usePage<{ auth: { permissions?: string[] } }>();
    const can = (p: string) => (page.props.auth?.permissions ?? []).includes(p);
    const confirm = useConfirm();

    const [perms, setPerms] = useState<Permission[]>([]);
    const [loading, setLoading] = useState(false);
    const [search, setSearch] = useState('');

    const [open, setOpen] = useState(false);
    const [editing, setEditing] = useState<Permission | null>(null);
    const [form, setForm] = useState({ resource: '', action: '', guard_name: 'web' });
    const [errors, setErrors] = useState<Record<string, string[]>>({});
    const [saving, setSaving] = useState(false);

    const load = useCallback(async () => {
        setLoading(true);
        try {
            const params = new URLSearchParams({ per_page: '200', search });
            const res = await api.get<{ data: Permission[] }>(`/permissions/data?${params}`);
            setPerms(res.data);
        } finally { setLoading(false); }
    }, [search]);

    useEffect(() => { load(); }, [load]);

    const groups = useMemo(() => groupPermissions(perms), [perms]);
    const totals = useMemo(() => ({
        total: perms.length,
        groups: groups.filter((g) => g.resource !== UNGROUPED).length,
        unused: perms.filter((p) => (p.roles_count ?? 0) === 0).length,
    }), [perms, groups]);

    const openCreate = (resource?: string) => {
        setEditing(null);
        setForm({ resource: resource && resource !== UNGROUPED ? resource : '', action: '', guard_name: 'web' });
        setErrors({});
        setOpen(true);
    };

    const openEdit = (perm: Permission) => {
        setEditing(perm);
        const parts = perm.name.split('.');
        setForm({
            resource: parts.length > 1 ? parts[0] : '',
            action: parts.length > 1 ? parts.slice(1).join('.') : perm.name,
            guard_name: perm.guard_name,
        });
        setErrors({});
        setOpen(true);
    };

    const submit = async () => {
        setSaving(true); setErrors({});
        const name = form.resource ? `${form.resource}.${form.action}` : form.action;
        const payload = { name, guard_name: form.guard_name };
        try {
            if (editing) {
                await api.put(`/permissions/${editing.id}`, payload);
                toast.success(`Permission "${name}" updated`);
            } else {
                await api.post('/permissions', payload);
                toast.success(`Permission "${name}" created`);
            }
            setOpen(false); load();
        } catch (e) {
            const err = e as { errors?: Record<string, string[]>; message?: string };
            if (err.errors) setErrors(err.errors);
            else toast.error(err.message ?? 'Error');
        } finally { setSaving(false); }
    };

    const remove = async (perm: Permission) => {
        const ok = await confirm({
            title: 'Delete permission?',
            description: `This will permanently remove "${perm.name}".`,
            confirmText: 'Delete',
            tone: 'error',
        });
        if (!ok) return;
        try {
            await api.delete(`/permissions/${perm.id}`);
            toast.success(`Permission "${perm.name}" deleted`);
            load();
        } catch (e) {
            const err = e as { message?: string };
            toast.error(err.message ?? 'Delete failed');
        }
    };

    return (
        <>
            <Head title="Permissions" />
            <Box sx={{ p: 3 }}>
                <Box sx={{ mb: 2, display: 'flex', alignItems: 'center', gap: 2 }}>
                    <Key color="primary" />
                    <Typography variant="h5" fontWeight={600}>Permissions</Typography>
                    <Box sx={{ flexGrow: 1 }} />
                    {can('permissions.create') && (
                        <Button startIcon={<Add />} variant="contained" onClick={() => openCreate()}>New Permission</Button>
                    )}
                </Box>

                <Grid container spacing={2} sx={{ mb: 2 }}>
                    <Grid size={{ xs: 12, md: 4 }}>
                        <Card><CardContent sx={{ display: 'flex', alignItems: 'center', gap: 2 }}>
                            <Avatar variant="rounded" sx={{ bgcolor: 'primary.main' }}><Key /></Avatar>
                            <Box>
                                <Typography variant="overline" color="text.secondary">Total Permissions</Typography>
                                <Typography variant="h5" fontWeight={600}>{totals.total}</Typography>
                            </Box>
                        </CardContent></Card>
                    </Grid>
                    <Grid size={{ xs: 12, md: 4 }}>
                        <Card><CardContent sx={{ display: 'flex', alignItems: 'center', gap: 2 }}>
                            <Avatar variant="rounded" sx={{ bgcolor: 'success.main' }}><CategoryIcon /></Avatar>
                            <Box>
                                <Typography variant="overline" color="text.secondary">Resources</Typography>
                                <Typography variant="h5" fontWeight={600}>{totals.groups}</Typography>
                            </Box>
                        </CardContent></Card>
                    </Grid>
                    <Grid size={{ xs: 12, md: 4 }}>
                        <Card><CardContent sx={{ display: 'flex', alignItems: 'center', gap: 2 }}>
                            <Avatar variant="rounded" sx={{ bgcolor: 'warning.main' }}><Shield /></Avatar>
                            <Box>
                                <Typography variant="overline" color="text.secondary">Unused</Typography>
                                <Typography variant="h5" fontWeight={600}>{totals.unused}</Typography>
                            </Box>
                        </CardContent></Card>
                    </Grid>
                </Grid>

                <TextField
                    size="small" placeholder="Search permission name…"
                    value={search} onChange={(e) => setSearch(e.target.value)}
                    sx={{ mb: 2, width: 360 }}
                    slotProps={{ input: { startAdornment: <InputAdornment position="start"><Search fontSize="small" /></InputAdornment> } }}
                />

                {loading && groups.length === 0 && (
                    <Box sx={{ display: 'flex', justifyContent: 'center', p: 4 }}><CircularProgress /></Box>
                )}

                {groups.length === 0 && !loading && (
                    <Card><CardContent><Typography color="text.secondary" align="center">No permissions found.</Typography></CardContent></Card>
                )}

                <Stack spacing={1}>
                    {groups.map((group) => (
                        <Accordion
                            key={group.resource}
                            defaultExpanded
                            disableGutters
                            elevation={0}
                            sx={{ border: 1, borderColor: 'divider', borderRadius: 1, '&:before': { display: 'none' } }}
                        >
                            <AccordionSummary expandIcon={<ExpandMore />}>
                                <Stack direction="row" spacing={1.5} alignItems="center" sx={{ width: '100%' }}>
                                    <Avatar variant="rounded" sx={{ bgcolor: 'primary.light', color: 'primary.contrastText', width: 32, height: 32 }}>
                                        {RESOURCE_ICONS[group.resource] ?? <Key />}
                                    </Avatar>
                                    <Typography sx={{ textTransform: 'capitalize', fontWeight: 600 }}>
                                        {group.resource === UNGROUPED ? 'Uncategorized' : group.resource}
                                    </Typography>
                                    <Chip size="small" label={group.items.length} />
                                    <Box sx={{ flexGrow: 1 }} />
                                    {can('permissions.create') && group.resource !== UNGROUPED && (
                                        <Button
                                            size="small"
                                            startIcon={<Add />}
                                            onClick={(e) => { e.stopPropagation(); openCreate(group.resource); }}
                                        >
                                            Add action
                                        </Button>
                                    )}
                                </Stack>
                            </AccordionSummary>
                            <AccordionDetails>
                                <Box sx={{ display: 'flex', flexWrap: 'wrap', gap: 1 }}>
                                    {group.items.map((perm) => {
                                        const inUse = (perm.roles_count ?? 0) > 0;
                                        return (
                                            <Tooltip
                                                key={perm.id}
                                                title={inUse ? `Used by ${perm.roles_count} role(s) · click to edit (revoke first to delete)` : 'Unused · click to edit, × to delete'}
                                            >
                                                <Chip
                                                    icon={<VpnKey fontSize="small" />}
                                                    label={
                                                        <Stack direction="row" spacing={0.75} alignItems="center">
                                                            <span>{actionOf(perm.name)}</span>
                                                            {inUse && (
                                                                <Badge
                                                                    badgeContent={perm.roles_count}
                                                                    color="primary"
                                                                    sx={{ '& .MuiBadge-badge': { position: 'static', transform: 'none', minWidth: 18, height: 18, fontSize: 10 } }}
                                                                />
                                                            )}
                                                        </Stack>
                                                    }
                                                    color={inUse ? 'default' : 'warning'}
                                                    variant={inUse ? 'filled' : 'outlined'}
                                                    onClick={can('permissions.update') ? () => openEdit(perm) : undefined}
                                                    onDelete={can('permissions.delete') && !inUse ? () => remove(perm) : undefined}
                                                    deleteIcon={<Delete fontSize="small" />}
                                                    sx={{ pl: 0.5, cursor: can('permissions.update') ? 'pointer' : 'default' }}
                                                />
                                            </Tooltip>
                                        );
                                    })}
                                </Box>
                            </AccordionDetails>
                        </Accordion>
                    ))}
                </Stack>

                <Typography variant="caption" color="text.secondary" sx={{ mt: 2, display: 'block' }}>
                    Tip: click a chip to edit, or the × to delete. Convention: <code>resource.action</code> (e.g. <code>products.view</code>). Assign to roles on the <strong>Roles</strong> page.
                </Typography>
            </Box>

            <Dialog open={open} onClose={() => setOpen(false)} fullWidth maxWidth="sm">
                <DialogTitle>
                    <Stack direction="row" spacing={1} alignItems="center">
                        {editing ? <Edit color="primary" /> : <Key color="primary" />}
                        <span>{editing ? 'Edit Permission' : 'New Permission'}</span>
                    </Stack>
                </DialogTitle>
                <DialogContent>
                    <Stack spacing={2} sx={{ mt: 1 }}>
                        <TextField
                            label="Resource" value={form.resource}
                            onChange={(e) => setForm({ ...form, resource: e.target.value.toLowerCase().trim() })}
                            helperText="e.g. products, sales, inventory (leave blank for ungrouped)"
                            fullWidth
                            slotProps={{ input: { startAdornment: <InputAdornment position="start"><CategoryIcon fontSize="small" /></InputAdornment> } }}
                        />
                        <TextField
                            label="Action" value={form.action}
                            onChange={(e) => setForm({ ...form, action: e.target.value.toLowerCase().trim() })}
                            error={!!errors.name} helperText={errors.name?.[0] ?? 'e.g. view, create, update, delete, refund'}
                            required fullWidth autoFocus
                            slotProps={{ input: { startAdornment: <InputAdornment position="start"><VpnKey fontSize="small" /></InputAdornment> } }}
                        />
                        {form.action && (
                            <Box sx={{ p: 1.5, bgcolor: 'action.hover', borderRadius: 1 }}>
                                <Typography variant="caption" color="text.secondary">Permission name</Typography>
                                <Typography variant="body2" fontFamily="monospace" fontWeight={600}>
                                    {form.resource ? `${form.resource}.${form.action}` : form.action}
                                </Typography>
                            </Box>
                        )}
                        <TextField
                            label="Guard" select value={form.guard_name}
                            onChange={(e) => setForm({ ...form, guard_name: e.target.value })}
                            fullWidth
                            slotProps={{ input: { startAdornment: <InputAdornment position="start"><Shield fontSize="small" /></InputAdornment> } }}
                        >
                            <MenuItem value="web">web</MenuItem>
                            <MenuItem value="api">api</MenuItem>
                            <MenuItem value="sanctum">sanctum</MenuItem>
                        </TextField>
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
